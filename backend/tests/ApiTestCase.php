<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base des tests fonctionnels : base SQLite de test recréée et fixtures rechargées avant chaque test.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    private ?string $csrf = null;
    private static bool $schemaCree = false;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $em = $this->em();

        if (!self::$schemaCree) {
            $tool = new SchemaTool($em);
            $metadata = $em->getMetadataFactory()->getAllMetadata();
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
            self::$schemaCree = true;
        }

        $loader = new Loader();
        $loader->addFixture(new AppFixtures(static::getContainer()->get(UserPasswordHasherInterface::class)));
        (new ORMExecutor($em, new ORMPurger($em)))->execute($loader->getFixtures());
        $em->clear();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function user(string $email): ?User
    {
        return $this->em()->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /** Repart d'un navigateur vierge (nouvelle session) puis se connecte. */
    protected function connecter(string $email, string $password = AppFixtures::MOT_DE_PASSE): array
    {
        $this->client->getCookieJar()->clear();
        $this->csrf = null;
        $reponse = $this->api('POST', '/api/connexion', ['email' => $email, 'password' => $password]);
        // La connexion change la session et invalide le jeton CSRF : le front en redemande un.
        $this->csrf = null;

        return $reponse;
    }

    /** Requête JSON avec le jeton CSRF ; renvoie le corps décodé. */
    protected function api(string $method, string $url, ?array $data = null, bool $avecCsrf = true): array
    {
        if ($avecCsrf && null === $this->csrf && 'GET' !== $method) {
            $this->client->request('GET', '/api/session');
            $this->csrf = json_decode($this->client->getResponse()->getContent(), true)['csrfToken'];
        }

        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($avecCsrf && $this->csrf) {
            $headers['HTTP_X_CSRF_TOKEN'] = $this->csrf;
        }

        $this->client->request($method, $url, server: $headers, content: null === $data ? null : json_encode($data));
        $contenu = $this->client->getResponse()->getContent();

        return $contenu ? (json_decode($contenu, true) ?? []) : [];
    }

    protected function statut(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    /** Première publication active d'un autre auteur que l'e-mail donné. */
    protected function publicationDunAutre(string $email): array
    {
        foreach ($this->api('GET', '/api/publications')['items'] as $p) {
            if (!$p['estAuteur'] && !$p['publieeParMairie']) {
                return $p;
            }
        }
        self::fail('Aucune publication trouvée.');
    }
}
