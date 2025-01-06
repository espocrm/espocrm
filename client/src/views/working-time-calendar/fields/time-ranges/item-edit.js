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

import View from 'view';
import moment from 'moment';
import Timepicker from 'ui/timepicker';

export default class TimeRangeItemEdit extends View  {

    // language=Handlebars
    templateContent = `
        <div class="row">
            <div class="start-container col-xs-5">
                <input
                    class="form-control numeric-text"
                    type="text"
                    data-name="start"
                    value="{{start}}"
                    autocomplete="espo-start"
                    spellcheck="false"
                >
            </div>
            <div class="start-container col-xs-1 center-align">
                <span class="field-row-text-item">&nbsp;–&nbsp;</span>
            </div>
            <div class="end-container col-xs-5">
                <input
                    class="form-control numeric-text"
                    type="text"
                    data-name="end"
                    value="{{end}}"
                    autocomplete="espo-end"
                    spellcheck="false"
                >
            </div>
            <div class="col-xs-1 center-align">
                <a
                    role="button"
                    tabindex="0"
                    class="remove-item field-row-text-item"
                    data-key="{{key}}"
                    title="{{translate 'Remove'}}"
                ><span class="fas fa-times"></span></a>
            </div>
        </div>
    `

    timeFormatMap = {
        'HH:mm': 'H:i',
        'hh:mm A': 'h:i A',
        'hh:mm a': 'h:i a',
        'hh:mmA': 'h:iA',
        'hh:mma': 'h:ia',
    }

    minuteStep = 30

    /**
     * @private
     * @type {HTMLInputElement}
     */
    startElement

    /**
     * @private
     * @type {HTMLInputElement}
     */
    endElement

    /**
     * @private
     * @type {import('ui/timepicker').default}
     */
    startTimepicker

    /**
     * @private
     * @type {import('ui/timepicker').default}
     */
    endTimepicker

    data () {
        const data = {};

        data.start = this.convertTimeToDisplay(this.value[0]);
        data.end = this.convertTimeToDisplay(this.value[1]);

        data.key = this.key;

        return data;
    }

    setup () {
        this.value = this.options.value || [null, null];
        this.key = this.options.key;

        this.on('remove', () => this.destroyTimepickers());
    }

    convertTimeToDisplay(value) {
        if (!value) {
            return '';
        }

        const m = moment(value, 'HH:mm');

        if (!m.isValid()) {
            return '';
        }

        return m.format(this.getDateTime().timeFormat);
    }

    /**
     * @param {string} value
     * @return {string|null}
     */
    convertTimeFromDisplay(value) {
        if (!value) {
            return null;
        }

        const m = moment(value, this.getDateTime().timeFormat);

        if (!m.isValid()) {
            return null;
        }

        return m.format('HH:mm');
    }

    afterRender() {
        this.startElement = this.element.querySelector('[data-name="start"]');
        this.endElement = this.element.querySelector('[data-name="end"]');

        if (this.startElement) {
            this.startTimepicker = this.initTimepicker(this.startElement);
            this.endTimepicker = this.initTimepicker(this.endElement);

            this.setMinTime();

            this.startTimepicker.addChangeEventListener(() => this.setMinTime());
        }
    }

    setMinTime() {
        const value = this.startElement.value;

        const parsedValue = this.convertTimeFromDisplay(value);

        if (parsedValue !== '00:00') {
            this.endTimepicker.setMaxTime(this.convertTimeToDisplay('24:00'));
        } else {
            this.endTimepicker.setMaxTime(null);
        }

        if (!value) {
            this.endTimepicker.setMinTime(null);

            return;
        }

        const minValue = moment(parsedValue, 'HH:mm')
            .add(this.minuteStep, 'minute')
            .format(this.getDateTime().timeFormat);

        this.endTimepicker.setMinTime(minValue);
    }

    /**
     * @private
     * @param {HTMLInputElement} element
     * @return {Timepicker}
     */
    initTimepicker(element) {
        const timepicker = new Timepicker(element, {
            step: this.minuteStep,
            timeFormat: this.timeFormatMap[this.getDateTime().timeFormat],
        })

        timepicker.addChangeEventListener(() => this.trigger('change'));

        element.setAttribute('autocomplete', 'espo-time-range-item');

        return timepicker;
    }

    destroyTimepickers() {
        if (this.startTimepicker) {
            this.startTimepicker.destroy();
        }

        if (this.endTimepicker) {
            this.endTimepicker.destroy();
        }
    }

    fetch() {
        return [
            this.convertTimeFromDisplay(this.startElement.value),
            this.convertTimeFromDisplay(this.endElement.value),
        ];
    }
}
