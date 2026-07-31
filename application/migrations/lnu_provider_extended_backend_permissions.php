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
 * LNU: Extended Backend Permissions for Providers (README.md #9).
 *
 * A single "provider_extended_backend_permissions" setting gates all of this feature's behavior: when enabled,
 * providers can access each other's bookings in the calendar, and can view/edit their own record under
 * Users > Providers as well as view/edit Services. The "users"/"services" columns on the "provider" role are
 * granted the view (1) and edit (4) bits (5 total) so that can()/cannot() structurally allow it - the setting
 * itself is what actually toggles the behavior on top of that, checked separately wherever it matters, so
 * granting the bits here does not by itself give providers access unless the setting is also turned on.
 */
class Migration_Provider_extended_backend_permissions extends EA_Migration
{
    private const FIELD_NAME = 'provider_extended_backend_permissions';

    private const GRANTED_PERMISSIONS = 5; // PRIV_VIEW (1) | PRIV_EDIT (4)

    private const ROLE_FIELD_NAMES = ['users', 'services'];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        if (!$this->db->get_where('settings', ['name' => self::FIELD_NAME])->num_rows()) {
            $this->db->insert('settings', [
                'name' => self::FIELD_NAME,
                'value' => '0',
            ]);

            $changed = true;
        }

        $provider_role = $this->db->get_where('roles', ['slug' => 'provider'])->row_array();

        foreach (self::ROLE_FIELD_NAMES as $field_name) {
            $current_value = (int) $provider_role[$field_name];

            if (($current_value & self::GRANTED_PERMISSIONS) !== self::GRANTED_PERMISSIONS) {
                $this->db->update(
                    'roles',
                    [$field_name => $current_value | self::GRANTED_PERMISSIONS],
                    ['slug' => 'provider'],
                );

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

        if ($this->db->get_where('settings', ['name' => self::FIELD_NAME])->num_rows()) {
            $this->db->delete('settings', ['name' => self::FIELD_NAME]);

            $changed = true;
        }

        $provider_role = $this->db->get_where('roles', ['slug' => 'provider'])->row_array();

        foreach (self::ROLE_FIELD_NAMES as $field_name) {
            $current_value = (int) $provider_role[$field_name];

            if (($current_value & self::GRANTED_PERMISSIONS) !== 0) {
                $this->db->update(
                    'roles',
                    [$field_name => $current_value & ~self::GRANTED_PERMISSIONS],
                    ['slug' => 'provider'],
                );

                $changed = true;
            }
        }

        return $changed;
    }
}
