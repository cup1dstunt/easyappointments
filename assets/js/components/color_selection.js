/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Color selection component.
 *
 * This module implements the color selection functionality.
 */
App.Components.ColorSelection = (function () {
    /**
     * Event: Color Selection Option "Click"
     *
     * @param {jQuery.Event} event
     */
    function onColorSelectionOptionClick(event) {
        const $target = $(event.currentTarget);

        const $colorSelection = $target.closest('.color-selection');

        $colorSelection.find('.color-selection-option.selected').removeClass('selected');

        $target.addClass('selected');
    }

    /**
     * Get target color.
     *
     * @param {jQuery} $target Container element ".color-selection" selector.
     */
    function getColor($target) {
        return $target.find('.color-selection-option.selected').data('value');
    }

    /**
     * Remove any dynamically added color option (see setColor()) from a previous call, so at most one can ever
     * be present at a time.
     *
     * LNU: Provider Colour in Appointments (README.md #10).
     *
     * @param {jQuery} $target Container element ".color-selection" selector.
     */
    function removeDynamicColorSelectionOption($target) {
        $target.find('.color-selection-option.dynamic-color').remove();
    }

    /**
     * Add a dynamic color option for a color that isn't one of the available options, so it doesn't get lost
     * (rather than falling back to "no colour", which would silently overwrite it on the next save if the admin
     * doesn't touch the picker). Removed again by removeDynamicColorSelectionOption() on the next
     * setColor()/disable() call.
     *
     * LNU: Provider Colour in Appointments (README.md #10).
     *
     * @param {jQuery} $target Container element ".color-selection" selector.
     * @param {String} color Color value.
     */
    function addDynamicColorSelectionOption($target, color) {
        if (!$target.find('.color-selection-option.selected').length && color) {
            const $colorSelectionOption = $('<button/>', {
                'type': 'button',
                'class': 'color-selection-option dynamic-color selected',
                'data-value': color,
                'html': [$('<i/>', {'class': 'fas fa-check'})],
            });

            $target.append($colorSelectionOption);

            $colorSelectionOption.css('background-color', color);
        }
    }

    /**
     * Set target color.
     *
     * @param {jQuery} $target Container element ".color-selection" selector.
     * @param {String} color Color value.
     */
    function setColor($target, color) {
        removeDynamicColorSelectionOption($target);

        $target
            .find('.color-selection-option')
            .removeClass('selected')
            .each((index, colorSelectionOptionEl) => {
                var $colorSelectionOption = $(colorSelectionOptionEl);

                if ($colorSelectionOption.data('value') === color) {
                    $colorSelectionOption.addClass('selected');
                    return false;
                }
            });

        addDynamicColorSelectionOption($target, color);
    }

    /**
     * Disable the color selection for the target element.
     *
     * @param {jQuery} $target
     */
    function disable($target) {
        removeDynamicColorSelectionOption($target);
        $target.find('.color-selection-option').prop('disabled', true).removeClass('selected');
        $target.find('.color-selection-option:first').addClass('selected');
    }

    /**
     * Enable the color selection for the target element.
     *
     * @param {jQuery} $target
     */
    function enable($target) {
        $target.find('.color-selection-option').prop('disabled', false);
    }

    function applyBackgroundColors() {
        $(document)
            .find('.color-selection-option')
            .each((index, colorSelectionOptionEl) => {
                const $colorSelectionOption = $(colorSelectionOptionEl);

                const color = $colorSelectionOption.data('value');

                $colorSelectionOption.css('background-color', color);
            });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        $(document).on('click', '.color-selection-option', onColorSelectionOptionClick);

        applyBackgroundColors();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        disable,
        enable,
        getColor,
        setColor,
    };
})();
