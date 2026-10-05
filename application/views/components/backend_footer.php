<?php
/**
 * Local variables.
 *
 * @var string $user_display_name
 */
?>
<div id="footer" class="d-lg-flex justify-content-lg-start align-items-lg-center p-2 text-center text-lg-left mt-auto bg-body border-top" style="font-size: 11px;">
    <div class="mb-3 me-lg-5 mb-lg-0">
        <img class="me-1" src="<?= base_url('assets/img/logo-16x16.png') ?>" alt="Easy!Appointments Logo">

        <a href="https://easyappointments.org" target="_blank">Easy!Appointments</a>

        <span>v<?= config('version') ?></span>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <i class="fas fa-code-branch me-1"></i>
        <a href="#" id="whats-new-open"><?= lang('whats_new') ?> (<?= e(config('fork_version')) ?>)</a>
        &middot;
        <a href="<?= e(config('fork_changes_url')) ?>" target="_blank" rel="noopener">
            <?= lang('fork_changes_link') ?>
        </a>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <img class="me-1" src="<?= base_url('assets/img/alextselegidis-logo-16x16.png') ?>" alt="Alex Tselegidis Logo">

        <a href="https://alextselegidis.com" target="_blank">Alex Tselegidis</a>

        &copy; <?= date('Y') ?> - Software Development
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <?= lang('licensed_under') ?>
        <a href="https://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank">
            GPL-3.0
        </a>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <span id="select-language" class="badge bg-dark">
            <i class="fas fa-language me-2"></i>
        	<?= ucfirst(config('language')) ?>
        </span>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <a href="<?= site_url('appointments') ?>">
            <?= lang('go_to_booking_page') ?>
        </a>
    </div>

    <div class="ms-lg-auto">
        <strong id="footer-user-display-name">
            <?= lang('hello') . ', ' . e($user_display_name) ?>!
        </strong>
    </div>
</div>



<?php
$whats_new_german = strtolower((string) config('language')) === 'german';
$whats_new_entries = config('whats_new') ?: [];
?>
<div class="modal fade" id="whats-new-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('whats_new') ?> &ndash; v<?= e(config('fork_version')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php foreach ($whats_new_entries as $entry): ?>
                    <h6 class="fw-bold">
                        v<?= e($entry['version']) ?> <small class="text-muted fw-normal"><?= e($entry['date']) ?></small>
                    </h6>
                    <ul>
                        <?php foreach ($entry['items'] as $item): ?>
                            <li><?= e($whats_new_german ? $item['de'] : $item['en']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <a href="<?= e(config('fork_changes_url')) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <?= lang('fork_changes_link') ?>
                </a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= lang('close') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // LNU: show the "What's new" window once per browser after the fork version changed.
    document.addEventListener('DOMContentLoaded', function () {
        const key = 'ea_whats_new_seen_version';
        const version = <?= json_encode((string) config('fork_version')) ?>;
        const modal = new bootstrap.Modal(document.getElementById('whats-new-modal'));

        function remember() {
            try {
                localStorage.setItem(key, version);
            } catch (e) {}
        }

        document.getElementById('whats-new-modal').addEventListener('hidden.bs.modal', remember);

        document.getElementById('whats-new-open').addEventListener('click', function (event) {
            event.preventDefault();
            modal.show();
        });

        let seen = null;

        try {
            seen = localStorage.getItem(key);
        } catch (e) {}

        if (seen !== version) {
            modal.show();
        }
    });
</script>
