<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

/**
 * Notifications library.
 *
 * Handles the notifications related functionality.
 *
 * @package Libraries
 */
class Notifications
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Notifications constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('settings_model');

        $this->CI->load->library('email_messages');
        $this->CI->load->library('ics_file');
        $this->CI->load->library('timezones');
    }

    /**
     * Switch the active language for the notification currently being built.
     *
     * LNU: Language Replacements (README.md #17) - apply_language_replacements() is otherwise only called once,
     * from EA_Controller::configure_language(), for the requesting user's own session language. Every switch to
     * a recipient's own language here (customer, provider, admin, secretary can each differ) bypassed it
     * entirely, so "~word~" markers were left unsubstituted in every notification email.
     *
     * @param string $language
     */
    private function set_language(string $language): void
    {
        config(['language' => $language]);

        $this->CI->lang->load('translations');

        apply_language_replacements($this->CI->lang->language);
    }

    /**
     * Send the required notifications, related to an appointment creation/modification.
     *
     * @param array $appointment Appointment data.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param array $settings Required settings.
     * @param bool|false $manage_mode Manage mode.
     */
    public function notify_appointment_saved(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        bool $manage_mode = false,
    ): void {
        try {
            $current_language = config('language');

            $customer_link = site_url('booking/reschedule/' . $appointment['hash']);

            $provider_link = site_url('calendar/reschedule/' . $appointment['hash']);

            $ics_stream = $this->CI->ics_file->get_stream($appointment, $service, $provider, $customer);

            // Notify customer.
            $send_customer =
                !empty($customer['email']) && filter_var(setting('customer_notifications'), FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === true) {
                $this->set_language($customer['language']);
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_booked');
                $message = $manage_mode ? '' : lang('thank_you_for_appointment');

                // LNU: Zoom Meeting Links (README.md #14) - the customer must never receive the host/start
                // link, which lets whoever holds it start and control the meeting as host. The join link
                // (meeting_link) is unaffected and stays visible as usual.
                $customer_appointment = $appointment;
                unset($customer_appointment['zoom_start_link']);

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $customer_appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $subject,
                        $message,
                        $customer_link,
                        $customer['email'],
                        $ics_stream,
                        $customer['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to customer', $appointment['id'] ?? null);
                }
            }

            // Notify provider.
            $send_provider = filter_var(
                $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($send_provider === true) {
                $this->set_language($provider['language']);
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                // LNU: Reply-To Customer for Provider Email - lets the provider reply directly to the customer.
                $reply_to_customer = filter_var(
                    setting('reply_to_customer_for_provider_email'),
                    FILTER_VALIDATE_BOOLEAN,
                );

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $subject,
                        $message,
                        $provider_link,
                        $provider['email'],
                        $ics_stream,
                        $provider['timezone'],
                        $reply_to_customer,
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to provider', $appointment['id'] ?? null);
                }
            }

            // Notify admins.
            $admins = $this->CI->admins_model->get();

            foreach ($admins as $admin) {
                if ($admin['settings']['notifications'] === '0') {
                    continue;
                }

                $this->set_language($admin['language']);
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $subject,
                        $message,
                        $provider_link,
                        $admin['email'],
                        $ics_stream,
                        $admin['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to admin', $appointment['id'] ?? null);
                }
            }

            // Notify secretaries.
            $secretaries = $this->CI->secretaries_model->get();

            foreach ($secretaries as $secretary) {
                if ($secretary['settings']['notifications'] === '0') {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers'])) {
                    continue;
                }

                $this->set_language($secretary['language']);
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $subject,
                        $message,
                        $provider_link,
                        $secretary['email'],
                        $ics_stream,
                        $secretary['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to secretary', $appointment['id'] ?? null);
                }
            }
        } catch (Throwable $e) {
            $this->log_exception($e, 'appointment-saved (general exception)', $appointment['id'] ?? null);
        } finally {
            $this->set_language($current_language ?? 'english');
        }
    }

    /**
     * Send the required notifications, related to an appointment removal.
     *
     * @param array $appointment Appointment data.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param array $settings Required settings.
     */
    public function notify_appointment_deleted(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        string $cancellation_reason = '',
    ): void {
        try {
            $current_language = config('language');

            // Notify provider.
            $send_provider = filter_var(
                $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($send_provider === true) {
                $this->set_language($provider['language']);

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $provider['email'],
                        $cancellation_reason,
                        $provider['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to provider', $appointment['id'] ?? null);
                }
            }

            // Notify customer.
            $send_customer =
                !empty($customer['email']) && filter_var(setting('customer_notifications'), FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === true) {
                $this->set_language($customer['language']);

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $customer['email'],
                        $cancellation_reason,
                        $customer['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to customer', $appointment['id'] ?? null);
                }
            }

            // Notify admins.
            $admins = $this->CI->admins_model->get();

            foreach ($admins as $admin) {
                if ($admin['settings']['notifications'] === '0') {
                    continue;
                }

                $this->set_language($admin['language']);

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $admin['email'],
                        $cancellation_reason,
                        $admin['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to admin', $appointment['id'] ?? null);
                }
            }

            // Notify secretaries.
            $secretaries = $this->CI->secretaries_model->get();

            foreach ($secretaries as $secretary) {
                if ($secretary['settings']['notifications'] === '0') {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers'])) {
                    continue;
                }

                $this->set_language($secretary['language']);

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $secretary['email'],
                        $cancellation_reason,
                        $secretary['timezone'],
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to secretary', $appointment['id'] ?? null);
                }
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Notifications - Could not email cancellation details of appointment (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        } finally {
            $this->set_language($current_language ?? 'english');
        }
    }

    private function log_exception(Throwable $e, string $message, ?int $appointment_id): void
    {
        log_message(
            'error',
            'Notifications - Could not email ' . $message . ' (' . ($appointment_id ?? '-') . ') : ' . $e->getMessage(),
        );
        log_message('error', $e->getTraceAsString());
    }
}
