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
 * "What's new" window.
 *
 * LNU: shown automatically once after an update, and on demand via the footer link.
 */
(function () {
    document.addEventListener('DOMContentLoaded', () => {
        const $modal = $('#whats-new-modal');

        if (!$modal.length) {
            return;
        }

        $('#whats-new-link').on('click', (event) => {
            event.preventDefault();
            $modal.modal('show');
        });

        // The server already marked the release as seen while rendering the page.
        if ($modal.data('autoShow') === 1) {
            $modal.modal('show');
        }
    });
})();
