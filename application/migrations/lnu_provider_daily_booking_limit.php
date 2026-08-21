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
 * LNU: Provider Daily Booking Limit.
 *
 * Adds the per-provider "max_appointments_per_day" column. A value of "0" means no limit.
 */
class Migration_Provider_daily_booking_limit extends EA_Migration
{
    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        if ($this->db->field_exists('max_appointments_per_day', 'users')) {
            return false;
        }

        $this->dbforge->add_column('users', [
            'max_appointments_per_day' => [
                'type' => 'INT',
                'constraint' => '11',
                'default' => '0',
                'after' => 'language',
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
        if (!$this->db->field_exists('max_appointments_per_day', 'users')) {
            return false;
        }

        $this->dbforge->drop_column('users', 'max_appointments_per_day');

        return true;
    }
}
