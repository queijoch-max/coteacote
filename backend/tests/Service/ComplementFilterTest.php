<?php

namespace App\Tests\Service;

use App\Repository\MotInterditRepository;
use App\Service\ComplementFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Critère de validation : « Un test automatisé prouve que chaque règle RG-01 à RG-07 refuse l'exemple donné. »
 */
class ComplementFilterTest extends TestCase
{
    private ComplementFilter $filter;

    protected function setUp(): void
    {
        $repository = $this->createStub(MotInterditRepository::class);
        $repository->method('findMotsActifs')->willReturn(['con', 'cretin', 'connard']);

        $this->filter = new ComplementFilter($repository);
    }

    /** Exemples donnés par le cahier des charges (section 5). */
    public static function exemplesDuCahierDesCharges(): iterable
    {
        yield 'RG-01 plus de 4 chiffres' => ['06 12 34', 'RG-01'];
        yield 'RG-02 mot de voirie' => ['12 rue des lilas', 'RG-02'];
        yield 'RG-03 arobase' => ['marie@', 'RG-03'];
        yield 'RG-04 site web' => ['www.site.fr', 'RG-04'];
        yield 'RG-05 insulte' => ['quel cretin', 'RG-05'];
        yield 'RG-06 caractères spéciaux' => ['<script>', 'RG-06'];
        yield 'RG-07 plus de 30 caractères' => [str_repeat('a', 31), 'RG-07'];
    }

    #[DataProvider('exemplesDuCahierDesCharges')]
    public function testChaqueRegleRefuseSonExemple(string $texte, string $regle): void
    {
        self::assertSame($regle, $this->filter->regleEnfreinte($texte));
        self::assertFalse($this->filter->estAutorise($texte));
    }

    /** Tentatives de contournement déjouées par la normalisation. */
    public static function contournements(): iterable
    {
        yield 'voirie avec accent et majuscules' => ['AVENUE du Parc', 'RG-02'];
        yield 'boulevard espacé' => ['b o u l e v a r d', 'RG-02'];
        yield 'lieu-dit' => ['au lieu-dit les Fosses', 'RG-02'];
        yield 'abréviation av' => ['15 av foch', 'RG-02'];
        yield 'gmail espacé' => ['g m a i l', 'RG-03'];
        yield 'orange avec zéro' => ['0range', 'RG-03'];
        yield 'arobase en toutes lettres' => ['marie arobase', 'RG-03'];
        yield 'http' => ['http site', 'RG-04'];
        yield 'w w w' => ['w-w-w truc', 'RG-04'];
        yield 'point com en toutes lettres' => ['marie point com', 'RG-04'];
        yield 'insulte en majuscules avec accent' => ['CRÉTIN', 'RG-05'];
        yield 'insulte longue cachée' => ['c-o-n-n-a-r-d', 'RG-05'];
        yield 'insulte avec chiffres look-alike' => ['cr3tin', 'RG-05'];
        yield 'chiffres séparés' => ['0 6 1 2 3', 'RG-01'];
        yield 'point' => ['marie.dupont', 'RG-06'];
        yield 'symbole euro' => ['10 €', 'RG-09'];
        yield 'mot euros' => ['contre 10 euros', 'RG-09'];
        yield 'prix' => ['petit prix', 'RG-09'];
        yield 'payer' => ['je peux payer', 'RG-09'];
    }

    #[DataProvider('contournements')]
    public function testContournementsRefuses(string $texte, string $regle): void
    {
        self::assertSame($regle, $this->filter->regleEnfreinte($texte));
    }

    /** Textes ordinaires qui ne doivent pas être bloqués (pas de faux positifs). */
    public static function textesAcceptes(): iterable
    {
        yield [null];
        yield [''];
        yield ['le samedi matin'];
        yield ['taille 4 ans'];
        yield ['lave-linge'];
        yield ['remplacer un joint'];
        yield ['déplacer une armoire'];
        yield ['aide pour la CAF'];
        yield ['en semaine après 17h'];
        yield ['L\'après-midi'];
        yield ['économiser du temps'];
        yield ['conseils pour semis'];
        yield [str_repeat('a', 30)];
    }

    #[DataProvider('textesAcceptes')]
    public function testTextesOrdinairesAcceptes(?string $texte): void
    {
        self::assertNull($this->filter->regleEnfreinte($texte), (string) $texte);
    }

    public function testNormalisation(): void
    {
        self::assertSame('orangeesai', ComplementFilter::normaliser('0rângé - E_5.4 I'));
    }
}
