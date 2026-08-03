<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div class="d-flex align-items-center justify-content-center min-vh-100-">
    <div class="text-center py-4 px-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-4" style="width: 100px; height: 100px;">
            <i class="fas fa-calendar-check fa-3x text-success"></i>
        </div>

        <h3 class="text-success fw-semibold mb-4"><?= lang('appointment_registered') ?></h3>

        <p class="fs-5 text-muted mb-1">
            <?= lang('appointment_details_was_sent_to_you') ?>
        </p>

        <p class="text-muted small mb-4">
            <?= lang('check_spam_folder') ?>
        </p>

        <?php
        // LNU: Custom Messages during Booking (README.md #12) - a custom link, when configured, replaces the
        // "return to booking" link below. $custom_link/$custom_link_text can each be either a translation id or
        // literal plain text - see booking_type_step.php for the resolution rules.
        $custom_link = setting('booking_custom_message_confirm_link', '');
        $custom_link_text = setting('booking_custom_message_confirm_link_text', '');
        $resolved_custom_link = get_instance()->lang->line($custom_link, false);
        $resolved_custom_link_text = get_instance()->lang->line($custom_link_text, false);
        $custom_link_enabled =
            setting('booking_custom_messages_enabled', 0) &&
            $custom_link !== '' &&
            $custom_link_text !== '' &&
            $resolved_custom_link !== '' &&
            $resolved_custom_link_text !== '';
        $custom_link_display = $resolved_custom_link !== false ? $resolved_custom_link : $custom_link;
        $custom_link_text_display = $resolved_custom_link_text !== false ? $resolved_custom_link_text : $custom_link_text;
        ?>

        <?php // The custom link can be arbitrarily long, so it stays stacked with "Add to Google Calendar" instead
        // of sharing a row with it (unlike the short, fixed-width default "return to booking" button). ?>
        <div class="d-flex flex-column <?= $custom_link_enabled ? 'align-items-center' : 'flex-sm-row' ?> gap-2 justify-content-center mt-4">
            <?php if ($custom_link_enabled): ?>
            <a href="<?= $custom_link_display ?>" id="custom-message-confirm-link" class="btn btn-primary px-4 py-2">
                <?= $custom_link_text_display ?>
            </a>
            <?php else: ?>
            <a href="<?= site_url() ?>" class="btn btn-primary px-4 py-2">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= lang('go_to_booking_page') ?>
            </a>
            <?php endif; ?>

            <?php if (vars('display_add_to_google_calendar') === '1'): ?>
            <a href="<?= vars(
                'add_to_google_url',
            ) ?>" id="add-to-google-calendar" class="btn btn-outline-primary px-4 py-2" target="_blank">
                <i class="fab fa-google me-2"></i>
                <?= lang('add_to_google_calendar') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
