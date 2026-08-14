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
 * LNU: OIDC Booking Login (README.md #15).
 *
 * Seeds the account-wide OIDC settings (configurable via Settings > Integrations > OIDC), including
 * "oidc_enabled_booking" - whether OIDC currently gates the public booking wizard specifically. No new
 * database columns are needed - the feature doesn't add any persistent per-customer/per-appointment state
 * beyond what already exists.
 */
class Migration_Oidc_booking_login extends EA_Migration
{
    private const SETTINGS_FIELD_DEFAULTS = [
        // Settings for enabling OIDC login
        'oidc_enabled_booking' => '0',
        // Common OIDC settings
        'oidc_client_id' => '',
        'oidc_client_secret' => '',
        'oidc_idp_url' => '',
        'oidc_booking_user_param_restrictions' => '',
        'oidc_booking_user_param_disallowed_title' => 'default_oidc_booking_user_param_disallowed_title',
        'oidc_booking_user_param_disallowed_message' => 'default_oidc_booking_user_param_disallowed_message',
        'oidc_booking_logout_after_register' => '0',
    ];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        foreach (self::SETTINGS_FIELD_DEFAULTS as $field_name => $default_value) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->insert('settings', [
                    'name' => $field_name,
                    'value' => config($field_name, $default_value),
                ]);

                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Downgrade method.
     *
     * @return bool True if any change was applied, false if everything was already reverted.
     */
    public function down(): bool
    {
        $changed = false;

        foreach (self::SETTINGS_FIELD_DEFAULTS as $field_name => $default_value) {
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->delete('settings', ['name' => $field_name]);

                $changed = true;
            }
        }

        return $changed;
    }
}
