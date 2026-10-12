<?php

namespace App\Controller\Api;

use App\Dto\CategorieInput;
use App\Dto\CompteMairieInput;
use App\Dto\MotInterditInput;
use App\Entity\Categorie;
use App\Entity\MotInterdit;
use App\Entity\User;
use App\Enum\StatutUser;
use App\Exception\ApiException;
use App\Repository\CategorieRepository;
use App\Repository\CommuneRepository;
use App\Repository\MotInterditRepository;
use App\Repository\UserRepository;
use App\Service\Journal;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Administrateur association : catégories, mots interdits, comptes mairie.
 * Aucun accès aux données des habitants.
 */
#[OA\Tag(name: 'Administration')]
#[Route('/api/admin', requirements: ['id' => Requirement::DIGITS])]
#[IsGranted(User::ROLE_ADMIN)]
class AdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/categories', name: 'api_admin_categories', methods: ['GET'])]
    public function categories(CategorieRepository $categories): JsonResponse
    {
        return $this->json(array_map(fn (Categorie $c) => $this->categorie($c) + [
            'sousCategories' => array_map(fn (Categorie $e) => $this->categorie($e), $c->getEnfants()->toArray()),
        ], $categories->findRacines(actifsSeulement: false)));
    }

    #[Route('/categories', name: 'api_admin_categories_creer', methods: ['POST'])]
    public function creerCategorie(#[MapRequestPayload] CategorieInput $input, CategorieRepository $categories): JsonResponse
    {
        $categorie = new Categorie();
        $this->remplirCategorie($categorie, $input, $categories);
        $this->em->persist($categorie);
        $this->em->flush();

        return $this->json($this->categorie($categorie), 201);
    }

    #[Route('/categories/{id}', name: 'api_admin_categories_modifier', methods: ['PUT'])]
    public function modifierCategorie(Categorie $categorie, #[MapRequestPayload] CategorieInput $input, CategorieRepository $categories): JsonResponse
    {
        $this->remplirCategorie($categorie, $input, $categories);
        $this->em->flush();

        return $this->json($this->categorie($categorie));
    }

    #[Route('/mots-interdits', name: 'api_admin_mots', methods: ['GET'])]
    public function mots(MotInterditRepository $mots): JsonResponse
    {
        return $this->json(array_map(fn (MotInterdit $m) => $this->mot($m), $mots->findBy([], ['mot' => 'ASC'])));
    }

    #[Route('/mots-interdits', name: 'api_admin_mots_creer', methods: ['POST'])]
    public function creerMot(#[MapRequestPayload] MotInterditInput $input, MotInterditRepository $mots): JsonResponse
    {
        if ($mots->findOneBy(['mot' => mb_strtolower(trim($input->mot))])) {
            throw new ApiException('Ce mot est déjà dans la liste.', 409);
        }

        $mot = (new MotInterdit())->setMot($input->mot)->setActif($input->actif);
        $this->em->persist($mot);
        $this->em->flush();

        return $this->json($this->mot($mot), 201);
    }

    #[Route('/mots-interdits/{id}', name: 'api_admin_mots_modifier', methods: ['PUT'])]
    public function modifierMot(MotInterdit $mot, #[MapRequestPayload] MotInterditInput $input): JsonResponse
    {
        $mot->setMot($input->mot)->setActif($input->actif);
        $this->em->flush();

        return $this->json($this->mot($mot));
    }

    #[Route('/mots-interdits/{id}', name: 'api_admin_mots_supprimer', methods: ['DELETE'])]
    public function supprimerMot(MotInterdit $mot): JsonResponse
    {
        $this->em->remove($mot);
        $this->em->flush();

        return new JsonResponse(null, 204);
    }

    #[Route('/comptes-mairie', name: 'api_admin_comptes_mairie', methods: ['GET'])]
    public function comptesMairie(UserRepository $users): JsonResponse
    {
        return $this->json(array_map(static fn (User $u) => [
            'id' => $u->getId(),
            'prenom' => $u->getPrenom(),
            'nom' => $u->getNom(),
            'email' => $u->getEmail(),
            'createdAt' => $u->getCreatedAt()->format(\DATE_ATOM),
        ], $users->findByRole(User::ROLE_MAIRIE)));
    }

    #[Route('/comptes-mairie', name: 'api_admin_comptes_mairie_creer', methods: ['POST'])]
    public function creerCompteMairie(
        #[CurrentUser] User $admin,
        #[MapRequestPayload] CompteMairieInput $input,
        UserRepository $users,
        CommuneRepository $communes,
        UserPasswordHasherInterface $hasher,
        Journal $journal,
    ): JsonResponse {
        if ($users->findOneBy(['email' => mb_strtolower(trim($input->email))])) {
            throw new ApiException('Un compte existe déjà avec cette adresse e-mail.', 409);
        }

        $commune = $communes->findCommune();
        $user = (new User())
            ->setPrenom($input->prenom)
            ->setNom($input->nom)
            ->setEmail($input->email)
            ->setRue($commune?->getNom() ?? 'Mairie')
            ->setRole(User::ROLE_MAIRIE)
            ->setStatut(StatutUser::Valide)
            ->setEmailConfirme(true)
            ->setCommune($commune);
        $user->setPassword($hasher->hashPassword($user, $input->password));

        $this->em->persist($user);
        $this->em->flush();
        $journal->enregistrer($admin, Journal::CREATION_COMPTE_MAIRIE, 'user', $user->getId());
        $this->em->flush();

        return $this->json(['id' => $user->getId(), 'email' => $user->getEmail()], 201);
    }

    private function remplirCategorie(Categorie $categorie, CategorieInput $input, CategorieRepository $categories): void
    {
        $parent = $input->parentId ? $categories->find($input->parentId) : null;
        if ($input->parentId && (!$parent || $parent->getParent() || $parent === $categorie)) {
            throw new ApiException('La catégorie parente doit être une catégorie principale.', 422);
        }
        if ($parent && $categorie->getEnfants()->count() > 0) {
            throw new ApiException('Une catégorie qui a des sous-catégories ne peut pas devenir une sous-catégorie.', 422);
        }

        $categorie->setLibelle($input->libelle)->setParent($parent)->setActif($input->actif)->setOrdre($input->ordre);
    }

    private function categorie(Categorie $c): array
    {
        return [
            'id' => $c->getId(),
            'libelle' => $c->getLibelle(),
            'parentId' => $c->getParent()?->getId(),
            'actif' => $c->isActif(),
            'ordre' => $c->getOrdre(),
        ];
    }

    private function mot(MotInterdit $m): array
    {
        return ['id' => $m->getId(), 'mot' => $m->getMot(), 'actif' => $m->isActif()];
    }
}
