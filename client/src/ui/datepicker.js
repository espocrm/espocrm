/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
import Language from 'language';
import Settings from 'models/settings';
import {inject} from 'di';
import DateTime from 'date-time';

/**
 * A datepicker.
 *
 * @since 9.0.0
 */
class Datepicker {


    /**
     * @private
     * @type {Language}
     */
    @inject(Language)
    language

    /**
     * @private
     * @type {Settings}
     */
    @inject(Settings)
    config

    /**
     * @param {HTMLElement} element
     * @param {{
     *     format: string,
     *     weekStart: number,
     *     todayButton?: boolean,
     *     startDate?: string|undefined,
     *     onChange?: function(),
     * }} options
     */
    constructor(element, options) {
        /**
         * @private
         */
        this.$element = $(element);

        if (element instanceof HTMLInputElement) {
            let wait = false;

            this.$element.on('change', /** Record */e => {
                if (!wait) {
                    if (options.onChange) {
                        options.onChange();
                    }

                    wait = true;
                    setTimeout(() => wait = false, 100);
                }

                if (e.isTrigger && document.activeElement !== this.$element.get(0)) {
                    this.$element.focus();
                }
            });

            this.$element.on('click', () => this.show());
        }

        const modalBodyElement = element.closest('.modal-body');

        const language = this.config.get('language');

        const format = options.format;

        const datepickerOptions = {
            autoclose: true,
            todayHighlight: true,
            keyboardNavigation: true,
            assumeNearbyYear: true,

            format: format.toLowerCase(),
            weekStart: options.weekStart,
            todayBtn: options.todayButton || false,
            startDate: options.startDate,
            orientation: 'bottom auto',
            templates: {
                leftArrow: '<span class="fas fa-chevron-left fa-sm"></span>',
                rightArrow: '<span class="fas fa-chevron-right fa-sm"></span>',
            },
            container: modalBodyElement ? $(modalBodyElement) : 'body',
            language: language,
        };

        // noinspection JSUnresolvedReference
        if (!(language in $.fn.datepicker.dates)) {
            // noinspection JSUnresolvedReference
            $.fn.datepicker.dates[language] = {
                days: this.language.get('Global', 'lists', 'dayNames'),
                daysShort: this.language.get('Global', 'lists', 'dayNamesShort'),
                daysMin: this.language.get('Global', 'lists', 'dayNamesMin'),
                months: this.language.get('Global', 'lists', 'monthNames'),
                monthsShort: this.language.get('Global', 'lists', 'monthNamesShort'),
                today: this.language.translate('Today'),
                clear: this.language.translate('Clear'),
            };
        }

        this.$element.datepicker(datepickerOptions);

        if (element.classList.contains('input-group') && !(element instanceof HTMLInputElement)) {
            element.querySelectorAll('input').forEach(input => {
                $(input).on('click', () => $(input).datepicker('show'));
            });
        }
    }

    /**
     * Set a start date.
     *
     * @param {string|undefined} startDate
     */
    setStartDate(startDate) {
        this.$element.datepicker('setStartDate', startDate);
    }

    /**
     * Show.
     */
    show() {
        this.$element.datepicker('show');
    }
}

export default Datepicker;
