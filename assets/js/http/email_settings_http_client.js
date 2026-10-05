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
 * LNU: SMTP settings in the admin GUI.
 */
App.Http.EmailSettings = (function () {
    /**
     * Save the email settings.
     *
     * @param {Array} emailSettings
     * @param {Boolean} clearSmtpPass
     *
     * @return {Object}
     */
    function save(emailSettings, clearSmtpPass) {
        const url = App.Utils.Url.siteUrl('email_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            email_settings: emailSettings,
            clear_smtp_pass: clearSmtpPass ? 1 : 0,
        };

        return $.post(url, data);
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

        const data = {
            csrf_token: vars('csrf_token'),
            recipient,
        };

        return $.post(url, data);
    }

    return {
        save,
        sendTest,
    };
})();
