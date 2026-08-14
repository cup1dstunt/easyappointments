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
 * OIDC Settings page.
 *
 * This module implements the functionality of the OIDC Settings page.
 */
App.Pages.OidcSettings = (function () {
    const $saveSettings = $('#save-settings');

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#oidc-settings .is-invalid').removeClass('is-invalid');

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    /**
     * Apply the setting values to the form.
     *
     * @param {Array} oidcSettings
     */
    function deserialize(oidcSettings) {
        oidcSettings.forEach((oidcSetting) => {
            const $field = $('[data-field="' + oidcSetting.name + '"]');

            $field.is(':checkbox')
                ? $field.prop('checked', Boolean(Number(oidcSetting.value)))
                : $field.val(oidcSetting.value);
        });
    }

    /**
     * Serialize the form values into an array.
     *
     * @return {Array}
     */
    function serialize() {
        const oidcSettings = [];

        $('[data-field]').each((index, field) => {
            const $field = $(field);

            oidcSettings.push({
                name: $field.data('field'),
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
            });
        });

        return oidcSettings;
    }

    /**
     * Save the settings.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            App.Layouts.Backend.displayNotification(lang('user_settings_are_invalid'));
            return;
        }

        const oidcSettings = serialize();

        App.Http.OidcSettings.save(oidcSettings).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);

        const oidcSettings = vars('oidc_settings');

        deserialize(oidcSettings);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
