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
 * LNU: Provider meeting link.
 *
 * Adds the per-provider "meeting_link" column - the link to the provider's online meeting room. New appointments of
 * the provider take it over as their meeting link (unless Jitsi or Google Meet generated one), so it shows up in the
 * confirmation email ({meeting_link}) and in the calendar entry.
 */
class Migration_Provider_meeting_link extends EA_Migration
{
    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        if ($this->db->field_exists('meeting_link', 'users')) {
            return false;
        }

        $this->dbforge->add_column('users', [
            'meeting_link' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
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
        if (!$this->db->field_exists('meeting_link', 'users')) {
            return false;
        }

        $this->dbforge->drop_column('users', 'meeting_link');

        return true;
    }
}
