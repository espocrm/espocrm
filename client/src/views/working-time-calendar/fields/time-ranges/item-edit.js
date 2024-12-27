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

import View from 'view';
import moment from 'moment';

export default class extends View  {


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
        this.$start = this.$el.find('[data-name="start"]');
        this.$end = this.$el.find('[data-name="end"]');

        this.initTimepicker(this.$start);
        this.initTimepicker(this.$end);

        this.setMinTime();

        this.$start.on('change', () => this.setMinTime());
    }

    setMinTime() {
        const value = this.$start.val();

        const parsedValue = this.convertTimeFromDisplay(value);

        if (parsedValue !== '00:00') {
            this.$end.timepicker('option', 'maxTime', this.convertTimeToDisplay('24:00'));
        } else {
            this.$end.timepicker('option', 'maxTime', null);
        }

        if (!value) {
            this.$end.timepicker('option', 'minTime', null);

            return;
        }

        const minValue = moment(parsedValue, 'HH:mm')
            .add(this.minuteStep, 'minute')
            .format(this.getDateTime().timeFormat);

        this.$end.timepicker('option', 'minTime', minValue);
    }

    initTimepicker($el) {
        $el.timepicker({
            step: this.minuteStep,
            timeFormat: this.timeFormatMap[this.getDateTime().timeFormat],
        });

        $el.on('change', () => this.trigger('change'));
        $el.attr('autocomplete', 'espo-time-range-item');
    }

    fetch() {
        return [
            this.convertTimeFromDisplay(this.$start.val()),
            this.convertTimeFromDisplay(this.$end.val()),
        ];
    }
}
