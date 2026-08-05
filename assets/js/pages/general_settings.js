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
 * General settings page.
 *
 * This module implements the functionality of the general settings page.
 */
App.Pages.GeneralSettings = (function () {
    const $saveSettings = $('#save-settings');
    const $companyLogo = $('#company-logo');
    const $companyLogoPreview = $('#company-logo-preview');
    const $removeCompanyLogo = $('#remove-company-logo');
    const $companyColor = $('#company-color');
    const $resetCompanyColor = $('#reset-company-color');
    const $calendarSlotMinTime = $('#calendar-slot-min-time');
    const $calendarSlotMaxTime = $('#calendar-slot-max-time');
    const $calendarScrollTime = $('#calendar-scroll-time');
    let companyLogoBase64 = '';

    // LNU: Calendar Display Settings - stored/exchanged with the server as 24h "HH:mm:ss" (matching
    // FullCalendar's slotMinTime/slotMaxTime/scrollTime), but displayed in the admin's configured time_format
    // via the same flatpickr time picker used everywhere else (App.Utils.UI.initializeTimePicker()).
    const CALENDAR_TIME_FIELD_NAMES = ['calendar_slot_min_time', 'calendar_slot_max_time', 'calendar_scroll_time'];

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#general-settings .is-invalid').removeClass('is-invalid');

            // Validate required fields.

            let missingRequiredFields = false;

            $('#general-settings .required').each((index, requiredField) => {
                const $requiredField = $(requiredField);

                if (!$requiredField.val()) {
                    $requiredField.addClass('is-invalid');
                    missingRequiredFields = true;
                }
            });

            if (missingRequiredFields) {
                throw new Error(lang('fields_are_required'));
            }

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    function deserialize(generalSettings) {
        generalSettings.forEach((generalSetting) => {
            if (generalSetting.name === 'company_logo' && generalSetting.value) {
                companyLogoBase64 = generalSetting.value;
                $companyLogoPreview.attr('src', generalSetting.value);
                $companyLogoPreview.prop('hidden', false);
                $removeCompanyLogo.prop('hidden', false);
                return;
            }

            if (generalSetting.name === 'company_color' && generalSetting.value !== '#ffffff') {
                $resetCompanyColor.prop('hidden', false);
            }

            const $field = $('[data-field="' + generalSetting.name + '"]');

            if (CALENDAR_TIME_FIELD_NAMES.includes(generalSetting.name)) {
                App.Utils.UI.setDateTimePickerValue($field, moment(generalSetting.value, 'HH:mm:ss').toDate());
                return;
            }

            $field.is(':checkbox')
                ? $field.prop('checked', Boolean(Number(generalSetting.value)))
                : $field.val(generalSetting.value);
        });
    }

    function serialize() {
        const generalSettings = [];

        $('[data-field]').each((index, field) => {
            const $field = $(field);
            const fieldName = $field.data('field');

            if (CALENDAR_TIME_FIELD_NAMES.includes(fieldName)) {
                return; // Handled below - the picker's own .val() is a localized display string, not HH:mm:ss.
            }

            generalSettings.push({
                name: fieldName,
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
            });
        });

        CALENDAR_TIME_FIELD_NAMES.forEach((fieldName) => {
            const $field = $('[data-field="' + fieldName + '"]');

            generalSettings.push({
                name: fieldName,
                value: moment(App.Utils.UI.getDateTimePickerValue($field)).format('HH:mm:ss'),
            });
        });

        generalSettings.push({
            name: 'company_logo',
            value: companyLogoBase64,
        });

        return generalSettings;
    }

    /**
     * Save the account information.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            App.Layouts.Backend.displayNotification(lang('settings_are_invalid'));
            return;
        }

        const generalSettings = serialize();

        App.Http.GeneralSettings.save(generalSettings).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'), [
                {
                    label: lang('reload'), // Reload Page
                    function: () => window.location.reload(),
                },
            ]);
        });
    }

    /**
     * Convert the selected image to a base64 encoded string.
     */
    function onCompanyLogoChange() {
        const file = $companyLogo[0].files[0];

        if (!file) {
            $removeCompanyLogo.trigger('click');
            return;
        }

        App.Utils.File.toBase64(file).then((base64) => {
            companyLogoBase64 = base64;
            $companyLogoPreview.attr('src', base64);
            $companyLogoPreview.prop('hidden', false);
            $removeCompanyLogo.prop('hidden', false);
        });
    }

    /**
     * Remove the company logo data.
     */
    function onRemoveCompanyLogoClick() {
        companyLogoBase64 = '';
        $companyLogo.val('');
        $companyLogoPreview.attr('src', '#');
        $companyLogoPreview.prop('hidden', true);
        $removeCompanyLogo.prop('hidden', true);
    }

    /**
     * Toggle the reset company color button.
     */
    function onCompanyColorChange() {
        $resetCompanyColor.prop('hidden', $companyColor.val() === '#ffffff');
    }

    /**
     * Set the company color value to "#ffffff" which is the default one.
     */
    function onResetCompanyColorClick() {
        $companyColor.val('#ffffff');
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);

        $companyLogo.on('change', onCompanyLogoChange);

        $removeCompanyLogo.on('click', onRemoveCompanyLogoClick);

        $companyColor.on('change', onCompanyColorChange);

        $resetCompanyColor.on('click', onResetCompanyColorClick);

        // Must run before deserialize() below, which sets their values via the flatpickr instance.
        App.Utils.UI.initializeTimePicker($calendarSlotMinTime);
        App.Utils.UI.initializeTimePicker($calendarSlotMaxTime);
        App.Utils.UI.initializeTimePicker($calendarScrollTime);

        const generalSettings = vars('general_settings');

        deserialize(generalSettings);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
