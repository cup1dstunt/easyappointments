/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Attached files utility.
 *
 * Shared by the appointments modal (backend calendar) and the booking wizard (public booking form), both of
 * which render the "attached_files" component and need the same attach/discard behavior.
 *
 * A previously-attached file's "discarded" state is tracked as its own explicit ".data('discarded')" flag,
 * not inferred from the row's visibility - the backend calendar reuses the same modal for many different
 * appointments without a page reload, and initialize() can run before the modal itself is shown, at which
 * point every element inside it reads as invisible via jQuery's :visible/:hidden (they depend on all
 * ancestors being visible too), regardless of the row's own show/hide state.
 */
window.App.Utils.AttachedFiles = (function () {
    /**
     * Get the max number of attached files supported by the current page, based on how many rows were rendered.
     *
     * @return {Number}
     */
    function getMaxAttachedFiles() {
        return $('.attached-file-name-row').length;
    }

    /**
     * Get the File objects currently selected across all attached-file-input elements.
     *
     * @return {File[]}
     */
    function getAttachedFiles() {
        return Array.from(document.getElementsByClassName('attached-file-input'))
            .map((input) => input.files[0])
            .filter(Boolean);
    }

    /**
     * Get the file names the user has discarded from the previously-attached list.
     *
     * @return {String[]}
     */
    function getDiscardedFileNames() {
        return Array.from(document.getElementsByClassName('existing-file-name-row'))
            .map((row) => $(row))
            .filter(($row) => $row.data('filename') && $row.data('discarded'))
            .map(($row) => $row.data('filename'));
    }

    /**
     * Enable/disable the "Attach file" button based on whether the max number of files has been reached.
     */
    function toggleAttachButton() {
        const existingCount = Array.from(document.getElementsByClassName('existing-file-name-row'))
            .map((row) => $(row))
            .filter(($row) => $row.data('filename') && !$row.data('discarded')).length;
        const attachedCount = getAttachedFiles().length;

        $('#attach-file-button').prop('disabled', existingCount + attachedCount >= getMaxAttachedFiles());
    }

    /**
     * Reset the attached-files UI, optionally populating it with an appointment's existing attached files.
     *
     * @param {Number|String|null} appointmentId Needed to build download links for existing files.
     * @param {String[]} existingFileNames File names already attached to the appointment.
     */
    function initialize(appointmentId, existingFileNames = []) {
        $('.attached-file-input').val(null);
        $('.attached-file-name-row').hide();

        const maxAttachedFiles = getMaxAttachedFiles();

        for (let i = 1; i <= maxAttachedFiles; i++) {
            const $row = $(`#existing-file-name-row-${i}`);
            const $name = $(`#existing-file-name-${i}`);
            const fileName = existingFileNames[i - 1];

            if (fileName) {
                const url = App.Utils.Url.baseUrl(`storage/uploads/${appointmentId}/${encodeURIComponent(fileName)}`);
                $name.html(`<a href="${url}" target="_blank">${App.Utils.String.escapeHtml(fileName)}</a>`);
                $row.data('filename', fileName).data('discarded', false).show();
            } else {
                $row.removeData('filename').removeData('discarded').hide();
            }
        }

        toggleAttachButton();
    }

    /**
     * Wire up the attach/discard event listeners. Safe to call once per page load - listeners are delegated,
     * so they keep working even though initialize() may run again later (e.g. when a different appointment is
     * selected for editing).
     */
    function addEventListeners() {
        $(document).on('click', '#attach-file-button', () => {
            const maxAttachedFiles = getMaxAttachedFiles();

            for (let i = 1; i <= maxAttachedFiles; i++) {
                const input = document.getElementById(`attached-file-input-${i}`);

                if (input && !input.files.length) {
                    input.click();
                    return;
                }
            }
        });

        $(document).on('change', '.attached-file-input', (event) => {
            const index = $(event.target).data('index');
            const file = event.target.files[0];
            const $row = $(`#attached-file-name-row-${index}`);

            if (file) {
                $(`#attached-file-name-${index}`).text(file.name);
                $row.show();
            } else {
                $row.hide();
            }

            toggleAttachButton();
        });

        $(document).on('click', '.existing-file-name-row .discard-file-button', (event) => {
            $(event.target).closest('.existing-file-name-row').data('discarded', true).hide();
            toggleAttachButton();
        });

        $(document).on('click', '.attached-file-name-row .discard-file-button', (event) => {
            const index = $(event.target).closest('.attached-file-name-row').data('index');
            $(`#attached-file-input-${index}`).val(null).trigger('change');
        });
    }

    return {
        getMaxAttachedFiles,
        getAttachedFiles,
        getDiscardedFileNames,
        toggleAttachButton,
        initialize,
        addEventListeners,
    };
})();
