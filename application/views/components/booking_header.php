<?php
/**
 * Local variables.
 *
 * @var string $company_name
 */
// LNU: Hide Provider Selection (README.md #3).
$hide_provider_selection = boolval(setting('display_any_provider', 0)) && boolval(setting('hide_provider_selection', 0));

// LNU: Configurable order for booking wizard steps - see resolve_booking_step_order() (booking_helper.php)
// for the validation this goes through; shared with Booking.php, which passes this same setting through as
// the "booking_step_order" script var.
$step_order = resolve_booking_step_order();

$step_labels = [
    'service' => lang('service_and_provider'),
    'time' => lang('appointment_date_and_time'),
    'info' => lang('customer_information'),
    'confirmation' => lang('appointment_confirmation'),
];
?>

<div id="header" class="overflow-hidden p-3 p-md-4 d-flex flex-column flex-lg-row align-items-center bg-primary">
    <div id="company-name" class="d-block d-md-inline-block float-md-start text-center text-md-start text-white fs-4 fw-light my-3 my-md-0 mw-100 flex-grow-1" style="min-width: 0; line-height: 1.4;">
        <img src="<?= vars('company_logo') ?: base_url('assets/img/logo.png') ?>" alt="logo" id="company-logo"
             class="d-block d-md-inline-block mx-auto mx-md-0 float-md-start me-md-3 mb-3 mb-md-0" style="max-height: 56px;">

        <span>
            <?= e($company_name) ?>
        </span>

        <div class="d-flex justify-content-center justify-content-md-start">
            <span class="display-booking-selection small fw-normal text-white-50">
                <?= lang('service') ?><?= $hide_provider_selection ? '' : ' │ ' . lang('provider') ?>
            </span>
        </div>
    </div>

    <div id="steps" class="d-block d-md-inline-block float-md-end overflow-hidden mx-auto my-3 my-md-1">
        <?php // LNU: Configurable order for booking wizard steps - booking.js's initialize() marks the actual
        // first-shown step active (which may not be $step_order's first entry, if the service step ends up
        // auto-skipped), so no step is marked active here. ?>
        <?php foreach ($step_order as $index => $step): ?>
            <div id="step-<?= $index + 1 ?>"
                 class="book-step d-inline-block float-start rounded text-center"
                 data-tippy-content="<?= $step_labels[$step] ?>" data-step="<?= $step ?>">
                <strong class="d-block" style="cursor: default;"><?= $index + 1 ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>
