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
 * LNU: Zoom Meeting Links (README.md #14).
 *
 * Zoom_settings controller.
 *
 * Handles the account-wide Zoom API credentials. Whether a given provider actually gets Zoom links is a
 * separate, per-provider opt-in (Users > Providers > "create_zoom_links") - see Zoom_client.
 *
 * @package Controllers
 */
class Zoom_settings extends EA_Controller
{
    /**
     * Zoom_settings constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->model('providers_model');

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

        $zoom_settings = [
            [
                'name' => 'zoom_enabled',
                'value' => setting('zoom_enabled', '0'),
            ],
            [
                'name' => 'zoom_client_id',
                'value' => setting('zoom_client_id', ''),
            ],
            [
                'name' => 'zoom_client_secret',
                'value' => setting('zoom_client_secret', ''),
            ],
            [
                'name' => 'zoom_account_id',
                'value' => setting('zoom_account_id', ''),
            ],
        ];

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'zoom_settings' => filter_sensitive_settings($zoom_settings),
            'zoom_configured' => $this->is_zoom_configured(),
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/zoom_settings');
    }

    /**
     * Save the Zoom settings.
     */
    public function save(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            check('zoom_settings', 'array|null');

            $zoom_settings = request('zoom_settings', []);

            foreach ($zoom_settings as $zoom_setting) {
                if ($zoom_setting['name'] === 'zoom_client_secret') {
                    // The field is never re-populated with the real value for display (filter_sensitive_settings()
                    // strips it before it reaches the browser), so a blank submission means "leave it unchanged",
                    // not "clear it" - otherwise saving any other field would silently wipe the stored secret.
                    if ($zoom_setting['value'] === '') {
                        continue;
                    }

                    $zoom_setting['value'] = encrypt_secret($zoom_setting['value']);
                }

                setting([
                    $zoom_setting['name'] => $zoom_setting['value'],
                ]);
            }

            json_response([
                'zoom_configured' => $this->is_zoom_configured(),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Enable the "Create Zoom Links" opt-in for every provider at once.
     */
    public function apply_to_all_providers(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            if (!$this->is_zoom_configured()) {
                throw new RuntimeException('The Zoom integration must be active and fully configured first.');
            }

            $this->providers_model->set_create_zoom_links_for_all_providers(true);

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Whether the integration is active and all credential settings have a value.
     */
    private function is_zoom_configured(): bool
    {
        return setting('zoom_enabled') === '1' &&
            setting('zoom_client_id') &&
            setting('zoom_client_secret') &&
            setting('zoom_account_id');
    }
}
