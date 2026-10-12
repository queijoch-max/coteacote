<?php

namespace App\Tests\Api;

use App\Entity\Publication;
use App\Enum\StatutPublication;
use App\Tests\ApiTestCase;

class PublicationApiTest extends ApiTestCase
{
    private const MARIE = 'marie.dupont@exemple.test';
    private const JEAN = 'jean.martin@exemple.test';
    private const EN_ATTENTE = 'monique.faure@exemple.test';
    private const SUSPENDU = 'lea.girard@exemple.test';

    public function testVisiteurNeVoitPasLesPublications(): void
    {
        $this->api('GET', '/api/publications');
        self::assertSame(401, $this->statut());
    }

    /** ES-08 : aucune réponse d'API ne contient le téléphone ou l'e-mail d'un autre habitant. */
    public function testAucuneCoordonneeDansLaListe(): void
    {
        $this->connecter(self::MARIE);
        $this->client->request('GET', '/api/publications?page=1');
        $json = $this->client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('@', $json);
        self::assertDoesNotMatchRegularExpression('/0\d([\s.]?\d{2}){4}/', $json);
        self::assertStringNotContainsString('telephone', $json);
        self::assertStringNotContainsString('Dupont', $json, 'Seule l\'initiale du nom est affichée');

        $this->client->request('GET', '/api/publications/mes');
        self::assertStringNotContainsString('@', $this->client->getResponse()->getContent());
    }

    public function testPublicationAfficheUniquementLesChampsPrevus(): void
    {
        $this->connecter(self::MARIE);
        $p = $this->publicationDunAutre(self::MARIE);

        self::assertMatchesRegularExpression('/^\p{L}[\p{L} -]+ \p{Lu}\.$/u', $p['auteur']);
        self::assertArrayNotHasKey('email', $p);
        self::assertArrayNotHasKey('nbReponses', $p);
    }

    public function testFiltres(): void
    {
        $this->connecter(self::MARIE);
        $resultat = $this->api('GET', '/api/publications?type=offre&nature=pret');

        self::assertNotEmpty($resultat['items']);
        foreach ($resultat['items'] as $p) {
            self::assertSame('offre', $p['type']);
            self::assertSame('pret', $p['nature']);
        }
    }

    public function testCreerUnePublication(): void
    {
        $this->connecter(self::MARIE);
        $categorie = $this->sousCategorie();

        $p = $this->api('POST', '/api/publications', [
            'type' => 'demande', 'categorieId' => $categorie, 'nature' => 'aide', 'complement' => 'le mardi',
        ]);

        self::assertSame(201, $this->statut());
        self::assertSame('le mardi', $p['complement']);
        self::assertSame('Marie D.', $p['auteur']);
    }

    /** ES-04 / EF-11 : filtre côté serveur, message générique. */
    public function testComplementInterditRefuseAvecMessageGenerique(): void
    {
        $this->connecter(self::MARIE);

        $reponse = $this->api('POST', '/api/publications', [
            'type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide', 'complement' => '12 rue des lilas',
        ]);

        self::assertSame(422, $this->statut());
        self::assertSame('Ce texte contient des éléments non autorisés.', $reponse['errors']['complement']);
    }

    public function testCategoriePrincipaleRefusee(): void
    {
        $this->connecter(self::MARIE);
        $references = $this->api('GET', '/api/references');

        $this->api('POST', '/api/publications', [
            'type' => 'demande', 'categorieId' => $references['categories'][0]['id'], 'nature' => 'aide',
        ]);
        self::assertSame(422, $this->statut());
    }

    /** EF-14 : 5 publications actives maximum. */
    public function testMaximumCinqPublicationsActives(): void
    {
        $this->connecter(self::MARIE);
        $actives = $this->api('GET', '/api/publications/mes')['actives'];

        for ($i = $actives; $i < Publication::MAX_ACTIVES; ++$i) {
            $this->api('POST', '/api/publications', ['type' => 'offre', 'categorieId' => $this->sousCategorie(), 'nature' => 'don']);
            self::assertSame(201, $this->statut());
        }

        $reponse = $this->api('POST', '/api/publications', ['type' => 'offre', 'categorieId' => $this->sousCategorie(), 'nature' => 'don']);
        self::assertSame(422, $this->statut());
        self::assertStringContainsString('5 publications', $reponse['message']);
    }

    /** Critère : un habitant en attente ou suspendu ne peut ni publier ni répondre, même via l'API. */
    public function testHabitantEnAttenteNePeutRienFaire(): void
    {
        $this->connecter(self::EN_ATTENTE);

        $this->api('GET', '/api/publications');
        self::assertSame(403, $this->statut());

        $this->api('POST', '/api/publications', ['type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide']);
        self::assertSame(403, $this->statut());

        $id = $this->em()->getRepository(Publication::class)->findOneBy(['statut' => StatutPublication::Active])->getId();
        $this->api('POST', "/api/publications/$id/repondre");
        self::assertSame(403, $this->statut());
    }

    public function testHabitantSuspenduNePeutNiPublierNiRepondre(): void
    {
        $this->connecter(self::SUSPENDU);

        $this->api('POST', '/api/publications', ['type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide']);
        self::assertSame(403, $this->statut());

        $p = $this->publicationDunAutre(self::SUSPENDU);
        $this->api('POST', "/api/publications/{$p['id']}/repondre");
        self::assertSame(403, $this->statut());
    }

    /** Critère : un habitant ne peut pas supprimer la publication d'un autre. */
    public function testNePeutPasSupprimerLaPublicationDunAutre(): void
    {
        $this->connecter(self::MARIE);
        $p = $this->publicationDunAutre(self::MARIE);

        $this->api('DELETE', "/api/publications/{$p['id']}");
        self::assertSame(403, $this->statut());

        $this->api('POST', "/api/publications/{$p['id']}/resoudre");
        self::assertSame(403, $this->statut());
    }

    public function testAuteurResoutEtSupprimeSaPublication(): void
    {
        $this->connecter(self::MARIE);
        $p = $this->api('POST', '/api/publications', ['type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide']);

        $resolue = $this->api('POST', "/api/publications/{$p['id']}/resoudre");
        self::assertSame('resolue', $resolue['statut']);

        $this->api('DELETE', "/api/publications/{$p['id']}");
        self::assertSame(204, $this->statut());
    }

    /** EF-12 : un seul renouvellement. */
    public function testRenouvellementUnique(): void
    {
        $this->connecter(self::MARIE);
        $p = $this->api('POST', '/api/publications', ['type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide']);

        $this->api('POST', "/api/publications/{$p['id']}/renouveler");
        self::assertResponseIsSuccessful();

        $this->api('POST', "/api/publications/{$p['id']}/renouveler");
        self::assertSame(400, $this->statut());
    }

    /** EF-18 à EF-20 : contact envoyé uniquement à l'auteur, une seule réponse. */
    public function testRepondreEnvoieLeContactALAuteurUneSeuleFois(): void
    {
        $this->connecter(self::JEAN);
        $p = $this->publicationDunAutre(self::JEAN);

        $reponse = $this->api('POST', "/api/publications/{$p['id']}/repondre");
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('@', json_encode($reponse));

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailHtmlBodyContains($email, self::JEAN);
        self::assertEmailHtmlBodyContains($email, 'Jean');

        $this->api('POST', "/api/publications/{$p['id']}/repondre");
        self::assertSame(409, $this->statut());
    }

    public function testNePeutPasRepondreASaProprePublication(): void
    {
        $this->connecter(self::MARIE);
        $p = $this->api('POST', '/api/publications', ['type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide']);

        $this->api('POST', "/api/publications/{$p['id']}/repondre");
        self::assertSame(403, $this->statut());
    }

    /** EF-22 : masquée automatiquement à 3 signalements de personnes différentes. */
    public function testMasqueeApresTroisSignalements(): void
    {
        $this->connecter(self::MARIE);
        $p = $this->api('POST', '/api/publications', ['type' => 'offre', 'categorieId' => $this->sousCategorie(), 'nature' => 'don']);

        foreach (['jean.martin', 'fatima.benali', 'lucas.bernard'] as $i => $habitant) {
            $this->connecter($habitant.'@exemple.test');
            $this->api('POST', "/api/publications/{$p['id']}/signaler", ['motif' => 'inapproprie']);
            self::assertResponseIsSuccessful();

            if ($i < 2) {
                // Le même habitant ne compte qu'une fois
                $this->api('POST', "/api/publications/{$p['id']}/signaler", ['motif' => 'inapproprie']);
                self::assertSame(409, $this->statut());
            }

            $this->em()->clear();
            $attendu = $i < 2 ? StatutPublication::Active : StatutPublication::Masquee;
            self::assertSame($attendu, $this->em()->find(Publication::class, $p['id'])->getStatut(), 'Après le signalement n°'.($i + 1));
        }
    }

    public function testCsrfObligatoire(): void
    {
        $this->connecter(self::MARIE);
        $this->api('POST', '/api/publications', ['type' => 'demande', 'categorieId' => $this->sousCategorie(), 'nature' => 'aide'], avecCsrf: false);

        self::assertSame(403, $this->statut());
    }

    private function sousCategorie(): int
    {
        return $this->api('GET', '/api/references')['categories'][3]['sousCategories'][0]['id'];
    }
}
