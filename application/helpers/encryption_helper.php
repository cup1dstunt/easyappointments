<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.6.0
 * ---------------------------------------------------------------------------- */

/**
 * LNU: shared encryption helper for third-party API credentials.
 *
 * Small reusable wrapper around CodeIgniter's Encryption library (config's "encryption_key"), for credentials
 * the app must recover in plaintext to reuse - e.g. a Zoom client secret or an OIDC client secret - unlike
 * user passwords, which are hashed and never need to be recovered. Not tied to any single feature; any
 * controller/library that stores this kind of secret should encrypt it on write with encrypt_secret() and
 * decrypt it with decrypt_secret() right before use.
 */

if (!function_exists('encrypt_secret')) {
    /**
     * Encrypt a secret value for storage.
     *
     * @param string $value Plaintext value to encrypt. Empty strings are returned unchanged.
     *
     * @return string Returns the encrypted value, or an empty string if $value was empty.
     */
    function encrypt_secret(string $value): string
    {
        if ($value === '') {
            return '';
        }

        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $CI->load->library('encryption');

        return $CI->encryption->encrypt($value);
    }
}

if (!function_exists('decrypt_secret')) {
    /**
     * Decrypt a value previously encrypted with encrypt_secret().
     *
     * Defensive: if decryption fails, the original value is returned unchanged rather than throwing or silently
     * returning nothing - this covers a value that was stored as plain text before encryption was introduced.
     *
     * @param string $value Encrypted value to decrypt. Empty strings are returned unchanged.
     *
     * @return string Returns the decrypted value, or the original value if it was empty or not decryptable.
     */
    function decrypt_secret(string $value): string
    {
        if ($value === '') {
            return '';
        }

        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $CI->load->library('encryption');

        $decrypted = $CI->encryption->decrypt($value);

        return $decrypted !== false ? $decrypted : $value;
    }
}
