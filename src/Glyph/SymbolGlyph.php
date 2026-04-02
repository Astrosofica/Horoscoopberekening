<?php

namespace Tijd\Glyph;

class SymbolGlyph
{
    public const PLANET_SUN         = 33;
    public const PLANET_MOON        = 34;
    public const PLANET_MERCURY     = 35;
    public const PLANET_VENUS       = 36;
    public const PLANET_MARS        = 37;
    public const PLANET_JUPITER     = 38;
    public const PLANET_SATURN      = 39;
    public const PLANET_URANUS      = 40;
    public const PLANET_NEPTUNE     = 41;
    public const PLANET_PLUTO       = 42;
    public const PLANET_NORTH_NODE  = 43;
    public const PLANET_ASCENDANT   = 45;
    public const PLANET_MC          = 46;
    public const PLANET_CHIRON      = 51;
    public const PLANET_PARS_FORTUNA = 124;

    public const SIGN_ARIES         = 80;
    public const SIGN_TAURUS        = 81;
    public const SIGN_GEMINI        = 82;
    public const SIGN_CANCER        = 83;
    public const SIGN_LEO           = 84;
    public const SIGN_VIRGO         = 85;
    public const SIGN_LIBRA         = 86;
    public const SIGN_SCORPIO       = 87;
    public const SIGN_SAGITTARIUS   = 88;
    public const SIGN_CAPRICORN     = 89;
    public const SIGN_AQUARIUS      = 90;
    public const SIGN_PISCES        = 91;

    public const ASPECT_CONJUNCTION = 93;
    public const ASPECT_OPPOSITION  = 94;
    public const ASPECT_TRINE       = 95;
    public const ASPECT_SQUARE      = 96;
    public const ASPECT_SEXTILE     = 97;
    public const ASPECT_SEMISQUARE  = 98;
    public const ASPECT_SESQUIQUADRATE = 99;
    public const ASPECT_INCONJUNCT  = 100;
    public const STATUS_RETROGRADE  = 118;

    private static array $planets = [
        self::PLANET_SUN         => ['name' => 'Zon', 'code' => '!'],
        self::PLANET_MOON        => ['name' => 'Maan', 'code' => '"'],
        self::PLANET_MERCURY     => ['name' => 'Mercurius', 'code' => '#'],
        self::PLANET_VENUS       => ['name' => 'Venus', 'code' => '$'],
        self::PLANET_MARS        => ['name' => 'Mars', 'code' => '%'],
        self::PLANET_JUPITER     => ['name' => 'Jupiter', 'code' => '&'],
        self::PLANET_SATURN      => ['name' => 'Saturnus', 'code' => "'"],
        self::PLANET_URANUS      => ['name' => 'Uranus', 'code' => '('],
        self::PLANET_NEPTUNE     => ['name' => 'Neptunus', 'code' => ')'],
        self::PLANET_PLUTO       => ['name' => 'Pluto', 'code' => '*'],
        self::PLANET_NORTH_NODE  => ['name' => 'Noordknoop', 'code' => '+'],
        self::PLANET_ASCENDANT   => ['name' => 'Ascendant', 'code' => '-'],
        self::PLANET_MC          => ['name' => 'MC', 'code' => '.'],
        self::PLANET_CHIRON      => ['name' => 'Chiron', 'code' => '3'],
        self::PLANET_PARS_FORTUNA => ['name' => 'Pars Fortuna', 'code' => '|'],
    ];

    private static array $signs = [
        self::SIGN_ARIES       => ['name' => 'Ram', 'code' => 'P'],
        self::SIGN_TAURUS      => ['name' => 'Stier', 'code' => 'Q'],
        self::SIGN_GEMINI      => ['name' => 'Tweelingen', 'code' => 'R'],
        self::SIGN_CANCER      => ['name' => 'Kreeft', 'code' => 'S'],
        self::SIGN_LEO         => ['name' => 'Leeuw', 'code' => 'T'],
        self::SIGN_VIRGO       => ['name' => 'Maagd', 'code' => 'U'],
        self::SIGN_LIBRA       => ['name' => 'Weegschaal', 'code' => 'V'],
        self::SIGN_SCORPIO     => ['name' => 'Schorpioen', 'code' => 'W'],
        self::SIGN_SAGITTARIUS => ['name' => 'Boogschutter', 'code' => 'X'],
        self::SIGN_CAPRICORN   => ['name' => 'Steenbok', 'code' => 'Y'],
        self::SIGN_AQUARIUS    => ['name' => 'Waterman', 'code' => 'Z'],
        self::SIGN_PISCES      => ['name' => 'Vissen', 'code' => '['],
    ];

    private static array $aspects = [
        self::ASPECT_CONJUNCTION    => ['name' => 'Conjunctie', 'code' => ']', 'degrees' => 0],
        self::ASPECT_OPPOSITION     => ['name' => 'Oppositie', 'code' => '^', 'degrees' => 180],
        self::ASPECT_TRINE          => ['name' => 'Driehoek', 'code' => '_', 'degrees' => 120],
        self::ASPECT_SQUARE         => ['name' => 'Vierkant', 'code' => '`', 'degrees' => 90],
        self::ASPECT_SEXTILE        => ['name' => 'Sextiel', 'code' => 'a', 'degrees' => 60],
        self::ASPECT_SEMISQUARE     => ['name' => 'Halfvierkant', 'code' => 'b', 'degrees' => 45],
        self::ASPECT_SESQUIQUADRATE => ['name' => 'Anderhalfvierkant', 'code' => 'c', 'degrees' => 135],
        self::ASPECT_INCONJUNCT     => ['name' => 'Inconjunct', 'code' => 'd', 'degrees' => 150],
    ];

    private static array $status = [
        self::STATUS_RETROGRADE => ['name' => 'Retrograde', 'code' => 'v'],
    ];

    public static function getPlanet(int $code): ?array
    {
        return self::$planets[$code] ?? null;
    }

    public static function getSign(int $code): ?array
    {
        return self::$signs[$code] ?? null;
    }

    public static function getAspect(int $code): ?array
    {
        return self::$aspects[$code] ?? null;
    }

    public static function getStatus(int $code): ?array
    {
        return self::$status[$code] ?? null;
    }

    public static function getPlanetByCode(string $char): ?array
    {
        foreach (self::$planets as $code => $data) {
            if ($data['code'] === $char) {
                return ['code' => $code] + $data;
            }
        }
        return null;
    }

    public static function getSignByCode(string $char): ?array
    {
        foreach (self::$signs as $code => $data) {
            if ($data['code'] === $char) {
                return ['code' => $code] + $data;
            }
        }
        return null;
    }

    public static function getAspectByCode(string $char): ?array
    {
        foreach (self::$aspects as $code => $data) {
            if ($data['code'] === $char) {
                return ['code' => $code] + $data;
            }
        }
        return null;
    }

    public static function getPlanetGlyph(int $planetIndex): string
    {
        $mapping = [
            0 => self::PLANET_SUN,
            1 => self::PLANET_MOON,
            2 => self::PLANET_MERCURY,
            3 => self::PLANET_VENUS,
            4 => self::PLANET_MARS,
            5 => self::PLANET_JUPITER,
            6 => self::PLANET_SATURN,
            7 => self::PLANET_URANUS,
            8 => self::PLANET_NEPTUNE,
            9 => self::PLANET_PLUTO,
            10 => self::PLANET_NORTH_NODE,
            11 => self::PLANET_CHIRON,
            12 => self::PLANET_PARS_FORTUNA,
            20 => self::PLANET_ASCENDANT,
            21 => self::PLANET_MC,
        ];

        $code = $mapping[$planetIndex] ?? null;
        if ($code === null || !isset(self::$planets[$code])) {
            return '?';
        }

        return self::$planets[$code]['code'];
    }
    
    public static function getPlanetGlyphByName(string $name): string
    {
        $mapping = [
            'Sun' => self::PLANET_SUN,
            'Moon' => self::PLANET_MOON,
            'Mercury' => self::PLANET_MERCURY,
            'Venus' => self::PLANET_VENUS,
            'Mars' => self::PLANET_MARS,
            'Jupiter' => self::PLANET_JUPITER,
            'Saturn' => self::PLANET_SATURN,
            'Uranus' => self::PLANET_URANUS,
            'Neptune' => self::PLANET_NEPTUNE,
            'Pluto' => self::PLANET_PLUTO,
            'NorthNode' => self::PLANET_NORTH_NODE,
            'Ascendant' => self::PLANET_ASCENDANT,
            'MC' => self::PLANET_MC,
            'Midhemel' => self::PLANET_MC,
            'Chiron' => self::PLANET_CHIRON,
            'ParsFortuna' => self::PLANET_PARS_FORTUNA,
        ];

        $code = $mapping[$name] ?? null;
        if ($code === null || !isset(self::$planets[$code])) {
            return '?';
        }

        return self::$planets[$code]['code'];
    }

    public static function getSignGlyph(float $longitude): string
    {
        $signIndex = (int) floor($longitude / 30);
        $signIndex = max(0, min(11, $signIndex));

        $mapping = [
            0 => self::SIGN_ARIES,
            1 => self::SIGN_TAURUS,
            2 => self::SIGN_GEMINI,
            3 => self::SIGN_CANCER,
            4 => self::SIGN_LEO,
            5 => self::SIGN_VIRGO,
            6 => self::SIGN_LIBRA,
            7 => self::SIGN_SCORPIO,
            8 => self::SIGN_SAGITTARIUS,
            9 => self::SIGN_CAPRICORN,
            10 => self::SIGN_AQUARIUS,
            11 => self::SIGN_PISCES,
        ];

        $code = $mapping[$signIndex];
        return self::$signs[$code]['code'];
    }

    public static function getAspectGlyph(int $degrees): string
    {
        $mapping = [
            0 => self::ASPECT_CONJUNCTION,
            45 => self::ASPECT_SEMISQUARE,
            60 => self::ASPECT_SEXTILE,
            90 => self::ASPECT_SQUARE,
            120 => self::ASPECT_TRINE,
            135 => self::ASPECT_SESQUIQUADRATE,
            150 => self::ASPECT_INCONJUNCT,
            180 => self::ASPECT_OPPOSITION,
        ];

        $code = $mapping[$degrees] ?? null;
        if ($code === null || !isset(self::$aspects[$code])) {
            return '?';
        }

        return self::$aspects[$code]['code'];
    }

    public static function getAllPlanets(): array
    {
        return self::$planets;
    }

    public static function getAllSigns(): array
    {
        return self::$signs;
    }

    public static function getAllAspects(): array
    {
        return self::$aspects;
    }

    public static function getRetrogradeGlyph(): string
    {
        return self::$status[self::STATUS_RETROGRADE]['code'];
    }

    public static function getPlanetGlyphByIndex(int $index): string
    {
        $mapping = [
            0 => self::PLANET_SUN,
            1 => self::PLANET_MOON,
            2 => self::PLANET_MERCURY,
            3 => self::PLANET_VENUS,
            4 => self::PLANET_MARS,
            5 => self::PLANET_JUPITER,
            6 => self::PLANET_SATURN,
            7 => self::PLANET_URANUS,
            8 => self::PLANET_NEPTUNE,
            9 => self::PLANET_PLUTO,
            10 => self::PLANET_NORTH_NODE,
            11 => self::PLANET_ASCENDANT,
            12 => self::PLANET_MC,
            14 => self::PLANET_PARS_FORTUNA,
        ];
        $code = $mapping[$index] ?? null;
        if ($code === null || !isset(self::$planets[$code])) {
            return '?';
        }
        return self::$planets[$code]['code'];
    }

    public static function getSignGlyphByIndex(int $index): string
    {
        $mapping = [
            0 => self::SIGN_ARIES,
            1 => self::SIGN_TAURUS,
            2 => self::SIGN_GEMINI,
            3 => self::SIGN_CANCER,
            4 => self::SIGN_LEO,
            5 => self::SIGN_VIRGO,
            6 => self::SIGN_LIBRA,
            7 => self::SIGN_SCORPIO,
            8 => self::SIGN_SAGITTARIUS,
            9 => self::SIGN_CAPRICORN,
            10 => self::SIGN_AQUARIUS,
            11 => self::SIGN_PISCES,
        ];
        $code = $mapping[$index] ?? null;
        if ($code === null || !isset(self::$signs[$code])) {
            return '?';
        }
        return self::$signs[$code]['code'];
    }

    public static function getGlyphForTarget(int $targetIndex): string
    {
        if ($targetIndex >= 0 && $targetIndex <= 12) {
            return self::getPlanetGlyphByIndex($targetIndex);
        }
        if ($targetIndex >= 20 && $targetIndex <= 31) {
            return self::getSignGlyphByIndex($targetIndex - 20);
        }
        if ($targetIndex >= 40 && $targetIndex <= 51) {
            return 'H' . ($targetIndex - 39);
        }
        if ($targetIndex === 60) {
            return 'R';
        }
        if ($targetIndex === 61) {
            return 'D';
        }
        return '?';
    }

    public static function render(string $text): string
    {
        return '<span class="astro-font">' . htmlspecialchars($text) . '</span>';
    }

    public static function renderPlanet(int $code): string
    {
        $planet = self::getPlanet($code);
        if ($planet === null) {
            return self::render('?');
        }
        return self::render($planet['code']);
    }

    public static function renderSign(int $code): string
    {
        $sign = self::getSign($code);
        if ($sign === null) {
            return self::render('?');
        }
        return self::render($sign['code']);
    }

    public static function renderAspect(int $code): string
    {
        $aspect = self::getAspect($code);
        if ($aspect === null) {
            return self::render('?');
        }
        return self::render($aspect['code']);
    }
}
