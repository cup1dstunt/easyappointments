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
 * Appointments model.
 *
 * @package Models
 */
class Appointments_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'is_unavailability' => 'boolean',
        'id_users_provider' => 'integer',
        'id_users_customer' => 'integer',
        'id_services' => 'integer',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'book' => 'book_datetime',
        'start' => 'start_datetime',
        'end' => 'end_datetime',
        'location' => 'location',
        'meetingLink' => 'meeting_link',
        'color' => 'color',
        'status' => 'status',
        'notes' => 'notes',
        'hash' => 'hash',
        'serviceId' => 'id_services',
        'providerId' => 'id_users_provider',
        'customerId' => 'id_users_customer',
        'googleCalendarId' => 'id_google_calendar',
        'caldavCalendarId' => 'id_caldav_calendar',
    ];

    /**
     * Appointments_model constructor.
     * Configurable number of appointment custom fields added dynamically.
     */
    function __construct() {
        parent::__construct();
        for ($i = 1; $i <= config('max_appt_custom_fields', 5); $i++) {
            $this->api_resource['apptCustomField' . $i] = 'appt_custom_field_' . $i;
        }
    }

    /**
     * Save (insert or update) an appointment.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @return int Returns the appointment ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $appointment): int
    {
        $this->validate($appointment);

        if (empty($appointment['id'])) {
            return $this->insert($appointment);
        } else {
            return $this->update($appointment);
        }
    }

    /**
     * Validate the appointment data.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $appointment): void
    {
        // If an appointment ID is provided then check whether the record really exists in the database.
        if (!empty($appointment['id'])) {
            $count = $this->db->get_where('appointments', ['id' => $appointment['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided appointment ID does not exist in the database: ' . $appointment['id'],
                );
            }
        }

        // Make sure all required fields are provided.

        $require_notes = filter_var(setting('require_notes'), FILTER_VALIDATE_BOOLEAN);

        if (
            empty($appointment['start_datetime']) ||
            empty($appointment['end_datetime']) ||
            empty($appointment['id_services']) ||
            empty($appointment['id_users_provider']) ||
            empty($appointment['id_users_customer']) ||
            (empty($appointment['notes']) && $require_notes)
        ) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($appointment, true));
        }

        // Make sure that the provided appointment date time values are valid.
        if (!validate_datetime($appointment['start_datetime'])) {
            throw new InvalidArgumentException('The appointment start date time is invalid.');
        }

        if (!validate_datetime($appointment['end_datetime'])) {
            throw new InvalidArgumentException('The appointment end date time is invalid.');
        }

        // Make the appointment lasts longer than the minimum duration (in minutes).
        $diff = (strtotime($appointment['end_datetime']) - strtotime($appointment['start_datetime'])) / 60;

        if ($diff < EVENT_MINIMUM_DURATION) {
            throw new InvalidArgumentException(
                'The appointment duration cannot be less than ' . EVENT_MINIMUM_DURATION . ' minutes.',
            );
        }

        // Make sure the provider ID really exists in the database.
        $count = $this->db
            ->select()
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.id', $appointment['id_users_provider'])
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get()
            ->num_rows();

        if (!$count) {
            throw new InvalidArgumentException(
                'The appointment provider ID was not found in the database: ' . $appointment['id_users_provider'],
            );
        }

        if (!filter_var($appointment['is_unavailability'], FILTER_VALIDATE_BOOLEAN)) {
            // Make sure the customer ID really exists in the database.
            $count = $this->db
                ->select()
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'inner')
                ->where('users.id', $appointment['id_users_customer'])
                ->where('roles.slug', DB_SLUG_CUSTOMER)
                ->get()
                ->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The appointment customer ID was not found in the database: ' . $appointment['id_users_customer'],
                );
            }

            // Make sure the service ID really exists in the database.
            $count = $this->db->get_where('services', ['id' => $appointment['id_services']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException('Appointment service id is invalid.');
            }
        }
    }

    /**
     * Get all appointments that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of appointments.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $appointments = $this->db
            ->get_where('appointments', ['is_unavailability' => false], $limit, $offset)
            ->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Insert a new appointment into the database.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @return int Returns the appointment ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $appointment): int
    {
        $appointment['book_datetime'] = date('Y-m-d H:i:s');
        $appointment['create_datetime'] = date('Y-m-d H:i:s');
        $appointment['update_datetime'] = date('Y-m-d H:i:s');
        $appointment['hash'] = random_string('alnum', 12);

        if (!$this->db->insert('appointments', $appointment)) {
            throw new RuntimeException('Could not insert appointment.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing appointment.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @return int Returns the appointment ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $appointment): int
    {
        $appointment['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->update('appointments', $appointment, ['id' => $appointment['id']])) {
            throw new RuntimeException('Could not update appointment record.');
        }

        return $appointment['id'];
    }

    /**
     * Get a specific appointment from the database.
     *
     * @param int $appointment_id The ID of the record to be returned.
     *
     * @return array Returns an array with the appointment data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $appointment_id): array
    {
        $appointment = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();

        if (!$appointment) {
            throw new InvalidArgumentException(
                'The provided appointment ID was not found in the database: ' . $appointment_id,
            );
        }

        $this->cast($appointment);

        return $appointment;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $appointment_id Appointment ID.
     * @param string $field Name of the value to be returned.
     *
     * @return mixed Returns the selected appointment value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $appointment_id, string $field): mixed
    {
        if (empty($field)) {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($appointment_id)) {
            throw new InvalidArgumentException('The appointment ID argument cannot be empty.');
        }

        // Check whether the appointment exists.
        $query = $this->db->get_where('appointments', ['id' => $appointment_id]);

        if (!$query->num_rows()) {
            throw new InvalidArgumentException(
                'The provided appointment ID was not found in the database: ' . $appointment_id,
            );
        }

        // Check if the required field is part of the appointment data.
        $appointment = $query->row_array();

        $this->cast($appointment);

        if (!array_key_exists($field, $appointment)) {
            throw new InvalidArgumentException('The requested field was not found in the appointment data: ' . $field);
        }

        return $appointment[$field];
    }

    /**
     * Remove all the Google Calendar event IDs from appointment records.
     *
     * @param int $provider_id Matching provider ID.
     */
    public function clear_google_sync_ids(int $provider_id): void
    {
        $this->db->update('appointments', ['id_google_calendar' => null], ['id_users_provider' => $provider_id]);
    }

    /**
     * Remove all the Google Calendar event IDs from appointment records.
     *
     * @param int $provider_id Matching provider ID.
     */
    public function clear_caldav_sync_ids(int $provider_id): void
    {
        $this->db->update('appointments', ['id_caldav_calendar' => null], ['id_users_provider' => $provider_id]);
    }

    /**
     * Deletes recurring CalDAV events for the provided date period.
     *
     * @param string $start_date_time
     * @param string $end_date_time
     *
     * @return void
     */
    public function delete_caldav_recurring_events(string $start_date_time, string $end_date_time): void
    {
        $this->db
            ->where('start_datetime >=', $start_date_time)
            ->where('end_datetime <=', $end_date_time)
            ->where('is_unavailability', true)
            ->like('id_caldav_calendar', 'RECURRENCE')
            ->delete('appointments');
    }

    /**
     * Remove an existing appointment from the database.
     *
     * @param int $appointment_id Appointment ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $appointment_id): void
    {
        $this->db->delete('appointments', ['id' => $appointment_id]);
    }

    /**
     * Get the attendants number for the requested period.
     *
     * @param DateTime $start Period start.
     * @param DateTime $end Period end.
     * @param int $service_id Service ID.
     * @param int $provider_id Provider ID.
     * @param int|null $exclude_appointment_id Exclude an appointment from the result set.
     *
     * @return int Returns the number of appointments that match the provided criteria.
     */
    public function get_attendants_number_for_period(
        DateTime $start,
        DateTime $end,
        int $service_id,
        int $provider_id,
        ?int $exclude_appointment_id = null,
    ): int {
        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        $result = $this->db
            ->select('count(*) AS attendants_number')
            ->from('appointments')
            ->group_start()
            ->group_start()
            ->where('start_datetime <=', $start->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $start->format('Y-m-d H:i:s'))
            ->group_end()
            ->or_group_start()
            ->where('start_datetime <', $end->format('Y-m-d H:i:s'))
            ->where('end_datetime >=', $end->format('Y-m-d H:i:s'))
            ->group_end()
            ->group_end()
            ->where('id_services', $service_id)
            ->where('id_users_provider', $provider_id)
            ->get()
            ->row_array();

        return $result['attendants_number'];
    }

    /**
     *
     * Returns the number of the other service attendants number for the provided time slot.
     *
     * @param DateTime $start Period start.
     * @param DateTime $end Period end.
     * @param int $service_id Service ID.
     * @param int $provider_id Provider ID.
     * @param int|null $exclude_appointment_id Exclude an appointment from the result set.
     *
     * @return int Returns the number of appointments that match the provided criteria.
     */
    public function get_other_service_attendants_number(
        DateTime $start,
        DateTime $end,
        int $service_id,
        int $provider_id,
        ?int $exclude_appointment_id = null,
    ): int {
        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        $result = $this->db
            ->select('count(*) AS attendants_number')
            ->from('appointments')
            ->group_start()
            ->group_start()
            ->where('start_datetime <=', $start->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $start->format('Y-m-d H:i:s'))
            ->group_end()
            ->or_group_start()
            ->where('start_datetime <', $end->format('Y-m-d H:i:s'))
            ->where('end_datetime >=', $end->format('Y-m-d H:i:s'))
            ->group_end()
            ->group_end()
            ->where('id_services !=', $service_id)
            ->where('id_users_provider', $provider_id)
            ->get()
            ->row_array();

        return $result['attendants_number'];
    }

    /**
     * Get the query builder interface, configured for use with the appointments table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        return $this->db->from('appointments');
    }

    /**
     * Search appointments by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of appointments.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $appointments = $this->db
            ->select('appointments.*')
            ->from('appointments')
            ->join('services', 'services.id = appointments.id_services', 'left')
            ->join('users AS providers', 'providers.id = appointments.id_users_provider', 'inner')
            ->join('users AS customers', 'customers.id = appointments.id_users_customer', 'left')
            ->where('is_unavailability', false)
            ->group_start()
            ->like('appointments.start_datetime', $keyword)
            ->or_like('appointments.end_datetime', $keyword)
            ->or_like('appointments.location', $keyword)
            ->or_like('appointments.hash', $keyword)
            ->or_like('appointments.notes', $keyword)
            ->or_like('services.name', $keyword)
            ->or_like('services.description', $keyword)
            ->or_like('providers.first_name', $keyword)
            ->or_like('providers.last_name', $keyword)
            ->or_like('providers.email', $keyword)
            ->or_like('providers.phone_number', $keyword)
            ->or_like('customers.first_name', $keyword)
            ->or_like('customers.last_name', $keyword)
            ->or_like('customers.email', $keyword)
            ->or_like('customers.phone_number', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($this->quote_order_by($order_by))
            ->get()
            ->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Get appointments as options for dropdowns.
     *
     * @param array|string|null $where Where conditions.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function to_options(array|string|null $where = null): array
    {
        if ($where !== null) {
            $this->db->where($where);
        }

        $appointments = $this->db
            ->select('appointments.id, appointments.start_datetime, services.name AS service_name')
            ->from('appointments')
            ->join('services', 'services.id = appointments.id_services', 'left')
            ->where('is_unavailability', false)
            ->order_by('start_datetime', 'DESC')
            ->get()
            ->result_array();

        $options = [];

        foreach ($appointments as $appointment) {
            $options[] = [
                'value' => (int) $appointment['id'],
                'label' => $appointment['start_datetime'] . ' - ' . ($appointment['service_name'] ?? 'N/A'),
            ];
        }

        return $options;
    }

    /**
     * Load related resources to an appointment.
     *
     * @param array $appointment Associative array with the appointment data.
     * @param array $resources Resource names to be attached ("service", "provider", "customer" supported).
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$appointment, array $resources): void
    {
        if (empty($appointment) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            switch ($resource) {
                case 'service':
                    $appointment['service'] = $this->db
                        ->get_where('services', [
                            'id' => $appointment['id_services'] ?? ($appointment['serviceId'] ?? null),
                        ])
                        ->row_array();
                    break;

                case 'provider':
                    $appointment['provider'] = $this->db
                        ->get_where('users', [
                            'id' => $appointment['id_users_provider'] ?? ($appointment['providerId'] ?? null),
                        ])
                        ->row_array();
                    break;

                case 'customer':
                    $appointment['customer'] = $this->db
                        ->get_where('users', [
                            'id' => $appointment['id_users_customer'] ?? ($appointment['customerId'] ?? null),
                        ])
                        ->row_array();
                    break;

                default:
                    throw new InvalidArgumentException(
                        'The requested appointment relation is not supported: ' . $resource,
                    );
            }
        }
    }

    /**
     * Convert the database appointment record to the equivalent API resource.
     *
     * @param array $appointment Appointment data.
     */
    public function api_encode(array &$appointment): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $appointment) ? (int) $appointment['id'] : null,
            'book' => $appointment['book_datetime'],
            'start' => $appointment['start_datetime'],
            'end' => $appointment['end_datetime'],
            'hash' => $appointment['hash'],
            'color' => $appointment['color'],
            'status' => $appointment['status'],
            'location' => $appointment['location'],
            'notes' => $appointment['notes'],
            'customerId' => $appointment['id_users_customer'] !== null ? (int) $appointment['id_users_customer'] : null,
            'providerId' => $appointment['id_users_provider'] !== null ? (int) $appointment['id_users_provider'] : null,
            'serviceId' => $appointment['id_services'] !== null ? (int) $appointment['id_services'] : null,
            'meetingLink' => $appointment['meeting_link'],
            'googleCalendarId' =>
                $appointment['id_google_calendar'] !== null ? $appointment['id_google_calendar'] : null,
            'caldavCalendarId' =>
                $appointment['id_caldav_calendar'] !== null ? $appointment['id_caldav_calendar'] : null,
        ];

        for ($i = 1; $i <= config('max_appt_custom_fields', 5); $i++) {
            $encoded_resource['apptCustomField' . $i] = $appointment['appt_custom_field_' . $i];
        }

        $encoded_resource['attachedFileNames'] = !empty($appointment['id'])
            ? $this->get_attached_files((int) $appointment['id'])
            : [];

        $appointment = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database appointment record.
     *
     * @param array $appointment API resource.
     * @param array|null $base Base appointment data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$appointment, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $appointment)) {
            $decoded_resource['id'] = $appointment['id'];
        }

        if (array_key_exists('book', $appointment)) {
            $decoded_resource['book_datetime'] = $appointment['book'];
        }

        if (array_key_exists('start', $appointment)) {
            $decoded_resource['start_datetime'] = $appointment['start'];
        }

        if (array_key_exists('end', $appointment)) {
            $decoded_resource['end_datetime'] = $appointment['end'];
        }

        if (array_key_exists('hash', $appointment)) {
            $decoded_resource['hash'] = $appointment['hash'];
        }

        if (array_key_exists('color', $appointment)) {
            $decoded_resource['color'] = $appointment['color'];
        }

        if (array_key_exists('location', $appointment)) {
            $decoded_resource['location'] = $appointment['location'];
        }

        if (array_key_exists('status', $appointment)) {
            $decoded_resource['status'] = $appointment['status'];
        }

        if (array_key_exists('notes', $appointment)) {
            $decoded_resource['notes'] = $appointment['notes'];
        }

        if (array_key_exists('customerId', $appointment)) {
            $decoded_resource['id_users_customer'] = $appointment['customerId'];
        }

        if (array_key_exists('providerId', $appointment)) {
            $decoded_resource['id_users_provider'] = $appointment['providerId'];
        }

        if (array_key_exists('serviceId', $appointment)) {
            $decoded_resource['id_services'] = $appointment['serviceId'];
        }

        if (array_key_exists('googleCalendarId', $appointment)) {
            $decoded_resource['id_google_calendar'] = $appointment['googleCalendarId'];
        }

        if (array_key_exists('caldavCalendarId', $appointment)) {
            $decoded_resource['id_caldav_calendar'] = $appointment['caldavCalendarId'];
        }

        if (array_key_exists('meetingLink', $appointment)) {
            $decoded_resource['meeting_link'] = $appointment['meetingLink'];
        }

        for ($i = 1; $i <= config('max_appt_custom_fields', 5); $i++) {
            if (array_key_exists('apptCustomField' . $i, $appointment)) {
                $decoded_resource['appt_custom_field_' . $i] = $appointment['apptCustomField' . $i];
            }
        }

        $decoded_resource['is_unavailability'] = false;

        $appointment = $decoded_resource;
    }

    /**
     * Calculate the end date time of an appointment based on the selected service.
     *
     * @param array $appointment Appointment data.
     *
     * @return string Returns the end date time value.
     *
     * @throws Exception
     */
    public function calculate_end_datetime(array $appointment): string
    {
        $duration = $this->db->get_where('services', ['id' => $appointment['id_services']])?->row()?->duration;

        $end_date_time_object = new DateTime($appointment['start_datetime']);

        $end_date_time_object->add(new DateInterval('PT' . $duration . 'M'));

        return $end_date_time_object->format('Y-m-d H:i:s');
    }

    /**
     * Check if the provider has a conflicting appointment at the given time period.
     *
     * @param int $provider_id Provider ID.
     * @param string $start_datetime Start date time of the appointment.
     * @param string $end_datetime End date time of the appointment.
     * @param int|null $exclude_appointment_id Exclude an appointment from the conflict check (useful for updates).
     *
     * @return bool Returns true if there is a conflict, false otherwise.
     */
    public function has_provider_conflict(
        int $provider_id,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null,
    ): bool {
        $this->db->select('id')->from('appointments')->where('id_users_provider', $provider_id);

        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        // Check for overlapping appointments:
        // An overlap occurs when:  (existing_start < new_end) AND (existing_end > new_start)

        return $this->db
            ->group_start()
            ->where('start_datetime <', $end_datetime)
            ->where('end_datetime >', $start_datetime)
            ->group_end()
            ->get()
            ->num_rows() > 0;
    }

    /**
     * LNU: Support for Attached Files (README.md #2).
     *
     * Each appointment's attached files live in their own directory, named after the appointment ID, under
     * storage/uploads/. This is the single source of truth for which files are attached to an appointment -
     * there is no database column to keep in sync.
     */

    /**
     * Get the directory where an appointment's attached files are stored.
     *
     * @param int $appointment_id Appointment ID.
     *
     * @return string Relative path to the appointment's attached files directory.
     */
    public function get_attached_files_directory(int $appointment_id): string
    {
        return 'storage/uploads/' . $appointment_id;
    }

    /**
     * Get the list of attached file names for an appointment.
     *
     * @param int $appointment_id Appointment ID.
     *
     * @return string[] File names, sorted alphabetically.
     */
    public function get_attached_files(int $appointment_id): array
    {
        $directory = $this->get_attached_files_directory($appointment_id);

        if (!is_dir($directory)) {
            return [];
        }

        $files = array_values(array_diff(scandir($directory) ?: [], ['.', '..']));

        sort($files);

        return $files;
    }

    /**
     * Validate and store an uploaded file as one of an appointment's attached files.
     *
     * If a file with the same name is already attached to the appointment, the new file is saved under a
     * disambiguated name (e.g. "invoice (1).pdf") rather than overwriting it.
     *
     * @param int $appointment_id Appointment ID.
     * @param string $file_field_name The $_FILES key to read the upload from (e.g. "attached_file_data_1").
     *
     * @return string|null The stored file name, or null if no file was provided for this field.
     *
     * @throws RuntimeException If the upload is invalid, too large, or not an allowed file type.
     */
    public function save_attached_file(int $appointment_id, string $file_field_name): ?string
    {
        if (!isset($_FILES[$file_field_name]) || $_FILES[$file_field_name]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (!isset($_FILES[$file_field_name]['error']) || is_array($_FILES[$file_field_name]['error'])) {
            throw new RuntimeException(lang('invalid_parameters'));
        }

        switch ($_FILES[$file_field_name]['error']) {
            case UPLOAD_ERR_OK:
                break;

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException(lang('system_file_size_exceeded'));

            default:
                throw new RuntimeException(lang('unknown_error'));
        }

        $tmp_name = $_FILES[$file_field_name]['tmp_name'];
        $size = (int) $_FILES[$file_field_name]['size'];
        $original_name = basename($_FILES[$file_field_name]['name']);

        $max_size = (int) setting('attached_files_max_size');

        if ($size > $max_size) {
            $this->load->helper('number');

            throw new RuntimeException(sprintf(lang('attached_files_max_size_exceeded'), byte_format($max_size)));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($tmp_name);

        $allowed_types = array_map('trim', explode(',', (string) setting('attached_files_allowed_types')));
        $mimes = get_mimes();
        $allowed_mime_types = [];

        foreach ($allowed_types as $allowed_type) {
            $allowed_type = trim($allowed_type, '. ');

            if (array_key_exists($allowed_type, $mimes)) {
                $allowed_mime_types = array_merge($allowed_mime_types, (array) $mimes[$allowed_type]);
            } elseif (in_array($allowed_type, $mimes, true)) {
                $allowed_mime_types[] = $allowed_type;
            }
        }

        if (!in_array($mime_type, $allowed_mime_types, true)) {
            throw new RuntimeException(
                sprintf(lang('attached_files_invalid_format'), lang(setting('attached_files_allowed_types_hint', ''))),
            );
        }

        $directory = $this->get_attached_files_directory($appointment_id);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(lang('failed_to_move_file'));
        }

        $final_name = $this->disambiguate_attached_file_name($directory, $original_name);

        if (!move_uploaded_file($tmp_name, $directory . '/' . $final_name)) {
            throw new RuntimeException(lang('failed_to_move_file'));
        }

        return $final_name;
    }

    /**
     * Delete one of an appointment's attached files.
     *
     * @param int $appointment_id Appointment ID.
     * @param string $filename File name to delete.
     */
    public function delete_attached_file(int $appointment_id, string $filename): void
    {
        $path = $this->get_attached_files_directory($appointment_id) . '/' . basename($filename);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Delete every attached file for an appointment, along with its directory.
     *
     * @param int $appointment_id Appointment ID.
     */
    public function delete_attached_files(int $appointment_id): void
    {
        $directory = $this->get_attached_files_directory($appointment_id);

        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $file) {
            unlink($directory . '/' . $file);
        }

        rmdir($directory);
    }

    /**
     * Find a file name that does not already exist in the given directory, appending " (1)", " (2)", etc.
     * to the base name as needed.
     *
     * @param string $directory Directory to check for a collision.
     * @param string $filename Desired file name.
     *
     * @return string A file name that does not currently exist in the directory.
     */
    private function disambiguate_attached_file_name(string $directory, string $filename): string
    {
        $path_info = pathinfo($filename);
        $name = $path_info['filename'];
        $extension = isset($path_info['extension']) && $path_info['extension'] !== '' ? '.' . $path_info['extension'] : '';

        $candidate = $filename;

        for ($suffix = 1; file_exists($directory . '/' . $candidate); $suffix++) {
            $candidate = $name . ' (' . $suffix . ')' . $extension;
        }

        return $candidate;
    }
}
