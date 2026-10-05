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
 * Email settings page.
 *
 * This module implements the functionality of the email settings page.
 */
App.Pages.EmailSettings = (function () {
    const $saveSettings = $('#save-settings');
    const $sendTest = $('#send-test-email');
    const $testRecipient = $('#smtp-test-recipient');
    const $password = $('#smtp-pass');

    function deserialize(emailSettings) {
        emailSettings.forEach((emailSetting) => {
            const $field = $('[data-field="' + emailSetting.name + '"]');

            $field.is(':checkbox')
                ? $field.prop('checked', emailSetting.value === '1')
                : $field.val(emailSetting.value);
        });
    }

    function serialize() {
        return $('[data-field]')
            .map((index, field) => {
                const $field = $(field);

                return {
                    name: $field.data('field'),
                    value: $field.is(':checkbox') ? Number($field.prop('checked')) : $field.val(),
                };
            })
            .get();
    }

    function onSaveSettingsClick() {
        App.Http.EmailSettings.save(serialize(), $password.val()).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));

            if ($password.val()) {
                $password.val('');
                $('#smtp-pass-hint').removeClass('d-none');
            }
        });
    }

    function onSendTestClick() {
        $sendTest.prop('disabled', true);

        App.Http.EmailSettings.sendTest($testRecipient.val())
            .done((response) => {
                App.Layouts.Backend.displayNotification(
                    response.success ? lang('smtp_test_sent') : lang('smtp_test_failed') + ' ' + response.message,
                );
            })
            .always(() => $sendTest.prop('disabled', false));
    }

    function initialize() {
        $saveSettings.on('click', onSaveSettingsClick);
        $sendTest.on('click', onSendTestClick);

        deserialize(vars('email_settings'));

        $testRecipient.val(vars('default_test_recipient'));

        $('#smtp-pass-hint').toggleClass('d-none', !vars('has_smtp_password'));
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
