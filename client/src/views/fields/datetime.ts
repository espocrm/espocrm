/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

/** @module views/fields/datetime */

import DateFieldView from 'views/fields/date';
import moment from 'moment';
import {Options as BaseOptions, ViewSchema} from 'views/fields/base';
import JQuery from 'jquery'

const $ = JQuery;

interface Params {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Use numeric format.
     */
    useNumericFormat?: boolean;
    /**
     * Display seconds.
     */
    hasSeconds?: boolean;
    /**
     * A minutes step.
     */
    minuteStep?: number;
    /**
     * Validate to be after another date field.
     */
    after?: string;
    /**
     * Validate to be before another date field.
     */
    before?: string;
    /**
     * Allow an equal date for 'after' validation.
     */
    afterOrEqual?: boolean;
}

interface Options extends BaseOptions {
    /**
     * A label text of other field. Used in before/after validations.
     */
    otherFieldLabelText?: string;
}

/**
 * A date-time field.
 */
class DatetimeFieldView<
    S extends ViewSchema = ViewSchema,
    O extends Options = Options,
    P extends Params = Params,
> extends DateFieldView<S, O, P> {

    readonly type: string = 'datetime'

    protected editTemplate = 'fields/datetime/edit'

    protected validations = [
        'required',
        'datetime',
        'after',
        'before',
    ]

    protected searchTypeList = [
        'lastSevenDays',
        'ever',
        'isEmpty',
        'currentMonth',
        'lastMonth',
        'nextMonth',
        'currentQuarter',
        'lastQuarter',
        'currentYear',
        'lastYear',
        'today',
        'past',
        'future',
        'lastXDays',
        'nextXDays',
        'olderThanXDays',
        'afterXDays',
        'on',
        'after',
        'before',
        'between',
    ]

    readonly timeFormatMap: Record<string, string> = {
        'HH:mm': 'H:i',
        'hh:mm A': 'h:i A',
        'hh:mm a': 'h:i a',
        'hh:mmA': 'h:iA',
        'hh:mma': 'h:ia',
    }

    private $date: JQuery | null = null
    private $time: JQuery | null = null

    /**
     * A none option value,
     */
    protected noneOption: string | null = null

    protected data() {
        const data = super.data();

        data.date = data.time = '';

        const value = this.getDateTime().toDisplay(this.model.get(this.name));

        if (value) {
            const pair = this.splitDatetime(value);

            data.date = pair[0];
            data.time = pair[1];
        }

        return data;
    }

    protected getDateStringValue(): string | -1 | null {
        if (this.mode === this.MODE_DETAIL && !this.model.has(this.name)) {
            return -1;
        }

        const value = this.model.get(this.name);

        if (!value) {
            if (
                this.mode === this.MODE_EDIT ||
                this.mode === this.MODE_SEARCH ||
                this.mode === this.MODE_LIST ||
                this.mode === this.MODE_LIST_LINK
            ) {
                return '';
            }

            return null;
        }

        if (
            this.mode === this.MODE_LIST ||
            this.mode === this.MODE_DETAIL ||
            this.mode === this.MODE_LIST_LINK
        ) {
            if (this.getConfig().get('readableDateFormatDisabled') || this.params.useNumericFormat) {
                return this.getDateTime().toDisplay(value);
            }

            let timeFormat = this.getDateTime().timeFormat;

            if (this.params.hasSeconds) {
                timeFormat = timeFormat.replace(/:mm/, ':mm:ss');
            }

            const d = this.getDateTime().toMoment(value);
            // @ts-ignore
            const now = moment().tz(this.getDateTime().timeZone || 'UTC');
            const dt = now.clone().startOf('day');

            const ranges = {
                'today': [dt.unix(), dt.add(1, 'days').unix()],
                'tomorrow': [dt.unix(), dt.add(1, 'days').unix()],
                'yesterday': [dt.add(-3, 'days').unix(), dt.add(1, 'days').unix()]
            };

            if (d.unix() >= ranges['today'][0] && d.unix() < ranges['today'][1]) {
                return this.translate('Today') + ' ' + d.format(timeFormat);
            }

            if (d.unix() > ranges['tomorrow'][0] && d.unix() < ranges['tomorrow'][1]) {
                return this.translate('Tomorrow') + ' ' + d.format(timeFormat);
            }

            if (d.unix() > ranges['yesterday'][0] && d.unix() < ranges['yesterday'][1]) {
                return this.translate('Yesterday') + ' ' + d.format(timeFormat);
            }

            const readableFormat = this.getDateTime().getReadableDateFormat();

            if (d.format('YYYY') === now.format('YYYY')) {
                return d.format(readableFormat) + ' ' + d.format(timeFormat);
            }

            return d.format(readableFormat + ', YYYY') + ' ' + d.format(timeFormat);
        }

        return this.getDateTime().toDisplay(value);
    }

    protected initTimepicker() {
        const $time = this.$time;

        const modalBodyElement = this.element.closest('.modal-body');

        // @ts-ignore
        $time.timepicker({
            step: this.params.minuteStep || 30,
            scrollDefaultNow: true,
            timeFormat: this.timeFormatMap[this.getDateTime().timeFormat],
            appendTo: modalBodyElement ? $(modalBodyElement) : 'body',
        });

        $time
            ?.parent()
            .find('button.time-picker-btn')
            .on('click', () => {
                // @ts-ignore
                $time.timepicker('show');
            });
    }

    protected setDefaultTime() {
        const dtString = moment('2014-01-01 00:00').format(this.getDateTime().getDateTimeFormat()) || '';

        const pair = this.splitDatetime(dtString);

        if (pair.length === 2) {
            this.$time?.val(pair[1]);
        }
    }

    protected splitDatetime(value: string): [string, string] {
        const m = moment(value, this.getDateTime().getDateTimeFormat());

        const dateValue = m.format(this.getDateTime().getDateFormat());
        const timeValue = value.substring(dateValue.length + 1);

        return [dateValue, timeValue];
    }

    protected setup() {
        super.setup();

        this.on('remove', () => this.destroyTimepicker());
        this.on('mode-changed', () => this.destroyTimepicker());
    }

    protected destroyTimepicker() {
        if (this.$time && this.$time[0]) {
            // @ts-ignore
            this.$time.timepicker('remove');
        }
    }

    protected afterRender() {
        super.afterRender();

        if (this.mode !== this.MODE_EDIT) {
            return;
        }

        this.$date = this.$element;
        const $time = this.$time = this.$el.find('input.time-part');

        this.initTimepicker();

        this.$element?.on('change.datetime', () => {
            if (this.$element?.val() && !$time.val()) {
                this.setDefaultTime();
                this.trigger('change');
            }
        });

        let timeout = false;
        let isTimeFormatError = false;
        let previousValue = $time.val();

        $time.on('change', () => {
            if (!timeout) {
                if (isTimeFormatError) {
                    $time.val(previousValue);

                    return;
                }

                if (this.noneOption && $time.val() === '' && this.$date?.val() !== '') {
                    $time.val(this.noneOption);

                    return;
                }

                this.trigger('change');

                previousValue = $time.val();
            }

            timeout = true;

            setTimeout(() => timeout = false, 100);
        });

        $time.on('timeFormatError', () => {
            isTimeFormatError = true;

            setTimeout(() => isTimeFormatError = false, 50);
        });
    }

    protected parse(string: string): string| -1| null {
        if (!string) {
            return null;
        }

        return this.getDateTime().fromDisplay(string);
    }

    fetch() {
        const data = {} as Record<string, any>;

        const date = this.$date?.val();
        const time = this.$time?.val();

        let value = null;

        if (date !== '' && time !== '') {
            value = this.parse(date + ' ' + time);
        }

        data[this.name] = value;

        return data;
    }

    // noinspection JSUnusedGlobalSymbols
    validateDatetime() {
        if (this.model.get(this.name) === -1) {
            const msg = this.translate('fieldShouldBeDatetime', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }

        return false;
    }

    fetchSearch(): Record<string, any> | null  {
        const data = super.fetchSearch();

        if (data) {
            data.dateTime = true;
            delete data.date;
        }

        return data;
    }

    /**
     * Not implemented. For datetimeOptions too.
     * When implementing, keep in mind the duration field.
     */
    protected onAfterChange() {}
}

export default DatetimeFieldView;
