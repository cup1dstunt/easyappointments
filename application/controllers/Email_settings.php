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
 * Email_settings controller.
 *
 * LNU: handles the SMTP / email delivery settings, so the mail server can be configured without editing files.
 *
 * @package Controllers
 */
class Email_settings extends EA_Controller
{
    /**
     * Setting names that are saved from the page (the password is handled separately).
     */
    private const FIELDS = [
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_crypto',
        'smtp_auth',
        'smtp_user',
        'smtp_from_name',
        'smtp_from_address',
    ];

    /**
     * Email_settings constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('email_messages');

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

        $email_settings = [];

        foreach (self::FIELDS as $name) {
            $email_settings[] = ['name' => $name, 'value' => setting($name)];
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => session('role_slug'),
            'email_settings' => $email_settings,
            'has_smtp_password' => (bool) setting('smtp_pass'),
            'default_test_recipient' => setting('company_email'),
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/email_settings');
    }

    /**
     * Save the email settings.
     */
    public function save(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            check('email_settings', 'array|null');

            foreach (request('email_settings', []) as $email_setting) {
                if (in_array($email_setting['name'], self::FIELDS, true)) {
                    setting([$email_setting['name'] => trim((string) $email_setting['value'])]);
                }
            }

            // An empty password field means "keep the stored password".
            $password = request('smtp_pass');

            if (!empty($password)) {
                setting(['smtp_pass' => $password]);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Send a test email using the saved settings and report the outcome.
     */
    public function send_test(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $recipient = (string) request('recipient');

            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException(lang('invalid_email'));
            }

            $this->email_messages->send_test($recipient);

            response(['success' => true]);
        } catch (Throwable $e) {
            response(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
