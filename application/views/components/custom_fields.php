<?php
/**
 * Local variables.
 *
 * @var bool $disabled (false)
 * @var string $fieldset ('customer')
 */

$max_custom_fields = config('max_custom_fields', 5);
$max_appt_custom_fields = config('max_appt_custom_fields', 5);
$disabled = $disabled ?? false;
$fieldset = $fieldset ?? 'customer';
?>

<?php
/**
 * Custom fields support several input types (text, select, checkbox/radio groups, ...),
 * placeholders, extra HTML attributes, and translated option lists - all without
 * adding any new columns to the settings database table and without any UI changes to the settings
 * page. This is done by letting the "label_<field_prefix>_<i>" setting optionally carry
 * a trailing JSON blob after the human-readable label, e.g.:
 *
 *   Favourite fruit {"type": "select", "sort": true}
 *
 * get_custom_field_params() below is what parses that out. It's admittedly a bit of a
 * hack, but it keeps the settings model and UI completely unchanged and backwards compatible
 * while covering every customization we need.
 */

/**
 * Check whether a translation actually exists for the given language key.
 *
 * lang() falls back to returning the key itself when no translation is found, so
 * "the key comes back unchanged" is how we detect "no such translation".
 */
if (!function_exists('lang_exists')) {
    function lang_exists($key) {
        return lang($key) !== $key;
    }
}

/**
 * Function to get parameters for a custom field based on its settings
 */
if (!function_exists('get_custom_field_params')) {
    function get_custom_field_params($field_prefix, $i) {
        $field_params = [];
        $allowed_types = array('checkbox','color','date','email','month','number','password','radio','range','tel','text','time','url','week','textarea','select');
        $field_id_prefix = str_replace('_', '-', $field_prefix);
        preg_match('/^([\w\s\d:,"\'()]+)\s*({[\w\s\d:,;\-="\']*})?\s*$/U', setting("label_{$field_prefix}_{$i}", lang($field_prefix)), $matches);
        $field_params['label'] = (sizeof($matches) > 1) ? $matches[1] : setting("label_{$field_prefix}_{$i}", lang($field_prefix));
        $field_params['translated_label'] = lang($field_params['label']) ?: lang("{$field_prefix}");
        $field_params['is_displayed'] = filter_var(setting("display_{$field_prefix}_{$i}", 0), FILTER_VALIDATE_BOOLEAN);
        $field_params['is_required'] = filter_var(setting("require_{$field_prefix}_{$i}", 0), FILTER_VALIDATE_BOOLEAN);
        $field_params['id'] = "{$field_id_prefix}-{$i}";
        $field_params['name'] = "{$field_id_prefix}-{$i}";
        $field_data = (sizeof($matches) > 2) ? json_decode($matches[2], true) : array();
        $field_params['type'] = isset($field_data['type']) ?  (in_array($field_data['type'], $allowed_types) ? $field_data['type'] : 'text') : 'text';
        $field_params['tag'] = in_array($field_params['type'], array('textarea','select')) ? $field_params['type'] : 'input';
        $field_params['input_class'] = $field_id_prefix;
        $field_params['control'] =
            ( in_array($field_params['type'], array('checkbox','radio')) ? 'form-check' :
            ( in_array($field_params['type'], array('select')) ? 'form-select' :
            'form-control'));
        $field_params['attributes'] = e($field_data['attributes'] ?? '');
        $field_params['is_sorted'] = filter_var($field_data['sort'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $field_params['placeholder'] = isset($field_data['placeholder']) ? "placeholder = '" . e(lang($field_data['placeholder'])) . "'" : '';
        $field_params['is_group'] = in_array($field_params['type'], array('checkbox','radio'));

        return $field_params;
    }
}

/**
 * Function for writing out html code for a custom field label
 */
if (!function_exists('output_custom_field_label')) {
    function output_custom_field_label($field_params, $is_disabled) {
        echo ('<label for="' . $field_params['id'] . '" class="form-label">');
        echo ($field_params['translated_label']);
        if ($field_params['is_required']) {
            echo ('<span class="text-danger"' . ($is_disabled ? ' hidden' : '') . '> *</span>');
        }
        echo ('</label>');
    }
}

/**
 * Function for writing out html code for a custom field input
 */
if (!function_exists('output_custom_field_input')) {
    function output_custom_field_input($field_params, $is_disabled) {
        echo ('<' . $field_params['tag'] . ' ');
        echo ('type="' . $field_params['type'] . '" ');
        echo ('id="' . $field_params['id'] . '" ');
        echo ('name="' . $field_params['name'] . '" ');
        echo ('class="' . $field_params['input_class'] . ' form-input ' . ($field_params['is_required'] ? 'required ' : '') . $field_params['control'] . '" ');
        echo ($field_params['attributes']);
        echo ($field_params['placeholder']);
        echo (($is_disabled ? ' disabled' : '') . '>');
        if ($field_params['tag'] == "select") {
            output_custom_field_select_options($field_params);
        }
        echo ('</' . $field_params['tag'] . '>');
    }
}

/**
 * Function for writing out html code for options of a select custom field
 */
if (!function_exists('output_custom_field_select_options')) {
    function output_custom_field_select_options($field_params) {
        // Add a prompt if it is defined in the translation files (typically 'Select option...')
        if (lang_exists("{$field_params['label']}_prompt")) {
            echo ('<option value="" disabled selected hidden>' . lang("{$field_params['label']}_prompt") . '</option>');
        }
        $options = [];
        $option_idx = 1;
        // Loop while a translation exists for the next option index
        while (lang_exists("{$field_params['label']}_{$option_idx}")) {
            $options["{$field_params['label']}_{$option_idx}"] = lang("{$field_params['label']}_{$option_idx}");
            $option_idx++;
        }
        // Sorting will ensure that the options are sorted in all languages
        if ($field_params['is_sorted']) {
            asort($options);
        }
        // Add a last option if it exists in translation files (typically for options such as 'Other')
        if (lang_exists("{$field_params['label']}_last")) {
            $options["{$field_params['label']}_last"] = lang("{$field_params['label']}_last");
        }
        foreach ($options as $option_value => $option_text) {
            echo ('<option value="' . $option_value . '">' . $option_text . '</option>');
        }
    }
}

/**
 * Function for writing out html code for a custom field group label
 */
if (!function_exists('output_custom_field_group_label')) {
    function output_custom_field_group_label($field_params) {
        echo ('<label for="' . $field_params['id'] . '" class="form-group-label ' . $field_params['control'] . '-label">');
        echo ($field_params['translated_label']);
        echo ('</label>');
    }
}

/**
 * Function for writing out html code for an input in a custom field group
 */
if (!function_exists('output_custom_field_group_input')) {
    function output_custom_field_group_input($field_params, $is_disabled) {
        echo ('<' . $field_params['tag'] . ' ');
        echo ('type="' . $field_params['type'] . '" ');
        echo ('id="' . $field_params['id'] . '" ');
        echo ('name="' . $field_params['name'] . '" ');
        echo ('value="' . $field_params['value'] . '" ');
        echo ('class="' . $field_params['input_class'] . ' ' . $field_params['control'] . '-input" ');
        echo ($field_params['attributes']);
        echo ($field_params['placeholder']);
        echo (($is_disabled ? ' disabled' : '') . '>');
        echo ('</' . $field_params['tag'] . '>');
    }
}

/**
 * Function for writing out html code for all inputs of a custom field group
 */
if (!function_exists('output_custom_field_group_inputs')) {
    function output_custom_field_group_inputs($field_params, $is_disabled) {
        echo ('<input id="' . $field_params['id'] . '" class="form-input ' . $field_params['input_class'] . ($field_params['is_required'] ? ' required' : '') .'" type="hidden"></input>');
        $inputs = [];
        $input_idx = 1;
        while (lang_exists("{$field_params['label']}_{$input_idx}")) {
            $inputs["{$field_params['label']}_{$input_idx}"] = lang("{$field_params['label']}_{$input_idx}");
            $input_idx++;
        }
        if ($field_params['is_sorted']) {
            asort($inputs);
        }
        if (lang_exists("{$field_params['label']}_last")) {
            $inputs["{$field_params['label']}_last"] = lang("{$field_params['label']}_last");
        }
        $id_idx = 1;
        echo ('<div class="form-input-group">');
        foreach ($inputs as $input_value => $input_text) {
            $field_params['id'] = $field_params['name'] . '-' . $id_idx;
            $field_params['value'] = $input_value;
            $field_params['translated_label'] = $input_text;
            echo ('<div class="' . $field_params['control'] . '">');
            output_custom_field_group_input($field_params, $is_disabled);
            output_custom_field_group_label($field_params);
            echo ('</div>');
            $id_idx++;
        }
        echo ('</div>');
    }
}

/**
 * Maps each supported fieldset name to its field-key prefix, field count, and container class.
 */
$fieldset_configs = [
    'customer' => [
        'field_prefix' => 'custom_field',
        'max_fields' => $max_custom_fields,
        'container_class' => 'custom-field-container',
    ],
    'appointment' => [
        'field_prefix' => 'appt_custom_field',
        'max_fields' => $max_appt_custom_fields,
        'container_class' => 'appt-custom-field-container',
    ],
];
?>

<?php foreach ($fieldset_configs as $fieldset_name => $fieldset_config): ?>
    <?php if ($fieldset === $fieldset_name || $fieldset === 'all'): ?>
        <?php for ($i = 1; $i <= $fieldset_config['max_fields']; $i++): ?>
            <?php $field_params = get_custom_field_params($fieldset_config['field_prefix'], $i); ?>
            <?php if ($field_params['is_displayed']): ?>
                <div class="mb-3 <?= $fieldset_config['container_class'] ?>" data-field-index="<?= $i ?>">
                    <?php if ($field_params['is_group']): ?>
                        <?php output_custom_field_label($field_params, $disabled); ?>
                        <?php output_custom_field_group_inputs($field_params, $disabled); ?>
                    <?php else: ?>
                        <?php output_custom_field_label($field_params, $disabled); ?>
                        <?php output_custom_field_input($field_params, $disabled); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    <?php endif; ?>
<?php endforeach; ?>
