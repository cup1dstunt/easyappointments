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

        // Remember that the newest release was seen, so the window does not open again.
        $modal.on('hidden.bs.modal', () => {
            if ($modal.data('autoShow') === 1) {
                $modal.data('autoShow', 0);

                $.post(App.Utils.Url.siteUrl('whats_new/dismiss'), {csrf_token: vars('csrf_token')});
            }
        });

        if ($modal.data('autoShow') === 1) {
            $modal.modal('show');
        }
    });
})();
