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

/** @module views/fields/datetime-optional */

import DatetimeFieldView from 'views/fields/datetime';
import moment from 'moment';
import {Options as BaseOptions, ViewSchema} from 'views/fields/base';

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
}

interface Options extends BaseOptions {
    /**
     * A label text of other field. Used in before/after validations.
     */
    otherFieldLabelText?: string;
}

/**
 * A date-time or date.
 */
class DatetimeOptionalFieldView<
    S extends ViewSchema = ViewSchema,
    O extends Options = Options,
    P extends Params = Params,
> extends DatetimeFieldView<S, O, P> {


    readonly type: string = 'datetimeOptional'

    protected nameDate: string;

    protected emptyTimeInInlineEditDisabled: boolean = false
    protected noneOptionIsHidden: boolean = false;
    protected validateAfterAllowSameDay: boolean = false

    protected isEnd: boolean = false;

    protected setup() {
        super.setup();

        this.noneOption = this.translate('None');
        this.nameDate = this.name + 'Date';
    }

    isDate(): boolean {
        const dateValue = this.model.get(this.nameDate);

        if (dateValue && dateValue !== '') {
            return true;
        }

        return false;
    }

    protected data() {
        const data = super.data();

        if (this.isDate()) {
            const dateValue = this.model.get(this.nameDate);

            data.date = this.getDateTime().toDisplayDate(dateValue);
            data.time = this.noneOption;
        }

        return data;
    }

    protected getDateStringValue(): string | -1 | null {
        if (this.isDate()) {
            const dateValue = this.model.get(this.nameDate);

            return this.stringifyDateValue(dateValue);
        }

        return super.getDateStringValue();
    }

    protected setDefaultTime() {
        this.$time?.val(this.noneOption);
    }

    protected initTimepicker() {
        const $time = this.$time;

        const modalBodyElement = this.element.closest('.modal-body');

        const o = {
            step: this.params.minuteStep || 30,
            scrollDefaultNow: true,
            timeFormat: this.timeFormatMap[this.getDateTime().timeFormat],
            noneOption: [{
                label: this.noneOption,
                value: this.noneOption,
            }],
            appendTo: modalBodyElement ? $(modalBodyElement) : 'body',
        } as Record<string, any>;

        if (this.emptyTimeInInlineEditDisabled && this.isInlineEditMode() || this.noneOptionIsHidden) {
            delete o.noneOption;
        }

        // @ts-ignore
        $time?.timepicker(o);

        $time?.parent().find('button.time-picker-btn').on('click', () => {
            // @ts-ignore
            $time.timepicker('show');
        });
    }

    fetch() {
        const data = {} as Record<string, any>;

        const date = this.$date?.val() as string;
        const time = this.$time?.val();
        let value = null;

        if (time !== this.noneOption && time !== '') {
            if (date !== '' && time !== '') {
                value = this.parse(date + ' ' + time);
            }

            data[this.name] = value;
            data[this.nameDate] = null;

            return data;
        }

        if (date !== '') {
            data[this.nameDate] = this.getDateTime().fromDisplayDate(date);

            let dateTimeValue = data[this.nameDate] + ' 00:00:00';


            dateTimeValue = moment
                // @ts-ignore
                .tz(dateTimeValue, this.getConfig().get('timeZone') || 'UTC')
                .add(this.isEnd ? 1 : 0, 'days')
                .utc()
                .format(this.getDateTime().internalDateTimeFullFormat);

            data[this.name] = dateTimeValue;

            return data;
        }

        data[this.nameDate] = null;
        data[this.name] = null;

        return data;
    }

    validateAfter() {
        const field = this.params.after;

        if (!field) {
            return false;
        }

        const fieldDate = field + 'Date';
        const value = this.model.get(this.name) || this.model.get(this.nameDate);
        const otherValue = this.model.get(field) || this.model.get(fieldDate);

        if (!(value && otherValue)) {
            return false;
        }

        const isNotValid = this.validateAfterAllowSameDay && this.model.get(this.nameDate) ?
            moment(value).unix() < moment(otherValue).unix() :
            moment(value).unix() <= moment(otherValue).unix();

        if (isNotValid) {
            const otherFieldLabelText = this.options.otherFieldLabelText ||
                this.translate(field, 'fields', this.entityType);

            const msg = this.translate('fieldShouldAfter', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{otherField}', otherFieldLabelText);

            this.showValidationMessage(msg);

            return true;
        }

        return false;
    }

    validateBefore() {
        const field = this.params.before;

        if (!field) {
            return false;
        }

        const fieldDate = field + 'Date';
        const value = this.model.get(this.name) || this.model.get(this.nameDate);
        const otherValue = this.model.get(field) || this.model.get(fieldDate);

        if (!(value && otherValue)) {
            return;
        }

        if (moment(value).unix() >= moment(otherValue).unix()) {
            const msg = this.translate('fieldShouldBefore', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{otherField}', this.translate(field, 'fields', this.entityType));

            this.showValidationMessage(msg);

            return true;
        }
    }

    validateRequired() {
        if (!this.isRequired()) {
            return false;
        }

        if (this.model.get(this.name) === null && this.model.get(this.nameDate) === null) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }

        return false;
    }

    protected getStartDateForDatePicker(): string | undefined {
        if (!this.isEditMode() || !this.params.after) {
            return undefined;
        }

        const date = this.model.attributes[this.params.after + 'Date'] as string | undefined;

        if (date) {
            return this.getDateTime().toDisplayDate(date);
        }

        return super.getStartDateForDatePicker();
    }
}

// noinspection JSUnusedGlobalSymbols
export default DatetimeOptionalFieldView;
