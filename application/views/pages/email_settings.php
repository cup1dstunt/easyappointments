<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="email-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="email-settings" class="col-sm-9">
            <form>
                <fieldset>
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                        <h4 class="mb-0 fw-light">
                            <?= lang('email_settings') ?>
                        </h4>

                        <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                            <button type="button" id="save-settings" class="btn btn-primary">
                                <i class="fas fa-check-square me-2"></i>
                                <?= lang('save') ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="form-text text-muted mb-4">
                        <?= lang('email_settings_info') ?>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="smtp-enabled" data-field="smtp_enabled">
                            <label class="form-check-label" for="smtp-enabled"><?= lang('smtp_enabled') ?></label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="smtp-host" class="form-label"><?= lang('smtp_host') ?></label>
                            <input id="smtp-host" class="form-control" data-field="smtp_host" placeholder="smtp.example.com">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="smtp-port" class="form-label"><?= lang('smtp_port') ?></label>
                            <input id="smtp-port" type="number" class="form-control" data-field="smtp_port" placeholder="587">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="smtp-crypto" class="form-label"><?= lang('smtp_encryption') ?></label>
                        <select id="smtp-crypto" class="form-select" data-field="smtp_crypto">
                            <option value="tls">STARTTLS (587)</option>
                            <option value="ssl">SSL/TLS (465)</option>
                            <option value=""><?= lang('smtp_encryption_none') ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="smtp-auth" data-field="smtp_auth">
                            <label class="form-check-label" for="smtp-auth"><?= lang('smtp_auth') ?></label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp-user" class="form-label"><?= lang('username') ?></label>
                            <input id="smtp-user" class="form-control" data-field="smtp_user" autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp-pass" class="form-label"><?= lang('password') ?></label>
                            <input id="smtp-pass" type="password" class="form-control" autocomplete="new-password">
                            <div id="smtp-pass-hint" class="form-text d-none"><?= lang('smtp_password_keep') ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp-from-name" class="form-label"><?= lang('smtp_from_name') ?></label>
                            <input id="smtp-from-name" class="form-control" data-field="smtp_from_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp-from-address" class="form-label"><?= lang('smtp_from_address') ?></label>
                            <input id="smtp-from-address" type="email" class="form-control" data-field="smtp_from_address">
                        </div>
                    </div>

                    <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                        <div class="border-top pt-3 mt-3">
                            <label for="smtp-test-recipient" class="form-label"><?= lang('smtp_test_recipient') ?></label>
                            <div class="input-group">
                                <input id="smtp-test-recipient" type="email" class="form-control">
                                <button type="button" id="send-test-email" class="btn btn-outline-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?= lang('smtp_send_test') ?>
                                </button>
                            </div>
                            <div class="form-text"><?= lang('smtp_test_hint') ?></div>
                        </div>
                    <?php endif; ?>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/email_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/email_settings.js') ?>"></script>

<?php end_section('scripts'); ?>
