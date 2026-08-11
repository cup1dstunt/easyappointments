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
 * Creates, updates and deletes Zoom meetings for appointments via Zoom's Server-to-Server OAuth API. Credentials
 * are read from the "zoom_client_id"/"zoom_client_secret"/"zoom_account_id" settings (Settings > Integrations >
 * Zoom); a provider only gets new Zoom links when the account-wide integration is active ("zoom_enabled"), those
 * credentials are present, AND the provider's own "create_zoom_links" opt-in is on.
 *
 * New meetings are created under the provider's own Zoom account (matched by email, via
 * /users/{email}/meetings) rather than the Server-to-Server app's own "me" account - this is what makes the
 * start link host as the actual provider rather than a single shared/fixed identity, and requires the provider
 * to have a real Zoom account with that same email within the same Zoom organization as the app.
 *
 * If an appointment's provider changes later, the meeting is deliberately NOT recreated under the new
 * provider - Zoom's API has no reliable way to reassign an existing meeting's host, and recreating it would
 * break the customer's already-shared join link. Instead, the same meeting/links carry over as-is, and
 * "join_before_host" (set on every created meeting) means a substitute provider can just use the regular join
 * link like anyone else and appear as themselves - they won't have the original host's formal controls
 * (managing the waiting room, muting/removing participants, etc.), which is an accepted trade-off for this
 * case. See Booking::register()/Calendar::save_appointment() for the cleanup that runs instead when a
 * provider is reassigned to one without Zoom enabled at all.
 *
 * "zoom_enabled" only gates the creation/update of meetings (sync_meeting()) - cancel_appointment_meeting() still
 * removes an appointment's existing meeting regardless, since turning the integration off should not leave stale
 * meetings dangling on Zoom's side for appointments deleted afterwards.
 *
 * The two public entry points, sync_meeting() and cancel_appointment_meeting(), are deliberately defensive: they
 * never throw, and silently do nothing when Zoom is not configured for the relevant provider. A Zoom API failure
 * must never prevent an appointment from being booked, edited or deleted - it only means the appointment is left
 * without an up-to-date meeting link, which is logged for diagnosis.
 *
 * @package Libraries
 */
class Zoom_client
{
    private const API_BASE_URL = 'https://api.zoom.us/v2';

    private const OAUTH_URL = 'https://zoom.us/oauth/token';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    /**
     * Create or update the Zoom meeting for an appointment, depending on whether it already has one.
     *
     * The join link is stored in the generic "meeting_link" column, shared with Jitsi/Google Meet/manual links -
     * only the host-only "start" link needs its own column, since it must never reach the customer.
     *
     * @param array $appointment Appointment data (must contain start_datetime and end_datetime).
     * @param string $topic Meeting topic (typically the translated service name).
     * @param string $timezone The provider's timezone - start_datetime/end_datetime are wall-clock times in this
     *                         timezone, not UTC (matching how the rest of the app stores/reads appointment times).
     * @param string $host_email The provider's email - a new meeting is created under this Zoom user (via
     *                           /users/{email}/meetings) rather than the Server-to-Server app's own "me"
     *                           account, so the provider shows up as host when they use the start link. Only
     *                           works if the provider actually has a Zoom account with this same email within
     *                           the same Zoom organization as the Server-to-Server app; a mismatch fails the
     *                           create call the same way any other Zoom API error does (logged, no meeting).
     * @param array|null $existing The appointment's current zoom_meeting_id/zoom_start_link/meeting_link values,
     *                             if any (pass the previously stored values here to update instead of recreate).
     *
     * @return array|null Returns ['zoom_meeting_id', 'zoom_start_link', 'meeting_link'], or null if Zoom is not
     *                     configured or the API call failed.
     */
    public function sync_meeting(
        array $appointment,
        string $topic,
        string $timezone,
        string $host_email,
        ?array $existing = null,
    ): ?array {
        if (setting('zoom_enabled') !== '1') {
            return null;
        }

        $client_id = setting('zoom_client_id');
        $client_secret = decrypt_secret((string) setting('zoom_client_secret', ''));
        $account_id = setting('zoom_account_id');

        if (empty($client_id) || empty($client_secret) || empty($account_id)) {
            log_message('error', 'Zoom API: integration is active but credentials are incomplete - skipping sync.');

            return null;
        }

        $access_token = $this->authenticate($client_id, $client_secret, $account_id);

        if (!$access_token) {
            // Logged here rather than inside authenticate() - log_message('error', ...) captures a backtrace
            // (see Common.php::log_message()) whose frames include each call's arguments, and authenticate()'s
            // own frame (with $client_secret as a literal argument) would still be on the stack there. By the
            // time we're back here, that frame is gone, so the trace can no longer contain the secret.
            log_message('error', 'Zoom API: authentication failed.');

            return null;
        }

        $start_time = $this->to_zoom_start_time($appointment['start_datetime'], $timezone);
        $duration = $this->calculate_duration_in_minutes($appointment['start_datetime'], $appointment['end_datetime']);

        $existing_meeting_id = $existing['zoom_meeting_id'] ?? null;

        if ($existing_meeting_id) {
            $updated = $this->update_meeting($access_token, $existing_meeting_id, $start_time, $duration);

            if (!$updated) {
                return null;
            }

            return [
                'zoom_meeting_id' => $existing_meeting_id,
                'zoom_start_link' => $existing['zoom_start_link'] ?? null,
                'meeting_link' => $existing['meeting_link'] ?? null,
            ];
        }

        return $this->generate_meeting_links($access_token, $host_email, $topic, $start_time, $duration);
    }

    /**
     * Delete an appointment's Zoom meeting, if it has one and Zoom is configured. No-op otherwise.
     *
     * @param array $appointment Appointment data.
     */
    public function cancel_appointment_meeting(array $appointment): void
    {
        if (empty($appointment['zoom_meeting_id'])) {
            return;
        }

        $client_id = setting('zoom_client_id');
        $client_secret = decrypt_secret((string) setting('zoom_client_secret', ''));
        $account_id = setting('zoom_account_id');

        if (empty($client_id) || empty($client_secret) || empty($account_id)) {
            return;
        }

        $access_token = $this->authenticate($client_id, $client_secret, $account_id);

        if (!$access_token) {
            // See sync_meeting() for why this is logged here rather than inside authenticate().
            log_message('error', 'Zoom API: authentication failed.');

            return;
        }

        $this->delete_meeting($access_token, $appointment['zoom_meeting_id']);
    }

    /**
     * Authenticate against Zoom's Server-to-Server OAuth endpoint.
     *
     * @param string $client_id Zoom OAuth app client ID.
     * @param string $client_secret Zoom OAuth app client secret.
     * @param string $account_id Zoom account ID.
     *
     * @return string|null Returns the access token, or null on failure.
     */
    private function authenticate(string $client_id, string $client_secret, string $account_id): ?string
    {
        $url = self::OAUTH_URL . '?' . http_build_query([
            'grant_type' => 'account_credentials',
            'account_id' => $account_id,
        ]);

        $response = $this->request('POST', $url, null, [
            'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
        ]);

        if ($response === null || empty($response['access_token'])) {
            return null;
        }

        return $response['access_token'];
    }

    /**
     * Create a new Zoom meeting, hosted by a specific Zoom user rather than the Server-to-Server app's own
     * "me" account, so the meeting's host link works as that user rather than a shared/fixed identity.
     *
     * @param string $access_token Zoom OAuth access token.
     * @param string $host_email The Zoom user (by email) to host the meeting as.
     * @param string $topic Meeting topic.
     * @param string $start_time Meeting start time, already formatted as UTC ("Y-m-d\TH:i:s\Z").
     * @param int $duration Meeting duration, in minutes.
     *
     * @return array|null Returns ['zoom_meeting_id', 'zoom_start_link', 'meeting_link'], or null on failure.
     */
    private function generate_meeting_links(
        string $access_token,
        string $host_email,
        string $topic,
        string $start_time,
        int $duration,
    ): ?array {
        $payload = [
            'topic' => $topic,
            'type' => 2, // Scheduled meeting.
            'start_time' => $start_time,
            'duration' => $duration,
            'settings' => [
                // Zoom's API has no reliable way to reassign a meeting's host, so if the appointment's
                // provider changes later, the same meeting/links are kept rather than recreated - the
                // substitute provider just uses the regular join link like anyone else, appearing as
                // themselves rather than any pre-determined identity. Without this, since the originally
                // scheduled host will never actually show up to "start" it, nobody could get in at all.
                'join_before_host' => true,
            ],
        ];

        $response = $this->request(
            'POST',
            self::API_BASE_URL . '/users/' . rawurlencode($host_email) . '/meetings',
            $payload,
            ['Authorization: Bearer ' . $access_token],
        );

        if ($response === null || empty($response['id'])) {
            log_message('error', 'Zoom API: failed to create meeting for host ' . $host_email . '.');

            return null;
        }

        return [
            'zoom_meeting_id' => (string) $response['id'],
            'zoom_start_link' => $response['start_url'] ?? null,
            'meeting_link' => $response['join_url'] ?? null,
        ];
    }

    /**
     * Update an existing Zoom meeting's time and duration.
     *
     * @param string $access_token Zoom OAuth access token.
     * @param string $meeting_id Zoom meeting ID.
     * @param string $start_time Meeting start time, already formatted as UTC ("Y-m-d\TH:i:s\Z").
     * @param int $duration Meeting duration, in minutes.
     *
     * @return bool Returns true on success, false on failure.
     */
    private function update_meeting(string $access_token, string $meeting_id, string $start_time, int $duration): bool
    {
        $payload = [
            'start_time' => $start_time,
            'duration' => $duration,
        ];

        $status_code = $this->request(
            'PATCH',
            self::API_BASE_URL . '/meetings/' . rawurlencode($meeting_id),
            $payload,
            ['Authorization: Bearer ' . $access_token],
            true,
        );

        if ($status_code !== 204) {
            log_message('error', 'Zoom API: failed to update meeting ' . $meeting_id . '.');

            return false;
        }

        return true;
    }

    /**
     * Delete a Zoom meeting.
     *
     * @param string $access_token Zoom OAuth access token.
     * @param string $meeting_id Zoom meeting ID.
     *
     * @return bool Returns true on success, false on failure.
     */
    private function delete_meeting(string $access_token, string $meeting_id): bool
    {
        // schedule_for_reminder=false asks Zoom not to email the host about the cancellation. Zoom has an
        // acknowledged, unresolved bug where this is honored inconsistently, so don't rely on it fully.
        $status_code = $this->request(
            'DELETE',
            self::API_BASE_URL . '/meetings/' . rawurlencode($meeting_id) . '?schedule_for_reminder=false',
            null,
            ['Authorization: Bearer ' . $access_token],
            true,
        );

        if ($status_code !== 204) {
            log_message('error', 'Zoom API: failed to delete meeting ' . $meeting_id . '.');

            return false;
        }

        return true;
    }

    /**
     * Perform an HTTP request against the Zoom API.
     *
     * @param string $method HTTP method.
     * @param string $url Request URL.
     * @param array|null $payload JSON request body, if any.
     * @param array $headers Extra request headers.
     * @param bool $return_status_code If true, return the HTTP status code instead of the decoded JSON response.
     *
     * @return array|int|null Returns the decoded JSON response, the HTTP status code, or null on transport failure.
     */
    private function request(
        string $method,
        string $url,
        ?array $payload,
        array $headers,
        bool $return_status_code = false,
    ): array|int|null {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT_SECONDS);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::REQUEST_TIMEOUT_SECONDS);

        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $body = curl_exec($curl);

        if ($body === false) {
            log_message('error', 'Zoom API: cURL error: ' . curl_error($curl));
            curl_close($curl);

            return null;
        }

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($return_status_code) {
            return $status_code;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Convert an appointment's start date/time - a wall-clock time in the provider's timezone, not UTC - to the
     * UTC format Zoom's API expects.
     *
     * @param string $start_time Appointment start date/time (Y-m-d H:i:s), in $timezone.
     * @param string $timezone The timezone $start_time is expressed in.
     *
     * @return string Returns the start time formatted as "Y-m-d\TH:i:s\Z", converted to UTC.
     */
    private function to_zoom_start_time(string $start_time, string $timezone): string
    {
        $date = new DateTime($start_time, new DateTimeZone($timezone));

        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Calculate the duration between two date/time strings, in whole minutes.
     *
     * @param string $start_datetime Start date/time (Y-m-d H:i:s).
     * @param string $end_datetime End date/time (Y-m-d H:i:s).
     *
     * @return int Returns the duration in minutes (minimum 1).
     */
    private function calculate_duration_in_minutes(string $start_datetime, string $end_datetime): int
    {
        $seconds = (new DateTime($end_datetime))->getTimestamp() - (new DateTime($start_datetime))->getTimestamp();

        return max(1, (int) round($seconds / 60));
    }
}
