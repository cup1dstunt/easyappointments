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
 * Email_templates controller.
 *
 * LNU: Editable confirmation email - handles the settings page where the subject and text of the customer
 * confirmation email can be edited.
 *
 * @package Controllers
 */
class Email_templates extends EA_Controller
{
    /**
     * Email_templates constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->library('confirmation_email');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            show_error('Forbidden', 403);
        }
    }

    /**
     * Render the settings page.
     */
    public function index(): void
    {
        $user_id = session('user_id');

        script_vars([
            'user_id' => $user_id,
            'role_slug' => session('role_slug'),
            'email_template_settings' => [
                [
                    'name' => 'email_confirmation_subject',
                    'value' => setting('email_confirmation_subject', ''),
                ],
                [
                    'name' => 'email_confirmation_body',
                    'value' => setting('email_confirmation_body', ''),
                ],
            ],
            'email_template_variables' => Confirmation_email::VARIABLES,
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/email_templates');
    }

    /**
     * Save the email template settings.
     */
    public function save(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            check('email_template_settings', 'array|null');

            $allowed = ['email_confirmation_subject', 'email_confirmation_body'];

            foreach (request('email_template_settings', []) as $email_template_setting) {
                if (!in_array($email_template_setting['name'], $allowed, true)) {
                    continue;
                }

                setting([
                    $email_template_setting['name'] => trim((string) $email_template_setting['value']),
                ]);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
