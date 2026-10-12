<?php

namespace App\Tests\Api;

use App\Entity\JournalAction;
use App\Entity\User;
use App\Enum\StatutUser;
use App\Service\GestionComptes;
use App\Tests\ApiTestCase;

class CompteEtMairieApiTest extends ApiTestCase
{
    private const MAIRIE = 'mairie@coteacote.test';
    private const ADMIN = 'admin@coteacote.test';

    /** EF-01 à EF-05 : parcours complet d'inscription. */
    public function testInscriptionConfirmationEtValidation(): void
    {
        $this->api('POST', '/api/inscription', [
            'prenom' => 'Paul', 'nom' => 'Nouveau', 'email' => 'paul@exemple.test',
            'password' => 'un-mot-de-passe-long', 'rue' => 'Rue des Lilas', 'cgu' => true,
        ]);
        self::assertSame(201, $this->statut());
        self::assertEmailCount(1);

        $user = $this->user('paul@exemple.test');
        self::assertSame([User::ROLE_PENDING], $user->getRoles());
        self::assertFalse($user->isEmailConfirme());

        // Connecté mais en attente : pas d'accès aux publications
        $moi = $this->connecter('paul@exemple.test', 'un-mot-de-passe-long');
        self::assertSame('en_attente', $moi['statut']);

        $this->api('POST', '/api/confirmer-email', ['token' => $user->getConfirmationToken()]);
        self::assertResponseIsSuccessful();

        $this->connecter(self::MAIRIE);
        $inscriptions = $this->api('GET', '/api/mairie/inscriptions');
        self::assertContains('paul@exemple.test', array_column($inscriptions, 'email'));

        $this->api('POST', "/api/mairie/inscriptions/{$user->getId()}/valider");
        self::assertResponseIsSuccessful();

        $this->em()->clear();
        $user = $this->user('paul@exemple.test');
        self::assertSame([User::ROLE_HABITANT], $user->getRoles());
        self::assertSame(StatutUser::Valide, $user->getStatut());
        self::assertNotNull($this->em()->getRepository(JournalAction::class)->findOneBy(['action' => 'validation_inscription', 'cibleId' => $user->getId()]));
    }

    public function testInscriptionRefuseNumeroDeRueEtMotDePasseCourt(): void
    {
        $reponse = $this->api('POST', '/api/inscription', [
            'prenom' => 'Paul', 'nom' => 'Nouveau', 'email' => 'paul@exemple.test',
            'password' => 'court', 'rue' => '12 rue des Lilas', 'cgu' => false,
        ]);

        self::assertSame(422, $this->statut());
        self::assertArrayHasKey('password', $reponse['errors']);
        self::assertArrayHasKey('rue', $reponse['errors']);
        self::assertArrayHasKey('cgu', $reponse['errors']);
    }

    public function testLienDeConfirmationExpire(): void
    {
        $this->api('POST', '/api/inscription', [
            'prenom' => 'Paul', 'nom' => 'Nouveau', 'email' => 'paul@exemple.test',
            'password' => 'un-mot-de-passe-long', 'rue' => 'Rue des Lilas', 'cgu' => true,
        ]);
        $user = $this->user('paul@exemple.test');
        $user->setConfirmationExpiresAt(new \DateTimeImmutable('-1 minute'));
        $this->em()->flush();

        $reponse = $this->api('POST', '/api/confirmer-email', ['token' => $user->getConfirmationToken()]);
        self::assertSame(400, $this->statut());
        self::assertStringContainsString('expiré', $reponse['message']);
    }

    public function testMauvaisMotDePasse(): void
    {
        $this->connecter('marie.dupont@exemple.test', 'mauvais-mot-de-passe');
        self::assertSame(401, $this->statut());
    }

    public function testCloisonnementDesRoles(): void
    {
        $this->connecter('marie.dupont@exemple.test');
        $this->api('GET', '/api/mairie/tableau-de-bord');
        self::assertSame(403, $this->statut());

        $this->connecter(self::ADMIN);
        $this->api('GET', '/api/mairie/habitants');
        self::assertSame(403, $this->statut(), 'L\'administrateur association n\'accède pas aux données des habitants');

        $this->connecter(self::MAIRIE);
        $this->api('GET', '/api/admin/mots-interdits');
        self::assertSame(403, $this->statut());
    }

    /** EF-24 : la suspension empêche de publier. */
    public function testSuspension(): void
    {
        $marie = $this->user('marie.dupont@exemple.test');

        $this->connecter(self::MAIRIE);
        $this->api('POST', "/api/mairie/habitants/{$marie->getId()}/suspendre");
        self::assertResponseIsSuccessful();

        $this->connecter('marie.dupont@exemple.test');
        $references = $this->api('GET', '/api/references');
        $this->api('POST', '/api/publications', [
            'type' => 'demande', 'categorieId' => $references['categories'][0]['sousCategories'][0]['id'], 'nature' => 'aide',
        ]);
        self::assertSame(403, $this->statut());
    }

    /** EF-25, EF-26 */
    public function testTableauDeBordEtPublicationPourHabitantSansInternet(): void
    {
        $this->connecter(self::MAIRIE);
        $tableau = $this->api('GET', '/api/mairie/tableau-de-bord');
        self::assertSame(2, $tableau['inscriptionsEnAttente']);
        self::assertSame(1, $tableau['signalementsEnAttente']);

        $references = $this->api('GET', '/api/references');
        $p = $this->api('POST', '/api/mairie/publications', [
            'type' => 'demande', 'categorieId' => $references['categories'][5]['sousCategories'][0]['id'], 'nature' => 'aide',
            'prenom' => 'Robert', 'initiale' => 'b', 'rue' => 'Rue du Lavoir',
        ]);
        self::assertSame(201, $this->statut());
        self::assertSame('Robert B.', $p['auteur']);
        self::assertTrue($p['publieeParMairie']);

        // La réponse d'un habitant part à l'adresse de la mairie
        $this->connecter('jean.martin@exemple.test');
        $this->api('POST', "/api/publications/{$p['id']}/repondre");
        self::assertResponseIsSuccessful();
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'mairie@coteacote.test');
    }

    /** EF-07, RG-11, DP-04 */
    public function testExportEtSuppressionDuCompte(): void
    {
        $this->connecter('marie.dupont@exemple.test');

        $this->client->request('GET', '/api/moi/export');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Disposition', 'attachment; filename=mes-donnees-cote-a-cote.json');
        $export = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('marie.dupont@exemple.test', $export['compte']['email']);
        self::assertNotEmpty($export['publications']);

        $moi = $this->api('DELETE', '/api/moi');
        self::assertSame('supprime', $moi['statut']);
        self::assertSame(0, $this->api('GET', '/api/publications/mes')['actives']);

        $moi = $this->api('POST', '/api/moi/annuler-suppression');
        self::assertSame('valide', $moi['statut']);

        // Après 30 jours, l'effacement est définitif
        $this->api('DELETE', '/api/moi');
        $comptes = static::getContainer()->get(GestionComptes::class);
        $comptes->purger(new \DateTimeImmutable('+31 days'));
        $this->em()->clear();
        self::assertNull($this->user('marie.dupont@exemple.test'));
    }

    public function testAdminGereLesMotsInterdits(): void
    {
        $this->connecter(self::ADMIN);
        $this->api('POST', '/api/admin/mots-interdits', ['mot' => 'gougnafier']);
        self::assertSame(201, $this->statut());

        $this->connecter('marie.dupont@exemple.test');
        $references = $this->api('GET', '/api/references');
        $reponse = $this->api('POST', '/api/publications', [
            'type' => 'demande', 'categorieId' => $references['categories'][0]['sousCategories'][0]['id'], 'nature' => 'aide',
            'complement' => 'un gougnafier',
        ]);
        self::assertSame(422, $this->statut());
        self::assertSame('Ce texte contient des éléments non autorisés.', $reponse['errors']['complement']);
    }
}
