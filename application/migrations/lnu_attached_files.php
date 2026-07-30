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
 * LNU: Support for Attached Files (README.md #2).
 *
 * Attached files themselves are not tracked in the database - they live directly under
 * storage/uploads/{appointment_id}/, which is the single source of truth for what is attached to an
 * appointment (see Appointments_model::get_attached_files() and friends). This migration only manages the
 * feature's settings rows.
 */
class Migration_Attached_files extends EA_Migration
{
    // The default value for each field name, set in the up() method,
    // depends on there being a corresponding config (having the same name as the field name),
    // with a fallback value of '0' in case the config does not exist
    private const FIELD_NAMES = [
        'attached_files_supported',
        'max_attached_files',
        'attached_files_max_size',
        'attached_files_allowed_types',
        'attached_files_allowed_types_hint',
    ];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        foreach (self::FIELD_NAMES as $field_name) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->insert('settings', [
                    'name' => $field_name,
                    'value' => config($field_name, 0),
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

        foreach (self::FIELD_NAMES as $field_name) {
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->delete('settings', ['name' => $field_name]);

                $changed = true;
            }
        }

        return $changed;
    }
}
