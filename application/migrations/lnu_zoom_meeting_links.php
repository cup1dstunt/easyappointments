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
 * LNU: Zoom Meeting Links (README.md #14).
 *
 * Adds the per-provider "create_zoom_links" opt-in column, the "zoom_meeting_id"/"zoom_start_link" appointment
 * columns, and seeds the account-wide Zoom credential settings (configurable via Settings > Integrations >
 * Zoom). The join link itself is not a separate column - it's stored in the existing generic "meeting_link"
 * column shared with Jitsi/Google Meet/manual links, since only the host-only "start" link needs to stay out
 * of the customer-facing fields.
 */
class Migration_Zoom_meeting_links extends EA_Migration
{
    private const SETTINGS_FIELD_NAMES = ['zoom_client_id', 'zoom_client_secret', 'zoom_account_id'];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        if (!$this->db->field_exists('create_zoom_links', 'users')) {
            $this->dbforge->add_column('users', [
                'create_zoom_links' => [
                    'type' => 'TINYINT',
                    'constraint' => '4',
                    'default' => '0',
                    'after' => 'language',
                ],
            ]);

            $changed = true;
        }

        if (!$this->db->field_exists('zoom_meeting_id', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'zoom_meeting_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'null' => true,
                    'after' => 'meeting_link',
                ],
            ]);

            $changed = true;
        }

        if (!$this->db->field_exists('zoom_start_link', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'zoom_start_link' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'zoom_meeting_id',
                ],
            ]);

            $changed = true;
        }

        foreach (self::SETTINGS_FIELD_NAMES as $field_name) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->insert('settings', [
                    'name' => $field_name,
                    'value' => config($field_name, ''),
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

        if ($this->db->field_exists('create_zoom_links', 'users')) {
            $this->dbforge->drop_column('users', 'create_zoom_links');

            $changed = true;
        }

        foreach (['zoom_start_link', 'zoom_meeting_id'] as $field_name) {
            if ($this->db->field_exists($field_name, 'appointments')) {
                $this->dbforge->drop_column('appointments', $field_name);

                $changed = true;
            }
        }

        foreach (self::SETTINGS_FIELD_NAMES as $field_name) {
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->delete('settings', ['name' => $field_name]);

                $changed = true;
            }
        }

        return $changed;
    }
}
