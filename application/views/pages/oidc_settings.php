<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="oidc-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="oidc-settings" class="col-sm-9">
            <form>
                <fieldset>
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                        <h4 class="mb-0 fw-light">
                            <?= lang('oidc') ?>
                        </h4>

                        <div>
                            <a href="<?= site_url('integrations') ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-chevron-left me-2"></i>
                                <?= lang('back') ?>
                            </a>

                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <div class="form-text text-muted mb-4">
                                    <?= lang('oidc_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="oidc-enabled-booking"
                                           data-field="oidc_enabled_booking">
                                    <label class="form-check-label" for="oidc-enabled-booking">
                                        <?= lang('oidc_enabled_booking') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="oidc-client-id" class="form-label">
                                    <?= lang('oidc_client_id') ?>
                                </label>
                                <input type="text" class="form-control" id="oidc-client-id"
                                       data-field="oidc_client_id"
                                       placeholder="<?= lang('oidc_client_id') ?>">
                                <div class="form-text text-muted">
                                    <?= lang('oidc_client_id_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="oidc-client-secret" class="form-label">
                                    <?= lang('oidc_client_secret') ?>
                                </label>
                                <input type="password" class="form-control" id="oidc-client-secret"
                                       data-field="oidc_client_secret"
                                       placeholder="<?= setting('oidc_client_secret')
                                           ? lang('oidc_client_secret_saved_placeholder')
                                           : lang('oidc_client_secret') ?>">
                                <div class="form-text text-muted">
                                    <?= lang('oidc_client_secret_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="oidc-idp-url" class="form-label">
                                    <?= lang('oidc_idp_url') ?>
                                </label>
                                <input type="text" class="form-control" id="oidc-idp-url"
                                       data-field="oidc_idp_url"
                                       placeholder="https://idp.example.com/realms/example">
                                <div class="form-text text-muted">
                                    <?= lang('oidc_idp_url_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="oidc-booking-user-param-restrictions" class="form-label">
                                    <?= lang('oidc_booking_user_param_restrictions') ?>
                                </label>
                                <input type="text" class="form-control" id="oidc-booking-user-param-restrictions"
                                       data-field="oidc_booking_user_param_restrictions"
                                       placeholder="affiliation=student|employee">
                                <div class="form-text text-muted">
                                    <?= lang('oidc_booking_user_param_restrictions_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="oidc-booking-user-param-disallowed-title" class="form-label">
                                    <?= lang('oidc_booking_user_param_disallowed_title') ?>
                                </label>
                                <input type="text" class="form-control" id="oidc-booking-user-param-disallowed-title"
                                       data-field="oidc_booking_user_param_disallowed_title">
                            </div>

                            <div class="mb-3">
                                <label for="oidc-booking-user-param-disallowed-message" class="form-label">
                                    <?= lang('oidc_booking_user_param_disallowed_message') ?>
                                </label>
                                <input type="text" class="form-control" id="oidc-booking-user-param-disallowed-message"
                                       data-field="oidc_booking_user_param_disallowed_message">
                                <div class="form-text text-muted">
                                    <?= lang('oidc_booking_user_param_disallowed_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="oidc-booking-logout-after-register"
                                           data-field="oidc_booking_logout_after_register">
                                    <label class="form-check-label" for="oidc-booking-logout-after-register">
                                        <?= lang('oidc_booking_logout_after_register') ?>
                                    </label>
                                </div>
                                <div class="form-text text-muted">
                                    <?= lang('oidc_booking_logout_after_register_info') ?>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= lang('oidc_setup_info') ?>
                            </div>
                        </div>
                    </div>

                </fieldset>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/oidc_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/oidc_settings.js') ?>"></script>

<?php end_section('scripts'); ?>
