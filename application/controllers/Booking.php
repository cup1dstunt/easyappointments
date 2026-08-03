<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking controller.
 *
 * Handles the booking related operations.
 *
 * Notice: This file used to have the booking page related code which since v1.5 has now moved to the Booking.php
 * controller for improved consistency.
 *
 * @package Controllers
 */
class Booking extends EA_Controller
{
    public array $allowed_customer_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'timezone',
        'language',
    ];
    public mixed $allowed_provider_fields = ['id', 'first_name', 'last_name', 'services', 'timezone'];
    public array $allowed_appointment_fields = [
        'id',
        'start_datetime',
        'end_datetime',
        'location',
        'meeting_link',
        'notes',
        'color',
        'status',
        'is_unavailability',
        'id_users_provider',
        'id_users_customer',
        'id_services',
    ];

    /**
     * Booking constructor.
     */
    public function __construct()
    {
        parent::__construct();

        for ($i = 1; $i <= config('max_custom_fields', 5); $i++) {
            array_push($this->allowed_customer_fields, 'custom_field_' . $i);
        }

        for ($i = 1; $i <= config('max_appt_custom_fields', 5); $i++) {
            array_push($this->allowed_appointment_fields, 'appt_custom_field_' . $i);
        }

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('admins_model');
        $this->load->model('secretaries_model');
        $this->load->model('service_categories_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');
        $this->load->model('consents_model');

        $this->load->library('timezones');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('availability');
        $this->load->library('webhooks_client');
        $this->load->library('jitsi_client');
    }

    /**
     * Verify CSRF token for booking submissions.
     *
     * @throws RuntimeException If CSRF token is invalid.
     */
    private function verify_csrf_token(): void
    {
        $csrf_token = request('csrf_token') ?? $this->input->get_request_header('X-CSRF');
        $csrf_cookie = $this->input->cookie('csrf_cookie');

        if (empty($csrf_token) || empty($csrf_cookie) || !hash_equals($csrf_cookie, $csrf_token)) {
            log_message('error', 'Invalid CSRF token in booking request from IP: ' . $this->input->ip_address());
            throw new RuntimeException('Security validation failed. Please refresh the page and try again.');
        }
    }

    /**
     * Render the booking page and display the selected appointment.
     *
     * This method will call the "index" callback to handle the page rendering.
     *
     * @param string $appointment_hash
     */
    public function reschedule(string $appointment_hash): void
    {
        html_vars(['appointment_hash' => $appointment_hash]);

        $this->index();
    }

    /**
     * Render the booking page.
    /**
     * Render the booking page.
     */
    public function index(): void
    {
        method('get');

        if (!is_app_installed()) {
            redirect('installation');

            return;
        }

        $company_name = setting('company_name');
        $company_logo = setting('company_logo');
        $company_color = setting('company_color');
        $disable_booking = setting('disable_booking');
        $google_analytics_code = setting('google_analytics_code');
        $matomo_analytics_url = setting('matomo_analytics_url');
        $matomo_analytics_site_id = setting('matomo_analytics_site_id');

        if ($disable_booking) {
            $disable_booking_message = setting('disable_booking_message');

            html_vars([
                'show_message' => true,
                'page_title' => lang('page_title') . ' ' . e($company_name),
                'message_title' => lang('booking_is_disabled'),
                'message_text' => $disable_booking_message,
                'message_icon' => base_url('assets/img/error.png'),
                'google_analytics_code' => $google_analytics_code,
                'matomo_analytics_url' => $matomo_analytics_url,
                'matomo_analytics_site_id' => $matomo_analytics_site_id,
                'display_login_button' => setting('display_login_button'),
                'legal_notice_url' => setting('legal_notice_url'),
                'imprint_url' => setting('imprint_url'),
            ]);

            $this->load->view('pages/booking_message');

            return;
        }

        $available_services = $this->services_model->get_available_services(true);
        $available_providers = $this->providers_model->get_available_providers(true);

        foreach ($available_providers as &$available_provider) {
            // Only expose the required provider data.

            $this->providers_model->only($available_provider, $this->allowed_provider_fields);
        }

        $date_format = setting('date_format');
        $time_format = setting('time_format');
        $first_weekday = setting('first_weekday');
        $display_first_name = setting('display_first_name');
        $require_first_name = setting('require_first_name');
        $display_last_name = setting('display_last_name');
        $require_last_name = setting('require_last_name');
        $display_email = setting('display_email');
        $require_email = setting('require_email');
        $display_phone_number = setting('display_phone_number');
        $require_phone_number = setting('require_phone_number');
        $display_address = setting('display_address');
        $require_address = setting('require_address');
        $display_city = setting('display_city');
        $require_city = setting('require_city');
        $display_zip_code = setting('display_zip_code');
        $require_zip_code = setting('require_zip_code');
        $display_notes = setting('display_notes');
        $require_notes = setting('require_notes');
        $display_cookie_notice = setting('display_cookie_notice');
        $cookie_notice_content = setting('cookie_notice_content');
        $display_terms_and_conditions = setting('display_terms_and_conditions');
        $terms_and_conditions_content = setting('terms_and_conditions_content');
        $display_privacy_policy = setting('display_privacy_policy');
        $privacy_policy_content = setting('privacy_policy_content');
        $display_any_provider = setting('display_any_provider');
        $display_login_button = setting('display_login_button');
        $display_delete_personal_information = setting('display_delete_personal_information');
        $book_advance_timeout = setting('book_advance_timeout');
        $book_advance_timeout_unit = setting('book_advance_timeout_unit', config('default_book_advance_timeout_unit'));
        $legal_notice_url = setting('legal_notice_url');
        $imprint_url = setting('imprint_url');
        $theme = request('theme', setting('theme', 'default'));

        // Sanitize theme parameter to prevent directory traversal
        if (!empty($theme)) {
            // Only allow alphanumeric characters, underscores, and hyphens
            $theme = preg_replace('/[^a-zA-Z0-9_\-]/', '', $theme);
        }

        if (empty($theme) || !file_exists(__DIR__ . '/../../assets/css/themes/' . $theme . '.min.css')) {
            $theme = 'default';
        }

        $timezones = $this->timezones->to_array();
        $grouped_timezones = $this->timezones->to_grouped_array();

        $appointment_hash = html_vars('appointment_hash');

        if (!empty($appointment_hash)) {
            // Load the appointments data and enable the manage mode of the booking page.

            $manage_mode = true;

            $results = $this->appointments_model->get(['hash' => $appointment_hash]);

            if (empty($results)) {
                html_vars([
                    'show_message' => true,
                    'page_title' => lang('page_title') . ' ' . $company_name,
                    'message_title' => lang('appointment_not_found'),
                    'message_text' => lang('appointment_does_not_exist_in_db'),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => $google_analytics_code,
                    'matomo_analytics_url' => $matomo_analytics_url,
                    'matomo_analytics_site_id' => $matomo_analytics_site_id,
                    'display_login_button' => $display_login_button,
                    'legal_notice_url' => $legal_notice_url,
                    'imprint_url' => $imprint_url,
                ]);

                $this->load->view('pages/booking_message');

                return;
            }

            $appointment = $results[0];
            $appointment['attached_file_names'] = $this->appointments_model->get_attached_files((int) $appointment['id']);
            $provider = $this->providers_model->find($appointment['id_users_provider']);

            // Make sure the appointment can still be rescheduled.

            $provider_timezone = new DateTimeZone($provider['timezone']);

            $appointment_start = new DateTime($appointment['start_datetime'], $provider_timezone);

            $limit = new DateTime('now', $provider_timezone);

            $limit->modify('+' . $book_advance_timeout . ' ' . $book_advance_timeout_unit);

            if ($appointment_start < $limit) {
                html_vars([
                    'show_message' => true,
                    'page_title' => lang('page_title') . ' ' . $company_name,
                    'message_title' => lang('appointment_locked'),
                    'message_text' => strtr(lang('appointment_locked_message'), [
                        '{$limit}' => sprintf('%d %s', $book_advance_timeout, lang($book_advance_timeout_unit)),
                    ]),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => $google_analytics_code,
                    'matomo_analytics_url' => $matomo_analytics_url,
                    'matomo_analytics_site_id' => $matomo_analytics_site_id,
                    'display_login_button' => $display_login_button,
                    'legal_notice_url' => $legal_notice_url,
                    'imprint_url' => $imprint_url,
                ]);

                $this->load->view('pages/booking_message');

                return;
            }
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $this->customers_model->only($customer, $this->allowed_customer_fields);
            $customer_token = md5(uniqid(mt_rand(), true));

            // Cache the token for 10 minutes.
            $this->cache->save('customer-token-' . $customer_token, $customer['id'], 600);
        } else {
            $manage_mode = false;
            $customer_token = false;
            $appointment = null;
            $provider = null;
            $customer = null;
        }

        script_vars([
            'manage_mode' => $manage_mode,
            'available_services' => $available_services,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_weekday' => $first_weekday,
            'display_cookie_notice' => $display_cookie_notice,
            'display_any_provider' => setting('display_any_provider'),
            'hide_customer_timezone' => setting('hide_customer_timezone', 0),
            'hide_provider_selection' => setting('hide_provider_selection'),
            'ANY_PROVIDER' => ANY_PROVIDER,
            // LNU: Custom Messages during Booking (README.md #12).
            'custom_messages_enabled' => setting('booking_custom_messages_enabled', 0),
            'custom_message_time_unavailable' => setting('booking_custom_message_time_unavailable', ''),
            'future_booking_limit' => setting('future_booking_limit'),
            'appointment_data' => $appointment,
            'provider_data' => $provider ? filter_sensitive_user_data($provider) : null,
            'customer_data' => $customer,
            'customer_token' => $customer_token,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
        ]);

        html_vars([
            'available_services' => $available_services,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'theme' => $theme,
            'company_name' => $company_name,
            'company_logo' => $company_logo,
            'company_color' => $company_color === '#ffffff' ? '' : $company_color,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_weekday' => $first_weekday,
            'display_first_name' => $display_first_name,
            'require_first_name' => $require_first_name,
            'display_last_name' => $display_last_name,
            'require_last_name' => $require_last_name,
            'display_email' => $display_email,
            'require_email' => $require_email,
            'display_phone_number' => $display_phone_number,
            'require_phone_number' => $require_phone_number,
            'display_address' => $display_address,
            'require_address' => $require_address,
            'display_city' => $display_city,
            'require_city' => $require_city,
            'display_zip_code' => $display_zip_code,
            'require_zip_code' => $require_zip_code,
            'display_notes' => $display_notes,
            'require_notes' => $require_notes,
            'display_cookie_notice' => $display_cookie_notice,
            'cookie_notice_content' => $cookie_notice_content,
            'display_terms_and_conditions' => $display_terms_and_conditions,
            'terms_and_conditions_content' => $terms_and_conditions_content,
            'display_privacy_policy' => $display_privacy_policy,
            'privacy_policy_content' => $privacy_policy_content,
            'display_any_provider' => $display_any_provider,
            'display_login_button' => $display_login_button,
            'display_delete_personal_information' => $display_delete_personal_information,
            'legal_notice_url' => $legal_notice_url,
            'imprint_url' => $imprint_url,
            'google_analytics_code' => $google_analytics_code,
            'matomo_analytics_url' => $matomo_analytics_url,
            'matomo_analytics_site_id' => $matomo_analytics_site_id,
            'timezones' => $timezones,
            'grouped_timezones' => $grouped_timezones,
            'manage_mode' => $manage_mode,
            'appointment_data' => $appointment,
            'provider_data' => $provider ? filter_sensitive_user_data($provider) : null,
            'customer_data' => $customer,
        ]);

        $this->load->view('pages/booking');
    }

    /**
     * Register the appointment to the database.
     */
    public function register(): void
    {
        try {
            method('post');

            // Verify CSRF token for booking submissions
            $this->verify_csrf_token();

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('post_data', 'string');
            check('captcha', 'string|null');

            // post_data arrives as a JSON string rather than a natively-nested array, since attached files
            // require a multipart/form-data request, which cannot carry nested fields on its own.
            $post_data = json_decode(request('post_data'), true);

            // Validate that post_data is an array
            if (!is_array($post_data)) {
                throw new InvalidArgumentException('Invalid request data format.');
            }

            $captcha = request('captcha');
            $appointment = $post_data['appointment'] ?? [];
            $customer = $post_data['customer'] ?? [];
            $manage_mode = filter_var($post_data['manage_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $discarded_file_names = $post_data['discarded_file_names'] ?? [];

            // Validate required appointment fields
            if (empty($appointment) || !is_array($appointment)) {
                throw new InvalidArgumentException('Invalid appointment data.');
            }

            // Validate required customer fields
            if (empty($customer) || !is_array($customer)) {
                throw new InvalidArgumentException('Invalid customer data.');
            }

            // Sanitize and validate customer email
            if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address format.');
            }

            // Sanitize customer fields - only allow expected fields
            $customer = array_intersect_key($customer, array_flip($this->allowed_customer_fields));

            // Sanitize appointment fields - only allow expected fields
            $appointment = array_intersect_key($appointment, array_flip($this->allowed_appointment_fields));

            if (!array_key_exists('address', $customer)) {
                $customer['address'] = '';
            }

            if (!array_key_exists('city', $customer)) {
                $customer['city'] = '';
            }

            if (!array_key_exists('zip_code', $customer)) {
                $customer['zip_code'] = '';
            }

            if (!array_key_exists('notes', $customer)) {
                $customer['notes'] = '';
            }

            if (!array_key_exists('phone_number', $customer)) {
                $customer['phone_number'] = '';
            }

            // LNU: Enforce the customer booking limits (README.md #7). This is the authoritative check - the
            // AJAX check_customer_booking_limits() endpoint only provides a live preview while the customer is
            // still filling out the wizard, it does not gate the actual save.
            $booking_limit_status = $this->get_customer_booking_limit_status(
                $customer['email'],
                (int) $appointment['id_services'],
                $appointment['start_datetime'],
                $appointment['id'] ?? null,
            );

            if (!$booking_limit_status['allowed']) {
                throw new RuntimeException($booking_limit_status['message']);
            }

            // Check appointment availability before registering it to the database.
            $appointment['id_users_provider'] = $this->check_datetime_availability();

            if (!$appointment['id_users_provider']) {
                throw new RuntimeException(lang('requested_hour_is_unavailable'));
            }

            $provider = $this->providers_model->find($appointment['id_users_provider']);

            $service = $this->services_model->find($appointment['id_services']);

            $require_captcha = (bool) setting('require_captcha');

            // Validate CAPTCHA or ALTCHA
            if ($require_captcha) {
                $altcha_enabled = setting('altcha_enabled') === '1';

                if ($altcha_enabled) {
                    // Validate ALTCHA
                    check('altcha_payload', 'string|null');
                    $altcha_payload = request('altcha_payload');

                    $this->load->library('altcha_client');

                    if (!$this->altcha_client->verify($altcha_payload)) {
                        json_response([
                            'altcha_verification' => false,
                        ]);
                        return;
                    }
                } else {
                    // Validate traditional CAPTCHA
                    $captcha_phrase = session('captcha_phrase');

                    if (strtoupper($captcha_phrase) !== strtoupper($captcha)) {
                        json_response([
                            'captcha_verification' => false,
                        ]);
                        return;
                    }
                }
            }

            if ($this->customers_model->exists($customer)) {
                $customer['id'] = $this->customers_model->find_record_id($customer);

                $existing_appointments = $this->appointments_model->get([
                    'id !=' => $manage_mode ? $appointment['id'] : null,
                    'id_users_customer' => $customer['id'],
                    'start_datetime <=' => $appointment['start_datetime'],
                    'end_datetime >=' => $appointment['end_datetime'],
                ]);

                if (count($existing_appointments)) {
                    throw new RuntimeException(lang('customer_is_already_booked'));
                }
            }

            // Jitsi integration: if enabled, generate a Jitsi meeting link for the appointment
            if (setting('jitsi_enabled') === '1') {
                $appointment['meeting_link'] = $this->jitsi_client->generate_link();
            }

            if (empty($appointment['location']) && !empty($service['location'])) {
                $appointment['location'] = $service['location'];
            }

            if (empty($appointment['color']) && !empty($service['color'])) {
                $appointment['color'] = $service['color'];
            }

            $customer_ip = $this->input->ip_address();

            // Create the consents (if needed).
            $consent = [
                'first_name' => $customer['first_name'] ?? '-',
                'last_name' => $customer['last_name'] ?? '-',
                'email' => $customer['email'] ?? '-',
                'ip' => $customer_ip,
            ];

            if (setting('display_terms_and_conditions')) {
                $consent['type'] = 'terms-and-conditions';

                $this->consents_model->save($consent);
            }

            if (setting('display_privacy_policy')) {
                $consent['type'] = 'privacy-policy';

                $this->consents_model->save($consent);
            }

            // Save customer language (the language which is used to render the booking page).
            $customer['language'] = session('language') ?? config('language');

            $this->customers_model->only($customer, $this->allowed_customer_fields);

            $customer_id = $this->customers_model->save($customer);
            $customer = $this->customers_model->find($customer_id);

            $appointment['id_users_customer'] = $customer_id;
            $appointment['is_unavailability'] = false;
            $appointment['color'] = $service['color'];

            $appointment_status_options_json = setting('appointment_status_options', '[]');
            $appointment_status_options = json_decode($appointment_status_options_json, true) ?? [];
            $appointment['status'] = $appointment_status_options[0] ?? null;
            $appointment['end_datetime'] = $this->appointments_model->calculate_end_datetime($appointment);

            $this->appointments_model->only($appointment, $this->allowed_appointment_fields);

            $appointment_id = $this->appointments_model->save($appointment);
            $appointment = $this->appointments_model->find($appointment_id);

            if ($manage_mode && is_array($discarded_file_names)) {
                foreach ($discarded_file_names as $discarded_file_name) {
                    $this->appointments_model->delete_attached_file($appointment_id, $discarded_file_name);
                }
            }

            $max_attached_files = boolval(setting('attached_files_supported', 0)) ? (int) setting('max_attached_files', 0) : 0;

            for ($i = 1; $i <= $max_attached_files; $i++) {
                $this->appointments_model->save_attached_file($appointment_id, 'attached_file_data_' . $i);
            }

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

            $this->notifications->notify_appointment_saved(
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                $manage_mode,
            );

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

            $response = [
                'appointment_id' => $appointment['id'],
                'appointment_hash' => $appointment['hash'],
            ];

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Check whether the provider is still available in the selected appointment date.
     *
     * It is possible that two or more customers select the same appointment date and time concurrently. The app won't
     * allow this to happen, so one of the two will eventually get the selected date and the other one will have
     * to choose for another one.
     *
     * Use this method just before the customer confirms the appointment registration. If the selected date was reserved
     * in the meanwhile, the customer must be prompted to select another time.
     *
     * @return int|null Returns the ID of the provider that is available for the appointment.
     *
     * @throws Exception
     */
    protected function check_datetime_availability(): ?int
    {
        $post_data = json_decode(request('post_data'), true);

        $appointment = $post_data['appointment'];

        $appointment_start = new DateTime($appointment['start_datetime']);

        $date = $appointment_start->format('Y-m-d');

        $hour = $appointment_start->format('H:i');

        if ($appointment['id_users_provider'] === ANY_PROVIDER) {
            $appointment['id_users_provider'] = match (setting('provider_selection_method')) {
                'around_date' => $this->search_provider_available_around_date(
                    $appointment['id_services'],
                    $appointment_start,
                ),
                default => $this->search_any_provider($appointment['id_services'], $date, $hour),
            };

            return $appointment['id_users_provider'];
        }

        $service = $this->services_model->find($appointment['id_services']);

        $exclude_appointment_id = $appointment['id'] ?? null;

        $provider = $this->providers_model->find($appointment['id_users_provider']);

        $available_hours = $this->availability->get_available_hours(
            $date,
            $service,
            $provider,
            $exclude_appointment_id,
        );

        $is_still_available = false;

        $appointment_hour = date('H:i', strtotime($appointment['start_datetime']));

        foreach ($available_hours as $available_hour) {
            if ($appointment_hour === $available_hour) {
                $is_still_available = true;
                break;
            }
        }

        return $is_still_available ? $appointment['id_users_provider'] : null;
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the provider with the most available periods.
     *
     * @param int $service_id Service ID
     * @param string $date Selected date (Y-m-d).
     * @param string|null $hour Selected hour (H:i).
     *
     * @return int|null Returns the ID of the provider that can provide the service at the selected date.
     *
     * @throws Exception
     */
    protected function search_any_provider(int $service_id, string $date, ?string $hour = null): ?int
    {
        $available_providers = $this->providers_model->get_available_providers(true);

        $service = $this->services_model->find($service_id);

        $provider_id = null;

        $max_hours_count = 0;

        foreach ($available_providers as $provider) {
            foreach ($provider['services'] as $provider_service_id) {
                if ($provider_service_id == $service_id) {
                    // Check if the provider is available for the requested date.
                    $available_hours = $this->availability->get_available_hours($date, $service, $provider);

                    if (
                        count($available_hours) > $max_hours_count &&
                        (empty($hour) || in_array($hour, $available_hours))
                    ) {
                        $provider_id = $provider['id'];

                        $max_hours_count = count($available_hours);
                    }
                }
            }
        }

        return $provider_id;
    }

    /**
     * LNU: Get the IDs of the providers who can provide the given service and are available at the given date
     * and hour (README.md #3).
     *
     * @param int $service_id Service ID.
     * @param string $date Selected date (Y-m-d).
     * @param string $hour Selected hour (H:i).
     *
     * @return int[] Returns the IDs of the available providers.
     *
     * @throws Exception
     */
    protected function get_available_providers_for_service(int $service_id, string $date, string $hour): array
    {
        $available_provider_ids = [];

        $service = $this->services_model->find($service_id);

        foreach ($this->providers_model->get_available_providers(true) as $provider) {
            if (!in_array($service_id, $provider['services'])) {
                continue;
            }

            $available_hours = $this->availability->get_available_hours($date, $service, $provider);

            if (in_array($hour, $available_hours)) {
                $available_provider_ids[] = $provider['id'];
            }
        }

        return $available_provider_ids;
    }

    /**
     * LNU: Search for the provider whose existing bookings are furthest away from the new booking, in order to
     * distribute bookings among providers as evenly as possible over time (README.md #3).
     *
     * Unlike search_any_provider(), which only looks at availability on the exact booking date, this also
     * takes into account how far away each available provider's closest existing booking is.
     *
     * @param int $service_id Service ID.
     * @param DateTime $appointment_start Selected appointment start date and time.
     *
     * @return int|null Returns the ID of the selected provider, or null if none are available.
     *
     * @throws Exception
     */
    protected function search_provider_available_around_date(int $service_id, DateTime $appointment_start): ?int
    {
        $available_providers = $this->get_available_providers_for_service(
            $service_id,
            $appointment_start->format('Y-m-d'),
            $appointment_start->format('H:i'),
        );

        return $this->providers_model->get_provider_available_around_date($appointment_start, $available_providers);
    }

    /**
     * Get the available appointment hours for the selected date.
     *
     * This method answers to an AJAX request. It calculates the available hours for the given service, provider and
     * date.
     */
    public function get_available_hours(): void
    {
        try {
            method('post');

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('provider_id', 'string|numeric|null');
            check('service_id', 'numeric');
            check('selected_date', 'date');
            check('manage_mode', 'bool|null');
            check('appointment_id', 'numeric|null');

            $provider_id = request('provider_id');
            $service_id = request('service_id');
            $selected_date = request('selected_date');

            // Do not continue if there was no provider selected (more likely there is no provider in the system).

            if (empty($provider_id)) {
                json_response();

                return;
            }

            // If manage mode is TRUE then the following we should not consider the selected appointment when
            // calculating the available time periods of the provider.

            $exclude_appointment_id = request('manage_mode') ? request('appointment_id') : null;

            // If the user has selected the "any-provider" option then we will need to search for an available provider
            // that will provide the requested service.

            $service = $this->services_model->find($service_id);

            if ($provider_id === ANY_PROVIDER) {
                $providers = $this->providers_model->get_available_providers(true);

                $available_hours = [];

                foreach ($providers as $provider) {
                    if (!in_array($service_id, $provider['services'])) {
                        continue;
                    }

                    $provider_available_hours = $this->availability->get_available_hours(
                        $selected_date,
                        $service,
                        $provider,
                        $exclude_appointment_id,
                    );

                    $available_hours = array_merge($available_hours, $provider_available_hours);
                }

                $available_hours = array_unique(array_values($available_hours));

                sort($available_hours);

                $response = $available_hours;
            } else {
                $provider = $this->providers_model->find($provider_id);

                $response = $this->availability->get_available_hours(
                    $selected_date,
                    $service,
                    $provider,
                    $exclude_appointment_id,
                );
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get the available appointment dates for the selected date period.
     *
     * Get an array with the available dates of a specific provider, service and month of the year. Provide the
     * "provider_id", "service_id" and "selected_date" as GET parameters to the request. The "selected_date" parameter
     * must have the "Y-m-d" format.
     *
     * Outputs a JSON string with the unavailability dates. that are unavailability.
     */
    public function get_unavailable_dates(): void
    {
        try {
            method('get');

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('provider_id', 'string|numeric|null');
            check('service_id', 'numeric');
            check('appointment_id', 'numeric|null');
            check('manage_mode', 'bool|null');
            check('selected_date', 'date');

            $provider_id = request('provider_id');
            $service_id = request('service_id');
            $appointment_id = request('appointment_id');
            $manage_mode = filter_var(request('manage_mode'), FILTER_VALIDATE_BOOLEAN);
            $selected_date_string = request('selected_date');
            $selected_date = new DateTime($selected_date_string);
            $number_of_days_in_month = (int) $selected_date->format('t');
            $unavailable_dates = [];

            $provider_ids =
                $provider_id === ANY_PROVIDER ? $this->search_providers_by_service($service_id) : [$provider_id];

            $exclude_appointment_id = $manage_mode ? $appointment_id : null;

            // Get the service record.
            $service = $this->services_model->find($service_id);

            for ($i = 1; $i <= $number_of_days_in_month; $i++) {
                $current_date = new DateTime($selected_date->format('Y-m') . '-' . $i);

                if ($current_date < new DateTime(date('Y-m-d 00:00:00'))) {
                    // Past dates become immediately unavailability.
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                    continue;
                }

                // Finding at least one slot of availability.
                foreach ($provider_ids as $current_provider_id) {
                    $provider = $this->providers_model->find($current_provider_id);

                    $available_hours = $this->availability->get_available_hours(
                        $current_date->format('Y-m-d'),
                        $service,
                        $provider,
                        $exclude_appointment_id,
                    );

                    if (!empty($available_hours)) {
                        break;
                    }
                }

                // No availability amongst all the provider.
                if (empty($available_hours)) {
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                }
            }

            if (count($unavailable_dates) === $number_of_days_in_month) {
                json_response([
                    'is_month_unavailable' => true,
                ]);

                return;
            }

            json_response($unavailable_dates);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Search for any provider that can handle the requested service.
     *
     * This method will return the database ID of the providers affected to the requested service.
     *
     * @param int $service_id The requested service ID.
     *
     * @return array Returns the ID of the provider that can provide the requested service.
     */
    protected function search_providers_by_service(int $service_id): array
    {
        $available_providers = $this->providers_model->get_available_providers(true);
        $provider_list = [];

        foreach ($available_providers as $provider) {
            foreach ($provider['services'] as $provider_service_id) {
                if ($provider_service_id === $service_id) {
                    // Check if the provider is affected to the selected service.
                    $provider_list[] = $provider['id'];
                }
            }
        }

        return $provider_list;
    }

    /**
     * LNU: Check whether the customer is allowed to make this booking, given the configured customer booking
     * limits (README.md #7). This is the AJAX endpoint the booking wizard polls to show a live message while
     * the customer is still filling out the form; register() separately calls
     * get_customer_booking_limit_status() again as the actual, authoritative check before saving.
     */
    public function check_customer_booking_limits(): void
    {
        try {
            method('post');

            check('customer_email', 'string');
            check('service_id', 'numeric');
            check('booking_date', 'date');
            check('exclude_appointment_id', 'numeric|null');

            // jQuery's $.post() serializes a JS `null` as an empty string on the wire, not as an absent field, so
            // both must be treated as "not editing an existing appointment" here.
            $exclude_appointment_id = request('exclude_appointment_id');
            $exclude_appointment_id =
                $exclude_appointment_id !== null && $exclude_appointment_id !== '' ? (int) $exclude_appointment_id : null;

            json_response(
                $this->get_customer_booking_limit_status(
                    request('customer_email'),
                    (int) request('service_id'),
                    request('booking_date'),
                    $exclude_appointment_id,
                ),
            );
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * LNU: Get whether the customer is allowed to make this booking, given the configured customer booking
     * limits, and the message to show them either way (README.md #7). A customer is identified by their email
     * address.
     *
     * @param string $customer_email
     * @param int $service_id
     * @param string $booking_date Start date/time of the appointment being booked.
     * @param int|null $exclude_appointment_id The ID of the appointment being edited, if rescheduling - it is
     * already included among the customer's existing appointments, so it must not also be counted as a new one.
     *
     * @return array{allowed: bool, message: string}
     */
    protected function get_customer_booking_limit_status(
        string $customer_email,
        int $service_id,
        string $booking_date,
        ?int $exclude_appointment_id,
    ): array {
        $is_test_email = in_array($customer_email, explode(';', config('test_email_addresses', '')), true);

        $max_appointments = (int) setting('max_customer_appointments');
        $max_service_bookings = (int) setting('max_customer_service_bookings');
        $limit_period = setting('max_customer_appointments_period');

        $num_appointments = 0;
        $num_service_bookings = 0;
        $next_service_booking_date = null;

        $customer = ['email' => $customer_email];

        if ($this->customers_model->exists($customer)) {
            $customer_id = $this->customers_model->find_record_id($customer);

            $existing_appointments = $this->appointments_model->get(
                ['id_users_customer' => $customer_id],
                null,
                null,
                'start_datetime DESC',
            );

            $period = $this->get_customer_booking_limit_period(new DateTimeImmutable($booking_date), $limit_period);

            $now = new DateTimeImmutable();

            foreach ($existing_appointments as $appointment) {
                $appointment_start = new DateTimeImmutable($appointment['start_datetime']);

                if ($appointment_start >= $period['start'] && $appointment_start <= $period['end']) {
                    $num_appointments++;
                }

                if ((int) $appointment['id_services'] !== $service_id || $appointment_start <= $now) {
                    continue;
                }

                $num_service_bookings++;

                if ((int) $appointment['id'] !== $exclude_appointment_id) {
                    $next_service_booking_date = $appointment['start_datetime'];
                }
            }
        }

        // When editing an existing appointment, it is already included in the counts above, so it should not
        // also be counted as a new one.
        $nth_appointment = $exclude_appointment_id !== null ? $num_appointments : $num_appointments + 1;
        $nth_service_booking = $exclude_appointment_id !== null ? $num_service_bookings : $num_service_bookings + 1;

        $max_appointments_exceeded = $max_appointments > 0 && $nth_appointment > $max_appointments;
        $max_service_bookings_exceeded = $max_service_bookings > 0 && $nth_service_booking > $max_service_bookings;

        $appointments_policy_message = $this->get_appointments_policy_message(
            $max_appointments,
            $nth_appointment,
            $limit_period,
        );

        if ($max_appointments_exceeded) {
            $message = sprintf(lang('disallowed_booking'), $appointments_policy_message);
        } elseif ($max_service_bookings_exceeded) {
            $message = sprintf(
                lang('disallowed_booking'),
                $this->get_service_bookings_policy_message(
                    $max_service_bookings,
                    $nth_service_booking,
                    $next_service_booking_date,
                ),
            );
        } else {
            $message = $max_appointments > 0 ? $appointments_policy_message : '';
        }

        return [
            'allowed' => $is_test_email || (!$max_appointments_exceeded && !$max_service_bookings_exceeded),
            'message' => $message,
        ];
    }

    /**
     * LNU: Get the start and end of the customer booking limit period containing the given date (README.md #7).
     *
     * @param DateTimeImmutable $date A date falling within the period.
     * @param string $period One of 'day', 'week', 'month', 'half-year', 'calendar_year', 'school_year'.
     *
     * @return DateTimeImmutable[] Returns ['start' => DateTimeImmutable, 'end' => DateTimeImmutable].
     */
    protected function get_customer_booking_limit_period(DateTimeImmutable $date, string $period): array
    {
        $start_of_day = $date->setTime(0, 0, 0);

        switch ($period) {
            case 'week':
                $day_names = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

                $first_weekday_index = array_search(setting('first_weekday'), $day_names);

                // PHP's "N" format is ISO-8601 (1 = Monday ... 7 = Sunday); "% 7" folds Sunday from 7 to 0, to
                // match $day_names' 0-indexed (Sunday first) order.
                $current_weekday_index = (int) $date->format('N') % 7;

                $days_since_first_weekday = ($current_weekday_index - $first_weekday_index + 7) % 7;

                $start = $start_of_day->modify("-{$days_since_first_weekday} days");
                $end = $start->modify('+6 days')->setTime(23, 59, 59);
                break;

            case 'month':
                $start = $start_of_day->modify('first day of this month');
                $end = $start_of_day->modify('last day of this month')->setTime(23, 59, 59);
                break;

            case 'half-year':
                $half_start_month = (int) $date->format('n') <= 6 ? 1 : 7;
                $start = $start_of_day->setDate((int) $date->format('Y'), $half_start_month, 1);
                $end = $start->modify('+5 months')->modify('last day of this month')->setTime(23, 59, 59);
                break;

            case 'calendar_year':
                $year = (int) $date->format('Y');
                $start = $start_of_day->setDate($year, 1, 1);
                $end = $start_of_day->setDate($year, 12, 31)->setTime(23, 59, 59);
                break;

            case 'school_year':
                $start_year = (int) $date->format('n') <= 6 ? (int) $date->format('Y') - 1 : (int) $date->format('Y');
                $start = $start_of_day->setDate($start_year, 7, 1);
                $end = $start_of_day->setDate($start_year + 1, 6, 30)->setTime(23, 59, 59);
                break;

            case 'day':
            default:
                $start = $start_of_day;
                $end = $start_of_day->setTime(23, 59, 59);
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * LNU: Build the "you can book at most N appointments during X" policy message (README.md #7).
     */
    private function get_appointments_policy_message(int $max_appointments, int $nth_appointment, string $limit_period): string
    {
        return sprintf(
            lang('allowed_bookings_policy'),
            $this->get_cardinal_word($max_appointments),
            $max_appointments === 1 ? lang('appointment_lc') : lang('appointments_lc'),
            lang('each_' . $limit_period),
            $this->get_ordinal_word($nth_appointment),
        );
    }

    /**
     * LNU: Build the "you can have at most N active bookings for this service" policy message (README.md #7).
     */
    private function get_service_bookings_policy_message(
        int $max_service_bookings,
        int $nth_service_booking,
        ?string $next_service_booking_date,
    ): string {
        $next_booking_datetime = $next_service_booking_date ? new DateTimeImmutable($next_service_booking_date) : null;

        return sprintf(
            lang('active_bookings_policy'),
            $this->get_cardinal_word($max_service_bookings),
            $max_service_bookings === 1 ? lang('active_booking') : lang('active_bookings'),
            $this->get_ordinal_word($nth_service_booking),
            $next_booking_datetime ? $next_booking_datetime->format('Y-m-d') : '',
            $next_booking_datetime ? $next_booking_datetime->format('H:i') : '',
        );
    }

    /**
     * LNU: Get the word for a small cardinal number (README.md #7), eg. 3 -> "three", falling back to the digit
     * itself for anything larger.
     */
    private function get_cardinal_word(int $number): string
    {
        $words = ['one', 'two', 'three', 'four', 'five'];

        return $number >= 1 && $number <= count($words) ? lang($words[$number - 1]) : (string) $number;
    }

    /**
     * LNU: Get the word for a small ordinal number (README.md #7), eg. 3 -> "third", falling back to "Nth" for
     * anything larger.
     */
    private function get_ordinal_word(int $number): string
    {
        $words = ['first', 'second', 'third', 'fourth', 'fifth'];

        return $number >= 1 && $number <= count($words) ? lang($words[$number - 1]) : $number . '.';
    }
}
