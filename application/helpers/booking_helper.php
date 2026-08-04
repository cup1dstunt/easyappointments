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

if (!function_exists('is_valid_booking_step_order')) {
    /**
     * LNU: Configurable order for booking wizard steps (README.md #10).
     *
     * @param string[] $step_order The step order to validate.
     * @param string[] $required_steps Every one of these must appear exactly once.
     * @param string[] $optional_steps May appear at most once each; may also be entirely absent.
     */
    function is_valid_booking_step_order(array $step_order, array $required_steps, array $optional_steps): bool
    {
        return empty(array_diff($required_steps, $step_order)) &&
            empty(array_diff($step_order, array_merge($required_steps, $optional_steps))) &&
            count($step_order) === count(array_unique($step_order)) &&
            end($step_order) === 'confirmation' &&
            array_search('service', $step_order, true) < array_search('time', $step_order, true);
    }
}

if (!function_exists('resolve_booking_step_order')) {
    /**
     * LNU: Configurable order for booking wizard steps (README.md #10).
     *
     * Resolves the actual step order to use, previously duplicated between Booking.php and
     * booking_header.php. Both the customer-configurable "booking_step_order" setting AND the
     * "default_booking_step_order" config value it falls back to are validated, not trusted as-is - a
     * malformed value in either one (e.g. a hand-edited config.php using the wrong separator) used to be able
     * to crash the booking page outright (an "undefined array key" once the malformed step name reached
     * booking_header.php's per-step rendering) rather than just falling back further, since only the
     * setting's value was ever checked. The hardcoded fallback below is the one value guaranteed to always be
     * valid, with nothing left to fall back to if even that were wrong.
     *
     * @return string[] The validated step order, e.g. ['service', 'time', 'info', 'confirmation'].
     */
    function resolve_booking_step_order(): array
    {
        $required_steps = ['service', 'time', 'info', 'confirmation'];
        $optional_steps = ['terms'];
        $hardcoded_default = ['service', 'time', 'info', 'confirmation'];

        $default_step_order = explode('>', (string) config('default_booking_step_order'));

        if (!is_valid_booking_step_order($default_step_order, $required_steps, $optional_steps)) {
            log_message(
                'error',
                'Invalid "default_booking_step_order" config value "' .
                    implode('>', $default_step_order) .
                    '" - falling back to the hardcoded default order.',
            );

            $default_step_order = $hardcoded_default;
        }

        if (!boolval(setting('booking_step_order_enabled', 0))) {
            return $default_step_order;
        }

        $step_order = explode('>', (string) setting('booking_step_order', implode('>', $default_step_order)));

        if (!is_valid_booking_step_order($step_order, $required_steps, $optional_steps)) {
            log_message(
                'error',
                'Invalid "booking_step_order" setting value "' .
                    implode('>', $step_order) .
                    '" - falling back to the default order.',
            );

            return $default_step_order;
        }

        return $step_order;
    }
}
