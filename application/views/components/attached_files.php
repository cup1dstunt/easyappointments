<?php
/**
 * Local variables.
 *
 * (none - the UI is always populated at runtime by App.Utils.AttachedFiles.initialize())
 */

$max_attached_files = boolval(setting('attached_files_supported', 0)) ? (int) setting('max_attached_files', 0) : 0;
$max_attached_files_text =
    $max_attached_files === 1
        ? lang('attached_files_one')
        : sprintf(lang('attached_files_multiple'), $max_attached_files);
$attached_files_allowed_types_hint = lang(setting('attached_files_allowed_types_hint', ''));
$attached_files_hint =
    sprintf(lang('attached_files_user_hint'), $max_attached_files_text) . ' ' . $attached_files_allowed_types_hint;
$allowed_attached_file_types = setting('attached_files_allowed_types', '');
?>

<?php if ($max_attached_files > 0): ?>
    <div class="mb-3">
        <label class="form-label">
            <?= lang('attached_files') ?>
        </label>

        <div class="form-text text-muted">
            <small><?= e($attached_files_hint) ?></small>
        </div>

        <?php for ($i = 1; $i <= $max_attached_files; $i++): ?>
            <div class="existing-file-name-row" id="existing-file-name-row-<?= $i ?>" style="display: none;">
                <small id="existing-file-name-<?= $i ?>"></small>
                <small>(<?= e(lang('previously_attached')) ?>)</small>
                <i class="fas fa-times-circle discard-file-button"></i>
            </div>
        <?php endfor; ?>

        <?php for ($i = 1; $i <= $max_attached_files; $i++): ?>
            <input type="file" class="form-control attached-file-input" style="display: none;"
                   data-index="<?= $i ?>" id="attached-file-input-<?= $i ?>"
                   accept="<?= e($allowed_attached_file_types) ?>">
        <?php endfor; ?>

        <button class="file-button btn btn-sm btn-outline-dark" id="attach-file-button" type="button">
            <?= lang('attach_file_button') ?>
        </button>

        <?php for ($i = 1; $i <= $max_attached_files; $i++): ?>
            <div class="attached-file-name-row" id="attached-file-name-row-<?= $i ?>" data-index="<?= $i ?>"
                 style="display: none;">
                <span id="attached-file-name-<?= $i ?>"></span>
                <i class="fas fa-times-circle discard-file-button"></i>
            </div>
        <?php endfor; ?>
    </div>
<?php endif; ?>
