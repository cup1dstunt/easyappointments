<?php
/**
 * @var string $attributes
 * @var array|null $custom_colors
 * @var bool|null $allow_no_color
 */

$colors = $custom_colors ?? [
    '#7cbae8',
    '#acbefb',
    '#82e4ec',
    '#7cebc1',
    '#abe9a4',
    '#ebe07c',
    '#f3bc7d',
    '#f3aea6',
    '#eb8687',
    '#dfaffe',
    '#e3e3e3',
];

$allow_no_color ??= false;
?>

<label class="form-label"><?= lang('color') ?></label>

<div <?= $attributes ?? '' ?> class="color-selection d-flex flex-wrap justify-content-between mb-4">
    <?php // LNU: Provider Colour in Appointments (README.md #10) - a dedicated "no colour" swatch, not part of
    // $colors, is the default selection whenever it's shown. ?>
    <?php if ($allow_no_color): ?>
        <button type="button" class="color-selection-option no-color selected" data-value="">
            <i class="fas fa-ban"></i>
        </button>
    <?php endif; ?>

    <?php foreach ($colors as $index => $color): ?>
        <button type="button" class="color-selection-option <?= !$allow_no_color && $index === 0 ? 'selected' : '' ?>" data-value="<?= $color ?>">
            <i class="fas fa-check"></i>
        </button>
    <?php endforeach; ?>
</div>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/components/color_selection.js') ?>"></script>

<?php end_section('scripts'); ?>
