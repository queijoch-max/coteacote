<?php

namespace App\Controller\Api;

use App\Dto\PublicationMairieInput;
use App\Entity\JournalAction;
use App\Entity\Publication;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\MotifRefus;
use App\Enum\StatutPublication;
use App\Enum\StatutUser;
use App\Exception\ApiException;
use App\Repository\CategorieRepository;
use App\Repository\JournalActionRepository;
use App\Repository\PublicationRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Journal;
use App\Service\Notificateur;
use App\Service\PublicationPresenter;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Espace mairie (EF-04, EF-05, EF-21 à EF-28). */
#[OA\Tag(name: 'Mairie')]
#[Route('/api/mairie', requirements: ['id' => Requirement::DIGITS])]
#[IsGranted(User::ROLE_MAIRIE)]
class MairieController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Journal $journal,
        private readonly UserRepository $users,
    ) {
    }

    /** EF-25 */
    #[Route('/tableau-de-bord', name: 'api_mairie_tableau', methods: ['GET'])]
    public function tableauDeBord(PublicationRepository $publications, SignalementRepository $signalements): JsonResponse
    {
        return $this->json([
            'inscriptionsEnAttente' => $this->users->countInscriptionsEnAttente(),
            'signalementsEnAttente' => $signalements->countPublicationsSignalees(),
            'publicationsActives' => $publications->countActives(),
            'publicationsDuMois' => $publications->countDuMois(StatutPublication::Active)
                + $publications->countDuMois(StatutPublication::Resolue),
            'publicationsResoluesDuMois' => $publications->countDuMois(StatutPublication::Resolue),
        ]);
    }

    #[Route('/inscriptions', name: 'api_mairie_inscriptions', methods: ['GET'])]
    public function inscriptions(): JsonResponse
    {
        return $this->json(array_map(static fn (User $u) => [
            'id' => $u->getId(),
            'prenom' => $u->getPrenom(),
            'nom' => $u->getNom(),
            'email' => $u->getEmail(),
            'rue' => $u->getRue(),
            'createdAt' => $u->getCreatedAt()->format(\DATE_ATOM),
        ], $this->users->findInscriptionsEnAttente()));
    }

    /** EF-04, EF-05 */
    #[Route('/inscriptions/{id}/valider', name: 'api_mairie_valider', methods: ['POST'])]
    public function valider(#[CurrentUser] User $mairie, User $habitant, Notificateur $notificateur): JsonResponse
    {
        $this->verifierEnAttente($habitant);

        $habitant->setRole(User::ROLE_HABITANT)->setStatut(StatutUser::Valide);
        $this->journal->enregistrer($mairie, Journal::VALIDATION_INSCRIPTION, 'user', $habitant->getId());
        $this->em->flush();
        $notificateur->inscriptionValidee($habitant);

        return $this->json(['message' => $habitant->getPrenom().' '.$habitant->getNom().' peut maintenant utiliser l\'application.']);
    }

    /** EF-04, EF-05 : le motif est choisi dans une liste. */
    #[Route('/inscriptions/{id}/refuser', name: 'api_mairie_refuser', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'motif', type: 'string')]))]
    public function refuser(#[CurrentUser] User $mairie, User $habitant, Request $request, Notificateur $notificateur): JsonResponse
    {
        $this->verifierEnAttente($habitant);
        $motif = MotifRefus::tryFrom((string) ($request->toArray()['motif'] ?? ''));

        $habitant->setStatut(StatutUser::Refuse)->setMotifRefus($motif);
        $this->journal->enregistrer($mairie, Journal::REFUS_INSCRIPTION, 'user', $habitant->getId());
        $this->em->flush();
        $notificateur->inscriptionRefusee($habitant);

        return $this->json(['message' => 'L\'inscription a été refusée.']);
    }

    /** EF-23 : signalements en attente, regroupés par publication. */
    #[Route('/signalements', name: 'api_mairie_signalements', methods: ['GET'])]
    public function signalements(SignalementRepository $signalements, PublicationPresenter $presenter): JsonResponse
    {
        $groupes = [];
        foreach ($signalements->findEnAttente() as $s) {
            /** @var Signalement $s */
            $p = $s->getPublication();
            $groupes[$p->getId()] ??= [
                'publication' => $presenter->presenter($p) + ['statut' => $p->getStatut()->value],
                'auteurId' => $p->isPublieeParMairie() ? null : $p->getAuteur()->getId(),
                'auteurStatut' => $p->getAuteur()->getStatut()->value,
                'motifs' => [],
                'nombre' => 0,
                'premierSignalement' => $s->getCreatedAt()->format(\DATE_ATOM),
            ];
            $groupes[$p->getId()]['nombre']++;
            $libelle = $s->getMotif()->libelle();
            $groupes[$p->getId()]['motifs'][$libelle] = ($groupes[$p->getId()]['motifs'][$libelle] ?? 0) + 1;
        }

        return $this->json(array_values($groupes));
    }

    /** Masquer une publication (en attendant une décision, ou de sa propre initiative). */
    #[Route('/publications/{id}/masquer', name: 'api_mairie_masquer', methods: ['POST'])]
    public function masquer(#[CurrentUser] User $mairie, Publication $publication): JsonResponse
    {
        $publication->setStatut(StatutPublication::Masquee);
        $this->journal->enregistrer($mairie, Journal::MASQUAGE_PUBLICATION, 'publication', $publication->getId());
        $this->em->flush();

        return $this->json(['message' => 'La publication est masquée.']);
    }

    /** EF-23 : rétablir une publication signalée. */
    #[Route('/publications/{id}/retablir', name: 'api_mairie_retablir', methods: ['POST'])]
    public function retablir(#[CurrentUser] User $mairie, Publication $publication, SignalementRepository $signalements): JsonResponse
    {
        if (\in_array($publication->getStatut(), [StatutPublication::Masquee, StatutPublication::Active], true)) {
            $publication->setStatut(StatutPublication::Active);
        }
        $signalements->marquerTraites($publication);
        $this->journal->enregistrer($mairie, Journal::RETABLISSEMENT_PUBLICATION, 'publication', $publication->getId());
        $this->em->flush();

        return $this->json(['message' => 'La publication est rétablie.']);
    }

    /** EF-23 : supprimer une publication signalée. La mairie ne modifie jamais le texte d'un habitant. */
    #[Route('/publications/{id}', name: 'api_mairie_supprimer_publication', methods: ['DELETE'])]
    public function supprimerPublication(#[CurrentUser] User $mairie, Publication $publication, SignalementRepository $signalements): JsonResponse
    {
        $publication->setStatut(StatutPublication::Supprimee);
        $signalements->marquerTraites($publication);
        $this->journal->enregistrer($mairie, Journal::SUPPRESSION_PUBLICATION, 'publication', $publication->getId());
        $this->em->flush();

        return $this->json(['message' => 'La publication est supprimée.']);
    }

    /** EF-26 : publication pour un habitant sans Internet ; le contact est celui de la mairie. */
    #[Route('/publications', name: 'api_mairie_publier', methods: ['POST'])]
    public function publier(
        #[CurrentUser] User $mairie,
        #[MapRequestPayload] PublicationMairieInput $input,
        CategorieRepository $categories,
        PublicationPresenter $presenter,
    ): JsonResponse {
        $publication = (new Publication())
            ->setType($input->type)
            ->setNature($input->nature)
            ->setCategorie(PublicationController::categorieValide($categories->find($input->categorieId)))
            ->setComplement($input->complement)
            ->setAuteur($mairie)
            ->setPublieeParMairie(true)
            ->setBeneficiaire(trim($input->prenom).' '.mb_strtoupper($input->initiale).'.')
            ->setRueBeneficiaire(trim($input->rue));

        $this->em->persist($publication);
        $this->em->flush();
        $this->journal->enregistrer($mairie, Journal::PUBLICATION_POUR_HABITANT, 'publication', $publication->getId());
        $this->em->flush();

        return $this->json($presenter->presenter($publication, $mairie, nbReponses: 0), 201);
    }

    /** Habitants validés ou suspendus (sans coordonnées). */
    #[Route('/habitants', name: 'api_mairie_habitants', methods: ['GET'])]
    public function habitants(#[MapQueryParameter] ?string $q = null): JsonResponse
    {
        return $this->json(array_map(static fn (User $u) => [
            'id' => $u->getId(),
            'prenom' => $u->getPrenom(),
            'nom' => $u->getNom(),
            'rue' => $u->getRue(),
            'statut' => $u->getStatut()->value,
            'statutLibelle' => $u->getStatut()->libelle(),
            'inscritLe' => $u->getCreatedAt()->format(\DATE_ATOM),
        ], $this->users->findHabitants($q)));
    }

    /** EF-24 */
    #[Route('/habitants/{id}/suspendre', name: 'api_mairie_suspendre', methods: ['POST'])]
    public function suspendre(#[CurrentUser] User $mairie, User $habitant): JsonResponse
    {
        if (!$habitant->hasRole(User::ROLE_HABITANT) || StatutUser::Valide !== $habitant->getStatut()) {
            throw new ApiException('Ce compte ne peut pas être suspendu.');
        }

        $habitant->setStatut(StatutUser::Suspendu);
        $this->journal->enregistrer($mairie, Journal::SUSPENSION_COMPTE, 'user', $habitant->getId());
        $this->em->flush();

        return $this->json(['message' => 'Le compte est suspendu.']);
    }

    #[Route('/habitants/{id}/reactiver', name: 'api_mairie_reactiver', methods: ['POST'])]
    public function reactiver(#[CurrentUser] User $mairie, User $habitant): JsonResponse
    {
        if (StatutUser::Suspendu !== $habitant->getStatut()) {
            throw new ApiException('Ce compte n\'est pas suspendu.');
        }

        $habitant->setStatut(StatutUser::Valide);
        $this->journal->enregistrer($mairie, Journal::REACTIVATION_COMPTE, 'user', $habitant->getId());
        $this->em->flush();

        return $this->json(['message' => 'Le compte est réactivé.']);
    }

    /** EF-28 */
    #[Route('/journal', name: 'api_mairie_journal', methods: ['GET'])]
    public function journal(JournalActionRepository $journal): JsonResponse
    {
        return $this->json(array_map(static fn (JournalAction $j) => [
            'id' => $j->getId(),
            'action' => $j->getAction(),
            'actionLibelle' => Journal::LIBELLES[$j->getAction()] ?? $j->getAction(),
            'cible' => $j->getCible(),
            'cibleId' => $j->getCibleId(),
            'par' => $j->getUser()?->getNomAffiche() ?? 'Compte supprimé',
            'createdAt' => $j->getCreatedAt()->format(\DATE_ATOM),
        ], $journal->findDernieres()));
    }

    private function verifierEnAttente(User $habitant): void
    {
        if (StatutUser::EnAttente !== $habitant->getStatut() || !$habitant->hasRole(User::ROLE_PENDING)) {
            throw new ApiException('Cette inscription a déjà été traitée.');
        }
        if (!$habitant->isEmailConfirme()) {
            throw new ApiException('L\'habitant n\'a pas encore confirmé son adresse e-mail.');
        }
    }
}
