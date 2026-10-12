# Côte à côte — application d'entraide communale

Prototype (V1) d'une application qui permet aux habitants d'**une commune** de demander et de proposer de l'aide entre eux, dans un cadre fixé par la mairie.

- **La sécurité vient de la structure de l'application.** Les annonces se créent avec des listes déroulantes, le seul champ libre fait 30 caractères et passe par un filtre de mots, et la mairie valide les inscriptions.
- **Aucune coordonnée n'est affichée.** Quand un habitant clique sur « Je peux aider », seul l'auteur de l'annonce reçoit son contact, par e-mail.
- **Le projet tourne uniquement en local** : SQLite, e-mails capturés par Mailpit, aucun service en ligne.

| Couche | Technologie |
|---|---|
| API | PHP 8.2+, Symfony 7.4 LTS, Doctrine ORM, API REST JSON |
| Front | React 19 + Vite, React Router, responsive (mobile d'abord) |
| Base de données | SQLite (`backend/var/data_dev.db`) |
| E-mails | Symfony Mailer + Mailpit (capture locale) |
| Tâches planifiées | Commandes Symfony + Symfony Scheduler |
| Tests | PHPUnit (back), Vitest + Testing Library (front) |
| Documentation API | OpenAPI (NelmioApiDocBundle) sur `/api/doc` |

---

## Installation locale

### Prérequis

- PHP **8.2 ou plus**, avec les extensions `pdo_sqlite`, `intl`, `mbstring`, `ctype`, `iconv`
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download)
- Node.js **20 ou plus** et npm
- *(facultatif)* [Mailpit](https://mailpit.axllent.org/) pour lire les e-mails envoyés par l'application

### 1. API Symfony

```bash
cd backend
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php bin/console assets:install public
symfony serve --no-tls --port=8000
```

L'API répond sur http://127.0.0.1:8000 et sa documentation est sur http://127.0.0.1:8000/api/doc.

### 2. Front React

Dans un second terminal :

```bash
cd frontend
npm install
npm run dev
```

Ouvrez ensuite **http://localhost:5173**. Vite redirige `/api` vers Symfony : le front et l'API ont la même origine, donc le cookie de session fonctionne sans configuration CORS.

### 3. E-mails (facultatif)

Lancez Mailpit (binaire, ou `docker compose up -d` dans `backend/`), puis ouvrez **http://localhost:8025**.
Sans Mailpit, l'application fonctionne quand même : l'échec d'envoi est journalisé, et le lien de confirmation d'inscription est écrit dans `backend/var/log/dev.log`.

### Comptes de test

Tous les comptes créés par les fixtures ont le mot de passe **`entraide-2026`**.

| Rôle | E-mail | Remarque |
|---|---|---|
| Administrateur mairie | `mairie@coteacote.test` | |
| Administrateur association | `admin@coteacote.test` | |
| Habitant validé | `marie.dupont@exemple.test` | 15 autres habitants validés (`prenom.nom@exemple.test`) |
| Habitant en attente | `monique.faure@exemple.test` | inscription à valider par la mairie |
| Habitant suspendu | `lea.girard@exemple.test` | ne peut ni publier ni répondre |

Les fixtures créent aussi 1 commune, 10 catégories et 30 sous-catégories, 41 annonces (dont une saisie par la mairie), 20 mots interdits et un signalement en attente.

---

## Commandes utiles

```bash
# Tests
cd backend && php bin/phpunit
cd frontend && npm test

# Tâches planifiées (lancées à la main…)
php bin/console app:publications:expirer      # EF-12 : expiration après 30 jours
php bin/console app:donnees:purger            # DP-05, RG-11, RG-12 : durées de conservation
php bin/console app:mairie:recapitulatif      # EF-27 : récapitulatif quotidien à la mairie

# … ou par le planificateur Symfony
php bin/console messenger:consume scheduler_default
```

---

## Architecture

```
backend/
  src/
    Controller/Api/     Session, Inscription, Profil, Références, Publications, Mairie, Admin
    Dto/                Données d'entrée validées (#[MapRequestPayload])
    Entity/             8 entités Doctrine (Commune, User, Categorie, Publication, Reponse,
                        Signalement, MotInterdit, JournalAction)
    Enum/               Enums PHP (types, natures, statuts, motifs)
    Security/           PublicationVoter, réponses JSON d'authentification
    Service/            ComplementFilter (RG-01 à RG-09), Notificateur, Journal, GestionComptes…
    Validator/          Contrainte #[ComplementAutorise] (ES-04)
    EventSubscriber/    CSRF, erreurs JSON, déconnexion
    Command/            Expiration, purge, récapitulatif mairie
  templates/emails/     Modèles d'e-mails
  tests/                Tests unitaires (filtre) et fonctionnels (API)

frontend/
  src/
    components/         Logo CSS, mise en page, carte d'annonce, champs de formulaire accessibles
    context/            Session (utilisateur, jeton CSRF, listes de référence)
    lib/                Client API, vérification du complément (confort), formats de date
    pages/              Pages publiques, habitant, mairie/, admin/
```

### Choix techniques

**Authentification par session plutôt que JWT (ES-02).** Le front est servi depuis la même origine que l'API grâce au proxy Vite. Le cookie de session (`HttpOnly`, `SameSite=Lax`) n'est donc jamais lisible en JavaScript : un script injecté ne peut pas le voler, alors qu'un JWT stocké dans `localStorage` le pourrait. La session se révoque immédiatement côté serveur, par exemple quand on suspend un compte, et cela ne demande ni bibliothèque supplémentaire ni gestion de jetons de rafraîchissement. La limitation de connexion (5 essais par 15 minutes) utilise directement le `login_throttling` de Symfony.

**CSRF (ES-05).** Le front récupère un jeton par `GET /api/session` et l'envoie dans l'en-tête `X-CSRF-Token` à chaque `POST`, `PUT` ou `DELETE`. Le jeton est vérifié par `CsrfSubscriber` avant le pare-feu. Comme Symfony renouvelle la session à la connexion, le front redemande un jeton juste après.

**Filtre du complément (section 5).** `ComplementFilter` contrôle deux lectures du texte :
- les mots du texte brut ;
- le texte normalisé : minuscules, sans accents, sans séparateurs, chiffres « look-alike » convertis.

Il déjoue ainsi les contournements comme `g m a i l` ou `0range`, sans bloquer « remplacer » ni « lave-linge ». La version JavaScript du front (`lib/complement.js`) n'est qu'un confort d'affichage : la vérification qui compte est la contrainte de validation côté serveur.

**Aucune coordonnée dans l'API (ES-08).** Les réponses JSON sont construites champ par champ (`PublicationPresenter`, `UserPresenter`) et ne sérialisent jamais une entité entière. Un test fonctionnel vérifie qu'aucune réponse ne contient de `@` ni de numéro de téléphone.

**Droits (ES-03).** Chaque contrôleur est protégé par `#[IsGranted]` par rôle. Le `PublicationVoter` gère les droits qui dépendent de la ressource ou de l'état du compte : seul l'auteur gère son annonce, et seul un habitant validé publie, répond ou signale.

**Multi-communes préparé.** L'entité `Commune` existe déjà avec une seule ligne, comme le prévoit le cahier des charges.

---

## Correspondance avec le cahier des charges

| Exigence | Où |
|---|---|
| EF-01 à EF-03 Inscription, confirmation 24 h | `InscriptionController`, `InscriptionInput`, `Inscription.jsx`, `ConfirmerEmail.jsx` |
| EF-04, EF-05 Validation / refus par la mairie | `MairieController::valider/refuser`, `mairie/Inscriptions.jsx` |
| EF-06, EF-07 Profil, contact partagé, suppression | `ProfilController`, `GestionComptes`, `Profil.jsx` |
| EF-08 à EF-11 Publication par listes, complément filtré | `PublicationController::creer`, `PublicationInput`, `ComplementFilter`, `Publier.jsx` |
| EF-12, EF-13 Expiration, renouvellement, résolution | `Publication::renouveler`, `ExpirerPublicationsCommand`, `MesPublications.jsx` |
| EF-14 5 annonces actives maximum | `PublicationController::creer` |
| EF-15, EF-16 Liste filtrable, affichage minimal | `PublicationRepository::findActives`, `PublicationPresenter`, `Publications.jsx` |
| EF-17 à EF-20 « Je peux aider », e-mail à l'auteur, réponse unique | `PublicationController::repondre`, `Notificateur::reponseRecue` |
| EF-21, EF-22 Signalement, masquage à 3 | `PublicationController::signaler` |
| EF-23, EF-24 Rétablir, supprimer, suspendre | `MairieController`, `mairie/Signalements.jsx`, `mairie/Habitants.jsx` |
| EF-25 Tableau de bord | `MairieController::tableauDeBord`, `mairie/TableauDeBord.jsx` |
| EF-26 Publication pour un habitant sans Internet | `MairieController::publier`, `mairie/PublicationsMairie.jsx` |
| EF-27 Récapitulatif quotidien | `RecapitulatifMairieCommand`, `Schedule.php` |
| EF-28 Journal | `Journal`, `JournalAction`, `mairie/Journal.jsx` |
| RG-01 à RG-09 | `ComplementFilter`, `ComplementFilterTest` |
| RG-10 Pas de catégorie à risque | `AppFixtures::CATEGORIES` |
| RG-11, RG-12, DP-05 Durées de conservation | `GestionComptes::purger`, `PurgerDonneesCommand` |
| ES-01 Mots de passe | `security.yaml` (`auto`), 12 caractères minimum dans les DTO |
| ES-06 Limitation de débit | `login_throttling`, `rate_limiter.yaml` |
| DP-02, DP-03 Pages légales | `pages/Legal.jsx` |
| DP-04 Export JSON | `ProfilController::export` |
| ENF-01 à ENF-03 Accessibilité, mobile | police Atkinson Hyperlegible (hébergée localement), texte 18 px, boutons ≥ 48 px, focus visible, lien d'évitement, libellés reliés aux champs |

### Critères de validation couverts par des tests automatisés

- Chaque règle RG-01 à RG-07 refuse l'exemple du cahier des charges : `tests/Service/ComplementFilterTest.php`.
- Aucune réponse d'API ne contient de téléphone ou d'e-mail : `PublicationApiTest::testAucuneCoordonneeDansLaListe`.
- Un habitant en attente ou suspendu ne peut ni publier ni répondre via l'API : `testHabitantEnAttenteNePeutRienFaire`, `testHabitantSuspenduNePeutNiPublierNiRepondre`.
- Un habitant ne peut pas supprimer l'annonce d'un autre : `testNePeutPasSupprimerLaPublicationDunAutre`.

Restent à vérifier à la main : l'audit Lighthouse, le test d'une publication par une personne qui découvre l'application, et l'installation sur une autre machine.

---

## Hors périmètre de la V1

Actions collectives et agenda (V2), commerces et plusieurs communes (V3), messagerie, photos, paiement et application mobile native sont volontairement exclus (cahier des charges, section 2).
