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
