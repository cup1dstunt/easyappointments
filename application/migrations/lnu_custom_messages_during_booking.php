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
 * LNU: Custom Messages during Booking (README.md #12).
 *
 * The message/link settings hold translation IDs, not plain text - see translations_lang.php for the default
 * IDs seeded here, and where those IDs actually get defined in each language.
 */
class Migration_Custom_messages_during_booking extends EA_Migration
{
    private const FIELD_DEFAULTS = [
        'booking_custom_messages_enabled' => false,
        'booking_custom_message_service_page' => 'booking_custom_message_special_teacher',
        'booking_custom_message_time_unavailable' => 'booking_custom_message_no_available_slots',
        'booking_custom_message_confirm_link' => 'booking_custom_message_easyappointments_link',
        'booking_custom_message_confirm_link_text' => 'booking_custom_message_easyappointments_link_text',
    ];

    /**
     * Upgrade method.
     *
     * @return bool True if any change was applied, false if everything already existed.
     */
    public function up(): bool
    {
        $changed = false;

        foreach (self::FIELD_DEFAULTS as $field_name => $default_value) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->insert('settings', [
                    'name' => $field_name,
                    'value' => $default_value,
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

        foreach (self::FIELD_DEFAULTS as $field_name => $default_value) {
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                $this->db->delete('settings', ['name' => $field_name]);

                $changed = true;
            }
        }

        return $changed;
    }
}
