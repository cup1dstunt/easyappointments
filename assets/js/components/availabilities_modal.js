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
 * Availabilities modal component.
 *
 * This module implements the availabilities modal functionality (README.md #8).
 *
 * Old Name: -
 */
App.Components.AvailabilitiesModal = (function () {
    const $availabilitiesModal = $('#availabilities-modal');
    const $startDatetime = $('#availability-start');
    const $endDatetime = $('#availability-end');
    const $selectProvider = $('#availability-provider');
    const $saveAvailability = $('#save-availability');
    const $insertAvailability = $('#insert-availability');
    const $reloadAppointments = $('#reload-appointments');
    const $selectFilterItem = $('#select-filter-item');

    const moment = window.moment;

    /**
     * Update the displayed provider timezone.
     */
    function updateTimezone() {
        const providerId = $selectProvider.val();

        const provider = vars('available_providers').find(
            (availableProvider) => Number(availableProvider.id) === Number(providerId),
        );

        if (provider && provider.timezone) {
            $availabilitiesModal.find('.provider-timezone').text(vars('timezones')[provider.timezone]);
        }
    }

    /**
     * Normalize a time string to "HH:mm", regardless of whether it came in as "HH:mm" (this app's own
     * client-constructed format) or "HH:mm:ss" (the raw MySQL TIME format, returned as-is by
     * working_plan_exceptions fetched from the server). mergeAvailability() does plain string comparisons
     * between exception times and newly-marked availability times, which only give correct results when both
     * sides use the same format.
     *
     * @param {String|null} time
     *
     * @return {String|null}
     */
    function normalizeTime(time) {
        return time ? time.slice(0, 5) : time;
    }

    /**
     * Get the working plan exception covering the given single day, if any exist yet, or a new one seeded
     * from the provider's regular working plan for that weekday (or empty, if it's normally a non-working day).
     *
     * A multi-day exception range is left untouched even if it covers this date - only an existing exception
     * whose own range is exactly this single day is reused; otherwise a new single-day entry is created.
     *
     * @param {Object} provider
     * @param {Array} exceptions The provider's existing working plan exceptions.
     * @param {String} date Y-m-d.
     *
     * @return {Object} Contains {exception, index} - index is -1 for a brand new exception.
     */
    function getWorkingPlanException(provider, exceptions, date) {
        const index = exceptions.findIndex(
            (exception) => exception.startDate === date && exception.endDate === date,
        );

        if (index >= 0) {
            const exception = {...exceptions[index]};
            exception.startTime = normalizeTime(exception.startTime);
            exception.endTime = normalizeTime(exception.endTime);
            return {exception, index};
        }

        const weekdayName = App.Utils.Date.getWeekdayName(moment(date, 'YYYY-MM-DD').day());
        const workingPlan = JSON.parse(provider.settings.working_plan);
        const dayWorkingPlan = workingPlan[weekdayName];

        const exception = dayWorkingPlan
            ? {
                  startDate: date,
                  endDate: date,
                  startTime: normalizeTime(dayWorkingPlan.start),
                  endTime: normalizeTime(dayWorkingPlan.end),
                  breaks: [...(dayWorkingPlan.breaks || [])],
              }
            : {
                  startDate: date,
                  endDate: date,
                  startTime: null,
                  endTime: null,
                  breaks: [],
              };

        return {exception, index: -1};
    }

    /**
     * Get every calendar date (Y-m-d) between the two given dates, inclusive.
     *
     * @param {String} startDate Y-m-d.
     * @param {String} endDate Y-m-d.
     *
     * @return {String[]}
     */
    function getDatesBetween(startDate, endDate) {
        const dates = [];
        const current = moment(startDate, 'YYYY-MM-DD');
        const last = moment(endDate, 'YYYY-MM-DD');

        while (current.isSameOrBefore(last)) {
            dates.push(current.format('YYYY-MM-DD'));
            current.add(1, 'day');
        }

        return dates;
    }

    /**
     * Merge a newly-marked available timespan into a working plan exception, in place - extending its start
     * and/or end as needed, and adding or adjusting breaks so the marked timespan itself is never a break.
     *
     * @param {Object} exception Contains startTime, endTime, breaks.
     * @param {String} startTime New availability start time (HH:mm).
     * @param {String} endTime New availability end time (HH:mm).
     */
    function mergeAvailability(exception, startTime, endTime) {
        if (!exception.startTime) {
            // No existing hours at all (a fresh or non-working day) - the marked timespan becomes the exception.
            exception.startTime = startTime;
            exception.endTime = endTime;
            exception.breaks = [];
            return;
        }

        // New availability ends before the existing start => add a break in between.
        if (endTime < exception.startTime) {
            exception.breaks.push({start: endTime, end: exception.startTime});
        }

        // New availability starts before the existing start => extend the start accordingly.
        if (startTime < exception.startTime) {
            exception.startTime = startTime;
        }

        // New availability starts after the existing end => add a break in between.
        if (startTime > exception.endTime) {
            exception.breaks.push({start: exception.endTime, end: startTime});
        }

        // New availability ends after the existing end => extend the end accordingly.
        if (endTime > exception.endTime) {
            exception.endTime = endTime;
        }

        for (let i = exception.breaks.length - 1; i >= 0; i--) {
            const brk = exception.breaks[i];

            if (startTime <= brk.start && endTime >= brk.end) {
                // New availability covers the entire break => remove it.
                exception.breaks.splice(i, 1);
                continue;
            }

            if (startTime < brk.end && endTime >= brk.end) {
                // New availability overlaps the end of the break => trim the break's end.
                brk.end = startTime;
            }

            if (endTime > brk.start && startTime <= brk.start) {
                // New availability overlaps the start of the break => trim the break's start.
                brk.start = endTime;
            }

            if (startTime > brk.start && endTime < brk.end) {
                // New availability is wholly inside the break => split it in two around the new timespan.
                exception.breaks.push({start: endTime, end: brk.end});
                brk.end = startTime;
            }
        }
    }

    /**
     * Save the working plan exception for each of the given dates, one at a time (a span crossing midnight
     * touches one exception per calendar day). Modifies "exceptions" in place as each day is saved.
     *
     * @param {Object} provider
     * @param {Array} exceptions The provider's working plan exceptions - modified in place.
     * @param {String[]} dates Remaining dates (Y-m-d) still to be saved.
     * @param {Object} boundaries Contains startDate, startTime, endDate, endTime of the marked availability.
     * @param {Number} providerId
     * @param {Function} onComplete Called once every date has been saved.
     */
    function saveAvailabilityForDates(provider, exceptions, dates, boundaries, providerId, onComplete) {
        if (!dates.length) {
            onComplete();
            return;
        }

        const [date, ...remainingDates] = dates;

        const dayStartTime = date === boundaries.startDate ? boundaries.startTime : '00:00';
        const dayEndTime = date === boundaries.endDate ? boundaries.endTime : '23:59';

        const {exception, index} = getWorkingPlanException(provider, exceptions, date);

        mergeAvailability(exception, dayStartTime, dayEndTime);

        App.Http.Calendar.saveWorkingPlanException(
            exception,
            providerId,
            (response) => {
                if (index >= 0) {
                    exceptions[index] = exception;
                } else {
                    if (response && response.id) {
                        exception.id = response.id;
                    }

                    exceptions.push(exception);
                }

                saveAvailabilityForDates(provider, exceptions, remainingDates, boundaries, providerId, onComplete);
            },
            null,
        );
    }

    /**
     * Add the component event listeners.
     */
    function addEventListeners() {
        /**
         * Event: Provider "Change"
         */
        $selectProvider.on('change', () => {
            updateTimezone();
        });

        /**
         * Event: Save Availability Button "Click"
         *
         * Merges the marked timespan into the provider's working plan exception for that day (creating one if
         * none exists yet) and saves it.
         */
        $saveAvailability.on('click', () => {
            $availabilitiesModal.find('.modal-message').addClass('d-none');
            $availabilitiesModal.find('.is-invalid').removeClass('is-invalid');

            if (!$selectProvider.val()) {
                $selectProvider.addClass('is-invalid');
                return;
            }

            const startDateTimeMoment = moment(App.Utils.UI.getDateTimePickerValue($startDatetime));

            if (!startDateTimeMoment.isValid()) {
                $startDatetime.addClass('is-invalid');
                return;
            }

            const endDateTimeMoment = moment(App.Utils.UI.getDateTimePickerValue($endDatetime));

            if (!endDateTimeMoment.isValid()) {
                $endDatetime.addClass('is-invalid');
                return;
            }

            if (startDateTimeMoment.isAfter(endDateTimeMoment)) {
                $availabilitiesModal
                    .find('.modal-message')
                    .text(lang('start_date_before_end_error'))
                    .addClass('alert-danger')
                    .removeClass('d-none');

                $startDatetime.addClass('is-invalid');
                $endDatetime.addClass('is-invalid');

                return;
            }

            const providerId = $selectProvider.val();

            const provider = vars('available_providers').find(
                (availableProvider) => Number(availableProvider.id) === Number(providerId),
            );

            let exceptions = JSON.parse(provider.settings.working_plan_exceptions || '[]');

            if (!Array.isArray(exceptions)) {
                exceptions = [];
            }

            // A span crossing midnight touches more than one working plan exception (one per calendar day), so
            // it is saved one day at a time: the first and last days are clipped to the marked start/end time,
            // any days in between get the full day (00:00-23:59).
            const boundaries = {
                startDate: startDateTimeMoment.format('YYYY-MM-DD'),
                startTime: startDateTimeMoment.format('HH:mm'),
                endDate: endDateTimeMoment.format('YYYY-MM-DD'),
                endTime: endDateTimeMoment.format('HH:mm'),
            };

            const dates = getDatesBetween(boundaries.startDate, boundaries.endDate);

            saveAvailabilityForDates(provider, exceptions, dates, boundaries, providerId, () => {
                provider.settings.working_plan_exceptions = JSON.stringify(exceptions);

                $reloadAppointments.trigger('click');

                App.Layouts.Backend.displayNotification(lang('availability_saved'));

                $availabilitiesModal.find('.alert').addClass('d-none');
                $availabilitiesModal.modal('hide');
            });
        });

        /**
         * Event: Insert Availability Button "Click"
         *
         * Opens the dialog so the user can mark a timespan as available.
         */
        $insertAvailability.on('click', () => {
            resetModal();

            const startMoment = moment();
            const currentMin = parseInt(startMoment.format('mm'));

            if (currentMin > 0 && currentMin < 15) {
                startMoment.set({minutes: 15});
            } else if (currentMin > 15 && currentMin < 30) {
                startMoment.set({minutes: 30});
            } else if (currentMin > 30 && currentMin < 45) {
                startMoment.set({minutes: 45});
            } else {
                startMoment.add(1, 'hour').set({minutes: 0});
            }

            if ($('.calendar-view').length === 0) {
                $selectProvider.val($selectFilterItem.val()).closest('.form-group').hide();
            }

            App.Utils.UI.setDateTimePickerValue($startDatetime, startMoment.toDate());
            App.Utils.UI.setDateTimePickerValue($endDatetime, startMoment.add(1, 'hour').toDate());

            $availabilitiesModal.find('.modal-header h3').text(lang('new_availability_title'));
            $availabilitiesModal.modal('show');
        });
    }

    /**
     * Reset the availability dialog form back to the initial state.
     */
    function resetModal() {
        const start = App.Utils.Date.format(moment().toDate(), vars('date_format'), vars('time_format'), true);

        const end = App.Utils.Date.format(
            moment().add(1, 'hour').toDate(),
            vars('date_format'),
            vars('time_format'),
            true,
        );

        App.Utils.UI.initializeDateTimePicker($startDatetime);
        $startDatetime.val(start);

        App.Utils.UI.initializeDateTimePicker($endDatetime);
        $endDatetime.val(end);
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        vars('available_providers').forEach((provider) => {
            $selectProvider.append(new Option(provider.first_name + ' ' + provider.last_name, provider.id));
        });

        addEventListeners();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        resetModal,
    };
})();
