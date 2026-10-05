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

                        <div>
                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-text text-muted mb-4">
                        <?= lang('email_settings_info') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="mail-protocol"><?= lang('mail_protocol') ?></label>
                        <select id="mail-protocol" class="form-select" data-field="mail_protocol">
                            <option value="smtp"><?= lang('mail_protocol_smtp') ?></option>
                            <option value="mail"><?= lang('mail_protocol_php') ?></option>
                        </select>
                        <div class="form-text text-muted small"><?= lang('mail_protocol_hint') ?></div>
                    </div>

                    <div id="smtp-fields">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label" for="mail-smtp-host"><?= lang('mail_smtp_host') ?></label>
                                <input id="mail-smtp-host" class="form-control" data-field="mail_smtp_host"
                                       placeholder="smtp.example.com" autocomplete="off">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="mail-smtp-port"><?= lang('mail_smtp_port') ?></label>
                                <input id="mail-smtp-port" type="number" min="1" max="65535" class="form-control"
                                       data-field="mail_smtp_port" placeholder="587">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mail-smtp-crypto"><?= lang('mail_smtp_crypto') ?></label>
                            <select id="mail-smtp-crypto" class="form-select" data-field="mail_smtp_crypto">
                                <option value="tls">STARTTLS (587)</option>
                                <option value="ssl">SSL/TLS (465)</option>
                                <option value="none"><?= lang('mail_smtp_crypto_none') ?></option>
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input id="mail-smtp-auth" type="checkbox" class="form-check-input"
                                   data-field="mail_smtp_auth">
                            <label class="form-check-label" for="mail-smtp-auth"><?= lang('mail_smtp_auth') ?></label>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="mail-smtp-user"><?= lang('mail_smtp_user') ?></label>
                                <input id="mail-smtp-user" class="form-control" data-field="mail_smtp_user"
                                       autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="mail-smtp-pass"><?= lang('mail_smtp_pass') ?></label>
                                <input id="mail-smtp-pass" type="password" class="form-control"
                                       data-field="mail_smtp_pass" autocomplete="new-password">
                                <div id="smtp-pass-hint" class="form-text text-muted small d-none">
                                    <?= lang('mail_smtp_pass_hint') ?>
                                    <a href="#" id="clear-smtp-pass"><?= lang('mail_smtp_pass_clear') ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="mail-mailtype"><?= lang('mail_type') ?></label>
                        <select id="mail-mailtype" class="form-select" data-field="mail_mailtype">
                            <option value="html"><?= lang('mail_type_html') ?></option>
                            <option value="text"><?= lang('mail_type_text') ?></option>
                        </select>
                    </div>

                    <h5 class="fw-light border-bottom pb-2 mt-4 mb-3"><?= lang('mail_sender') ?></h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="mail-from-name"><?= lang('mail_from_name') ?></label>
                            <input id="mail-from-name" class="form-control" data-field="mail_from_name"
                                   placeholder="<?= e(setting('company_name')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="mail-from-address"><?= lang('mail_from_address') ?></label>
                            <input id="mail-from-address" type="email" class="form-control"
                                   data-field="mail_from_address" placeholder="<?= e(setting('company_email')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="mail-reply-to"><?= lang('mail_reply_to') ?></label>
                        <input id="mail-reply-to" type="email" class="form-control" data-field="mail_reply_to"
                               placeholder="<?= e(setting('company_email')) ?>">
                        <div class="form-text text-muted small"><?= lang('mail_sender_hint') ?></div>
                    </div>

                    <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                        <h5 class="fw-light border-bottom pb-2 mt-4 mb-3"><?= lang('mail_test') ?></h5>

                        <div class="input-group mb-2">
                            <input id="mail-test-recipient" type="email" class="form-control"
                                   placeholder="<?= lang('email') ?>">
                            <button type="button" id="send-test-mail" class="btn btn-outline-primary">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?= lang('mail_test_send') ?>
                            </button>
                        </div>
                        <div class="form-text text-muted small"><?= lang('mail_test_hint') ?></div>
                        <div id="mail-test-result" class="alert mt-3 d-none" style="white-space: pre-wrap;"></div>
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
