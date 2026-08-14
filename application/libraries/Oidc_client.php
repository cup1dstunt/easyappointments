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

use Jumbojett\OpenIDConnectClient;

/**
 * LNU: OIDC Booking Login (README.md #15).
 *
 * OIDC login flow for the public booking wizard - its own settings are read from "oidc_*" (Settings >
 * Integrations > Booking Login), and its session state uses "oidc_*" keys too (a different store, so no
 * collision despite the shared prefix). The generic, method-agnostic settings ("booking_auth_*") are
 * Booking_auth_client's concern, not this class's. The client secret is stored encrypted (see
 * encrypt_secret()/decrypt_secret()).
 *
 * All of the IdP's userinfo claims are fetched exactly once, right after login, and cached in the session -
 * not re-fetched on every page load, and not fetched one HTTP round-trip per claim (the underlying library's
 * requestUserInfo() does a full round-trip per call regardless of how many attributes you ask for, so calling
 * it once and reading multiple keys out of the result is strictly better).
 *
 * @package Libraries
 */
class Oidc_client
{
    private string $callback_url;

    /**
     * Oidc_client constructor.
     *
     * @param array $params ['callback_url' => string] - where the IdP should redirect back to (a controller
     *                       action that calls handle_callback()).
     */
    public function __construct(array $params = [])
    {
        $this->callback_url = $params['callback_url'] ?? site_url('booking_login/oidc_callback');
    }

    /**
     * Redirect to the IdP (never returns) or, when called on the IdP's redirect back, exchange the code for
     * tokens and cache the resulting access/refresh tokens and userinfo claims in the session.
     */
    public function authenticate_and_get_tokens(): void
    {
        $client = $this->make_client(true);

        $client->authenticate();

        $access_token = $client->getAccessToken();

        if ($access_token === null) {
            return;
        }

        $payload = $client->getAccessTokenPayload();

        $userinfo = json_decode(json_encode($client->requestUserInfo()), true);

        session([
            $this->session_key('authenticated') => true,
            $this->session_key('access_token') => $access_token,
            $this->session_key('refresh_token') => $client->getRefreshToken(),
            $this->session_key('id_token') => $client->getIdToken(),
            $this->session_key('token_exp') => $payload->exp ?? null,
            $this->session_key('claims') => is_array($userinfo) ? $userinfo : [],
        ]);

        $this->set_browser_session_cookie();
    }

    /**
     * Redirect to the IdP, remembering a URL to return the browser to afterwards (Booking::index() is the only
     * caller that knows what that URL should be - e.g. a plain new booking vs. rescheduling a specific
     * appointment - this library has no notion of "booking" at all). Never returns.
     *
     * @param string $return_url Where handle_callback() should send the browser back to afterwards.
     */
    public function redirect_to_idp(string $return_url): void
    {
        session([$this->session_key('return_url') => $return_url]);

        $this->authenticate_and_get_tokens();
    }

    /**
     * Handle the IdP's redirect back after a login attempt (Booking_login::oidc_callback()), then send the
     * browser back to the URL previously passed to redirect_to_idp().
     */
    public function handle_callback(): void
    {
        try {
            $this->authenticate_and_get_tokens();
        } catch (Throwable $e) {
            log_message('error', 'OIDC: login callback failed: ' . $e->getMessage());
        }

        $return_url = session($this->session_key('return_url')) ?: site_url('');

        session([$this->session_key('return_url') => null]);

        redirect($return_url);
    }

    /**
     * Keep the session alive if (and only if) an OIDC login is currently active - safe to call unconditionally
     * (Booking_login::silent_refresh()), e.g. on a periodic keep-alive ping from the booking wizard.
     *
     * @return bool Returns whether the session is authenticated *after* this call - not just whether a
     *              refresh was attempted, since a stale, already-failed session (cleared by an earlier refresh
     *              attempt, e.g. from a previous ping) should also be reported as needing a fresh login, not
     *              treated as "nothing to do" just because there was no live session left to refresh here.
     */
    public function refresh_if_authenticated(): bool
    {
        if (session($this->session_key('authenticated'))) {
            $this->silent_refresh_access_token();
        }

        return (bool) session($this->session_key('authenticated'));
    }

    /**
     * The full login gate: validate the session and claims, transparently redirecting to the
     * IdP (never returns) when needed, up to twice in a row - a third consecutive failure is reported back
     * instead of redirecting again, so a stuck/misconfigured IdP can't bounce the browser forever.
     *
     * @param string $return_url Where to send the browser back to after a successful login, if a redirect to
     *                           the IdP turns out to be necessary. The caller decides what this should be.
     *
     * @return array{outcome: string, first_name?: ?string, last_name?: ?string, email?: ?string} Returns
     *         ['outcome' => 'ok', 'first_name' => ..., 'last_name' => ..., 'email' => ...] (the three standard
     *         OIDC profile/email claims) on success, or ['outcome' => 'login_server_error'] /
     *         ['outcome' => 'disallowed_params'] otherwise - the caller decides how to present each outcome.
     */
    public function enforce_gate(string $return_url): array
    {
        if (!$this->validate_session()) {
            $reauth_attempts = (int) session($this->session_key('reauth_attempts'), 0);

            if ($reauth_attempts >= 2) {
                // Reset here, not just on success below - otherwise this session is permanently stuck seeing
                // login_server_error on every future visit too, never even attempting a fresh redirect again,
                // regardless of whether whatever caused the original failures has since resolved.
                session([$this->session_key('reauth_attempts') => 0]);

                return ['outcome' => 'login_server_error'];
            }

            session([$this->session_key('reauth_attempts') => $reauth_attempts + 1]);

            // LNU: "SSO logout after booking and session expiry" (oidc_booking_logout_after_register) - force a
            // real IdP-side logout before redirecting to login whenever there's a stale login to kill, not just
            // when the customer happens to click a specific "Log In Again" link. This is the single choke point
            // every path reaches whenever a fresh login is about to be forced (a plain page reload, a closed
            // and reopened tab, a direct URL visit, or an explicit relogin link all end up here) - fixing it
            // here, rather than only at whatever triggered the relogin, means none of those paths can
            // accidentally bypass it and silently carry through via a still-alive IdP SSO session.
            if (setting('oidc_booking_logout_after_register')) {
                $this->sso_logout($return_url); // Never returns if there's something to sign out from.
            }

            $this->redirect_to_idp($return_url); // Never returns.
        }

        session([$this->session_key('reauth_attempts') => 0]);

        return [
            'outcome' => 'ok',
            'first_name' => $this->get_claim('given_name'),
            'last_name' => $this->get_claim('family_name'),
            'email' => $this->get_claim('email'),
        ];
    }

    /**
     * Log out of the OIDC session (does not affect the IdP's own session).
     */
    public function deauthenticate(): void
    {
        session([$this->session_key('authenticated') => false]);
    }

    /**
     * Real, IdP-side logout (RP-initiated logout) - unlike deauthenticate(), which only clears this app's own
     * session and leaves Keycloak's own SSO session cookie untouched. Needed so that a customer on a shared
     * computer who is "logged out" here can't have the next person silently carried through as them via a
     * still-alive Keycloak SSO session.
     *
     * Deliberately keyed on whether an id_token is cached, NOT on is currently authenticated - by the time an
     * idle-timeout logout is triggered, "authenticated" is already false (that's why it's being triggered), but
     * the id_token from that earlier login is still cached and still needs signing out. If there's no id_token
     * at all (nothing was ever cached this session), this just returns normally rather than redirecting to
     * nowhere - important for callers like Booking_confirmation::of() that pass their own URL as $redirect_url,
     * since an unconditional redirect there would loop back into itself forever.
     *
     * @param string $redirect_url Where the IdP should send the browser back to once logout completes, if it
     *                             does redirect. Must be registered as a valid post-logout redirect URI on the
     *                             IdP's client config.
     */
    public function sso_logout(string $redirect_url): void
    {
        $id_token = session($this->session_key('id_token'));

        $this->deauthenticate();

        // Cleared here, not just read - both enforce_gate() and Booking_confirmation::of() pass their own URL
        // as $redirect_url, so once used, this must not still look cached on the next pass through the same
        // page after the IdP redirects back, or it would sign out again forever.
        session([$this->session_key('id_token') => null]);

        if (!$id_token) {
            return;
        }

        try {
            $this->make_client(false)->signOut($id_token, $redirect_url); // Never returns on success.
        } catch (Throwable $e) {
            log_message('error', 'OIDC: SSO logout failed: ' . $e->getMessage());
        }

        // Only reached if signOut() itself threw before redirecting.
        redirect($redirect_url);
    }

    /**
     * Attempt to refresh the access token using the stored refresh token.
     *
     * @return bool Returns true on success. On failure, clears "authenticated" itself - both callers
     *              (validate_session()'s reactive check, and refresh_if_authenticated()'s periodic ping from
     *              the booking wizard) need this to happen the moment a refresh attempt fails, not just the
     *              former, so a dead session is never left looking authenticated until something else notices.
     */
    public function silent_refresh_access_token(): bool
    {
        $refresh_token = session($this->session_key('refresh_token'));

        if ($refresh_token) {
            $client = $this->make_client(false);

            try {
                $client->refreshToken($refresh_token);

                $access_token = $client->getAccessToken();

                if ($access_token) {
                    $payload = $client->getAccessTokenPayload();

                    session([
                        $this->session_key('access_token') => $access_token,
                        $this->session_key('refresh_token') => $client->getRefreshToken() ?: $refresh_token,
                        $this->session_key('token_exp') => $payload->exp ?? null,
                    ]);

                    return true;
                }
            } catch (Throwable $e) {
                log_message('error', 'OIDC: silent refresh failed: ' . $e->getMessage());
            }
        }

        session([$this->session_key('authenticated') => false]);

        return false;
    }

    /**
     * Validate the current OIDC session: authenticated, token not locally expired (attempting a silent refresh
     * once if it is), and the access token's signature AND liveness (introspection) both check out.
     *
     * Both checks are required, not either/or - a JWT signature check alone would miss a token revoked
     * server-side before its natural expiry, and introspection alone can be unreliable on some IdPs.
     *
     * @return bool Returns true if the session is valid, false otherwise (also clears "oidc_authenticated").
     */
    public function validate_session(): bool
    {
        if (!session($this->session_key('authenticated'))) {
            return false;
        }

        // The main app session cookie outlives the browser being closed (a week, by default) - this separate,
        // expire-on-browser-close cookie (see set_browser_session_cookie()) is what actually makes a fresh
        // browser instance require a fresh OIDC login, without shortening every other login's (backend admins,
        // providers, secretaries) session lifetime too.
        if (!$this->has_browser_session_cookie()) {
            session([$this->session_key('authenticated') => false]);

            return false;
        }

        $token_exp = session($this->session_key('token_exp'));

        // silent_refresh_access_token() already clears "authenticated" itself on failure.
        if ($token_exp && $token_exp <= time() && !$this->silent_refresh_access_token()) {
            return false;
        }

        $access_token = session($this->session_key('access_token'));

        if (!$access_token) {
            session([$this->session_key('authenticated') => false]);

            return false;
        }

        $client = $this->make_client(false);

        try {
            if (!$client->verifyJWTSignature($access_token)) {
                throw new RuntimeException('signature did not verify');
            }
        } catch (Throwable $e) {
            log_message('error', 'OIDC: access token signature verification failed: ' . $e->getMessage());
            session([$this->session_key('authenticated') => false]);

            return false;
        }

        try {
            $introspection = $client->introspectToken($access_token);

            if (empty($introspection->active)) {
                throw new RuntimeException('token reported inactive');
            }
        } catch (Throwable $e) {
            log_message('error', 'OIDC: access token introspection failed: ' . $e->getMessage());
            session([$this->session_key('authenticated') => false]);

            return false;
        }

        return true;
    }

    /**
     * Get a claim from the userinfo response cached at login time.
     *
     * @param string $name Claim name, e.g. "given_name", "email", or any IdP-specific attribute.
     *
     * @return string|null Returns the claim value, or null if not present.
     */
    public function get_claim(string $name): ?string
    {
        $claims = session($this->session_key('claims'), []);

        return is_array($claims) ? ($claims[$name] ?? null) : null;
    }

    /**
     * All claims from the userinfo response cached at login time, keyed by their own IdP-given names (e.g.
     * "affiliation") - lets a custom field reference an arbitrary claim (via an "auth-prop" attribute in its
     * own free-form "attributes" setting - see components/custom_fields.php) without this library or
     * Booking::index() needing to know about it in advance. "first_name"/"last_name" are added as aliases of
     * OIDC's own "given_name"/"family_name" claims, matching enforce_gate()'s own return keys, so callers only
     * ever need to know this app's generic property names, never OIDC's own vocabulary ("email" needs no such
     * alias, already matching).
     *
     * @return array<string, mixed> Returns the claims array, or [] if not authenticated.
     */
    public function get_claims(): array
    {
        $claims = session($this->session_key('claims'), []);

        $claims = is_array($claims) ? $claims : [];

        $claims['first_name'] = $claims['given_name'] ?? null;
        $claims['last_name'] = $claims['family_name'] ?? null;

        return $claims;
    }

    /**
     * Build an OpenIDConnectClient using the configured credentials.
     *
     * @param bool $for_redirect Whether this client will be used to redirect the browser to the IdP (adds the
     *                           redirect URL, PKCE, and UI locale hint). Pass false for a client only used to
     *                           validate/refresh an existing token, which needs none of that.
     */
    private function make_client(bool $for_redirect): OpenIDConnectClient
    {
        $client = new OpenIDConnectClient(
            setting($this->setting_key('idp_url'), ''),
            setting($this->setting_key('client_id'), ''),
            decrypt_secret((string) setting($this->setting_key('client_secret'), '')),
        );

        $client->addScope(['openid', 'profile', 'email']);

        if ($for_redirect) {
            $client->setRedirectURL($this->callback_url);
            $client->setCodeChallengeMethod('S256');
            $client->setHttpUpgradeInsecureRequests(false);
            $client->setResponseTypes(['code']);

            $language = session('language') ?? config('language');

            $language_code = array_search($language, config('language_codes', []), true);

            if ($language_code) {
                $client->addAuthParam(['ui_locales' => $language_code]);
            }
        }

        return $client;
    }

    /**
     * Build a settings key, e.g. "oidc_client_id".
     */
    private function setting_key(string $suffix): string
    {
        return "oidc_{$suffix}";
    }

    /**
     * Build a session key, e.g. "oidc_authenticated".
     */
    private function session_key(string $suffix): string
    {
        return "oidc_{$suffix}";
    }

    /**
     * Set a plain cookie (not part of the app's own session) with no expiration of its own, so the browser
     * clears it when fully closed (not just when a tab is closed) - unlike the app's main session cookie,
     * which intentionally outlives that (a week, by default) so backend users don't need to log in again on
     * every visit. Its mere presence is checked by has_browser_session_cookie(); its value is never read.
     */
    private function set_browser_session_cookie(): void
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        $CI->input->set_cookie([
            'name' => 'oidc_browser_session',
            'value' => '1',
            'expire' => 0, // 0 = no expiration set on the cookie itself = cleared when the browser fully closes.
        ]);
    }

    /**
     * @return bool Whether the browser-session cookie set by set_browser_session_cookie() is still present.
     */
    private function has_browser_session_cookie(): bool
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();

        return (bool) $CI->input->cookie('oidc_browser_session');
    }
}
