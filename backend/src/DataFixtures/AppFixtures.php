<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Commune;
use App\Entity\JournalAction;
use App\Entity\MotInterdit;
use App\Entity\Publication;
use App\Entity\Reponse;
use App\Entity\Signalement;
use App\Entity\User;
use App\Enum\MotifSignalement;
use App\Enum\NaturePublication;
use App\Enum\StatutPublication;
use App\Enum\StatutUser;
use App\Enum\TypePublication;
use App\Service\Journal;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de test : 1 commune, 1 compte mairie, 1 administrateur, 20 habitants, 40 publications.
 * Mot de passe de tous les comptes : voir MOT_DE_PASSE (documenté dans le README).
 */
class AppFixtures extends Fixture
{
    public const MOT_DE_PASSE = 'entraide-2026';

    /** RG-08 (catégories) et RG-10 (aucune catégorie à risque : électricité, gaz, travaux en hauteur). */
    public const CATEGORIES = [
        'Électroménager et mobilier' => ['Petit électroménager', 'Gros électroménager', 'Meubles', 'Vaisselle et cuisine'],
        'Vêtements et puériculture' => ['Vêtements adultes', 'Vêtements enfants', 'Matériel de puériculture'],
        'Jouets et livres' => ['Jouets', 'Livres', 'Jeux de société'],
        'Bricolage et petits dépannages' => ['Prêt d\'outils', 'Montage de meubles', 'Petites réparations'],
        'Jardinage' => ['Tonte et taille', 'Plants et graines', 'Prêt d\'outils de jardin'],
        'Courses et aide ponctuelle' => ['Courses', 'Garde d\'animaux', 'Arrosage pendant une absence'],
        'Transport et déménagement' => ['Accompagnement en voiture', 'Aide au déménagement', 'Prêt de remorque'],
        'Informatique et démarches en ligne' => ['Démarches administratives', 'Ordinateur et téléphone', 'Imprimer un document'],
        'Couture et réparation' => ['Retouches', 'Réparation de vêtements'],
        'Compagnie et visite' => ['Visite de convivialité', 'Promenade', 'Lecture'],
    ];

    private const RUES = [
        'Rue de la Mairie', 'Rue des Lilas', 'Chemin du Moulin', 'Allée des Tilleuls', 'Impasse des Vignes',
        'Rue du Lavoir', 'Place de l\'Église', 'Route de la Forêt',
    ];

    private const HABITANTS = [
        ['Marie', 'Dupont'], ['Jean', 'Martin'], ['Fatima', 'Benali'], ['Lucas', 'Bernard'], ['Chantal', 'Roux'],
        ['Mohamed', 'Haddad'], ['Sophie', 'Petit'], ['Pierre', 'Durand'], ['Nathalie', 'Leroy'], ['Hugo', 'Moreau'],
        ['Isabelle', 'Simon'], ['Bernard', 'Laurent'], ['Camille', 'Lefebvre'], ['Yannick', 'Michel'], ['Aïcha', 'Diallo'],
        ['Thomas', 'Garcia'], ['Monique', 'Faure'], ['Julien', 'Fournier'], ['Léa', 'Girard'], ['André', 'Bonnet'],
    ];

    /** Compléments plausibles, par sous-catégorie (les autres n'en ont pas). */
    private const COMPLEMENTS = [
        'Petit électroménager' => 'grille-pain bon état',
        'Meubles' => 'petite commode',
        'Vaisselle et cuisine' => 'service à café',
        'Vêtements enfants' => 'taille 4 ans',
        'Matériel de puériculture' => 'poussette',
        'Jouets' => 'Lego et peluches',
        'Livres' => 'deux cartons de romans',
        'Jeux de société' => 'jeu de cartes',
        'Prêt d\'outils' => 'perceuse',
        'Montage de meubles' => 'une armoire',
        'Tonte et taille' => 'une haie',
        'Prêt d\'outils de jardin' => 'tondeuse',
        'Courses' => 'le samedi matin',
        'Garde d\'animaux' => 'un chat pour une semaine',
        'Accompagnement en voiture' => 'pour un rendez-vous',
        'Aide au déménagement' => 'un après-midi',
        'Démarches administratives' => 'aide pour la CAF',
        'Ordinateur et téléphone' => 'en semaine après 17h',
        'Retouches' => 'ourlet de pantalon',
        'Visite de convivialité' => 'le mercredi',
    ];

    private const MOTS_INTERDITS = [
        'con', 'conne', 'connard', 'connasse', 'salaud', 'salope', 'pute', 'putain', 'merde', 'encule',
        'batard', 'abruti', 'debile', 'cretin', 'enfoire', 'pd', 'nique', 'ntm', 'fdp', 'bouffon',
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        mt_srand(42);

        $commune = (new Commune())
            ->setNom('Saint-Martin-sur-Loire')
            ->setCodePostal('45000')
            ->setCodeInsee('45999')
            ->setEmailContact('mairie@coteacote.test')
            ->setTelephoneContact('02 00 00 00 00');
        $manager->persist($commune);

        foreach (self::MOTS_INTERDITS as $mot) {
            $manager->persist((new MotInterdit())->setMot($mot));
        }

        $sousCategories = $this->categories($manager);

        $mairie = $this->user($commune, 'Claire', 'Mairie', 'mairie@coteacote.test', 'Rue de la Mairie', User::ROLE_MAIRIE, StatutUser::Valide);
        $admin = $this->user($commune, 'Alex', 'Association', 'admin@coteacote.test', 'Rue de la Mairie', User::ROLE_ADMIN, StatutUser::Valide);
        $manager->persist($mairie);
        $manager->persist($admin);

        $habitants = [];
        foreach (self::HABITANTS as $i => [$prenom, $nom]) {
            // 16 validés, 2 inscriptions à valider, 1 suspendu, 1 qui n'a pas confirmé son e-mail
            [$role, $statut] = match (true) {
                $i < 16 => [User::ROLE_HABITANT, StatutUser::Valide],
                $i < 18 => [User::ROLE_PENDING, StatutUser::EnAttente],
                18 === $i => [User::ROLE_HABITANT, StatutUser::Suspendu],
                default => [User::ROLE_PENDING, StatutUser::EnAttente],
            };
            $email = self::email($prenom, $nom);
            $user = $this->user($commune, $prenom, $nom, $email, self::RUES[$i % \count(self::RUES)], $role, $statut);
            $user->setEmailConfirme(19 !== $i);
            $user->setCreatedAt(new \DateTimeImmutable(\sprintf('-%d days', 60 - $i)));
            if (0 === $i % 3) {
                $user->setTelephone(\sprintf('06 %02d %02d %02d %02d', 10 + $i, 20 + $i, 30 + $i, 40 + $i))->setPartageTelephone(true);
            }
            $manager->persist($user);
            $habitants[] = $user;
        }

        $valides = \array_slice($habitants, 0, 16);
        $publications = [];
        for ($i = 0; $i < 40; ++$i) {
            $auteur = $valides[$i % 16];
            $categorie = $sousCategories[($i * 7) % \count($sousCategories)];
            $publication = (new Publication())
                ->setType(0 === $i % 2 ? TypePublication::Demande : TypePublication::Offre)
                ->setNature(self::naturePour($categorie, $i))
                ->setCategorie($categorie)
                ->setComplement(self::COMPLEMENTS[$categorie->getLibelle()] ?? null)
                ->setAuteur($auteur);

            $joursAvant = $i % 25;
            $publication->setCreatedAt(new \DateTimeImmutable(\sprintf('-%d days -%d hours', $joursAvant, $i)));
            $publication->setExpiresAt($publication->getCreatedAt()->modify('+30 days'));

            if ($i >= 34) {
                $publication->setStatut(StatutPublication::Resolue);
            }
            $manager->persist($publication);
            $publications[] = $publication;
        }

        // Une publication saisie par la mairie pour un habitant sans Internet (EF-26)
        $pourHabitant = (new Publication())
            ->setType(TypePublication::Demande)
            ->setNature(NaturePublication::Aide)
            ->setCategorie($sousCategories[array_search('Courses', array_map(fn (Categorie $c) => $c->getLibelle(), $sousCategories), true)])
            ->setComplement('le jeudi matin')
            ->setAuteur($mairie)
            ->setPublieeParMairie(true)
            ->setBeneficiaire('Germaine L.')
            ->setRueBeneficiaire('Rue du Lavoir');
        $manager->persist($pourHabitant);

        // Quelques réponses et un signalement en attente
        $manager->persist(new Reponse($publications[0], $valides[3]));
        $manager->persist(new Reponse($publications[2], $valides[5]));
        $manager->persist(new Signalement($publications[5], $valides[1], MotifSignalement::Commercial));
        $manager->persist(new Signalement($publications[5], $valides[2], MotifSignalement::Commercial));

        $manager->flush();

        // Journal cohérent avec les données : suspension du 19e habitant, publication pour Germaine
        $manager->persist(new JournalAction($mairie, Journal::SUSPENSION_COMPTE, 'user', $habitants[18]->getId()));
        $manager->persist(new JournalAction($mairie, Journal::PUBLICATION_POUR_HABITANT, 'publication', $pourHabitant->getId()));
        $manager->flush();
    }

    /** @return Categorie[] sous-catégories */
    private function categories(ObjectManager $manager): array
    {
        $sousCategories = [];
        $ordre = 0;
        foreach (self::CATEGORIES as $libelle => $enfants) {
            $parent = (new Categorie())->setLibelle($libelle)->setOrdre(++$ordre);
            $manager->persist($parent);
            foreach ($enfants as $j => $enfant) {
                $sous = (new Categorie())->setLibelle($enfant)->setParent($parent)->setOrdre($j + 1);
                $parent->getEnfants()->add($sous);
                $manager->persist($sous);
                $sousCategories[] = $sous;
            }
        }

        return $sousCategories;
    }

    private function user(Commune $commune, string $prenom, string $nom, string $email, string $rue, string $role, StatutUser $statut): User
    {
        $user = (new User())
            ->setPrenom($prenom)
            ->setNom($nom)
            ->setEmail($email)
            ->setRue($rue)
            ->setRole($role)
            ->setStatut($statut)
            ->setEmailConfirme(true)
            ->setCguAccepteesAt(new \DateTimeImmutable('-60 days'))
            ->setCommune($commune);
        $user->setPassword($this->hasher->hashPassword($user, self::MOT_DE_PASSE));

        return $user;
    }

    /** Nature plausible : prêt pour le matériel, don ou prêt pour les objets, aide pour les services. */
    private static function naturePour(Categorie $categorie, int $i): NaturePublication
    {
        $objets = ['Électroménager et mobilier', 'Vêtements et puériculture', 'Jouets et livres'];

        return match (true) {
            str_starts_with($categorie->getLibelle(), 'Prêt') => NaturePublication::Pret,
            \in_array($categorie->getParent()?->getLibelle(), $objets, true) => 0 === $i % 3 ? NaturePublication::Pret : NaturePublication::Don,
            default => NaturePublication::Aide,
        };
    }

    public static function email(string $prenom, string $nom): string
    {
        $base = mb_strtolower(\App\Service\ComplementFilter::sansAccents($prenom.'.'.$nom));

        return $base.'@exemple.test';
    }
}
