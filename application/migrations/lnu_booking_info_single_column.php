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
 * LNU: Booking info can use single column always.
 */
class Migration_Booking_info_single_column extends EA_Migration
{
    private const FIELD_NAME = 'booking_info_single_column';

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if it already existed.
     */
    public function up(): bool
    {
        if ($this->db->get_where('settings', ['name' => self::FIELD_NAME])->num_rows()) {
            return false;
        }

        $this->db->insert('settings', [
            'name' => self::FIELD_NAME,
            'value' => '0',
        ]);

        return true;
    }

    /**
     * Downgrade method.
     *
     * @return bool True if any change was applied, false if it was already reverted.
     */
    public function down(): bool
    {
        if (!$this->db->get_where('settings', ['name' => self::FIELD_NAME])->num_rows()) {
            return false;
        }

        $this->db->delete('settings', ['name' => self::FIELD_NAME]);

        return true;
    }
}
