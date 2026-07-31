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
 * LNU: Customer Booking Limits (README.md #7).
 */
class Migration_Customer_booking_limits extends EA_Migration
{
    private const FIELD_DEFAULTS = [
        'max_customer_appointments' => 0,
        'max_customer_appointments_period' => 'day',
        'max_customer_service_bookings' => 0,
    ];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        foreach (self::FIELD_DEFAULTS as $field_name => $default_value) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->insert('settings', [
                    'name' => $field_name,
                    'value' => $default_value,
                ]);

                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * Downgrade method.
     *
     * @return bool True if any change was applied, false if everything was already reverted.
     */
    public function down(): bool
    {
        $changed = false;

        foreach (self::FIELD_DEFAULTS as $field_name => $default_value) {
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->delete('settings', ['name' => $field_name]);

                $changed = true;
            }
        }

        return $changed;
    }
}
