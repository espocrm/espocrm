/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import $ from 'jquery';

/**
 * A timepicker.
 */
class Timepicker {

    /**
     * @param {HTMLElement} element
     * @param {{
     *     step: number,
     *     timeFormat: string,
     *     scrollDefaultNow?: boolean,
     * }} options
     */
    constructor(element, options) {
        /**
         * @private
         */
        this.$element = $(element);

        const modalBodyElement = element.closest('.modal-body');

        this.$element.timepicker({
            step: options.step,
            timeFormat: options.timeFormat,
            appendTo: modalBodyElement ? $(modalBodyElement) : 'body',
            scrollDefaultNow: options.scrollDefaultNow || false,
        });
    }

    /**
     * Set the min time.
     *
     * @param {string|null} minTime
     */
    setMinTime(minTime) {
        this.$element.timepicker('option', 'minTime', minTime);
    }

    /**
     * Set the max time.
     *
     * @param {string|null} maxTime
     */
    setMaxTime(maxTime) {
        this.$element.timepicker('option', 'maxTime', maxTime);
    }

    /**
     * Add a 'change' event listener.
     *
     * @param {function} callback
     */
    addChangeEventListener(callback) {
        this.$element.on('change', callback);
    }

    /**
     * Show.
     */
    show() {
        this.$element.timepicker('show');
    }

    /**
     * Destroy.
     */
    destroy() {
        if (!this.$element[0]) {
            return;
        }

        this.$element.timepicker('remove');
    }
}

export default Timepicker;
