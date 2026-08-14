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
 * The booking wizard's own thin policy layer, dispatching on each method's own "{method}_enabled_booking"
 * setting to whichever underlying client that method needs - currently only "oidc_enabled_booking"
 * (Oidc_client, with the callback URL fixed to booking_login/oidc_callback) is supported, but this class - not
 * Booking.php or Booking_login.php - is where a future second method would be added, so those two stay
 * method-agnostic.
 *
 * The only thing genuinely specific to booking is the redirect-target ("go back to a new booking, or back to
 * rescheduling a specific appointment") - everything else, including the reauth-loop breaker, is generic OIDC
 * gating logic and lives in Oidc_client::enforce_gate(). This keeps Booking.php down to one high-level call,
 * and Booking_login.php a thin HTTP dispatcher.
 *
 * @package Libraries
 */
class Booking_login_client
{
    private ?Oidc_client $oidc_client = null;

    /**
     * Whether a booking authentication method is currently active.
     */
    public function is_enabled(): bool
    {
        if (setting('oidc_enabled_booking', 0)) {
            return true;
        }
        return false;
    }

    /**
     * The booking wizard's login gate - resolves the redirect-back target (new booking vs. rescheduling a
     * specific appointment) and delegates the rest to the active method's gate (see Oidc_client::enforce_gate()
     * for what "the rest" covers: the reauth-loop breaker, session/claims validation, and the IdP redirect).
     *
     * @param string|null $reschedule_hash The appointment hash being rescheduled, if any (null for a new
     *                                      booking) - only used to send the browser back to the right place
     *                                      after a successful login.
     *
     * @return array{outcome: string, first_name?: ?string, last_name?: ?string, email?: ?string} Returns
     *         ['outcome' => 'ok', 'first_name' => ..., 'last_name' => ..., 'email' => ...] on success, or
     *         ['outcome' => 'login_server_error'] / ['outcome' => 'disallowed_params'] otherwise - the caller
     *         decides how to present each outcome (this library has no view/message-rendering concerns).
     */
    public function enforce_gate(?string $reschedule_hash): array
    {
        if (!$this->is_enabled()) {
            return ['outcome' => 'ok', 'first_name' => null, 'last_name' => null, 'email' => null];
        }

        // LNU: preserve query params (eg. "service"/"provider" auto-selection, README.md #13's "language"
        // having already been stripped/persisted to session by this point via EA_Controller::configure_language())
        // across the OIDC redirect-to-IdP-and-back round trip, since they're otherwise read purely client-side
        // off the URL by booking.js with no server-side persistence of their own.
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $query_params = $CI->input->get();

        $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';

        $return_url = $reschedule_hash
            ? site_url('booking/reschedule/' . $reschedule_hash) . $query_string
            : site_url('booking') . $query_string;

        $result = ['outcome' => 'ok', 'first_name' => null, 'last_name' => null, 'email' => null];

        if (setting('oidc_enabled_booking', 0)) {
            $result = $this->get_oidc_client()->enforce_gate($return_url);
        }

        if ($result['outcome'] === 'ok' && !$this->validate_params()) {
            $result = ['outcome' => 'disallowed_params'];

            // Log out rather than leaving a disallowed session sitting around - otherwise the customer is
            // stuck seeing this message on every visit until the session naturally expires, with no way to
            // retry with a different (allowed) account.
            if (setting('oidc_enabled_booking', 0)) {
                $this->get_oidc_client()->deauthenticate();
            }
        }

        return $result;
    }

    /**
     * Get an arbitrary claim from the currently active method's cached identity data.
     */
    public function get_user_prop(string $name): ?string
    {
        if (setting('oidc_enabled_booking', 0)) {
            $prop = $this->get_oidc_client()->get_claim($name);
        } else {
            $prop = null;
        }
        return $prop;
    }

    /**
     * All of the currently authenticated user's properties from the active method (e.g. OIDC's claims), keyed
     * by their own IdP-given names - lets a custom field reference an arbitrary one (an "auth-prop" attribute
     * in its own free-form "attributes" setting - see components/custom_fields.php), prefilled/locked
     * client-side (booking.js), without this library, Booking.php, or the custom field's own settings needing
     * to declare it anywhere.
     *
     * @return array<string, mixed> Returns the properties, or [] if no method is active or not authenticated.
     */
    public function get_user_props(): array
    {
        if (setting('oidc_enabled_booking', 0)) {
            $props = $this->get_oidc_client()->get_claims();
        } else {
            $props = [];
        }
        return $props;
    }

    /**
     * Validate the logged-in customer's claims against "oidc_booking_user_param_restrictions", e.g.
     * "avdelning=ub|it;affiliation=student" - all configured rules must pass (AND). An email address in
     * "test_email_addresses" (Settings > Business Logic) bypasses every rule, for QA/testing.
     *
     * @return bool Returns true if every configured restriction passes (or none are configured).
     */
    public function validate_params(): bool
    {
        $email = $this->get_user_prop('email');

        $test_email_addresses = array_filter(array_map('trim', explode(';', (string) config('test_email_addresses', ''))));

        // Case-insensitive - same reasoning as the restriction values below: neither the IdP's email claim nor
        // the admin-entered test_email_addresses setting is guaranteed to be cased consistently.
        if ($email && in_array(mb_strtolower($email), array_map('mb_strtolower', $test_email_addresses), true)) {
            return true;
        }

        if (setting('oidc_enabled_booking', 0)) {
            $restrictions = $this->parse_param_restrictions(
                (string) setting('oidc_booking_user_param_restrictions', ''),
            );
        } else {
            $restrictions = [];
        }

        foreach ($restrictions as $param_name => $allowed_values) {

            if (empty($allowed_values)) {
                continue;
            }

            if (!in_array(mb_strtolower((string) $this->get_user_prop($param_name)), $allowed_values, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse an "oidc_booking_user_param_restrictions" string into [param_name => [allowed_value, ...]].
     *
     * Malformed rules (no "=", or an empty param name) are silently skipped rather than erroring, matching how
     * this app treats other free-text settings that could be hand-edited into an invalid state.
     *
     * @param string $raw Raw setting value, e.g. "avdelning=ub|it;affiliation=student".
     *
     * @return array<string, string[]> Returns the parsed restrictions.
     */
    public function parse_param_restrictions(string $raw): array
    {
        $restrictions = [];

        foreach (array_filter(array_map('trim', explode(';', $raw))) as $rule) {
            if (!str_contains($rule, '=')) {
                continue;
            }

            [$param_name, $values] = explode('=', $rule, 2);

            $param_name = trim($param_name);

            if ($param_name === '') {
                continue;
            }

            // Case-insensitive - the IdP's own claim values aren't reliably cased consistently (e.g. "Student"
            // vs "student" depending on how the underlying directory data was entered), so an admin configuring
            // "student" shouldn't have to guess/match whatever casing happens to come back.
            $restrictions[$param_name] = array_values(
                array_filter(array_map(fn ($value) => mb_strtolower(trim($value)), explode('|', $values))),
            );
        }

        return $restrictions;
    }

    /**
     * The currently authenticated customer's email, without triggering an IdP redirect - safe to call from an
     * AJAX endpoint (Booking::register()), where redirecting to the IdP on a stale/missing session would make
     * no sense (unlike enforce_gate(), which is only meant for a page render).
     *
     * @return string|null Returns the email, or null if no method is active or the session isn't currently
     *                      valid.
     */
    public function get_authenticated_email(): ?string
    {
        if (!$this->is_enabled()) {
            return null;
        }

        $email = null;

        if (setting('oidc_enabled_booking', 0)) {
            $email = $this->get_oidc_client()->validate_session() ? $this->get_oidc_client()->get_claim('email') : null;
        }

        return $email;
    }

    /**
     * Real, IdP-side logout of the active method's session (see Oidc_client::sso_logout() - unlike a plain
     * local-session clear, this also kills the IdP's own SSO session, so a later login on the same browser
     * can't silently carry through as the same customer). A no-op if no method is active. Deliberately not
     * gated on "is currently authenticated" - Oidc_client::sso_logout() itself already handles that correctly
     * (keyed on whether there's a cached id_token to sign out with, not on current auth state, since by the
     * time an idle-timeout logout is triggered, the local session is already marked unauthenticated). Never
     * returns if it does redirect.
     *
     * @param string $redirect_url Where to send the browser back to once logout completes.
     */
    public function sso_logout(string $redirect_url): void
    {
        if (!$this->is_enabled()) {
            return;
        }

        if (setting('oidc_enabled_booking', 0)) {
            $this->get_oidc_client()->sso_logout($redirect_url); // Never returns if it does redirect.
        }
    }

    /**
     * Handle the IdP's redirect back after a login attempt (Booking_login::oidc_callback()).
     *
     * @param string|null $method A way to explicitly enforce the authentication method (eg. "oidc").
     *                             Falls back to the current setting if not given.
     */
    public function handle_callback(?string $method = null): void
    {
        if ($method === 'oidc' || setting('oidc_enabled_booking', 0)) {
            $this->get_oidc_client()->handle_callback();
        } else {
            redirect('booking');
        }
    }

    /**
     * Keep the session alive if a login is currently active (Booking_login::silent_refresh()).
     *
     * @return bool Returns true if no method is active, or the active method reports the session is still
     *              authenticated - false if a login is required but no longer valid.
     */
    public function refresh_if_authenticated(): bool
    {
        if (setting('oidc_enabled_booking', 0)) {
            return $this->get_oidc_client()->refresh_if_authenticated();
        } else {
            return true;
        }
    }

    /**
     * Lazily load the generic OIDC client.
     */
    private function get_oidc_client(): Oidc_client
    {
        if ($this->oidc_client === null) {
            /** @var EA_Controller $CI */
            $CI = &get_instance();

            $CI->load->library('oidc_client', [
                'callback_url' => site_url('booking_login/oidc_callback'),
            ]);

            $this->oidc_client = $CI->oidc_client;
        }

        return $this->oidc_client;
    }
}
