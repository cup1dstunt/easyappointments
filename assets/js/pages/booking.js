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
 * Booking page.
 *
 * This module implements the functionality of the booking page
 *
 * Old Name: FrontendBook
 */
App.Pages.Booking = (function () {
    const $selectDate = $('#select-date');
    const $selectService = $('#select-service');
    const $selectProvider = $('#select-provider');
    const $selectTimezone = $('#select-timezone');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $zipCode = $('#zip-code');
    const $notes = $('#notes');
    const $captchaTitle = $('.captcha-title');
    const $availableHours = $('#available-hours');
    const $bookAppointmentSubmit = $('#book-appointment-submit');
    const $deletePersonalInformation = $('#delete-personal-information');
    const $displayBookingSelection = $('.display-booking-selection');
    const $rememberMe = $('#remember-me');
    const tippy = window.tippy;
    const moment = window.moment;

    const STORAGE_KEY = 'EasyAppointments.CustomerInfo';

    /**
     * Determines the functionality of the page.
     *
     * @type {Boolean}
     */
    let manageMode = vars('manage_mode') || false;

    /**
     * LNU: Configurable order for booking wizard steps - the 1-based position (within stepOrder) of the step
     * currently on screen. Tracked as state here (rather than read from the clicked button, as the fixed-order
     * implementation used to do), since a step's position is no longer implied by which button was clicked.
     *
     * @type {Number}
     */
    let currentStepIndex = 1;

    /**
     * LNU: Configurable order for booking wizard steps - the step names in the order the customer moves
     * through them, eg. ['service', 'time', 'info', 'confirmation']. Booking.php has already validated this
     * (falling back to the default if invalid), so it's trusted as-is here. Mutated (not just read) when the
     * service step is auto-skipped, since 'service' is then removed from it entirely - see initialize().
     *
     * @type {String[]}
     */
    let stepOrder = vars('booking_step_order').split('>');

    /**
     * LNU: Configurable order for booking wizard steps - the wizard-frame element id showing the step at the
     * given 1-based position in stepOrder.
     *
     * @param {Number} stepIndex
     *
     * @returns {String}
     */
    function getWizardFrameForStepIndex(stepIndex) {
        return getWizardFrameForStepName(stepOrder[stepIndex - 1]);
    }

    /**
     * LNU: Configurable order for booking wizard steps - the wizard-frame element id for the given step name,
     * looked up via each wizard-frame's own data-step attribute (rather than a hardcoded name-to-id mapping),
     * so the frames themselves stay the single source of truth for which one represents which step.
     *
     * @param {String} stepName
     *
     * @returns {String}
     */
    function getWizardFrameForStepName(stepName) {
        return $(`.wizard-frame[data-step="${stepName}"]`).attr('id');
    }

    /**
     * LNU: Configurable order for booking wizard steps - the step marker element showing the step at the given
     * 1-based position in stepOrder. Looked up by name (like getWizardFrameForStepIndex()) rather than by its
     * numeric "step-N" id, since that id reflects the step's original, unfiltered position from
     * booking_header.php and no longer matches stepIndex once the service step has been removed from
     * stepOrder (see the service/provider auto-skip in initialize()).
     *
     * @param {Number} stepIndex
     *
     * @returns {jQuery}
     */
    function getStepMarkerForStepIndex(stepIndex) {
        return getStepMarkerForStepName(stepOrder[stepIndex - 1]);
    }

    /**
     * LNU: Configurable order for booking wizard steps - the step marker element for the given step name,
     * looked up via each marker's own data-step attribute.
     *
     * @param {String} stepName
     *
     * @returns {jQuery}
     */
    function getStepMarkerForStepName(stepName) {
        return $(`.book-step[data-step="${stepName}"]`);
    }

    /**
     * LNU: Configurable order for booking wizard steps - the 1-based position of the given step name within
     * stepOrder.
     *
     * @param {String} stepName
     *
     * @returns {Number}
     */
    function getStepIndexForStepName(stepName) {
        return stepOrder.indexOf(stepName) + 1;
    }

    /**
     * Detect the month step.
     *
     * @param previousDateTimeMoment
     * @param nextDateTimeMoment
     *
     * @returns {Number}
     */
    function detectDatepickerMonthChangeStep(previousDateTimeMoment, nextDateTimeMoment) {
        return previousDateTimeMoment.isAfter(nextDateTimeMoment) ? -1 : 1;
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        if (Boolean(Number(vars('display_cookie_notice'))) && window?.cookieconsent) {
            cookieconsent.initialise({
                palette: {
                    popup: {
                        background: '#ffffffbd',
                        text: '#666666',
                    },
                    button: {
                        background: '#429a82',
                        text: '#ffffff',
                    },
                },
                content: {
                    message: lang('website_using_cookies_to_ensure_best_experience'),
                    dismiss: 'OK',
                },
            });

            const $cookieNoticeLink = $('.cc-link');

            $cookieNoticeLink.replaceWith(
                $('<a/>', {
                    'data-bs-toggle': 'modal',
                    'data-bs-target': '#cookie-notice-modal',
                    'href': '#',
                    'class': 'cc-link',
                    'text': $cookieNoticeLink.text(),
                }),
            );
        }

        manageMode = vars('manage_mode');

        // Initialize page's components (tooltips, date pickers etc).
        tippy('[data-tippy-content]');

        let monthTimeout;

        App.Utils.UI.initializeDatePicker($selectDate, {
            inline: true,
            minDate: moment().subtract(1, 'day').set({hours: 23, minutes: 59, seconds: 59}).toDate(),
            maxDate: moment().add(vars('future_booking_limit'), 'days').toDate(),
            onChange: (selectedDates) => {
                App.Http.Booking.getAvailableHours(moment(selectedDates[0]).format('YYYY-MM-DD'));
                App.Pages.Booking.updateConfirmFrame();
            },

            onMonthChange: (selectedDates, dateStr, instance) => {
                $selectDate.parent().fadeTo(400, 0.3); // Change opacity during loading

                if (monthTimeout) {
                    clearTimeout(monthTimeout);
                }

                monthTimeout = setTimeout(() => {
                    const previousMoment = moment(instance.selectedDates[0]);

                    const displayedMonthMoment = moment(
                        instance.currentYearElement.value +
                            '-' +
                            String(Number(instance.monthsDropdownContainer.value) + 1).padStart(2, '0') +
                            '-01',
                    );

                    const monthChangeStep = detectDatepickerMonthChangeStep(previousMoment, displayedMonthMoment);

                    App.Http.Booking.getUnavailableDates(
                        $selectProvider.val(),
                        $selectService.val(),
                        displayedMonthMoment.format('YYYY-MM-DD'),
                        monthChangeStep,
                    );
                }, 500);
            },

            onYearChange: (selectedDates, dateStr, instance) => {
                setTimeout(() => {
                    const previousMoment = moment(instance.selectedDates[0]);

                    const displayedMonthMoment = moment(
                        instance.currentYearElement.value +
                            '-' +
                            String(Number(instance.monthsDropdownContainer.value) + 1).padStart(2, '0') +
                            '-01',
                    );

                    const monthChangeStep = detectDatepickerMonthChangeStep(previousMoment, displayedMonthMoment);

                    App.Http.Booking.getUnavailableDates(
                        $selectProvider.val(),
                        $selectService.val(),
                        displayedMonthMoment.format('YYYY-MM-DD'),
                        monthChangeStep,
                    );
                }, 500);
            },
        });

        App.Utils.UI.setDateTimePickerValue($selectDate, new Date());

        // LNU: Hide Timezone from Customers (README.md #6).
        if (Boolean(Number(vars('hide_customer_timezone')))) {
            const defaultTimezone = vars('default_timezone');
            const isDefaultTimezoneSupported = $selectTimezone.find(`option[value="${defaultTimezone}"]`).length > 0;
            $selectTimezone.val(isDefaultTimezoneSupported ? defaultTimezone : 'UTC');
        } else {
            const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const isTimezoneSupported = $selectTimezone.find(`option[value="${browserTimezone}"]`).length > 0;
            $selectTimezone.val(isTimezoneSupported ? browserTimezone : 'UTC');
        }

        // Bind the event handlers (might not be necessary every time we use this class).
        addEventListeners();
        App.Utils.AttachedFiles.addEventListeners();
        App.Utils.AttachedFiles.initialize(null, []);

        optimizeContactInfoDisplay();
        optimizeConfirmationDisplay();

        const serviceOptionCount = $selectService.find('option').length;

        if (serviceOptionCount === 2) {
            $selectService.find('option[value=""]').remove();
            const firstServiceId = $selectService.find('option:first').attr('value');
            $selectService.val(firstServiceId).trigger('change');
        }

        // If the manage mode is true, the appointment data should be loaded by default.
        if (manageMode) {
            applyAppointmentData(vars('appointment_data'), vars('provider_data'), vars('customer_data'));

            const wizardFrame = getWizardFrameForStepIndex(currentStepIndex);

            $(`#${wizardFrame}`)
                .css({
                    'visibility': 'visible',
                    'display': 'none',
                })
                .fadeIn();
        } else {
            // Check if a specific service was selected (via URL parameter).
            const selectedServiceId = App.Utils.Url.queryParam('service');

            if (selectedServiceId && $selectService.find('option[value="' + selectedServiceId + '"]').length > 0) {
                $selectService.val(selectedServiceId);
            }

            $selectService.trigger('change'); // Load the available hours.

            // Check if a specific provider was selected.
            const selectedProviderId = App.Utils.Url.queryParam('provider');

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length === 0) {
                // Select a service of this provider in order to make the provider available in the select box.
                for (const index in vars('available_providers')) {
                    const provider = vars('available_providers')[index];

                    if (Number(provider.id) === Number(selectedProviderId) && provider.services.length > 0) {
                        $selectService.val(provider.services[0]).trigger('change');
                    }
                }
            }

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length > 0) {
                $selectProvider.val(selectedProviderId).trigger('change');
            }

            const isSingleService = vars('available_services').length === 1;
            const isSingleProvider = vars('available_providers').length === 1;

            // LNU: Hide Provider Selection (README.md #3).
            const selectHiddenAnyProvider =
                Boolean(Number(vars('display_any_provider'))) && Boolean(Number(vars('hide_provider_selection')));

            // LNU: Configurable order for booking wizard steps - rather than only skipping the service step when
            // it happens to be first (and simulating a click through it), "service" is removed from stepOrder
            // entirely whenever it's skippable, so whichever step is actually configured first is shown normally,
            // regardless of where "service" falls in the order.
            const skipServiceStep =
                (selectedServiceId && selectedProviderId) ||
                (isSingleService && (isSingleProvider || selectHiddenAnyProvider));

            if (skipServiceStep) {
                if (!selectedServiceId) {
                    $selectService.val(vars('available_services')[0].id).trigger('change');
                }

                if (!selectedProviderId) {
                    $selectProvider
                        .val(isSingleProvider ? vars('available_providers')[0].id : vars('ANY_PROVIDER'))
                        .trigger('change');
                }

                getStepMarkerForStepName('service').hide().removeClass('d-inline-block');

                $('#steps .book-step:visible').each((index, bookStepEl) =>
                    $(bookStepEl)
                        .find('strong')
                        .text(index + 1),
                );

                stepOrder = stepOrder.filter((step) => step !== 'service');
            }

            const wizardFrame = getWizardFrameForStepIndex(currentStepIndex);

            $(`#${wizardFrame}`)
                .css({
                    'visibility': 'visible',
                    'display': 'none',
                })
                .fadeIn();

            // LNU: Configurable order for booking wizard steps - if skipping the service step above (or the
            // configured order itself) made "time" the first step actually shown, fetch its unavailable dates
            // and available hours ahead of time, same as the "next" button handler does when transitioning into
            // the time step normally.
            if (stepOrder[currentStepIndex - 1] === 'time') {
                const todayMoment = moment();

                App.Utils.UI.setDateTimePickerValue($selectDate, todayMoment.toDate());

                App.Http.Booking.getUnavailableDates(
                    $selectProvider.val(),
                    $selectService.val(),
                    todayMoment.format('YYYY-MM-DD'),
                );
            }

            // Hide the back button on whichever step the customer actually lands on first (the configured first
            // step, or the next one if it was auto-skipped above).
            $(document)
                .find(`#${getWizardFrameForStepIndex(currentStepIndex)}`)
                .find('.button-back')
                .css('visibility', 'hidden');

            prefillFromQueryParam('#first-name', 'first_name');
            prefillFromQueryParam('#last-name', 'last_name');
            prefillFromQueryParam('#email', 'email');
            prefillFromQueryParam('#phone-number', 'phone');
            prefillFromQueryParam('#address', 'address');
            prefillFromQueryParam('#city', 'city');
            prefillFromQueryParam('#zip-code', 'zip');

            // Initialize remember me after prefilling from query params
            initializeRememberMe();
        }

        // LNU: Configurable order for booking wizard steps - booking_header.php no longer bakes in which step
        // marker starts active, since that might not be stepOrder's first entry once the service step is
        // auto-skipped above; mark whichever step is actually shown first (currentStepIndex is always 1 here,
        // in both the manage mode and regular branches above) active instead.
        $('.book-step').removeClass('active-step');
        getStepMarkerForStepIndex(currentStepIndex).addClass('active-step');
    }

    function prefillFromQueryParam(field, param) {
        const $target = $(field);

        if (!$target.length) {
            return;
        }

        $target.val(App.Utils.Url.queryParam(param));
    }

    /**
     * Remove empty columns and center elements if needed.
     */
    function optimizeContactInfoDisplay() {
        // If a column has only one control shown then move the control to the other column.

        const $firstCol = $('#wizard-frame-3 .field-col:first');
        const $firstColInputs = $firstCol.children();
        const $secondCol = $('#wizard-frame-3 .field-col:last');
        const $secondColInputs = $secondCol.children();

        if ($firstColInputs.length === 1 && $secondColInputs.length > 1) {
            $firstColInputs.toArray().forEach((controlEl) => {
                $(controlEl).insertBefore($secondColInputs.first());
            });
        }

        // LNU: Booking info can use single column always - when enabled, every second-column child is moved
        // into the first column, not just a single leftover one; reversed first so each insertAfter() (which
        // always targets the same original last-of-first-column anchor) doesn't flip their relative order.
        // Operating on all children (not just .form-input elements) also sweeps up the "remember me" checkbox,
        // which uses .form-check-input (Bootstrap's checkbox styling class) instead of .form-input.
        if (
            ($secondColInputs.length === 1 && $firstColInputs.length > 1) ||
            Boolean(Number(vars('booking_info_single_column')))
        ) {
            $secondColInputs
                .toArray()
                .reverse()
                .forEach((controlEl) => {
                    $(controlEl).insertAfter($firstColInputs.last());
                });
        }

        // Hide columns that do not have any controls displayed.

        const $fieldCols = $(document).find('#wizard-frame-3 .field-col');

        $fieldCols.each((index, fieldColEl) => {
            const $fieldCol = $(fieldColEl);

            if (!$fieldCol.find('.form-input').length) {
                $fieldCol.hide();

                // LNU: Booking info can use single column always - the surviving column would otherwise stay
                // constrained to half-width (col-lg-6); widen it now that it's the only visible column.
                $fieldCols.removeClass('col-lg-6').addClass('col-md-8');
            }
        });
    }

    /**
     * Force the confirmation step's appointment/customer details into a single column, if enabled.
     *
     * LNU: Booking info can use single column always.
     */
    function optimizeConfirmationDisplay() {
        if (!Boolean(Number(vars('booking_info_single_column')))) {
            return;
        }

        const $frameContent = $('#appointment-details').closest('.frame-content');

        $frameContent.removeClass('row');

        $frameContent
            .find('.col-lg-6')
            .removeClass('col-lg-6 text-md-end mb-2 mb-md-0')
            .addClass('text-md-start mb-5');
    }

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        /**
         * Event: Timezone "Changed"
         */
        $selectTimezone.on('change', () => {
            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (!date) {
                return;
            }

            App.Http.Booking.getAvailableHours(moment(date).format('YYYY-MM-DD'));

            App.Pages.Booking.updateConfirmFrame();
        });

        /**
         * Event: Selected Provider "Changed"
         *
         * Whenever the provider changes the available appointment date - time periods must be updated.
         */
        $selectProvider.on('change', () => {
            App.Pages.Booking.updateConfirmFrame();
        });

        /**
         * Event: Selected Service "Changed"
         *
         * When the user clicks on a service, its available providers should
         * become visible.
         */
        $selectService.on('change', (event) => {
            const $target = $(event.target);
            const serviceId = $selectService.val();
            const previousProviderId = $selectProvider.val();

            // LNU: Hide Provider Selection (README.md #3).
            const selectHiddenAnyProvider =
                Boolean(Number(vars('display_any_provider'))) && Boolean(Number(vars('hide_provider_selection')));

            $selectProvider.parent().prop('hidden', !Boolean(serviceId) || selectHiddenAnyProvider);

            $selectProvider.empty();

            $selectProvider.append(new Option(lang('please_select'), ''));

            let previousProviderCanServe = false;

            vars('available_providers').forEach((provider) => {
                // If the current provider is able to provide the selected service, add him to the list box.
                const canServeService =
                    provider.services.filter((providerServiceId) => Number(providerServiceId) === Number(serviceId))
                        .length > 0;

                if (canServeService) {
                    $selectProvider.append(new Option(provider.first_name + ' ' + provider.last_name, provider.id));

                    if (String(provider.id) === String(previousProviderId)) {
                        previousProviderCanServe = true;
                    }
                }
            });

            const providerOptionCount = $selectProvider.find('option').length;

            // Remove the "Please Select" option, if there is only one provider available

            if (providerOptionCount === 2) {
                $selectProvider.find('option[value=""]').remove();
                $selectProvider.val($selectProvider.find('option:first').val());
            }

            // Add the "Any Provider" entry

            if (providerOptionCount > 2 && Boolean(Number(vars('display_any_provider')))) {
                $(new Option(lang('any_provider'), 'any-provider')).insertAfter($selectProvider.find('option:first'));
            }

            // Restore previous provider selection if they can serve the new service
            if (previousProviderId && previousProviderCanServe) {
                $selectProvider.val(previousProviderId);
            } else if (previousProviderId === 'any-provider' && providerOptionCount > 2 && Boolean(Number(vars('display_any_provider')))) {
                $selectProvider.val('any-provider');
            } else if (selectHiddenAnyProvider && providerOptionCount > 2) {
                // LNU: Hide Provider Selection (README.md #3) - no explicit choice to restore, so
                // default straight to "Any Provider" since the selection UI is hidden from the customer.
                $selectProvider.val('any-provider');
            }

            App.Pages.Booking.updateConfirmFrame();

            App.Pages.Booking.updateServiceDescription(serviceId);
        });

        /**
         * Event: Next Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "next" button on the book wizard.
         * Some special tasks might be performed, depending on the current wizard step.
         */
        $('.button-next').on('click', (event) => {
            const $target = $(event.currentTarget);

            // LNU: Configurable order for booking wizard steps - each step is now identified by name rather
            // than a fixed position, so the checks below compare currentStepIndex against where that step
            // actually falls in the configured order, instead of a hardcoded data-step_index value.
            const serviceStepIndex = getStepIndexForStepName('service');
            const timeStepIndex = getStepIndexForStepName('time');
            const infoStepIndex = getStepIndexForStepName('info');

            // If we are on the service step and there is no provider selected do not continue with the next step.
            if (currentStepIndex === serviceStepIndex && !$selectProvider.val()) {
                return;
            }

            // If we are on the time step then the user should have an appointment hour selected.
            if (currentStepIndex === timeStepIndex) {
                if (!$('.selected-hour').length) {
                    if (!$('#select-hour-prompt').length) {
                        $('<div/>', {
                            'id': 'select-hour-prompt',
                            'class': 'text-danger mb-4',
                            'text': lang('appointment_hour_missing'),
                        }).prependTo('#available-hours');
                    }
                    return;
                }
            }

            // If we are on the info step then we will need to validate the user's input before proceeding to the
            // next step.
            if (currentStepIndex === infoStepIndex) {
                if (!App.Pages.Booking.validateCustomerForm()) {
                    return; // Validation failed, do not continue.
                } else {
                    App.Pages.Booking.updateConfirmFrame();

                    // Initialize ALTCHA widget if present
                    if ($('#altcha-widget').length && App.Utils.Altcha) {
                        App.Utils.Altcha.initialize('altcha-widget');
                    }
                }
            }

            // LNU: Terms & Conditions Step - the customer must accept the terms before continuing. Only
            // reachable if "terms" is actually included in booking_step_order (getStepIndexForStepName()
            // returns 0 otherwise, which currentStepIndex can never match).
            const termsStepIndex = getStepIndexForStepName('terms');

            if (currentStepIndex === termsStepIndex) {
                const $acceptToTermsPage = $('#accept-to-terms-page-checkbox');

                $acceptToTermsPage.removeClass('is-invalid');
                $('#terms-form-message').text('');

                if (!$acceptToTermsPage.prop('checked')) {
                    $acceptToTermsPage.addClass('is-invalid');
                    $('#terms-form-message').text(lang('terms_and_conditions_required'));
                    return;
                }
            }

            // Display the next step tab (uses jquery animation effect).
            currentStepIndex = currentStepIndex + 1;

            // If we are entering the time step, fetch unavailable dates and available hours ahead of time.
            if (currentStepIndex === timeStepIndex) {
                const todayMoment = moment();

                App.Utils.UI.setDateTimePickerValue($selectDate, todayMoment.toDate());

                App.Http.Booking.getUnavailableDates(
                    $selectProvider.val(),
                    $selectService.val(),
                    todayMoment.format('YYYY-MM-DD'),
                );
            }

            // LNU: Customer Booking Limits (README.md #7) - checked here, on entering the confirmation step,
            // rather than on leaving the info step: with a custom booking_step_order, service isn't guaranteed
            // to be selected by the time info is completed, but confirmation is always last, so both service
            // and the customer's info are guaranteed to be known by this point regardless of order.
            if (currentStepIndex === getStepIndexForStepName('confirmation')) {
                App.Pages.Booking.checkCustomerBookingLimits();
            }

            const wizardFrame = getWizardFrameForStepIndex(currentStepIndex);

            // Update step indicator immediately
            $('.active-step').removeClass('active-step');
            getStepMarkerForStepIndex(currentStepIndex).addClass('active-step');

            $target
                .parents()
                .eq(1)
                .fadeOut(() => {
                    $(`#${wizardFrame}`).fadeIn();
                });

            // Scroll to the top of the page. On a small screen, especially on a mobile device, this is very useful.
            const scrollingElement = document.scrollingElement || document.body;
            if (window.innerHeight < scrollingElement.scrollHeight) {
                scrollingElement.scrollTop = 0;
            }
        });

        /**
         * Event: Back Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "back" button on the
         * book wizard.
         */
        $('.button-back').on('click', (event) => {
            currentStepIndex = currentStepIndex - 1;

            const wizardFrame = getWizardFrameForStepIndex(currentStepIndex);

            // Update step indicator immediately
            $('.active-step').removeClass('active-step');
            getStepMarkerForStepIndex(currentStepIndex).addClass('active-step');

            $(event.currentTarget)
                .parents()
                .eq(1)
                .fadeOut(() => {
                    $(`#${wizardFrame}`).fadeIn();
                });
        });

        /**
         * Event: Available Hour "Click"
         *
         * Triggered whenever the user clicks on an available hour for his appointment.
         */
        $availableHours.on('click', '.available-hour', (event) => {
            $availableHours.find('.selected-hour').removeClass('selected-hour');
            $(event.target).addClass('selected-hour');
            App.Pages.Booking.updateConfirmFrame();
        });

        if (manageMode) {
            /**
             * Event: Cancel Appointment Button "Click"
             *
             * When the user clicks the "Cancel" button this form is going to be submitted. We need
             * the user to confirm this action because once the appointment is cancelled, it will be
             * deleted from the database.
             *
             * @param {jQuery.Event} event
             */
            $('#cancel-appointment').on('click', () => {
                const $cancelAppointmentForm = $('#cancel-appointment-form');

                let $cancellationReason;

                const buttons = [
                    {
                        text: lang('close'),
                        click: (event, messageModal) => {
                            messageModal.hide();
                        },
                    },
                    {
                        text: lang('confirm'),
                        click: () => {
                            if ($cancellationReason.val() === '') {
                                $cancellationReason.css('border', '2px solid #DC3545');
                                return;
                            }
                            $cancelAppointmentForm.find('#hidden-cancellation-reason').val($cancellationReason.val());
                            $cancelAppointmentForm.submit();
                        },
                    },
                ];

                App.Utils.Message.show(
                    lang('cancel_appointment_title'),
                    lang('write_appointment_removal_reason'),
                    buttons,
                );

                $cancellationReason = $('<textarea/>', {
                    'class': 'form-control mt-2',
                    'id': 'cancellation-reason',
                    'rows': '3',
                    'css': {
                        'width': '100%',
                    },
                }).appendTo('#message-modal .modal-body');

                return false;
            });

            $deletePersonalInformation.on('click', () => {
                const buttons = [
                    {
                        text: lang('cancel'),
                        click: (event, messageModal) => {
                            messageModal.hide();
                        },
                    },
                    {
                        text: lang('delete'),
                        click: () => {
                            App.Http.Booking.deletePersonalInformation(vars('customer_token'));
                        },
                    },
                ];

                App.Utils.Message.show(
                    lang('delete_personal_information'),
                    lang('delete_personal_information_prompt'),
                    buttons,
                );
            });
        }

        /**
         * Event: Book Appointment Form "Submit"
         *
         * Before the form is submitted to the server we need to make sure that in the meantime the selected appointment
         * date/time wasn't reserved by another customer or event.
         *
         * @param {jQuery.Event} event
         */
        $bookAppointmentSubmit.on('click', () => {
            const $acceptToTermsAndConditions = $('#accept-to-terms-and-conditions');

            $acceptToTermsAndConditions.removeClass('is-invalid');

            if ($acceptToTermsAndConditions.length && !$acceptToTermsAndConditions.prop('checked')) {
                $acceptToTermsAndConditions.addClass('is-invalid');
                return;
            }

            const $acceptToPrivacyPolicy = $('#accept-to-privacy-policy');

            $acceptToPrivacyPolicy.removeClass('is-invalid');

            if ($acceptToPrivacyPolicy.length && !$acceptToPrivacyPolicy.prop('checked')) {
                $acceptToPrivacyPolicy.addClass('is-invalid');
                return;
            }

            App.Http.Booking.registerAppointment();
        });

        /**
         * Event: Refresh captcha image.
         */
        $captchaTitle.on('click', 'button', () => {
            $('.captcha-image').attr('src', App.Utils.Url.siteUrl('captcha?' + Date.now()));
        });

        $selectDate.on('mousedown', '.ui-datepicker-calendar td', () => {
            setTimeout(() => {
                App.Http.Booking.applyPreviousUnavailableDates();
            }, 300);
        });
    }

    /**
     * This function validates the customer's data input. The user cannot continue without passing all the validation
     * checks.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validateCustomerForm() {
        $('#wizard-frame-3 .is-invalid').removeClass('is-invalid');
        $('#wizard-frame-3 label.text-danger').removeClass('text-danger');

        App.Utils.CustomFields.joinAllGroupValues('appt-custom-field-container');
        App.Utils.CustomFields.joinAllGroupValues('custom-field-container');

        // Validate required fields.
        let missingRequiredField = false;

        $('.required').each((index, requiredField) => {
            if (!$(requiredField).val()) {
                $(requiredField).addClass('is-invalid');
                missingRequiredField = true;
            }
        });

        if (missingRequiredField) {
            $('#form-message').text(lang('fields_are_required'));
            return false;
        }

        // Validate email address.
        if ($email.val() && !App.Utils.Validation.email($email.val())) {
            $email.addClass('is-invalid');
            $('#form-message').text(lang('invalid_email'));
            return false;
        }

        // Validate phone number.
        const phoneNumber = $phoneNumber.val();

        if (phoneNumber && !App.Utils.Validation.phone(phoneNumber)) {
            $phoneNumber.addClass('is-invalid');
            $('#form-message').text(lang('invalid_phone'));
            return false;
        }

        return true;
    }

    /**
     * LNU: Check whether the customer is allowed to make this booking, given the configured customer booking
     * limits (README.md #7). Disables the submit button until the check completes, and re-disables it if the
     * booking turns out not to be allowed.
     */
    function checkCustomerBookingLimits() {
        const customerEmail = $('#email').val();
        const serviceId = $selectService.val();
        const bookingDate = moment(App.Utils.UI.getDateTimePickerValue($selectDate)).format('YYYY-MM-DD');
        const excludeAppointmentId = manageMode ? vars('appointment_data').id : null;

        $('#book-appointment-submit').prop('disabled', true);
        $('#customer-booking-limits-wait').show();
        $('#customer-booking-limits-text').hide();

        App.Http.Booking.checkCustomerBookingLimits(customerEmail, serviceId, bookingDate, excludeAppointmentId, (result) => {
            $('#book-appointment-submit').prop('disabled', !result.allowed);
            $('#customer-booking-limits-text').html(result.message).toggleClass('text-danger', !result.allowed);
            $('#customer-booking-limits-wait').hide();
            $('#customer-booking-limits-text').show();
        });
    }

    /**
     * Every time this function is executed, it updates the confirmation page with the latest
     * customer settings and input for the appointment booking.
     */
    function updateConfirmFrame() {
        const serviceId = $selectService.val();
        const providerId = $selectProvider.val();

        // LNU: Hide Provider Selection (README.md #3).
        const selectHiddenAnyProvider =
            Boolean(Number(vars('display_any_provider'))) && Boolean(Number(vars('hide_provider_selection')));

        const serviceOptionText = serviceId ? $selectService.find('option:selected').text() : lang('service');
        const providerOptionText = providerId ? $selectProvider.find('option:selected').text() : lang('provider');

        if (selectHiddenAnyProvider) {
            $displayBookingSelection.text(`${serviceOptionText}`);
        } else {
            $displayBookingSelection.text(`${serviceOptionText} │ ${providerOptionText}`); // Notice: "│" is a custom ASCII char
        }

        if (!$availableHours.find('.selected-hour').text()) {
            return; // No time is selected, skip the rest of this function...
        }

        // Render the appointment details

        const service = vars('available_services').find(
            (availableService) => Number(availableService.id) === Number(serviceId),
        );

        if (!service) {
            return; // Service was not found
        }

        // LNU: "duration" is the full blocked timeslot (customer-facing time + cooldown) (README.md #5).
        const customerDuration = Number(service.duration) - Number(service.cooldown);

        const selectedDateObject = App.Utils.UI.getDateTimePickerValue($selectDate);
        const selectedDateMoment = moment(selectedDateObject);
        const selectedDate = selectedDateMoment.format('YYYY-MM-DD');
        const selectedTime = $availableHours.find('.selected-hour').text();

        let formattedSelectedDate = '';

        if (selectedDateObject) {
            formattedSelectedDate =
                App.Utils.Date.format(selectedDate, vars('date_format'), vars('time_format'), false) +
                ' ' +
                selectedTime;
        }

        const timezoneOptionText = $selectTimezone.find('option:selected').text();

        let appointmentDetailsHtml = `
            <div>
                <div class="mb-2 fw-bold fs-3">
                    ${serviceOptionText}
                </div>
                <div class="mb-2 fw-bold text-muted" ${selectHiddenAnyProvider ? 'hidden' : ''}>
                    ${providerOptionText}
                </div>
                <div class="mb-2">
                    <i class="fas fa-calendar-day me-2"></i>
                    ${formattedSelectedDate}
                </div>
                <div class="mb-2">
                    <i class="fas fa-clock me-2"></i>
                    ${customerDuration} ${lang('minutes')}
                </div>
                <div class="mb-2" ${Boolean(Number(vars('hide_customer_timezone'))) ? 'hidden' : ''}>
                    <i class="fas fa-globe me-2"></i>
                    ${timezoneOptionText}
                </div>
                <div class="mb-2" ${!Number(service.price) ? 'hidden' : ''}>
                    <i class="fas fa-cash-register me-2"></i>
                    ${Number(service.price).toFixed(2)} ${service.currency}
                </div>
            </div>
        `;

        // Appointment custom fields
        Array.from(document.getElementsByClassName('appt-custom-field-container')).forEach((container) => {
            const label = App.Utils.String.escapeHtml(container.querySelector('.form-label').childNodes[0].textContent.trim());
            const rawValue = container.querySelector('.form-input').value;
            const value = App.Utils.String.escapeHtml(
                rawValue ? rawValue.split(';').map((string) => lang(string)).join('; ') : lang('no_field_value'),
            );
            appointmentDetailsHtml += `
                <div class="mb-2">
                    <b>${label}:</b> ${value}
                </div>
            `;
        });

        // Attached files
        if (App.Utils.AttachedFiles.getMaxAttachedFiles() > 0) {
            const existingFileRows = Array.from(document.getElementsByClassName('existing-file-name-row'))
                .map((row) => $(row))
                .filter(($row) => $row.data('filename'));
            const hasPreviousFiles = manageMode && existingFileRows.length > 0;

            const attachedFileNamesText = App.Utils.AttachedFiles.getAttachedFiles().length
                ? App.Utils.AttachedFiles.getAttachedFiles()
                      .map((file) => App.Utils.String.escapeHtml(file.name))
                      .join('; ')
                : App.Utils.String.escapeHtml(lang('no_field_value'));

            appointmentDetailsHtml += `
                <div class="mb-2">
                    <b>${hasPreviousFiles ? lang('new_attached_files') : lang('attached_files')}:</b> ${attachedFileNamesText}
                </div>
            `;

            if (hasPreviousFiles) {
                const previousFilesText = existingFileRows
                    .map(($row) => {
                        const fileName = App.Utils.String.escapeHtml($row.data('filename'));
                        return $row.data('discarded') ? `<s>${fileName}</s>` : fileName;
                    })
                    .join('; ');

                appointmentDetailsHtml += `
                    <div class="mb-2">
                        <b>${lang('prev_attached_files')}:</b> ${previousFilesText}
                    </div>
                `;
            }
        }

        $('#appointment-details').html(appointmentDetailsHtml);

        // Render the customer information

        const firstName = App.Utils.String.escapeHtml($firstName.val());
        const lastName = App.Utils.String.escapeHtml($lastName.val());
        const fullName = `${firstName} ${lastName}`.trim();
        const email = App.Utils.String.escapeHtml($email.val());
        const phoneNumber = App.Utils.String.escapeHtml($phoneNumber.val());
        const address = App.Utils.String.escapeHtml($address.val());
        const city = App.Utils.String.escapeHtml($city.val());
        const zipCode = App.Utils.String.escapeHtml($zipCode.val());

        const addressParts = [];

        if (city) {
            addressParts.push(city);
        }

        if (zipCode) {
            addressParts.push(zipCode);
        }

        let customerDetailsHtml = `
            <div>
                <div class="mb-2 fw-bold fs-3">
                    ${lang('contact_info')}
                </div>
                <div class="mb-2 fw-bold text-muted" ${!fullName ? 'hidden' : ''}>
                    ${fullName}
                </div>
                <div class="mb-2" ${!email ? 'hidden' : ''}>
                    ${email}
                </div>
                <div class="mb-2" ${!phoneNumber ? 'hidden' : ''}>
                    ${phoneNumber}
                </div>
                <div class="mb-2" ${!address ? 'hidden' : ''}>
                    ${address}
                </div>
                <div class="mb-2" ${!addressParts.length ? 'hidden' : ''}>
                    ${addressParts.join(', ')}
                </div>
            </div>
        `;

        // Customer custom fields
        Array.from(document.getElementsByClassName('custom-field-container')).forEach((container) => {
            const label = App.Utils.String.escapeHtml(container.querySelector('.form-label').childNodes[0].textContent.trim());
            const rawValue = container.querySelector('.form-input').value;
            const value = App.Utils.String.escapeHtml(
                rawValue ? rawValue.split(';').map((string) => lang(string)).join('; ') : lang('no_field_value'),
            );
            customerDetailsHtml += `
                <div class="mb-2">
                    <b>${label}:</b> ${value}
                </div>
            `;
        });

        $('#customer-details').html(customerDetailsHtml);

        // Update appointment form data for submission to server when the user confirms the appointment.

        const data = {};

        data.customer = {
            last_name: $lastName.val(),
            first_name: $firstName.val(),
            email: $email.val(),
            phone_number: $phoneNumber.val(),
            address: $address.val(),
            city: $city.val(),
            zip_code: $zipCode.val(),
            timezone: $selectTimezone.val(),
        };

        App.Utils.CustomFields.getFieldIndexes('custom-field-container').forEach((i) => {
            data.customer[`custom_field_${i}`] = $(`#custom-field-${i}`).val();
        });

        data.appointment = {
            start_datetime:
                moment(App.Utils.UI.getDateTimePickerValue($selectDate)).format('YYYY-MM-DD') +
                ' ' +
                moment($('.selected-hour').data('value'), 'HH:mm').format('HH:mm') +
                ':00',
            end_datetime: calculateEndDatetime(),
            notes: $notes.val(),
            is_unavailability: false,
            id_users_provider: $selectProvider.val(),
            id_services: $selectService.val(),
        };

        App.Utils.CustomFields.getFieldIndexes('appt-custom-field-container').forEach((i) => {
            data.appointment[`appt_custom_field_${i}`] = $(`#appt-custom-field-${i}`).val();
        });

        data.manage_mode = Number(manageMode);
        data.discarded_file_names = App.Utils.AttachedFiles.getDiscardedFileNames();

        if (manageMode) {
            data.appointment.id = vars('appointment_data').id;
            data.customer.id = vars('customer_data').id;
        }

        $('input[name="post_data"]').val(JSON.stringify(data));
    }

    /**
     * This method calculates the end datetime of the current appointment.
     *
     * End datetime is depending on the service and start datetime fields.
     *
     * @return {String} Returns the end datetime in string format.
     */
    function calculateEndDatetime() {
        // Find selected service duration.
        const serviceId = $selectService.val();

        const service = vars('available_services').find(
            (availableService) => Number(availableService.id) === Number(serviceId),
        );

        // Add the duration to the start datetime.
        const selectedDate = moment(App.Utils.UI.getDateTimePickerValue($selectDate)).format('YYYY-MM-DD');

        const selectedHour = $('.selected-hour').data('value'); // HH:mm

        const startMoment = moment(selectedDate + ' ' + selectedHour);

        let endMoment;

        if (service.duration && startMoment) {
            endMoment = startMoment.clone().add({'minutes': parseInt(service.duration)});
        } else {
            endMoment = moment();
        }

        return endMoment.format('YYYY-MM-DD HH:mm:ss');
    }

    /**
     * This method applies the appointment's data to the wizard so
     * that the user can start making changes on an existing record.
     *
     * @param {Object} appointment Selected appointment's data.
     * @param {Object} provider Selected provider's data.
     * @param {Object} customer Selected customer's data.
     *
     * @return {Boolean} Returns the operation result.
     */
    function applyAppointmentData(appointment, provider, customer) {
        try {
            // Select Service & Provider
            $selectService.val(appointment.id_services).trigger('change');
            $selectProvider.val(appointment.id_users_provider);

            // Set Appointment Date
            const startMoment = moment(appointment.start_datetime);
            App.Utils.UI.setDateTimePickerValue($selectDate, startMoment.toDate());
            App.Http.Booking.getAvailableHours(startMoment.format('YYYY-MM-DD'));

            // Update unavailable dates while in manage mode

            App.Http.Booking.getUnavailableDates(
                appointment.id_users_provider,
                appointment.id_services,
                startMoment.format('YYYY-MM-DD'),
            );

            // Initialize attached files
            App.Utils.AttachedFiles.initialize(appointment.id, appointment.attached_file_names || []);

            // Apply Customer's Data
            $lastName.val(customer.last_name);
            $firstName.val(customer.first_name);
            $email.val(customer.email);
            $phoneNumber.val(customer.phone_number);
            $address.val(customer.address);
            $city.val(customer.city);
            $zipCode.val(customer.zip_code);
            if (customer.timezone) {
                $selectTimezone.val(customer.timezone);
            }
            const appointmentNotes = appointment.notes !== null ? appointment.notes : '';
            $notes.val(appointmentNotes);

            App.Utils.CustomFields.getFieldIndexes('appt-custom-field-container').forEach((i) => {
                $(`#appt-custom-field-${i}`).val(appointment[`appt_custom_field_${i}`]);
            });

            App.Utils.CustomFields.splitAllGroupValues('appt-custom-field-container');

            App.Utils.CustomFields.getFieldIndexes('custom-field-container').forEach((i) => {
                $(`#custom-field-${i}`).val(customer[`custom_field_${i}`]);
            });

            App.Utils.CustomFields.splitAllGroupValues('custom-field-container');

            App.Pages.Booking.updateConfirmFrame();

            return true;
        } catch (exc) {
            return false;
        }
    }

    /**
     * Update the service description and information.
     *
     * This method updates the HTML content with a brief description of the
     * user selected service (only if available in db). This is useful for the
     * customers upon selecting the correct service.
     *
     * @param {Number} serviceId The selected service record id.
     */
    function updateServiceDescription(serviceId) {
        const $serviceDescription = $('#service-description');

        $serviceDescription.empty();

        const service = vars('available_services').find(
            (availableService) => Number(availableService.id) === Number(serviceId),
        );

        if (!service) {
            return; // Service not found
        }

        // Render the additional service information

        const additionalInfoParts = [];

        // LNU: "duration" is the full blocked timeslot (customer-facing time + cooldown) (README.md #5).
        const customerDuration = Number(service.duration) - Number(service.cooldown);

        if (customerDuration) {
            additionalInfoParts.push(`${lang('duration')}: ${customerDuration} ${lang('minutes')}`);
        }

        if (Number(service.price) > 0) {
            additionalInfoParts.push(`${lang('price')}: ${Number(service.price).toFixed(2)} ${service.currency}`);
        }

        if (service.location) {
            additionalInfoParts.push(`${lang('location')}: ${service.location}`);
        }

        if (additionalInfoParts.length) {
            $(`
                <div class="mb-2 fst-italic">
                    ${additionalInfoParts.join(', ')}
                </div>
            `).appendTo($serviceDescription);
        }

        // Render the service description

        if (service.description?.length) {
            const escapedDescription = App.Utils.String.escapeHtml(service.description);

            const multiLineDescription = escapedDescription.replaceAll('\n', '<br/>');

            $(`
                <div class="text-muted">
                    ${multiLineDescription}
                </div>
            `).appendTo($serviceDescription);
        }
    }

    /**
     * Save customer information to localStorage.
     */
    function saveCustomerInfo() {
        const customerInfo = {
            firstName: $firstName.val(),
            lastName: $lastName.val(),
            email: $email.val(),
            phoneNumber: $phoneNumber.val(),
            address: $address.val(),
            city: $city.val(),
            zipCode: $zipCode.val(),
            rememberMe: true,
        };

        App.Utils.CustomFields.getFieldIndexes('custom-field-container').forEach((i) => {
            customerInfo[`customField${i}`] = $(`#custom-field-${i}`).val();
        });

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(customerInfo));
        } catch (e) {
            console.warn('Could not save customer info to localStorage:', e);
        }
    }

    /**
     * Load customer information from localStorage.
     * GET parameters have priority over stored values.
     */
    function loadCustomerInfo() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);

            if (!stored) {
                return;
            }

            const customerInfo = JSON.parse(stored);

            // Restore remember me checkbox state
            if (customerInfo.rememberMe) {
                $rememberMe.prop('checked', true);
            }

            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);

            // Only populate fields that don't have GET params and are empty
            if (!urlParams.has('first_name') && !$firstName.val()) {
                $firstName.val(customerInfo.firstName || '');
            }
            if (!urlParams.has('last_name') && !$lastName.val()) {
                $lastName.val(customerInfo.lastName || '');
            }
            if (!urlParams.has('email') && !$email.val()) {
                $email.val(customerInfo.email || '');
            }
            if (!urlParams.has('phone_number') && !$phoneNumber.val()) {
                $phoneNumber.val(customerInfo.phoneNumber || '');
            }
            if (!urlParams.has('address') && !$address.val()) {
                $address.val(customerInfo.address || '');
            }
            if (!urlParams.has('city') && !$city.val()) {
                $city.val(customerInfo.city || '');
            }
            if (!urlParams.has('zip_code') && !$zipCode.val()) {
                $zipCode.val(customerInfo.zipCode || '');
            }
            App.Utils.CustomFields.getFieldIndexes('custom-field-container').forEach((i) => {
                const $field = $(`#custom-field-${i}`);
                if (!urlParams.has(`custom_field_${i}`) && !$field.val()) {
                    $field.val(customerInfo[`customField${i}`] || '');
                }
            });
            App.Utils.CustomFields.splitAllGroupValues('custom-field-container');
        } catch (e) {
            console.warn('Could not load customer info from localStorage:', e);
        }
    }

    /**
     * Clear customer information from localStorage.
     */
    function clearCustomerInfo() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            console.warn('Could not clear customer info from localStorage:', e);
        }
    }

    /**
     * Initialize the remember me functionality.
     */
    function initializeRememberMe() {
        // Skip if in manage mode (checkbox not present, use appointment data)
        if (manageMode || !$rememberMe.length) {
            return;
        }

        // Load stored customer info on page load
        loadCustomerInfo();

        // Handle remember me checkbox change
        $rememberMe.on('change', function () {
            if ($(this).prop('checked')) {
                saveCustomerInfo();
            } else {
                clearCustomerInfo();
            }
        });

        // Save customer info before form submission if remember me is checked
        $bookAppointmentSubmit.on('click', function () {
            if ($rememberMe.prop('checked')) {
                saveCustomerInfo();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        manageMode,
        updateConfirmFrame,
        updateServiceDescription,
        validateCustomerForm,
        checkCustomerBookingLimits,
    };
})();
