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
 * LNU: Provider Colour in Appointments (README.md #10).
 *
 * The "color" column holds the provider's calendar-marking color. An empty value means "no colour" (no marking
 * shown on the provider's appointments in the calendar) and is the default for both existing and newly created
 * providers - see Providers_model/calendar_default_view.js for where this is read.
 */
class Migration_Provider_color extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): bool
    {
        if ($this->db->field_exists('color', 'users')) {
            return false;
        }

        $this->dbforge->add_column('users', [
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'default' => '',
            ],
        ]);

        return true;
    }

    /**
     * Downgrade method.
     */
    public function down(): bool
    {
        if (!$this->db->field_exists('color', 'users')) {
            return false;
        }

        $this->dbforge->drop_column('users', 'color');

        return true;
    }
}
