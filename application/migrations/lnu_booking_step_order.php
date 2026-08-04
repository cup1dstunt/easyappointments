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
 * LNU: Configurable order for booking wizard steps.
 *
 * "booking_step_order" is a '>'-separated list of step names (service, time, info, confirmation), eg.
 * "time>service>info>confirmation". It's only actually used while "booking_step_order_enabled" is on -
 * Booking.php falls back to config('default_booking_step_order') otherwise, or if the custom value is ever
 * invalid, rather than breaking the booking page for customers.
 */
class Migration_Booking_step_order extends EA_Migration
{
    private const FIELD_NAMES = ['booking_step_order_enabled', 'booking_step_order'];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        foreach (self::FIELD_NAMES as $field_name) {
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                continue;
            }

            $default_value = $field_name === 'booking_step_order' ? config('default_booking_step_order') : '0';

            $this->db->insert('settings', [
                'name' => $field_name,
                'value' => $default_value,
            ]);

            $changed = true;
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

        foreach (self::FIELD_NAMES as $field_name) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                continue;
            }

            $this->db->delete('settings', ['name' => $field_name]);

            $changed = true;
        }

        return $changed;
    }
}
