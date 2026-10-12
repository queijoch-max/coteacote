<?php

namespace App\Controller\Api;

use App\Dto\PublicationInput;
use App\Entity\Categorie;
use App\Entity\Publication;
use App\Entity\Reponse;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\MotifSignalement;
use App\Enum\NaturePublication;
use App\Enum\StatutPublication;
use App\Enum\TypePublication;
use App\Exception\ApiException;
use App\Repository\CategorieRepository;
use App\Repository\PublicationRepository;
use App\Repository\ReponseRepository;
use App\Repository\SignalementRepository;
use App\Security\PublicationVoter;
use App\Service\Notificateur;
use App\Service\PublicationPresenter;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Publications et réponses (EF-08 à EF-24). */
#[OA\Tag(name: 'Publications')]
#[Route('/api/publications')]
#[IsGranted(new Expression("is_granted('ROLE_HABITANT') or is_granted('ROLE_MAIRIE')"))]
class PublicationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PublicationRepository $publications,
        private readonly ReponseRepository $reponses,
        private readonly SignalementRepository $signalements,
        private readonly PublicationPresenter $presenter,
    ) {
    }

    /** EF-15, EF-16 : liste filtrable des publications en ligne. */
    #[Route('', name: 'api_publications', methods: ['GET'])]
    public function liste(
        #[CurrentUser] User $user,
        CategorieRepository $categories,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?int $categorie = null,
        #[MapQueryParameter] ?string $nature = null,
        #[MapQueryParameter] int $page = 1,
    ): JsonResponse {
        $resultat = $this->publications->findActives(
            TypePublication::tryFrom((string) $type),
            $categorie ? $categories->find($categorie) : null,
            NaturePublication::tryFrom((string) $nature),
            max(1, $page),
        );

        $ids = array_map(static fn (Publication $p) => $p->getId(), $resultat['items']);
        $repondues = $this->reponses->idsRepondues($user, $ids);
        $signalees = $this->signalements->idsSignalees($user, $ids);

        return $this->json([
            'items' => array_map(fn (Publication $p) => $this->presenter->presenter($p, $user, $repondues, $signalees), $resultat['items']),
            'total' => $resultat['total'],
            'page' => max(1, $page),
            'parPage' => PublicationRepository::PAR_PAGE,
        ]);
    }

    /** Publications de l'utilisateur connecté (habitant, ou mairie pour ses publications EF-26). */
    #[Route('/mes', name: 'api_publications_mes', methods: ['GET'])]
    public function mes(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->publications->findDeAuteur($user);
        $nb = $this->reponses->compterParPublication(array_map(static fn (Publication $p) => $p->getId(), $items));

        return $this->json([
            'items' => array_map(fn (Publication $p) => $this->presenter->presenter($p, $user, nbReponses: $nb[$p->getId()] ?? 0) + [
                'beneficiaire' => $p->getBeneficiaire(),
            ], $items),
            'maxActives' => Publication::MAX_ACTIVES,
            'actives' => $this->publications->countActivesDe($user),
        ]);
    }

    /** EF-08 à EF-11, EF-14, ES-06 */
    #[Route('', name: 'api_publications_creer', methods: ['POST'])]
    #[IsGranted(PublicationVoter::CREER)]
    public function creer(
        #[CurrentUser] User $user,
        #[MapRequestPayload] PublicationInput $input,
        CategorieRepository $categories,
        RateLimiterFactoryInterface $publicationLimiter,
    ): JsonResponse {
        if ($this->publications->countActivesDe($user) >= Publication::MAX_ACTIVES) {
            throw new ApiException(\sprintf('Vous avez déjà %d publications en ligne. Marquez-en une comme résolue avant d\'en créer une autre.', Publication::MAX_ACTIVES), 422);
        }
        if (!$publicationLimiter->create('user_'.$user->getId())->consume()->isAccepted()) {
            throw new ApiException('Vous avez atteint la limite de 10 publications par jour.', 429);
        }

        $publication = (new Publication())
            ->setType($input->type)
            ->setNature($input->nature)
            ->setCategorie(self::categorieValide($categories->find($input->categorieId)))
            ->setComplement($input->complement)
            ->setAuteur($user);

        $this->em->persist($publication);
        $this->em->flush();

        return $this->json($this->presenter->presenter($publication, $user, nbReponses: 0), 201);
    }

    /** EF-12 : un seul renouvellement. */
    #[Route('/{id}/renouveler', name: 'api_publications_renouveler', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(PublicationVoter::GERER, 'publication')]
    public function renouveler(#[CurrentUser] User $user, Publication $publication): JsonResponse
    {
        $statut = $publication->getStatut();
        if ($publication->isRenouvelee() || !\in_array($statut, [StatutPublication::Active, StatutPublication::Expiree], true)) {
            throw new ApiException('Cette publication ne peut plus être renouvelée.');
        }
        if (!$publication->isActive() && !$user->hasRole(User::ROLE_MAIRIE)
            && $this->publications->countActivesDe($user) >= Publication::MAX_ACTIVES) {
            throw new ApiException(\sprintf('Vous avez déjà %d publications en ligne.', Publication::MAX_ACTIVES), 422);
        }

        $publication->renouveler();
        $this->em->flush();

        return $this->json($this->presenter->presenter($publication, $user, nbReponses: 0));
    }

    /** EF-13 */
    #[Route('/{id}/resoudre', name: 'api_publications_resoudre', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(PublicationVoter::GERER, 'publication')]
    public function resoudre(#[CurrentUser] User $user, Publication $publication): JsonResponse
    {
        if (StatutPublication::Active !== $publication->getStatut()) {
            throw new ApiException('Seule une publication en ligne peut être marquée comme résolue.');
        }

        $publication->setStatut(StatutPublication::Resolue);
        $this->em->flush();

        return $this->json($this->presenter->presenter($publication, $user, nbReponses: 0));
    }

    /** EF-13 : seul l'auteur supprime sa publication (Voter). */
    #[Route('/{id}', name: 'api_publications_supprimer', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[IsGranted(PublicationVoter::GERER, 'publication')]
    public function supprimer(Publication $publication): JsonResponse
    {
        $publication->setStatut(StatutPublication::Supprimee);
        $this->em->flush();

        return new JsonResponse(null, 204);
    }

    /** EF-17 à EF-20 : « Je peux aider » / « Ça m'intéresse ». */
    #[Route('/{id}/repondre', name: 'api_publications_repondre', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(PublicationVoter::REPONDRE, 'publication')]
    public function repondre(
        #[CurrentUser] User $user,
        Publication $publication,
        Notificateur $notificateur,
        RateLimiterFactoryInterface $reponseLimiter,
    ): JsonResponse {
        if ($this->reponses->existe($publication, $user)) {
            throw new ApiException('Vous avez déjà répondu à cette publication.', 409);
        }
        if (!$reponseLimiter->create('user_'.$user->getId())->consume()->isAccepted()) {
            throw new ApiException('Vous avez atteint la limite de 20 réponses par jour.', 429);
        }

        $this->em->persist(new Reponse($publication, $user));
        $this->em->flush();
        $notificateur->reponseRecue($publication, $user);

        $message = $publication->isPublieeParMairie()
            ? 'Merci ! La mairie a reçu vos coordonnées et va prévenir l\'habitant.'
            : \sprintf('Merci ! %s a reçu vos coordonnées par e-mail et va vous contacter.', $publication->getAuteur()->getPrenom());

        return $this->json(['message' => $message]);
    }

    /** EF-21, EF-22 */
    #[Route('/{id}/signaler', name: 'api_publications_signaler', requirements: ['id' => Requirement::DIGITS], methods: ['POST'])]
    #[IsGranted(PublicationVoter::SIGNALER, 'publication')]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'motif', type: 'string')]))]
    public function signaler(#[CurrentUser] User $user, Publication $publication, Request $request): JsonResponse
    {
        $motif = MotifSignalement::tryFrom((string) ($request->toArray()['motif'] ?? ''));
        if (!$motif) {
            throw new ApiException('Choisissez un motif de signalement.', 422);
        }
        if ($this->signalements->existe($publication, $user)) {
            throw new ApiException('Vous avez déjà signalé cette publication.', 409);
        }

        $this->em->persist(new Signalement($publication, $user, $motif));
        $this->em->flush();

        if ($this->signalements->countEnAttentePour($publication) >= Publication::SEUIL_SIGNALEMENTS) {
            $publication->setStatut(StatutPublication::Masquee);
            $this->em->flush();
        }

        return $this->json(['message' => 'Merci, la mairie a été prévenue.']);
    }

    /** Seules les sous-catégories actives (ou les catégories sans sous-catégorie) sont acceptées. */
    public static function categorieValide(?Categorie $categorie): Categorie
    {
        $enfantsActifs = $categorie?->getEnfants()->filter(static fn (Categorie $c) => $c->isActif())->count();

        if (!$categorie || !$categorie->isActif() || ($categorie->getParent() && !$categorie->getParent()->isActif()) || $enfantsActifs > 0) {
            throw new ApiException('Choisissez une catégorie et une sous-catégorie dans la liste.', 422);
        }

        return $categorie;
    }
}
