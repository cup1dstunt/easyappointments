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
 * LNU: SMTP settings in the admin GUI - mail server and sender of all outgoing emails.
 */
App.Pages.EmailSettings = (function () {
    const $saveSettings = $('#save-settings');
    const $protocol = $('#mail-protocol');
    const $smtpFields = $('#smtp-fields');
    const $smtpAuth = $('#mail-smtp-auth');
    const $smtpPass = $('#mail-smtp-pass');
    const $smtpPassHint = $('#smtp-pass-hint');
    const $testRecipient = $('#mail-test-recipient');
    const $testResult = $('#mail-test-result');
    let clearSmtpPass = false;

    function toggleFields() {
        $smtpFields.toggle($protocol.val() === 'smtp');
        $('#mail-smtp-user, #mail-smtp-pass').prop('disabled', !$smtpAuth.prop('checked'));
    }

    function deserialize(settings) {
        settings.forEach((setting) => {
            const $field = $('[data-field="' + setting.name + '"]');

            if ($field.is(':checkbox')) {
                $field.prop('checked', Boolean(Number(setting.value)));
            } else if (setting.value !== '') {
                $field.val(setting.value);
            }
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
        App.Http.EmailSettings.save(serialize(), clearSmtpPass).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));

            if ($smtpPass.val()) {
                $smtpPass.val('');
                $smtpPassHint.removeClass('d-none');
            }

            if (clearSmtpPass) {
                clearSmtpPass = false;
                $smtpPassHint.addClass('d-none');
            }
        });
    }

    function onSendTestClick() {
        $testResult.removeClass('d-none alert-success alert-danger').addClass('alert-info').text('…');

        App.Http.EmailSettings.sendTest($testRecipient.val())
            .done((response) => {
                const ok = response.success;

                $testResult
                    .removeClass('alert-info')
                    .addClass(ok ? 'alert-success' : 'alert-danger')
                    .text(ok ? lang('mail_test_success') : response.message);
            })
            .fail((jqXHR) => {
                $testResult
                    .removeClass('alert-info')
                    .addClass('alert-danger')
                    .text(jqXHR.responseJSON?.message ?? lang('mail_test_failed'));
            });
    }

    function initialize() {
        deserialize(vars('email_settings'));

        $testRecipient.val(vars('test_recipient') || '');

        if (vars('smtp_pass_is_set')) {
            $smtpPassHint.removeClass('d-none');
        }

        toggleFields();

        $protocol.on('change', toggleFields);
        $smtpAuth.on('change', toggleFields);

        $('#clear-smtp-pass').on('click', (event) => {
            event.preventDefault();
            clearSmtpPass = true;
            $smtpPass.val('');
            $smtpPassHint.addClass('d-none');
        });

        $saveSettings.on('click', onSaveSettingsClick);
        $('#send-test-mail').on('click', onSendTestClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
