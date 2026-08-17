<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.1.0
 * ---------------------------------------------------------------------------- */

if (!function_exists('lang')) {
    /**
     * Lang
     *
     * Fetches a language variable and optionally outputs a form label
     *
     * @param string|null $line The language line. LNU: accepts null (treated as '') even though the type hint
     *                          reads as optional-nullable rather than required - lang() is routinely called as
     *                          lang(setting(...)) to resolve settings that may be a translation key or literal
     *                          text (see the Settings Text Translatability call sites across the app), and a
     *                          setting that's legitimately unset/missing yields null, not an empty string. This
     *                          used to be a hard TypeError instead - found live on a production install where
     *                          "legal_notice_url" had never been seeded, taking down the entire booking page.
     * @param string $for The "for" value (id of the form element).
     * @param array $attributes Any additional HTML attributes.
     *
     * @return string
     */
    function lang(?string $line, string $for = '', array $attributes = []): string
    {
        if ($line === null) {
            return '';
        }

        /** @var EA_Controller $CI */
        $CI = get_instance();

        // LNU: don't log an error for every miss - lang() is also used to resolve admin-set settings values
        // (company name, legal content, etc.) that are usually literal text rather than a translation key,
        // which would otherwise log an error on every single one. Matches the same false already passed
        // explicitly at the other two lang->line() call sites (booking_type_step.php, booking_confirmation.php).
        $result = $CI->lang->line($line, false);

        if ($for !== '') {
            $result = '<label for="' . $for . '"' . _stringify_attributes($attributes) . '>' . $result . '</label>';
        }

        return $result ?: $line;
    }
}

if (!function_exists('apply_language_replacements')) {
    /**
     * LNU: Language Replacements (README.md #17).
     *
     * Substitutes configured words wherever they occur as whole words in the currently loaded translation
     * lines (setting "language_replacements" - semicolon-separated entries, each either a "word=replacement"
     * pair or, prefixed with "!", an exception phrase to leave untouched even though it contains a configured
     * word, e.g. "provider=handledare;!identity provider;!internet provider;"). One shared mapping across
     * every language - a configured word is already a specific word in a specific language, e.g. "customer"
     * vs "kund", so a collision between two languages needing different replacements for the same spelling
     * isn't expected in practice.
     *
     * Word-boundary matching matters for two reasons: it's what makes an exception phrase like "!identity
     * provider" work (the phrase is matched and left alone before the bare "provider" rule gets a chance to
     * match inside it), and it's what stops a short configured word from matching inside an unrelated longer
     * word by accident - Swedish "kund" ("customer") must not match inside "kunde" ("could"). The flip side is
     * that a compound word written as a single word, e.g. Swedish "kunduppgifter" ("customer data"), is NOT
     * caught by a "kund" rule and needs its own explicit "kunduppgifter=studentuppgifter" entry.
     *
     * Capitalization follows the matched word, not the configured replacement: matching "Customer" capitalizes
     * whatever "customer" is mapped to, so a single lowercase mapping entry covers both cases.
     *
     * Called once per request, right after $this->lang->load(), so both server-rendered lang() calls and the
     * JS-side lang() (populated from the same array by js_lang_script.php) see already-substituted text - no
     * separate client-side substitution logic needed.
     *
     * @param array $language_lines The currently loaded language array ($this->lang->language), by reference.
     */
    function apply_language_replacements(array &$language_lines): void
    {
        $replacements = [];
        $exception_phrases = [];

        foreach (array_filter(array_map('trim', explode(';', (string) setting('language_replacements', '')))) as $entry) {
            if (str_starts_with($entry, '!')) {
                $phrase = trim(substr($entry, 1));

                if ($phrase !== '') {
                    $exception_phrases[] = $phrase;
                }

                continue;
            }

            if (!str_contains($entry, '=')) {
                continue;
            }

            [$word, $replacement] = explode('=', $entry, 2);

            $word = mb_strtolower(trim($word));

            if ($word !== '') {
                $replacements[$word] = trim($replacement);
            }
        }

        if (!$replacements) {
            return;
        }

        $pattern_parts = [];

        foreach ($exception_phrases as $phrase) {
            $pattern_parts[] = '\b' . preg_quote($phrase, '/') . '\b';
        }

        foreach (array_keys($replacements) as $word) {
            $pattern_parts[] = '\b' . preg_quote($word, '/') . '\b';
        }

        $pattern = '/' . implode('|', $pattern_parts) . '/ui';

        $exception_lookup = array_flip(array_map('mb_strtolower', $exception_phrases));

        foreach ($language_lines as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $language_lines[$key] = preg_replace_callback(
                $pattern,
                static function (array $matches) use ($replacements, $exception_lookup) {
                    $matched = $matches[0];
                    $lower = mb_strtolower($matched);

                    if (isset($exception_lookup[$lower])) {
                        return $matched;
                    }

                    $replacement = $replacements[$lower] ?? $matched;

                    $first_char = mb_substr($matched, 0, 1);

                    if ($first_char === mb_strtoupper($first_char) && $first_char !== mb_strtolower($first_char)) {
                        $replacement = mb_strtoupper(mb_substr($replacement, 0, 1)) . mb_substr($replacement, 1);
                    }

                    return $replacement;
                },
                $value,
            );
        }
    }
}
