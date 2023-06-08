/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/** @module views/fields/datetime-optional */

import Dep from 'views/fields/datetime';
import moment from 'lib!moment';

/**
 * A date-time or date.
 *
 * @class Class
 * @extends module:views/fields/datetime
 */
export default Dep.extend(/** @lends Class# */{

    type: 'datetimeOptional',

    setup: function () {
        Dep.prototype.setup.call(this);

        this.noneOption = this.translate('None');
        this.nameDate = this.name + 'Date';
    },

    isDate: function () {
        let dateValue = this.model.get(this.nameDate);

        if (dateValue && dateValue !== '') {
            return true;
        }

        return false;
    },

    data: function () {
        let data = Dep.prototype.data.call(this);

        if (this.isDate()) {
            let dateValue = this.model.get(this.nameDate);

            data.date = this.getDateTime().toDisplayDate(dateValue);
            data.time = this.noneOption;
        }

        return data;
    },

    getDateStringValue: function () {
        if (this.isDate()) {
            var dateValue = this.model.get(this.nameDate);

            return this.stringifyDateValue(dateValue);
        }

        return Dep.prototype.getDateStringValue.call(this);
    },

    setDefaultTime: function () {
        this.$time.val(this.noneOption);
    },

    initTimepicker: function () {
        let $time = this.$time;

        let o = {
            step: this.params.minuteStep || 30,
            scrollDefaultNow: true,
            timeFormat: this.timeFormatMap[this.getDateTime().timeFormat],
            noneOption: [{
                label: this.noneOption,
                value: this.noneOption,
            }],
        };

        if (this.emptyTimeInInlineEditDisabled && this.isInlineEditMode() || this.noneOptionIsHidden) {
            delete o.noneOption;
        }

        $time.timepicker(o);

        $time.parent().find('button.time-picker-btn').on('click', () => {
            $time.timepicker('show');
        });
    },

    fetch: function () {
        let data = {};

        let date = this.$date.val();
        let time = this.$time.val();
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
    },

    validateAfter: function () {
        let field = this.model.getFieldParam(this.name, 'after');

        if (!field) {
            return;
        }

        let fieldDate = field + 'Date';
        let value = this.model.get(this.name) || this.model.get(this.nameDate);
        let otherValue = this.model.get(field) || this.model.get(fieldDate);

        if (!(value && otherValue)) {
            return;
        }

        let isNotValid = this.validateAfterAllowSameDay && this.model.get(this.nameDate) ?
            moment(value).unix() < moment(otherValue).unix() :
            moment(value).unix() <= moment(otherValue).unix();

        if (isNotValid) {
            let msg = this.translate('fieldShouldAfter', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{otherField}', this.translate(field, 'fields', this.model.name));

            this.showValidationMessage(msg);

            return true;
        }
    },

    validateBefore: function () {
        var field = this.model.getFieldParam(this.name, 'before');

        if (!field) {
            return;
        }

        let fieldDate = field + 'Date';
        let value = this.model.get(this.name) || this.model.get(this.nameDate);
        let otherValue = this.model.get(field) || this.model.get(fieldDate);

        if (!(value && otherValue)) {
            return;
        }

        if (moment(value).unix() >= moment(otherValue).unix()) {
            let msg = this.translate('fieldShouldBefore', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{otherField}', this.translate(field, 'fields', this.model.name));

            this.showValidationMessage(msg);

            return true;
        }
    },

    validateRequired: function () {
        if (!this.isRequired()) {
            return;
        }

        if (this.model.get(this.name) === null && this.model.get(this.nameDate) === null) {
            let msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    },
});
