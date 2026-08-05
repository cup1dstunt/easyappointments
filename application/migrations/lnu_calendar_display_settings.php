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
 * LNU: Calendar Display Settings - lets an admin narrow the visible time range of the backend calendar's day
 * view (so the whole working day fits without scrolling), hide weekend columns, adjust the time-slot row
 * height (a CSS custom property, set from JS - see calendar_default_view.js/calendar_table_view.js - rather
 * than a value hardcoded in the theme's CSS), and set the initial scroll position. The seeded defaults
 * ('00:00:00'/'23:59:59'/'0'/'1em'/'07:00:00') match the current built-in behavior, so installs see no
 * meaningful change until an admin actually adjusts them. calendar_slot_max_time uses '23:59:59' rather than
 * FullCalendar's own '24:00:00' full-day default, since the admin picks it via a normal time picker
 * (00:00-23:59) - moment.js round-trips '24:00:00' as the next day's midnight, which would silently corrupt
 * to '00:00:00' the first time this settings page is saved.
 */
class Migration_Calendar_display_settings extends EA_Migration
{
    private const FIELD_DEFAULTS = [
        'calendar_slot_min_time' => '00:00:00',
        'calendar_slot_max_time' => '23:59:59',
        'calendar_hide_weekends' => '0',
        'calendar_timegrid_slot_height' => '1em',
        'calendar_scroll_time' => '07:00:00',
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
            if ($this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                continue;
            }

            $this->db->insert('settings', [
                'name' => $field_name,
                'value' => $default_value,
            ]);

            $changed = true;
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

        foreach (array_keys(self::FIELD_DEFAULTS) as $field_name) {
            if (!$this->db->get_where('settings', ['name' => $field_name])->num_rows()) {
                continue;
            }

            $this->db->delete('settings', ['name' => $field_name]);

            $changed = true;
        }

        return $changed;
    }
}
