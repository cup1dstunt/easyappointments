<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="email-templates-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="email-templates" class="col-sm-9">
            <form>
                <fieldset>
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                        <h4 class="mb-0 fw-light">
                            <?= lang('email_templates') ?>
                        </h4>

                        <div>
                            <button type="button" id="reset-settings" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-undo me-2"></i>
                                <?= lang('email_template_reset') ?>
                            </button>

                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-text text-muted mb-4">
                        <?= lang('email_templates_info') ?>
                    </div>

                    <?php foreach (['confirmation', 'deleted', 'password_reset', 'recovery'] as $key): ?>
                        <div class="email-template card mb-4" data-template="<?= $key ?>">
                            <div class="card-header">
                                <strong><?= lang('email_template_' . $key) ?></strong>
                            </div>
                            <div class="card-body">
                                <div class="form-text text-muted mb-3">
                                    <?= lang('email_template_' . $key . '_info') ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="email-<?= $key ?>-subject">
                                        <?= lang('email_template_subject') ?>
                                    </label>
                                    <input type="text" id="email-<?= $key ?>-subject" class="form-control"
                                           data-field="email_<?= $key ?>_subject"
                                           placeholder="<?= e(lang('email_template_' . $key . '_default_subject')) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="email-<?= $key ?>-body">
                                        <?= lang('email_template_body') ?>
                                    </label>
                                    <textarea id="email-<?= $key ?>-body" class="form-control" rows="6"
                                              data-field="email_<?= $key ?>_body"
                                              placeholder="<?= e(lang('email_template_' . $key . '_default_body')) ?>"></textarea>
                                </div>

                                <div>
                                    <label class="form-label">
                                        <?= lang('email_template_variables') ?>
                                    </label>
                                    <div class="email-template-variables"></div>
                                    <div class="form-text text-muted small">
                                        <?= lang('email_template_variables_hint') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/email_templates_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/email_templates.js') ?>"></script>

<?php end_section('scripts'); ?>
