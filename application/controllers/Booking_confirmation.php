<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking confirmation controller.
 *
 * Handles the booking confirmation related operations.
 *
 * @package Controllers
 */
class Booking_confirmation extends EA_Controller
{
    /**
     * Booking_confirmation constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');

        $this->load->library('google_sync');
        $this->load->library('booking_login_client');
    }

    /**
     * Display the appointment registration success page.
     *
     * @throws Exception
     */
    public function of(): void
    {
        // LNU: OIDC Booking Login (README.md #15) - "SSO logout after booking and session expiry" (setting
        // "oidc_booking_logout_after_register"). This is the first full page load after a successful booking
        // (booking.js navigates here via window.location.href, not another AJAX call), so it's the first point
        // where a real redirect-based IdP logout can happen. sso_logout() never returns if it does redirect;
        // the IdP sends the browser straight back to this exact URL once logout completes, at which point
        // there's no id_token left cached (cleared before the redirect), so this falls through to rendering
        // normally below - no loop. Safe to call unconditionally whenever the setting is on - sso_logout()
        // itself is a no-op (no redirect at all) if there's nothing cached to sign out from.
        if (setting('oidc_booking_logout_after_register')) {
            $this->booking_login_client->sso_logout(current_url());
        }

        $appointment_hash = $this->uri->segment(3);

        $occurrences = $this->appointments_model->get(['hash' => $appointment_hash]);

        if (empty($occurrences)) {
            redirect('appointments'); // The appointment does not exist.

            return;
        }

        $appointment = $occurrences[0];

        $add_to_google_url = $this->google_sync->get_add_to_google_url($appointment['id']);

        html_vars([
            'page_title' => lang('success'),
            'company_color' => setting('company_color'),
            'google_analytics_code' => setting('google_analytics_code'),
            'matomo_analytics_url' => setting('matomo_analytics_url'),
            'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
            'add_to_google_url' => $add_to_google_url,
            'display_add_to_google_calendar' => setting('display_add_to_google_calendar', '1'),
        ]);

        $this->load->view('pages/booking_confirmation');
    }
}
