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
 * LNU: Editable emails - subject and text of the confirmation, cancellation, password reset and new password emails.
 */
App.Pages.EmailTemplates = (function () {
    const $saveSettings = $('#save-settings');
    const $resetSettings = $('#reset-settings');
    let $lastField = $('#email-confirmation-body');

    /**
     * Insert a {variable} at the cursor position of the given field.
     *
     * @param {jQuery} $field
     * @param {String} variable
     */
    function insertVariable($field, variable) {
        const field = $field.get(0);
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
            const $field = $('[data-field="' + setting.name + '"]');

            // Fields with a default (select boxes) show it while nothing is saved yet.
            $field.val(setting.value === '' && $field.data('default') !== undefined ? $field.data('default') : setting.value);
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
        $('[data-field]').each((index, field) => {
            $(field).val($(field).data('default') ?? '');
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        deserialize(vars('email_template_settings'));

        const variables = vars('email_template_variables');

        $('.email-template').each((index, card) => {
            const $card = $(card);
            const $container = $card.find('.email-template-variables');

            (variables[$card.data('template')] || []).forEach((variable) => {
                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-secondary me-1 mb-1',
                    text: '{' + variable + '}',
                })
                    .on('click', () => {
                        // Insert into the last focused field of this email, defaulting to its text field.
                        const $target = $lastField.closest('.email-template').is($card)
                            ? $lastField
                            : $card.find('textarea');

                        insertVariable($target, variable);
                    })
                    .appendTo($container);
            });
        });

        $('input[data-field], textarea[data-field]').on('focus', (event) => {
            $lastField = $(event.currentTarget);
        });

        $saveSettings.on('click', onSaveSettingsClick);
        $resetSettings.on('click', onResetSettingsClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
