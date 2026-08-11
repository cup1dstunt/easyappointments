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
 * Zoom Settings page.
 *
 * This module implements the functionality of the Zoom Settings page.
 */
App.Pages.ZoomSettings = (function () {
    const $saveSettings = $('#save-settings');
    const $applyToAllProviders = $('#apply-zoom-to-all-providers');

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#zoom-settings .is-invalid').removeClass('is-invalid');

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    /**
     * Apply the setting values to the form.
     *
     * @param {Array} zoomSettings
     */
    function deserialize(zoomSettings) {
        zoomSettings.forEach((zoomSetting) => {
            const $field = $('[data-field="' + zoomSetting.name + '"]');

            $field.is(':checkbox')
                ? $field.prop('checked', Boolean(Number(zoomSetting.value)))
                : $field.val(zoomSetting.value);
        });
    }

    /**
     * Serialize the form values into an array.
     *
     * @return {Array}
     */
    function serialize() {
        const zoomSettings = [];

        $('[data-field]').each((index, field) => {
            const $field = $(field);

            zoomSettings.push({
                name: $field.data('field'),
                value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
            });
        });

        return zoomSettings;
    }

    /**
     * Save the settings.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            App.Layouts.Backend.displayNotification(lang('settings_are_invalid'));
            return;
        }

        const zoomSettings = serialize();

        App.Http.ZoomSettings.save(zoomSettings).done((response) => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));
            $applyToAllProviders.prop('disabled', !response.zoom_configured);
        });
    }

    /**
     * Enable the "Create Zoom Links" opt-in for every provider at once, after confirmation.
     */
    function onApplyToAllProvidersClick() {
        const buttons = [
            {
                text: lang('cancel'),
                click: (event, messageModal) => {
                    messageModal.hide();
                },
            },
            {
                text: 'OK',
                click: (event, messageModal) => {
                    App.Http.ZoomSettings.applyToAllProviders()
                        .done(() => {
                            App.Layouts.Backend.displayNotification(lang('zoom_activated_for_all_providers'));
                        })
                        .always(() => {
                            messageModal.hide();
                        });
                },
            },
        ];

        App.Utils.Message.show(lang('zoom'), lang('activate_zoom_for_all_providers_prompt'), buttons);
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);
        $applyToAllProviders.on('click', onApplyToAllProvidersClick);

        const zoomSettings = vars('zoom_settings');

        deserialize(zoomSettings);

        $applyToAllProviders.prop('disabled', !vars('zoom_configured'));
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
