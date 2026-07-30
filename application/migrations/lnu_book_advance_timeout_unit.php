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
 * LNU: Unit Selection for Booking Advance Timeout (README.md #4).
 *
 * Also seeds "new_booking_advance_timeout" - a separate advance-timeout value for new bookings, so it can be
 * configured independently from "book_advance_timeout" (which now only governs rescheduling/cancellation). Both
 * share the single "book_advance_timeout_unit" setting.
 */
class Migration_Book_advance_timeout_unit extends EA_Migration
{
    private const UNIT_FIELD_NAME = 'book_advance_timeout_unit';
    private const NEW_BOOKING_FIELD_NAME = 'new_booking_advance_timeout';

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        if (!$this->db->get_where('settings', ['name' => self::UNIT_FIELD_NAME])->num_rows()) {
            $this->db->insert('settings', [
                'name' => self::UNIT_FIELD_NAME,
                'value' => config('default_book_advance_timeout_unit'),
            ]);

            $changed = true;
        }

        if (!$this->db->get_where('settings', ['name' => self::NEW_BOOKING_FIELD_NAME])->num_rows()) {
            // Seed with whatever "book_advance_timeout" is already set to, so upgrading an existing
            // installation doesn't change new-booking behavior until an admin explicitly configures it
            // separately from the rescheduling/cancellation timeout.
            $book_advance_timeout = $this->db->get_where('settings', ['name' => 'book_advance_timeout'])->row_array();

            $this->db->insert('settings', [
                'name' => self::NEW_BOOKING_FIELD_NAME,
                'value' => $book_advance_timeout['value'] ?? '30',
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

        if ($this->db->get_where('settings', ['name' => self::UNIT_FIELD_NAME])->num_rows()) {
            $this->db->delete('settings', ['name' => self::UNIT_FIELD_NAME]);

            $changed = true;
        }

        if ($this->db->get_where('settings', ['name' => self::NEW_BOOKING_FIELD_NAME])->num_rows()) {
            $this->db->delete('settings', ['name' => self::NEW_BOOKING_FIELD_NAME]);

            $changed = true;
        }

        return $changed;
    }
}
