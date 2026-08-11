<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="zoom-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="zoom-settings" class="col-sm-9">
            <form>
                <fieldset>
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                        <h4 class="mb-0 fw-light">
                            <?= lang('zoom') ?>
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
                                    <?= lang('zoom_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="zoom-enabled"
                                           data-field="zoom_enabled">
                                    <label class="form-check-label" for="zoom-enabled">
                                        <?= lang('active') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="zoom-client-id" class="form-label">
                                    <?= lang('zoom_client_id') ?>
                                </label>
                                <input type="text" class="form-control" id="zoom-client-id"
                                       data-field="zoom_client_id"
                                       placeholder="<?= lang('zoom_client_id') ?>">
                                <div class="form-text text-muted">
                                    <?= lang('zoom_client_id_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="zoom-client-secret" class="form-label">
                                    <?= lang('zoom_client_secret') ?>
                                </label>
                                <input type="password" class="form-control" id="zoom-client-secret"
                                       data-field="zoom_client_secret"
                                       placeholder="<?= setting('zoom_client_secret') ? lang('zoom_client_secret_saved_placeholder') : lang('zoom_client_secret') ?>">
                                <div class="form-text text-muted">
                                    <?= lang('zoom_client_secret_info') ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="zoom-account-id" class="form-label">
                                    <?= lang('zoom_account_id') ?>
                                </label>
                                <input type="text" class="form-control" id="zoom-account-id"
                                       data-field="zoom_account_id"
                                       placeholder="<?= lang('zoom_account_id') ?>">
                                <div class="form-text text-muted">
                                    <?= lang('zoom_account_id_info') ?>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= lang('zoom_setup_info') ?>
                                <a href="https://marketplace.zoom.us" target="_blank">
                                    Zoom App Marketplace
                                </a>
                            </div>

                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= lang('zoom_provider_opt_in_info') ?>
                            </div>

                            <div class="text-end mb-3">
                                <button class="btn btn-outline-secondary" id="apply-zoom-to-all-providers"
                                        type="button" disabled>
                                    <i class="fas fa-check"></i>
                                    <?= lang('activate_zoom_for_all_providers') ?>
                                </button>
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

<script src="<?= asset_url('assets/js/http/zoom_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/zoom_settings.js') ?>"></script>

<?php end_section('scripts'); ?>
