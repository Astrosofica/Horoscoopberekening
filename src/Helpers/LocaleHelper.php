<?php

namespace Astro\Helpers;

class LocaleHelper
{
    const SUPPORTED_LOCALES = ['nl_NL', 'en_GB', 'en_US'];
    const DEFAULT_LOCALE = 'nl_NL';

    /**
     * Detecteer de best passende locale uit de browser Accept-Language header.
     *
     * @return string Locale code (bijv. 'nl_NL', 'en_GB', 'en_US')
     */
    public static function detectFromBrowser(): string
    {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

        if (empty($acceptLanguage)) {
            return self::DEFAULT_LOCALE;
        }

        $languages = self::parseAcceptLanguage($acceptLanguage);

        foreach ($languages as $lang) {
            $matched = self::matchLocale($lang);
            if ($matched) {
                return $matched;
            }
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * Parse de Accept-Language header naar een gesorteerde lijst van talen.
     *
     * @param string $header Raw Accept-Language header value
     * @return array<string> Gesorteerd op quality (hoogste eerst)
     */
    private static function parseAcceptLanguage(string $header): array
    {
        $languages = [];

        foreach (explode(',', $header) as $part) {
            $parts = explode(';q=', trim($part));
            $lang = strtolower(trim($parts[0]));
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;

            if ($lang !== '' && $lang !== '*') {
                $languages[] = ['lang' => $lang, 'quality' => $quality];
            }
        }

        usort($languages, fn($a, $b) => $b['quality'] <=> $a['quality']);

        return array_map(fn($item) => $item['lang'], $languages);
    }

    /**
     * Match een browser taal tegen de ondersteunde locales.
     *
     * @param string $browserLang Browser taal (bijv. 'nl', 'en-gb', 'en-us')
     * @return string|null Matched locale of null
     */
    private static function matchLocale(string $browserLang): ?string
    {
        $normalized = str_replace('-', '_', $browserLang);

        // Exacte match (case-insensitive, bijv. 'nl_nl' → 'nl_NL')
        foreach (self::SUPPORTED_LOCALES as $supported) {
            if (strcasecmp($supported, $normalized) === 0) {
                return $supported;
            }
        }

        // Prefix match (bijv. 'nl' → 'nl_NL', 'en' → 'en_GB')
        $prefix = substr($normalized, 0, 2);
        $prefixMap = [
            'nl' => 'nl_NL',
            'en' => 'en_GB',  // Default Engels = British
        ];

        foreach ($prefixMap as $code => $locale) {
            if ($prefix === $code && in_array($locale, self::SUPPORTED_LOCALES)) {
                return $locale;
            }
        }

        return null;
    }
}
