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
 * Email settings HTTP client.
 *
 * This module implements the email settings related HTTP requests.
 */
App.Http.EmailSettings = (function () {
    /**
     * Save the email settings.
     *
     * @param {Array} emailSettings
     * @param {String} smtpPass Empty keeps the stored password.
     *
     * @return {Object}
     */
    function save(emailSettings, smtpPass) {
        const url = App.Utils.Url.siteUrl('email_settings/save');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            email_settings: emailSettings,
            smtp_pass: smtpPass,
        });
    }

    /**
     * Send a test email with the saved settings.
     *
     * @param {String} recipient
     *
     * @return {Object}
     */
    function sendTest(recipient) {
        const url = App.Utils.Url.siteUrl('email_settings/send_test');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            recipient,
        });
    }

    return {
        save,
        sendTest,
    };
})();
