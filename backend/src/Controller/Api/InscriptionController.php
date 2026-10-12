<?php

namespace App\Controller\Api;

use App\Dto\InscriptionInput;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\CommuneRepository;
use App\Repository\UserRepository;
use App\Service\Notificateur;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Inscription')]
class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Notificateur $notificateur,
    ) {
    }

    /** EF-01, EF-02, EF-03 */
    #[Route('/api/inscription', name: 'api_inscription', methods: ['POST'])]
    public function inscrire(
        #[MapRequestPayload] InscriptionInput $input,
        UserRepository $users,
        CommuneRepository $communes,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        if ($users->findOneBy(['email' => mb_strtolower(trim($input->email))])) {
            return $this->json([
                'message' => 'Certaines informations sont invalides.',
                'errors' => ['email' => 'Un compte existe déjà avec cette adresse e-mail.'],
            ], 422);
        }

        $user = (new User())
            ->setPrenom($input->prenom)
            ->setNom($input->nom)
            ->setEmail($input->email)
            ->setRue($input->rue)
            ->setRole(User::ROLE_PENDING)
            ->setCommune($communes->findCommune())
            ->setCguAccepteesAt(new \DateTimeImmutable());
        $user->setPassword($hasher->hashPassword($user, $input->password));
        $this->nouveauJeton($user);

        $this->em->persist($user);
        $this->em->flush();
        $this->notificateur->confirmationEmail($user);

        return $this->json(['message' => 'Un e-mail de confirmation vous a été envoyé.'], 201);
    }

    /** EF-03 : lien valable 24 heures. */
    #[Route('/api/confirmer-email', name: 'api_confirmer_email', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'token', type: 'string')]))]
    public function confirmer(Request $request, UserRepository $users): JsonResponse
    {
        $token = (string) ($request->toArray()['token'] ?? '');
        $user = '' !== $token ? $users->findOneBy(['confirmationToken' => $token]) : null;

        if (!$user) {
            throw new ApiException('Ce lien n\'est pas valide ou a déjà été utilisé.');
        }
        if ($user->getConfirmationExpiresAt() < new \DateTimeImmutable()) {
            throw new ApiException('Ce lien a expiré. Connectez-vous pour en recevoir un nouveau.');
        }

        $user->setEmailConfirme(true)->setConfirmationToken(null)->setConfirmationExpiresAt(null);
        $this->em->flush();

        return $this->json(['message' => 'Adresse e-mail confirmée. La mairie va examiner votre inscription.']);
    }

    #[Route('/api/moi/renvoyer-confirmation', name: 'api_renvoyer_confirmation', methods: ['POST'])]
    #[IsGranted(User::ROLE_PENDING)]
    public function renvoyer(#[CurrentUser] User $user): JsonResponse
    {
        if ($user->isEmailConfirme()) {
            throw new ApiException('Votre adresse e-mail est déjà confirmée.');
        }

        $this->nouveauJeton($user);
        $this->em->flush();
        $this->notificateur->confirmationEmail($user);

        return $this->json(['message' => 'Un nouvel e-mail de confirmation vous a été envoyé.']);
    }

    private function nouveauJeton(User $user): void
    {
        $user->setConfirmationToken(bin2hex(random_bytes(32)))
            ->setConfirmationExpiresAt(new \DateTimeImmutable('+24 hours'));
    }
}
