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
 * Easy!Appointments controller.
 *
 * @property EA_Benchmark $benchmark
 * @property EA_Cache $cache
 * @property EA_Calendar $calendar
 * @property EA_Config $config
 * @property EA_DB_forge $dbforge
 * @property EA_DB_query_builder $db
 * @property EA_DB_utility $dbutil
 * @property EA_Email $email
 * @property EA_Encrypt $encrypt
 * @property EA_Encryption $encryption
 * @property EA_Exceptions $exceptions
 * @property EA_Hooks $hooks
 * @property EA_Input $input
 * @property EA_Lang $lang
 * @property EA_Loader $load
 * @property EA_Log $log
 * @property EA_Migration $migration
 * @property EA_Output $output
 * @property EA_Profiler $profiler
 * @property EA_Router $router
 * @property EA_Security $security
 * @property EA_Session $session
 * @property EA_Upload $upload
 * @property EA_URI $uri
 *
 * @property Admins_model $admins_model
 * @property Appointments_model $appointments_model
 * @property Service_categories_model $service_categories_model
 * @property Consents_model $consents_model
 * @property Customers_model $customers_model
 * @property Providers_model $providers_model
 * @property Roles_model $roles_model
 * @property Secretaries_model $secretaries_model
 * @property Services_model $services_model
 * @property Settings_model $settings_model
 * @property Unavailabilities_model $unavailabilities_model
 * @property Users_model $users_model
 * @property Webhooks_model $webhooks_model
 * @property Blocked_periods_model $blocked_periods_model
 *
 * @property Accounts $accounts
 * @property Api $api
 * @property Cleanup $cleanup
 * @property Availability $availability
 * @property Email_messages $email_messages
 * @property Google_Sync $google_sync
 * @property Caldav_Sync $caldav_sync
 * @property Ics_file $ics_file
 * @property Instance $instance
 * @property Ldap_client $ldap_client
 * @property Notifications $notifications
 * @property Permissions $permissions
 * @property Synchronization $synchronization
 * @property Timezones $timezones
 * @property Webhooks_client $webhooks_client
 */
class EA_Controller extends CI_Controller
{
    /**
     * EA_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');

        $this->check_storage_writable();
        $this->ensure_user_exists();
        $this->configure_timezone();
        $this->configure_language();
        $this->load_common_html_vars();
        $this->load_common_script_vars();

        rate_limit($this->input->ip_address());
    }

    private function ensure_user_exists()
    {
        $user_id = session('user_id');

        if (!$user_id || !$this->db->table_exists('users')) {
            return;
        }

        if (!$this->accounts->does_account_exist($user_id)) {
            session_destroy();

            abort(403, 'Forbidden');
        }
    }

    /**
     * Configure the language.
     */
    private function configure_language()
    {
        $available_languages = config('available_languages');
        $language_codes = config('language_codes');

        // LNU: Booking Language with URL Parameter (README.md #13) - either 'language' or 'lang' as a GET query
        // param (matched here via $this->input->get() specifically, not the POST-merged request() helper, so
        // this can't collide with an unrelated POST body field of the same name on some other endpoint), given
        // as a full language name (eg. 'svenska') or a short code (eg. 'sv') resolved via $language_codes. When
        // present and valid, it always takes priority over whatever's already in session (someone visiting via
        // a fresh language-specific link is a more current signal of intent than a stale session value) and is
        // persisted to session - not just applied to this one request - so it also carries over to later
        // requests in the same visit that don't carry the parameter themselves, eg. the booking confirmation
        // page and its email after a successful booking. The URL is then redirected to (GET requests only, so
        // this can't hijack a POST-based AJAX endpoint expecting a JSON response) with the parameter stripped,
        // so it can't linger and re-apply on a later reload, fighting with a subsequent session-based language
        // change (eg. via the language switcher button).
        $query_language = $this->input->get('language') ?? $this->input->get('lang');

        if ($query_language !== null) {
            $query_language = $language_codes[$query_language] ?? $query_language;
        }

        if ($query_language !== null && in_array($query_language, $available_languages, true)) {
            if (session('language') !== $query_language) {
                session(['language' => $query_language]);
            }

            $language = $query_language;

            if (strtoupper($this->input->method()) === 'GET') {
                $remaining_params = $this->input->get();

                unset($remaining_params['language'], $remaining_params['lang']);

                $redirect_url =
                    current_url() . (!empty($remaining_params) ? '?' . http_build_query($remaining_params) : '');

                redirect($redirect_url, 'refresh');

                return;
            }
        } else {
            $session_language = session('language');
            $language = $session_language && in_array($session_language, $available_languages, true)
                ? $session_language
                : null;
        }

        if ($language) {
            config([
                'language' => $language,
                'language_code' => array_search($language, $language_codes) ?: 'en',
            ]);
        }

        $this->lang->load('translations');

        // LNU: Configurable Terminology (README.md #17) - mutates the just-loaded array in place, so both
        // lang() (reads via $this->lang->line()) and the JS-side lang() (dumped from this same array by
        // js_lang_script.php) see already-substituted text, with no separate client-side logic needed. Guarded
        // the same way as configure_timezone() - the settings table doesn't exist yet before installation.
        if ($this->db->table_exists('settings')) {
            apply_language_replacements($this->lang->language);
        }
    }

    /**
     * Load common script vars for all requests.
     */
    private function load_common_html_vars()
    {
        html_vars([
            'base_url' => config('base_url'),
            'index_page' => config('index_page'),
            'available_languages' => config('available_languages'),
            'language' => $this->lang->language,
            'csrf_token' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Load common script vars for all requests.
     */
    private function load_common_script_vars()
    {
        script_vars([
            'base_url' => config('base_url'),
            'index_page' => config('index_page'),
            'available_languages' => config('available_languages'),
            'csrf_token' => $this->security->get_csrf_hash(),
            'language' => config('language'),
            'language_code' => config('language_code'),
        ]);
    }

    /**
     * Set the default timezone of the app, based on the selected setting.
     */
    private function configure_timezone(): void
    {
        if (!$this->db->table_exists('settings')) {
            return;
        }

        $default_timezone = setting('default_timezone');

        date_default_timezone_set($default_timezone);
    }

    /**
     * Check if the storage folder is writable.
     */
    private function check_storage_writable(): void
    {
        $storage_path = APPPATH . '../storage';

        if (!is_dir($storage_path)) {
            show_error(
                'The storage folder does not exist: ' .
                    $storage_path .
                    '. ' .
                    'Please create this directory and ensure it is writable by the web server.',
                500,
                'Storage Configuration Error',
            );
        }

        if (!is_writable($storage_path)) {
            show_error(
                'The storage folder is not writable: ' .
                    $storage_path .
                    '. ' .
                    'Please ensure the web server has write permissions to this directory and its subdirectories (cache, logs, sessions, uploads).',
                500,
                'Storage Configuration Error',
            );
        }
    }
}
