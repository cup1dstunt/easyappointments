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
 * Oidc_settings controller.
 *
 * Handles the account-wide OIDC settings (currently, for the booking wizard)
 * See Booking_login for the actual login flow.
 *
 * @package Controllers
 */
class Oidc_settings extends EA_Controller
{
    /**
     * Oidc_settings constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');

        $role_slug = session('role_slug');

        $required_permissions = can('edit', PRIV_SYSTEM_SETTINGS);

        if ($required_permissions === false) {
            show_error('Forbidden', 403);
        }
    }

    /**
     * Render the settings page.
     */
    public function index(): void
    {
        $user_id = session('user_id');

        $role_slug = session('role_slug');

        $oidc_settings = [
            [
                'name' => 'oidc_enabled_booking',
                'value' => setting('oidc_enabled_booking', 0),
            ],
            [
                'name' => 'oidc_client_id',
                'value' => setting('oidc_client_id', ''),
            ],
            [
                'name' => 'oidc_client_secret',
                'value' => setting('oidc_client_secret', ''),
            ],
            [
                'name' => 'oidc_idp_url',
                'value' => setting('oidc_idp_url', ''),
            ],
            [
                'name' => 'oidc_booking_user_param_restrictions',
                'value' => setting('oidc_booking_user_param_restrictions', ''),
            ],
            [
                'name' => 'oidc_booking_user_param_disallowed_title',
                'value' => setting(
                    'oidc_booking_user_param_disallowed_title',
                    'default_oidc_booking_user_param_disallowed_title'),
            ],
            [
                'name' => 'oidc_booking_user_param_disallowed_message',
                'value' => setting(
                    'oidc_booking_user_param_disallowed_message',
                    'default_oidc_booking_user_param_disallowed_message',
                ),
            ],
            [
                'name' => 'oidc_booking_logout_after_register',
                'value' => setting('oidc_booking_logout_after_register', '0'),
            ],
        ];

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'oidc_settings' => filter_sensitive_settings($oidc_settings),
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/oidc_settings');
    }

    /**
     * Save the OIDC settings.
     */
    public function save(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            check('oidc_settings', 'array|null');

            $oidc_settings = request('oidc_settings', []);

            foreach ($oidc_settings as $oidc_setting) {
                if ($oidc_setting['name'] === 'oidc_client_secret') {
                    // The field is never re-populated with the real value for display (filter_sensitive_settings()
                    // strips it before it reaches the browser), so a blank submission means "leave it unchanged",
                    // not "clear it" - otherwise saving any other field would silently wipe the stored secret.
                    if ($oidc_setting['value'] === '') {
                        continue;
                    }

                    $oidc_setting['value'] = encrypt_secret($oidc_setting['value']);
                }

                setting([
                    $oidc_setting['name'] => $oidc_setting['value'],
                ]);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
