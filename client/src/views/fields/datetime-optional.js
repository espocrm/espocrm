/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/datetime-optional', 'views/fields/datetime', function (Dep) {

    return Dep.extend({

        type: "datetimeOptional",

        setup: function () {
            this.noneOption = this.translate('None');
            this.nameDate = this.name + 'Date';
        },

        isDate: function () {
            var dateValue = this.model.get(this.nameDate);

            if (dateValue && dateValue !== '') {
                return true;
            }

            return false;
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            if (this.isDate()) {
                var dateValue = this.model.get(this.nameDate);

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
            var $time = this.$time;

            var o = {
                step: this.params.minuteStep || 30,
                scrollDefaultNow: true,
                timeFormat: this.timeFormatMap[this.getDateTime().timeFormat],
                noneOption: [{
                    label: this.noneOption,
                    value: this.noneOption,
                }]
            };

            if (this.emptyTimeInInlineEditDisabled && this.isInlineEditMode() || this.noneOptionIsHidden) {
                delete o.noneOption;
            }

            $time.timepicker(o);

            $time.parent().find('button.time-picker-btn').on('click', function () {
                $time.timepicker('show');
            });
        },

        fetch: function () {
            var data = {};

            var date = this.$date.val();
            var time = this.$time.val();

            var value = null;

            if (time !== this.noneOption && time !== '') {
                if (date !== '' && time !== '') {
                    value = this.parse(date + ' ' + time);
                }

                data[this.name] = value;
                data[this.nameDate] = null;
            } else {
                if (date !== '') {
                    data[this.nameDate] = this.getDateTime().fromDisplayDate(date);

                    var dateTimeValue = data[this.nameDate] + ' 00:00:00';

                    dateTimeValue = moment.utc(dateTimeValue)
                        .tz(this.getConfig().get('timeZone') || 'UTC')
                        .format(this.getDateTime().internalDateTimeFullFormat);

                    data[this.name] = dateTimeValue;
                }
                else {
                    data[this.nameDate] = null;
                    data[this.name] = null;
                }
            }
            return data;
        },

        validateAfter: function () {
            var field = this.model.getFieldParam(this.name, 'after');

            if (field) {
                var fieldDate  = field + 'Date';
                var value = this.model.get(this.name) || this.model.get(this.nameDate);
                var otherValue = this.model.get(field) || this.model.get(fieldDate);

                if (value && otherValue) {
                    var isNotValid = false;

                    if (this.validateAfterAllowSameDay && this.model.get(this.nameDate)) {
                        isNotValid = moment(value).unix() < moment(otherValue).unix();
                    }
                    else {
                        isNotValid = moment(value).unix() <= moment(otherValue).unix();
                    }

                    if (isNotValid) {
                        var msg = this.translate('fieldShouldAfter', 'messages')
                            .replace('{field}', this.getLabelText())
                            .replace('{otherField}', this.translate(field, 'fields', this.model.name));

                        this.showValidationMessage(msg);

                        return true;
                    }
                }
            }
        },

        validateBefore: function () {
            var field = this.model.getFieldParam(this.name, 'before');

            if (field) {
                var fieldDate  = field + 'Date';
                var value = this.model.get(this.name) || this.model.get(this.nameDate);

                var otherValue = this.model.get(field) || this.model.get(fieldDate);

                if (value && otherValue) {
                    if (moment(value).unix() >= moment(otherValue).unix()) {
                        var msg = this.translate('fieldShouldBefore', 'messages')
                            .replace('{field}', this.getLabelText())
                            .replace('{otherField}', this.translate(field, 'fields', this.model.name));

                        this.showValidationMessage(msg);

                        return true;
                    }
                }
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.name) === null && this.model.get(this.nameDate) === null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg);

                    return true;
                }
            }
        },

    });
});
