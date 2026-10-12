<?php

namespace App\Service;

use App\Repository\MotInterditRepository;

/**
 * Filtre du champ « complément » (section 5 du cahier des charges, RG-01 à RG-07 et RG-09).
 *
 * Deux lectures du texte sont contrôlées :
 *  - les « mots » du texte brut (découpé sur tout ce qui n'est pas une lettre),
 *  - le texte normalisé (minuscules, sans accents, sans espaces/points/tirets/underscores,
 *    chiffres « look-alike » convertis), qui déjoue les contournements du type « g m a i l » ou « 0range ».
 *
 * Sur le texte normalisé, on ne cherche que des termes longs ou sans ambiguïté, pour éviter
 * les faux positifs (« av » dans « lave-linge », « place » dans « remplacer »…).
 */
class ComplementFilter
{
    public const LONGUEUR_MAX = 30;
    public const MESSAGE = 'Ce texte contient des éléments non autorisés.';

    private const LOOK_ALIKE = ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's'];

    /** RG-02 : mots de voirie (comparés mot à mot). */
    private const VOIRIE = ['rue', 'avenue', 'av', 'bd', 'boulevard', 'chemin', 'allee', 'impasse', 'place', 'route', 'lieudit'];
    /** RG-02 : sous-chaînes cherchées dans le texte normalisé (sans faux positif courant). */
    private const VOIRIE_NORMALISE = ['avenue', 'boulevard', 'impasse', 'lieudit'];

    /** RG-03 */
    private const EMAIL = ['arobase', 'gmail', 'hotmail', 'orange', 'free'];
    /** RG-04 */
    private const WEB = ['http', 'www'];
    /** RG-09 : l'entraide est gratuite. */
    private const ARGENT = ['euro', 'euros', 'prix', 'payer'];

    /** @var string[]|null */
    private ?array $insultes = null;

    public function __construct(private readonly MotInterditRepository $motInterditRepository)
    {
    }

    public function estAutorise(?string $texte): bool
    {
        return null === $this->regleEnfreinte($texte);
    }

    /**
     * Renvoie le code de la première règle enfreinte (ex. « RG-02 »), ou null si le texte est accepté.
     * Le code sert aux tests et aux journaux : il n'est jamais montré à l'habitant.
     */
    public function regleEnfreinte(?string $texte): ?string
    {
        if (null === $texte || '' === trim($texte)) {
            return null;
        }

        // RG-07 : longueur
        if (mb_strlen($texte) > self::LONGUEUR_MAX) {
            return 'RG-07';
        }

        // RG-01 : plus de 4 chiffres au total
        if (preg_match_all('/\d/u', $texte) > 4) {
            return 'RG-01';
        }

        // RG-03 / RG-04 / RG-09 : caractères repérables directement dans le texte brut
        if (str_contains($texte, '@')) {
            return 'RG-03';
        }
        if (preg_match('/\.\s*(fr|com)\b/iu', $texte)) {
            return 'RG-04';
        }
        if (str_contains($texte, '€')) {
            return 'RG-09';
        }

        // RG-06 : lettres, chiffres, espaces, apostrophe et tiret uniquement
        if (!preg_match("/^[\\p{L}\\p{N} '’-]*$/u", $texte)) {
            return 'RG-06';
        }

        $mots = $this->mots($texte);
        $normalise = self::normaliser($texte);

        if ($this->contientMot($mots, self::VOIRIE) || $this->contientSousChaine($normalise, self::VOIRIE_NORMALISE)) {
            return 'RG-02';
        }
        if ($this->contientMot($mots, self::EMAIL) || $this->contientSousChaine($normalise, self::EMAIL)) {
            return 'RG-03';
        }
        if ($this->contientMot($mots, self::WEB) || $this->contientSousChaine($normalise, [...self::WEB, 'pointfr', 'pointcom', 'dotcom'])) {
            return 'RG-04';
        }
        if ($this->contientMot($mots, self::ARGENT) || $this->contientSousChaine($normalise, ['prix', 'payer'])) {
            return 'RG-09';
        }

        $insultes = $this->insultes();
        $insultesLongues = array_filter($insultes, static fn (string $m) => mb_strlen($m) >= 5);
        if ($this->contientMot($mots, $insultes) || $this->contientSousChaine($normalise, $insultesLongues)) {
            return 'RG-05';
        }

        return null;
    }

    /**
     * Normalisation décrite en section 5 : minuscules, accents retirés, espaces/points/tirets/underscores
     * retirés, chiffres « look-alike » convertis.
     */
    public static function normaliser(string $texte): string
    {
        $texte = self::sansAccents(mb_strtolower($texte));
        $texte = preg_replace('/[\s.\-_\'’]+/u', '', $texte) ?? '';

        return strtr($texte, self::LOOK_ALIKE);
    }

    public static function sansAccents(string $texte): string
    {
        $translit = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');

        return $translit ? $translit->transliterate($texte) : $texte;
    }

    /** @return string[] mots du texte, en minuscules sans accents, chiffres look-alike convertis */
    private function mots(string $texte): array
    {
        $texte = self::sansAccents(mb_strtolower($texte));
        $mots = preg_split('/[^\p{L}\p{N}]+/u', $texte, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        $resultat = [];
        foreach ($mots as $mot) {
            $resultat[] = $mot;
            $resultat[] = strtr($mot, self::LOOK_ALIKE);
        }

        return array_unique($resultat);
    }

    private function contientMot(array $mots, array $interdits): bool
    {
        $interdits = array_map(fn (string $m) => self::sansAccents(mb_strtolower($m)), $interdits);

        return [] !== array_intersect($mots, $interdits);
    }

    private function contientSousChaine(string $normalise, array $interdits): bool
    {
        foreach ($interdits as $interdit) {
            $interdit = self::normaliser($interdit);
            if ('' !== $interdit && str_contains($normalise, $interdit)) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] RG-05 : liste gérée par l'administrateur association */
    private function insultes(): array
    {
        return $this->insultes ??= $this->motInterditRepository->findMotsActifs();
    }
}
