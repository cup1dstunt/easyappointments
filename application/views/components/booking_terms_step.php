<?php
/**
 * LNU: Terms & Conditions Step - an optional 5th booking wizard step ("terms" in booking_step_order) that
 * shows the full terms and conditions content inline, with its own acceptance checkbox. Independent of the
 * existing "read and agree to terms and conditions" checkbox on the confirmation step - an admin can use
 * either, both, or neither.
 */
?>

<div id="wizard-frame-5" class="wizard-frame p-3 p-md-4" style="display:none;" data-step="terms">
    <div class="frame-container py-3" style="min-height: 500px;">
        <h2 class="frame-title fw-light text-center mb-4 text-muted"><?= lang('terms_and_conditions') ?></h2>

        <div class="row frame-content">
            <div class="col-12">
                <div id="terms-text">
                    <?= lang(setting('terms_and_conditions_content', '')) ?>
                </div>
            </div>
        </div>

        <div class="d-flex fs-6 justify-content-around">
            <div class="form-check mt-4 mb-3">
                <input type="checkbox" class="required form-check-input" id="accept-to-terms-page-checkbox">
                <label class="form-check-label" for="accept-to-terms-page-checkbox">
                    <?= lang('terms_and_conditions_accepted') ?>
                </label>
            </div>
        </div>

        <div class="text-center">
            <span id="terms-form-message" class="text-danger small"></span>
        </div>
    </div>

    <div class="command-buttons text-center my-3 mx-auto d-md-flex justify-content-md-between">
        <button type="button" id="button-back-5" class="btn button-back btn-outline-secondary" style="min-width: 120px; margin-right: 10px;">
            <i class="fas fa-chevron-left me-2"></i>
            <?= lang('back') ?>
        </button>
        <button type="button" id="button-next-5" class="btn button-next btn-dark" style="min-width: 120px; margin-right: 10px;">
            <?= lang('next') ?>
            <i class="fas fa-chevron-right ms-2"></i>
        </button>
    </div>
</div>
