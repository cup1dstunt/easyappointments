<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div>
    <?php if (vars('message_icon_class')): ?>
        <?php // LNU: an icon-badge variant, matching booking_confirmation.php's style - opt in per-caller via
        // "message_icon_class"/"message_icon_color", falling back to the plain image below for callers that
        // don't set them, so existing pages don't need to change. ?>
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-<?= e(
            vars('message_icon_color', 'danger'),
        ) ?> bg-opacity-10 mx-auto mb-4" style="width: 100px; height: 100px;">
            <i class="fas <?= e(vars('message_icon_class')) ?> fa-3x text-<?= e(
                vars('message_icon_color', 'danger'),
            ) ?>"></i>
        </div>
    <?php else: ?>
        <img id="message-icon" class="mt-0 mb-5" src="<?= vars('message_icon') ?>" alt="warning">
    <?php endif; ?>
</div>

<div class="mb-5">
    <h4 class="mb-5"><?= vars('message_title') ?></h4>

    <p><?= pure_html(vars('message_text')) ?></p>

    <?php if (vars('retry_url')): ?>
        <p><a href="<?= e(vars('retry_url')) ?>"><?= lang('restart_booking') ?></a></p>
    <?php endif; ?>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
