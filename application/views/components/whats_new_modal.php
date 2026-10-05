<?php
/** @var EA_Controller $CI */
$CI = &get_instance();
$CI->load->library('whats_new');

$releases = $CI->whats_new->get_releases();
$compare_url = $CI->whats_new->get_compare_url();
$user_id = session('user_id') ? (int) session('user_id') : null;
$auto_show = $CI->whats_new->is_unseen($user_id);

// Count the release as seen as soon as it is shown, so it never reappears on the next page load or menu change.
if ($auto_show) {
    $CI->whats_new->mark_seen($user_id);
}
?>

<div id="whats-new-modal" class="modal fade" tabindex="-1" data-auto-show="<?= $auto_show ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?= lang('whats_new') ?></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <?php foreach ($releases as $index => $release): ?>
                    <h5 class="<?= $index > 0 ? 'mt-4 ' : '' ?>mb-2">
                        <?= e($release['date']) ?>
                        <?php if ($index === 0): ?>
                            <span class="badge bg-success ms-2"><?= lang('whats_new_latest') ?></span>
                        <?php endif; ?>
                    </h5>
                    <ul class="mb-0">
                        <?php foreach ($release['entries'] as $entry): ?>
                            <li class="mb-1"><?= e($entry) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>

            <div class="modal-footer">
                <?php if (!empty($compare_url)): ?>
                    <a href="<?= e($compare_url) ?>" target="_blank" rel="noopener" class="me-auto small">
                        <?= lang('whats_new_all_changes') ?>
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= lang('close') ?></button>
            </div>
        </div>
    </div>
</div>
