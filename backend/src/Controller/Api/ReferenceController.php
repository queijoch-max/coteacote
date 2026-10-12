<?php

namespace App\Controller\Api;

use App\Entity\Categorie;
use App\Enum\MotifRefus;
use App\Enum\MotifSignalement;
use App\Enum\NaturePublication;
use App\Enum\TypePublication;
use App\Repository\CategorieRepository;
use App\Repository\CommuneRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** Listes déroulantes utilisées par le front (EF-09). */
#[OA\Tag(name: 'Références')]
class ReferenceController extends AbstractController
{
    #[Route('/api/references', name: 'api_references', methods: ['GET'])]
    public function references(CategorieRepository $categories, CommuneRepository $communes): JsonResponse
    {
        $commune = $communes->findCommune();

        return $this->json([
            'commune' => $commune ? ['nom' => $commune->getNom(), 'codePostal' => $commune->getCodePostal()] : null,
            'types' => self::options(TypePublication::cases()),
            'natures' => self::options(NaturePublication::cases()),
            'motifsSignalement' => self::options(MotifSignalement::cases()),
            'motifsRefus' => self::options(MotifRefus::cases()),
            'categories' => array_map(static fn (Categorie $c) => [
                'id' => $c->getId(),
                'libelle' => $c->getLibelle(),
                'sousCategories' => array_values(array_map(
                    static fn (Categorie $e) => ['id' => $e->getId(), 'libelle' => $e->getLibelle()],
                    array_filter($c->getEnfants()->toArray(), static fn (Categorie $e) => $e->isActif()),
                )),
            ], $categories->findRacines()),
        ]);
    }

    /** @param \BackedEnum[] $cases */
    private static function options(array $cases): array
    {
        return array_map(static fn ($c) => ['value' => $c->value, 'libelle' => $c->libelle()], $cases);
    }
}
