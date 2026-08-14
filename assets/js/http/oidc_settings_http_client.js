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
 * Oidc Settings HTTP client.
 *
 * This module implements the OIDC Settings related HTTP requests.
 */
App.Http.OidcSettings = (function () {
    /**
     * Save the booking authentication settings.
     *
     * @param {Array} oidcSettings
     *
     * @return {*|jQuery}
     */
    function save(oidcSettings) {
        const url = App.Utils.Url.siteUrl('oidc_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            oidc_settings: oidcSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
