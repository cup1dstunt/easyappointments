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
 * Booking_login controller.
 *
 * The two HTTP entry points for the booking wizard's login flow. Both are thin dispatchers to
 * Booking_login_client - the actual gating decision (whether to redirect a customer here in the first place)
 * lives in Booking::index(), which uses the same library.
 *
 * @package Controllers
 */
class Booking_login extends EA_Controller
{
    /**
     * Booking_login constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('booking_login_client');
    }

    /**
     * Handle the IdP's redirect back after a login attempt.
     */
    public function oidc_callback(): void
    {
        method('get');

        $this->booking_login_client->handle_callback('oidc');
    }

    /**
     * Keep the login session alive during a long booking wizard session, without blocking the customer's
     * navigation on it - called periodically by the booking wizard while a login is active.
     */
    public function silent_refresh(): void
    {
        method('get');

        $still_authenticated = $this->booking_login_client->refresh_if_authenticated();

        json_response(['auth_required' => !$still_authenticated]);
    }

    /**
     * The booking wizard's own detection of a dead session (booking.js's periodic silent_refresh ping, or
     * registerAppointment() finding out too late) navigates here instead of showing an in-place popup, so that
     * by the time the customer actually reads "your session has expired", it's already true - not a promise
     * about to be fulfilled once they click something. Mirrors Booking_confirmation::of()'s pattern: the
     * security-critical action (sso_logout()) happens first, silently, via a self-redirect through the IdP and
     * back; only once that's genuinely done does this render the message.
     *
     * Note: enforce_gate() (Oidc_client) *also* triggers a real IdP logout on its own, for every path that
     * reaches the booking page with a dead session (a plain reload, a closed/reopened tab, a direct URL visit).
     * This page's own sso_logout() call isn't redundant with that - it's what makes the wording here accurate
     * at the moment it's shown, rather than relying on the *next* page load to have quietly done it already.
     *
     * @param string|null $reason Distinguishes what the customer is about to lose, set by whichever caller
     *                            detected the dead session - "booking_not_saved" (registerAppointment() found
     *                            out only once submitting) gets its own, more specific wording than the default
     *                            (an idle timeout with nothing actually submitted yet).
     */
    public function session_expired(): void
    {
        method('get');

        // LNU: preserve "reason"/"redirect" across the sso_logout() self-redirect round trip - current_url()
        // never includes the query string in this CodeIgniter version, so without this, the second pass
        // through this action (once the IdP redirects back) would lose which message to show and where the
        // "Log In Again" link should ultimately point.
        $query_params = $this->input->get();
        $self_url = current_url() . (!empty($query_params) ? '?' . http_build_query($query_params) : '');

        if (setting('oidc_booking_logout_after_register')) {
            $this->booking_login_client->sso_logout($self_url); // Never returns if there's something to sign out from.
        }

        $reason = $this->input->get('reason');

        html_vars([
            'show_message' => true,
            'page_title' => lang('page_title') . ' ' . e(lang(setting('company_name'))),
            'message_title' => lang('session_expired'),
            'message_text' => $reason === 'booking_not_saved'
                ? lang('session_expired_booking_not_saved_message')
                : lang('session_expired_message'),
            'message_icon_class' => 'fa-hourglass-end',
            'message_icon_color' => 'warning',
            'google_analytics_code' => setting('google_analytics_code'),
            'matomo_analytics_url' => setting('matomo_analytics_url'),
            'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
            'display_login_button' => false,
            // Where "Log In Again" sends the customer back to - the original booking page (with its
            // service/provider params intact), not this session_expired page itself.
            'retry_url' => $this->safe_redirect_target((string) $this->input->get('redirect')),
        ]);

        $this->load->view('pages/booking_message');
    }

    /**
     * Only ever redirect back to this same installation - "redirect" arrives as a public GET param (this
     * endpoint has to be reachable un-authenticated, since it runs right as a session is being cleared), so it
     * can't be trusted blindly; a URL pointing at a different host would otherwise turn "Log In Again" into an
     * open redirect. Expects a fully-qualified URL (not a bare relative path) - CodeIgniter's redirect() helper
     * only skips re-wrapping a target through site_url() for absolute URLs, so a relative path would otherwise
     * end up with the site's base URL prefixed onto it twice.
     */
    private function safe_redirect_target(string $url): string
    {
        $target_host = parse_url($url, PHP_URL_HOST);
        $own_host = parse_url(base_url(), PHP_URL_HOST);

        if (is_string($target_host) && $target_host !== '' && $target_host === $own_host) {
            return $url;
        }

        return site_url('booking');
    }
}
