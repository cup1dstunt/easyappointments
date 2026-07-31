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
 * LNU: Cooldown period for services (README.md #5).
 *
 * The "cooldown" column holds the number of minutes, out of the service's "duration", that are a buffer after
 * the appointment rather than the actual customer-facing meeting time. "duration" itself always represents the
 * full blocked timeslot (meeting time + cooldown) - see Services_model for where the customer-facing duration
 * (duration - cooldown) is derived.
 */
class Migration_Service_cooldown extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): bool
    {
        if ($this->db->field_exists('cooldown', 'services')) {
            return false;
        }

        $this->dbforge->add_column('services', [
            'cooldown' => [
                'type' => 'INT',
                'constraint' => '11',
                'default' => '0',
                'after' => 'duration',
            ],
        ]);

        return true;
    }

    /**
     * Downgrade method.
     */
    public function down(): bool
    {
        if (!$this->db->field_exists('cooldown', 'services')) {
            return false;
        }

        $this->dbforge->drop_column('services', 'cooldown');

        return true;
    }
}
