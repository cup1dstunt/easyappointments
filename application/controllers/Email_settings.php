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
 * LNU: SMTP settings in the admin GUI - handles the settings page where the mail server (SMTP) and the sender of
 * all outgoing emails are configured.
 *
 * @package Controllers
 */
class Email_settings extends EA_Controller
{
    /**
     * Settings that can be edited on the page (without the "mail_" prefix).
     */
    public const FIELDS = [
        'protocol',
        'mailtype',
        'smtp_host',
        'smtp_port',
        'smtp_crypto',
        'smtp_auth',
        'smtp_user',
        'smtp_pass',
        'from_name',
        'from_address',
        'reply_to',
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

        foreach (self::FIELDS as $field) {
            // The password never leaves the server, the page only learns whether one is stored.
            $value = $field === 'smtp_pass' ? '' : setting('mail_' . $field, '');

            $email_settings[] = ['name' => 'mail_' . $field, 'value' => $value];
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => session('role_slug'),
            'email_settings' => $email_settings,
            'smtp_pass_is_set' => (string) setting('mail_smtp_pass', '') !== '',
            'test_recipient' => setting('company_email', ''),
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

            $allowed = array_map(static fn(string $field): string => 'mail_' . $field, self::FIELDS);

            foreach (request('email_settings', []) as $email_setting) {
                if (!in_array($email_setting['name'], $allowed, true)) {
                    continue;
                }

                $value = trim((string) $email_setting['value']);

                // An empty password field keeps the stored password, "clear_smtp_pass" removes it.
                if ($email_setting['name'] === 'mail_smtp_pass' && $value === '') {
                    if (!request('clear_smtp_pass')) {
                        continue;
                    }
                }

                setting([$email_setting['name'] => $value]);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Send a test email using the saved settings.
     */
    public function send_test(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Forbidden');
            }

            $recipient = trim((string) request('recipient'));

            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException(lang('invalid_email'));
            }

            try {
                $this->email_messages->send_test($recipient);
            } catch (Throwable $e) {
                // Show the real SMTP error to the admin, json_exception() would redact e.g. connection errors.
                json_response(['success' => false, 'message' => $e->getMessage()]);

                return;
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
