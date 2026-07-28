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
 * LNU: Improvements to Custom Fields (README.md #1).
 */
class Migration_Custom_fields extends EA_Migration
{
    /**
     * Upstream's native 050/051 migrations already add custom_field_1..5 to the users table
     * and seed their display/require/label settings, independently of this migration and of
     * the max_custom_fields config. down() must never touch fields in this range - only
     * fields beyond it were actually added by this migration's up().
     */
    private const NATIVE_CUSTOM_FIELD_COUNT = 5;

    private const CUSTOMER_FIELD_SETTINGS = [
        'display' => '0',
        'require' => '0',
        'label' => '',
    ];

    private const APPT_FIELD_SETTINGS = [
        'display' => '0',
        'require' => '0',
        'label' => '',
    ];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        $max_custom_fields = config('max_custom_fields', 5);
        for ($i = $max_custom_fields; $i > 0; $i--) {
            $field_name = 'custom_field_' . $i;

            if (!$this->db->field_exists($field_name, 'users')) {
                $this->dbforge->add_column('users', [
                    $field_name => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                ]);

                $changed = true;
            }
        }

        for ($i = 1; $i <= $max_custom_fields; $i++) {
            $field_name = 'custom_field_' . $i;

            foreach (self::CUSTOMER_FIELD_SETTINGS as $name => $default_value) {
                $setting_name = $name . '_' . $field_name;

                if (!$this->db->get_where('settings', ['name' => $setting_name])->num_rows()) {
                    $this->db->insert('settings', [
                        'name' => $setting_name,
                        'value' => $default_value,
                    ]);

                    $changed = true;
                }
            }
        }

        $max_appt_custom_fields = config('max_appt_custom_fields', 5);
        for ($i = $max_appt_custom_fields; $i > 0; $i--) {
            $field_name = 'appt_custom_field_' . $i;

            if (!$this->db->field_exists($field_name, 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    $field_name => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                ]);

                $changed = true;
            }
        }

        for ($i = 1; $i <= $max_appt_custom_fields; $i++) {
            $field_name = 'appt_custom_field_' . $i;

            foreach (self::APPT_FIELD_SETTINGS as $name => $default_value) {
                $setting_name = $name . '_' . $field_name;

                if (!$this->db->get_where('settings', ['name' => $setting_name])->num_rows()) {
                    $this->db->insert('settings', [
                        'name' => $setting_name,
                        'value' => $default_value,
                    ]);

                    $changed = true;
                }
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

        $max_appt_custom_fields = config('max_appt_custom_fields', 5);
        for ($i = 1; $i <= $max_appt_custom_fields; $i++) {
            $field_name = 'appt_custom_field_' . $i;

            foreach (self::APPT_FIELD_SETTINGS as $name => $default_value) {
                $setting_name = $name . '_' . $field_name;

                if ($this->db->get_where('settings', ['name' => $setting_name])->num_rows()) {
                    $this->db->delete('settings', ['name' => $setting_name]);

                    $changed = true;
                }
            }
        }

        for ($i = $max_appt_custom_fields; $i > 0; $i--) {
            $field_name = 'appt_custom_field_' . $i;

            if ($this->db->field_exists($field_name, 'appointments')) {
                $this->dbforge->drop_column('appointments', $field_name);

                $changed = true;
            }
        }

        $max_custom_fields = config('max_custom_fields', 5);
        for ($i = self::NATIVE_CUSTOM_FIELD_COUNT + 1; $i <= $max_custom_fields; $i++) {
            $field_name = 'custom_field_' . $i;

            foreach (self::CUSTOMER_FIELD_SETTINGS as $name => $default_value) {
                $setting_name = $name . '_' . $field_name;

                if ($this->db->get_where('settings', ['name' => $setting_name])->num_rows()) {
                    $this->db->delete('settings', ['name' => $setting_name]);

                    $changed = true;
                }
            }
        }

        for ($i = $max_custom_fields; $i > self::NATIVE_CUSTOM_FIELD_COUNT; $i--) {
            $field_name = 'custom_field_' . $i;

            if ($this->db->field_exists($field_name, 'users')) {
                $this->dbforge->drop_column('users', $field_name);

                $changed = true;
            }
        }

        return $changed;
    }
}
