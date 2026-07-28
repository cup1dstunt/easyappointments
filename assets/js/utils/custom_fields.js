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
 * Custom fields utility.
 *
 * A checkbox/radio-group custom field renders as several individual checked/unchecked
 * inputs sharing one name, plus a single hidden input that carries the actual field value
 * as a semicolon-separated list (e.g. "custom_field_fruit_1;custom_field_fruit_3"). These
 * two functions convert between that on-the-wire value and the individual inputs' checked
 * state.
 */
window.App.Utils.CustomFields = (function () {
    /**
     * Join all checked inputs in a custom field group into the group's common hidden input.
     *
     * @param {Element} customFieldContainer The custom field's container element.
     */
    function joinGroupValues(customFieldContainer) {
        const inputGroupElem = customFieldContainer.querySelector('.form-input-group');

        if (!inputGroupElem) {
            return;
        }

        const joinedInputElem = customFieldContainer.querySelector('.form-input[type="hidden"]');
        const id = joinedInputElem.id;
        const groupInputValues = [];
        const groupInputElems = inputGroupElem.querySelectorAll(`input[name=${id}]`);

        groupInputElems.forEach((elem) => {
            if (elem.checked) {
                groupInputValues.push(elem.value);
            }
        });

        joinedInputElem.value = groupInputValues.join(';');
    }

    /**
     * Split a custom field group's common hidden input value back into the individual checked inputs.
     *
     * @param {Element} customFieldContainer The custom field's container element.
     */
    function splitGroupValues(customFieldContainer) {
        const inputGroupElem = customFieldContainer.querySelector('.form-input-group');

        if (!inputGroupElem) {
            return;
        }

        const joinedInputElem = customFieldContainer.querySelector('.form-input[type="hidden"]');
        const id = joinedInputElem.id;
        const groupInputValues = joinedInputElem.value.split(';');
        const groupInputElems = inputGroupElem.querySelectorAll(`input[name=${id}]`);

        groupInputElems.forEach((elem) => {
            elem.checked = groupInputValues.includes(elem.value);
        });
    }

    /**
     * Run joinGroupValues() on every element of the given class name.
     *
     * @param {String} containerClassName e.g. "custom-field-container" or "appt-custom-field-container".
     */
    function joinAllGroupValues(containerClassName) {
        Array.from(document.getElementsByClassName(containerClassName)).forEach((container) => {
            joinGroupValues(container);
        });
    }

    /**
     * Run splitGroupValues() on every element of the given class name.
     *
     * @param {String} containerClassName e.g. "custom-field-container" or "appt-custom-field-container".
     */
    function splitAllGroupValues(containerClassName) {
        Array.from(document.getElementsByClassName(containerClassName)).forEach((container) => {
            splitGroupValues(container);
        });
    }

    /**
     * Get the field numbers (e.g. [1, 2, 5]) of every rendered custom field container of the given class name.
     *
     * Only fields marked "displayed" are rendered, so their numbers are not necessarily contiguous
     * from 1 - use this instead of assuming a 1..count range when looking up field elements by id.
     *
     * @param {String} containerClassName e.g. "custom-field-container" or "appt-custom-field-container".
     *
     * @return {Number[]}
     */
    function getFieldIndexes(containerClassName) {
        return Array.from(document.getElementsByClassName(containerClassName)).map((container) =>
            Number(container.dataset.fieldIndex),
        );
    }

    return {
        joinGroupValues,
        splitGroupValues,
        joinAllGroupValues,
        splitAllGroupValues,
        getFieldIndexes,
    };
})();
