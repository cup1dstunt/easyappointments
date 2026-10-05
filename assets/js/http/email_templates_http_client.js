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
 * Email templates HTTP client.
 *
 * LNU: Editable confirmation email.
 */
App.Http.EmailTemplates = (function () {
    /**
     * Save the email template settings.
     *
     * @param {Array} emailTemplateSettings
     *
     * @return {Object}
     */
    function save(emailTemplateSettings) {
        const url = App.Utils.Url.siteUrl('email_templates/save');

        const data = {
            csrf_token: vars('csrf_token'),
            email_template_settings: emailTemplateSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
