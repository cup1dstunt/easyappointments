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
 * Email templates page.
 *
 * LNU: Editable confirmation email - subject and text of the customer confirmation email.
 */
App.Pages.EmailTemplates = (function () {
    const $saveSettings = $('#save-settings');
    const $resetSettings = $('#reset-settings');
    const $variables = $('#email-template-variables');
    let $lastField = $('#email-confirmation-body');

    /**
     * Insert a {variable} at the cursor position of the last focused field.
     *
     * @param {String} variable
     */
    function insertVariable(variable) {
        const field = $lastField.get(0);
        const text = '{' + variable + '}';
        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? field.value.length;

        field.value = field.value.slice(0, start) + text + field.value.slice(end);
        field.focus();
        field.setSelectionRange(start + text.length, start + text.length);
    }

    /**
     * Deserialize the settings.
     *
     * @param {Array} settings
     */
    function deserialize(settings) {
        settings.forEach((setting) => {
            $('[data-field="' + setting.name + '"]').val(setting.value);
        });
    }

    /**
     * Serialize the settings.
     *
     * @return {Array}
     */
    function serialize() {
        return $('[data-field]')
            .map((index, field) => ({name: $(field).data('field'), value: $(field).val()}))
            .get();
    }

    /**
     * Save the settings.
     */
    function onSaveSettingsClick() {
        App.Http.EmailTemplates.save(serialize()).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));
        });
    }

    /**
     * Empty the fields, so the default texts are used again.
     */
    function onResetSettingsClick() {
        $('[data-field]').val('');
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        deserialize(vars('email_template_settings'));

        vars('email_template_variables').forEach((variable) => {
            $('<button/>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-secondary me-1 mb-1',
                text: '{' + variable + '}',
            })
                .on('click', () => insertVariable(variable))
                .appendTo($variables);
        });

        $('[data-field]').on('focus', (event) => {
            $lastField = $(event.currentTarget);
        });

        $saveSettings.on('click', onSaveSettingsClick);
        $resetSettings.on('click', onResetSettingsClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
