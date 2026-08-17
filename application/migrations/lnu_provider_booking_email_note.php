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
 * LNU: Provider Booking Email Note (README.md #16).
 *
 * Adds the per-provider "booking_email_note" column - an optional free-text note a provider can set
 * (Users > Providers), shown to the customer in the appointment-saved confirmation email whenever it's
 * non-empty. The value goes through lang() when displayed (see appointment_saved_email.php), the same
 * fallback-to-literal pattern already used for other admin-authored content (e.g. the terms-of-use step) - so
 * it can be a plain note, or a translation key if the provider wants it to vary by the customer's chosen
 * language.
 */
class Migration_Provider_booking_email_note extends EA_Migration
{
    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        if ($this->db->field_exists('booking_email_note', 'users')) {
            return false;
        }

        $this->dbforge->add_column('users', [
            'booking_email_note' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'notes',
            ],
        ]);

        return true;
    }

    /**
     * Downgrade method.
     *
     * @return bool True if any change was applied, false if everything was already reverted.
     */
    public function down(): bool
    {
        if (!$this->db->field_exists('booking_email_note', 'users')) {
            return false;
        }

        $this->dbforge->drop_column('users', 'booking_email_note');

        return true;
    }
}
