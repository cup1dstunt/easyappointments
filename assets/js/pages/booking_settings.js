/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking settings page.
 *
 * This module implements the functionality of the booking settings page.
 */
App.Pages.BookingSettings = (function () {
    const $bookingSettings = $('#booking-settings');
    const $saveSettings = $('#save-settings');
    const $disableBooking = $('#disable-booking');
    const $disableBookingMessage = $('#disable-booking-message');

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#booking-settings .is-invalid').removeClass('is-invalid');

            // Validate required fields.

            let missingRequiredFields = false;

            $('#booking-settings .required').each((index, requiredField) => {
                const $requiredField = $(requiredField);

                if (!$requiredField.val()) {
                    $requiredField.addClass('is-invalid');
                    missingRequiredFields = true;
                }
            });

            if (missingRequiredFields) {
                throw new Error(lang('fields_are_required'));
            }

            // Ensure there is at least one field displayed.

            if (!$('.display-switch:checked').length) {
                throw new Error(lang('at_least_one_field'));
            }

            // Ensure there is at least one field required.

            if (!$('.require-switch:checked').length) {
                throw new Error(lang('at_least_one_field_required'));
            }

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    /**
     * Apply the booking settings into the page.
     *
     * @param {Object} bookingSettings
     */
    function deserialize(bookingSettings) {
        bookingSettings.forEach((bookingSetting) => {
            if (bookingSetting.name === 'disable_booking_message') {
                $disableBookingMessage.trumbowyg('html', bookingSetting.value);
                return;
            }

            const $field = $('[data-field="' + bookingSetting.name + '"]');

            if ($field.is(':checkbox')) {
                $field.prop('checked', Boolean(Number(bookingSetting.value)));
            } else {
                $field.val(bookingSetting.value);
            }
        });
    }

    /**
     * Serialize the page values into an array.
     *
     * @returns {Array}
     */
    function serialize() {
        const bookingSettings = [];

        $('[data-field]').each((index, field) => {
            const $field = $(field);

            bookingSettings.push({
                name: $field.data('field'),
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
            });
        });

        bookingSettings.push({
            name: 'disable_booking_message',
            value: $disableBookingMessage.trumbowyg('html'),
        });

        return bookingSettings;
    }

    /**
     * Update the UI based on the display switch state.
     *
     * @param {jQuery} $displaySwitch
     */
    function updateDisplaySwitch($displaySwitch) {
        const isChecked = $displaySwitch.prop('checked');

        const $formGroup = $displaySwitch.closest('.form-group');

        $formGroup.find('.require-switch').prop('disabled', !isChecked);

        $formGroup.find('.form-label, .form-control').toggleClass('opacity-25', !isChecked);

        if (!isChecked) {
            $formGroup.find('.require-switch').prop('checked', false);
            $formGroup.find('.text-danger').hide();
        }
    }

    /**
     * Update the UI based on the require switch state.
     *
     * @param {jQuery} $requireSwitch
     */
    function updateRequireSwitch($requireSwitch) {
        const isChecked = $requireSwitch.prop('checked');

        const $formGroup = $requireSwitch.closest('.form-group');

        $formGroup.find('.text-danger').toggle(isChecked);
    }

    /**
     * Update the UI based on the initial values.
     */
    function applyInitialState() {
        $bookingSettings.find('.display-switch').each((index, displaySwitchEl) => {
            const $displaySwitch = $(displaySwitchEl);

            updateDisplaySwitch($displaySwitch);
        });

        $bookingSettings.find('.require-switch').each((index, requireSwitchEl) => {
            const $requireSwitch = $(requireSwitchEl);

            updateRequireSwitch($requireSwitch);
        });

        $bookingSettings.find('.form-check-input').each((index, formCheckInputEl) => {
            updateSubSettings($(formCheckInputEl), false);
        });

        $disableBookingMessage.closest('.form-group').prop('hidden', !$disableBooking.prop('checked'));
    }

    /**
     * LNU: Enable/disable a setting's "subsettings" fields together with its own switch state.
     *
     * A "subsettings" block is a sibling ".subsettings" element within the same ".setting-group" container
     * as the ".form-check-input" switch - see booking_settings.php's "Attached Files" setting for an example.
     * ".setting-group" is a dedicated marker rather than relying on a Bootstrap spacing class like ".mb-3":
     * those are reused all over the page for unrelated spacing, so a switch with no closer ".mb-3" of its
     * own (e.g. "disable-booking") would otherwise walk up and match some unrelated, larger container that
     * only coincidentally also wraps a ".subsettings" block.
     *
     * @param {jQuery} $formCheckInput
     * @param {Boolean} [animate] Whether to slide the block in/out or apply the state instantly (used on
     * page load, so the initial state doesn't visibly animate in).
     */
    function updateSubSettings($formCheckInput, animate = true) {
        // A switch that is itself inside a ".subsettings" block (eg. "hide provider selection") is a
        // subsetting field, not a switch that owns a subsettings block of its own - skip it, otherwise its
        // own checked state would fight with the owning switch over the very same ".subsettings" block.
        if ($formCheckInput.closest('.subsettings').length) {
            return;
        }

        const $subSettings = $formCheckInput.closest('.setting-group').find('.subsettings');

        if (!$subSettings.length) {
            return;
        }

        const enabled = $formCheckInput.prop('checked');

        $subSettings.find('input, select').prop('disabled', !enabled);

        if (animate) {
            enabled ? $subSettings.slideDown() : $subSettings.slideUp();
        } else {
            $subSettings.toggle(enabled);
        }
    }

    /**
     * Update the UI.
     *
     * @param {Event} event
     */
    function onFormCheckInputClick(event) {
        updateSubSettings($(event.target));
    }

    /**
     * Save the account information.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            return;
        }

        const bookingSettings = serialize();

        App.Http.BookingSettings.save(bookingSettings).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));
        });
    }

    /**
     * Update the UI.
     *
     * @param {jQuery} event
     */
    function onDisplaySwitchClick(event) {
        const $displaySwitch = $(event.target);

        updateDisplaySwitch($displaySwitch);
    }

    /**
     * Update the UI.
     *
     * @param {Event} event
     */
    function onRequireSwitchClick(event) {
        const $requireSwitch = $(event.target);

        updateRequireSwitch($requireSwitch);
    }

    /**
     * Toggle the message container.
     */
    function onDisableBookingClick() {
        $disableBookingMessage.closest('.form-group').prop('hidden', !$disableBooking.prop('checked'));
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        const bookingSettings = vars('booking_settings');

        $saveSettings.on('click', onSaveSettingsClick);

        $disableBooking.on('click', onDisableBookingClick);

        $bookingSettings
            .on('click', '.display-switch', onDisplaySwitchClick)
            .on('click', '.require-switch', onRequireSwitchClick)
            .on('click', '.form-check-input', onFormCheckInputClick);

        $disableBookingMessage.trumbowyg();

        deserialize(bookingSettings);

        applyInitialState();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
