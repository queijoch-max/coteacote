<?php

namespace App\Controller\Api;

use App\Dto\ProfilInput;
use App\Entity\Publication;
use App\Entity\Reponse;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\StatutUser;
use App\Exception\ApiException;
use App\Service\GestionComptes;
use App\Service\PublicationPresenter;
use App\Service\UserPresenter;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Profil et données personnelles de l'habitant (EF-06, EF-07, DP-04). */
#[OA\Tag(name: 'Profil')]
#[Route('/api/moi')]
#[IsGranted(new Expression("is_granted('ROLE_HABITANT') or is_granted('ROLE_PENDING')"))]
class ProfilController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPresenter $userPresenter,
    ) {
    }

    #[Route('', name: 'api_moi', methods: ['GET'])]
    public function moi(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->userPresenter->presenter($user));
    }

    /** EF-06 : profil et choix du contact partagé. */
    #[Route('', name: 'api_moi_modifier', methods: ['PUT'])]
    public function modifier(#[CurrentUser] User $user, #[MapRequestPayload] ProfilInput $input): JsonResponse
    {
        $user->setPrenom($input->prenom)
            ->setNom($input->nom)
            ->setRue($input->rue)
            ->setTelephone($input->telephone)
            ->setPartageTelephone($input->partageTelephone)
            ->setPartageEmail($input->partageEmail);
        $this->em->flush();

        return $this->json($this->userPresenter->presenter($user));
    }

    /** EF-07, RG-11 : suppression définitive après 30 jours. */
    #[Route('', name: 'api_moi_supprimer', methods: ['DELETE'])]
    public function supprimer(#[CurrentUser] User $user, GestionComptes $comptes): JsonResponse
    {
        $comptes->demanderSuppression($user);

        return $this->json($this->userPresenter->presenter($user));
    }

    #[Route('/annuler-suppression', name: 'api_moi_annuler_suppression', methods: ['POST'])]
    public function annulerSuppression(#[CurrentUser] User $user, GestionComptes $comptes): JsonResponse
    {
        if (StatutUser::Supprime !== $user->getStatut()) {
            throw new ApiException('Aucune suppression n\'est en cours.');
        }
        $comptes->annulerSuppression($user);

        return $this->json($this->userPresenter->presenter($user));
    }

    /** DP-04 : export de toutes les données de l'habitant au format JSON. */
    #[Route('/export', name: 'api_moi_export', methods: ['GET'])]
    public function export(#[CurrentUser] User $user, PublicationPresenter $presenter): JsonResponse
    {
        $publications = $this->em->getRepository(Publication::class)->findBy(['auteur' => $user], ['createdAt' => 'ASC']);
        $reponses = $this->em->getRepository(Reponse::class)->findBy(['user' => $user], ['createdAt' => 'ASC']);
        $signalements = $this->em->getRepository(Signalement::class)->findBy(['user' => $user], ['createdAt' => 'ASC']);

        $data = [
            'exporteLe' => (new \DateTimeImmutable())->format(\DATE_ATOM),
            'compte' => $this->userPresenter->presenter($user) + [
                'inscritLe' => $user->getCreatedAt()->format(\DATE_ATOM),
                'conditionsAccepteesLe' => $user->getCguAccepteesAt()?->format(\DATE_ATOM),
            ],
            'publications' => array_map(fn (Publication $p) => $presenter->presenter($p, $user), $publications),
            'reponsesEnvoyees' => array_map(fn (Reponse $r) => [
                'publicationId' => $r->getPublication()->getId(),
                'date' => $r->getCreatedAt()->format(\DATE_ATOM),
            ], $reponses),
            'signalementsEnvoyes' => array_map(fn (Signalement $s) => [
                'publicationId' => $s->getPublication()->getId(),
                'motif' => $s->getMotif()->libelle(),
                'date' => $s->getCreatedAt()->format(\DATE_ATOM),
            ], $signalements),
        ];

        $response = $this->json($data);
        $response->setEncodingOptions(\JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mes-donnees-cote-a-cote.json',
        ));

        return $response;
    }
}
