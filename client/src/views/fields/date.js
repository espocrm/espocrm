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

define('views/fields/date', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'date',

        listTemplate: 'fields/date/list',

        listLinkTemplate: 'fields/date/list-link',

        detailTemplate: 'fields/date/detail',

        editTemplate: 'fields/date/edit',

        searchTemplate: 'fields/date/search',

        validations: ['required', 'date', 'after', 'before'],

        searchTypeList: [
            'lastSevenDays', 'ever', 'isEmpty', 'currentMonth', 'lastMonth', 'nextMonth', 'currentQuarter',
            'lastQuarter', 'currentYear', 'lastYear', 'today', 'past', 'future', 'lastXDays', 'nextXDays',
            'olderThanXDays', 'afterXDays', 'on', 'after', 'before', 'between',
        ],

        initialSearchIsNotIdle: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getConfig().get('fiscalYearShift')) {
                this.searchTypeList = Espo.Utils.clone(this.searchTypeList);

                if (this.getConfig().get('fiscalYearShift') % 3 !== 0) {
                    this.searchTypeList.push('currentFiscalQuarter');
                    this.searchTypeList.push('lastFiscalQuarter');
                }

                this.searchTypeList.push('currentFiscalYear');
                this.searchTypeList.push('lastFiscalYear');
            }
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.dateValue = this.getDateStringValue();

            if (this.isSearchMode()) {
                var value = this.getSearchParamsData().value || this.searchParams.dateValue;
                var valueTo = this.getSearchParamsData().valueTo || this.searchParams.dateValueTo;

                data.dateValue = this.getDateTime().toDisplayDate(value);
                data.dateValueTo = this.getDateTime().toDisplayDate(valueTo);

                if (~['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'].indexOf(this.getSearchType())) {
                    data.number = this.searchParams.value;
                }
            }

            return data;
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': function (e) {
                    var type = $(e.currentTarget).val();
                    this.handleSearchType(type);
                },
            }, this.events || {});
        },

        stringifyDateValue: function (value) {
            if (!value) {
                if (
                    this.mode === 'edit' ||
                    this.mode === 'search' ||
                    this.mode === 'list' ||
                    this.mode === 'listLink'
                ) {
                    return '';
                }

                return this.translate('None');
            }

            if (this.mode === 'list' || this.mode === 'detail' || this.mode === 'listLink') {
                return this.convertDateValueForDetail(value);
            }

            return this.getDateTime().toDisplayDate(value);
        },

        convertDateValueForDetail: function (value) {
            if (this.getConfig().get('readableDateFormatDisabled') || this.params.useNumericFormat) {
                return this.getDateTime().toDisplayDate(value);
            }

            var timezone = this.getDateTime().getTimeZone();
            var internalDateTimeFormat = this.getDateTime().internalDateTimeFormat;
            var readableFormat = this.getDateTime().getReadableDateFormat();
            var valueWithTime = value + ' 00:00:00';

            var today = moment().tz(timezone).startOf('day');
            var dateTime = moment.tz(valueWithTime, internalDateTimeFormat, timezone);

            var temp = today.clone();

            var ranges = {
                'today': [temp.unix(), temp.add(1, 'days').unix()],
                'tomorrow': [temp.unix(), temp.add(1, 'days').unix()],
                'yesterday': [temp.add(-3, 'days').unix(), temp.add(1, 'days').unix()],
            };

            if (dateTime.unix() >= ranges['today'][0] && dateTime.unix() < ranges['today'][1]) {
                return this.translate('Today');
            }

            if (dateTime.unix() >= ranges['tomorrow'][0] && dateTime.unix() < ranges['tomorrow'][1]) {
                return this.translate('Tomorrow');
            }

            if (dateTime.unix() >= ranges['yesterday'][0] && dateTime.unix() < ranges['yesterday'][1]) {
                return this.translate('Yesterday');
            }

            // Need to use UTC, otherwise there's a DST issue with old dates.
            var dateTime = moment.utc(valueWithTime, internalDateTimeFormat);

            if (dateTime.format('YYYY') === today.format('YYYY')) {
                return dateTime.format(readableFormat);
            }

            return dateTime.format(readableFormat + ', YYYY');
        },

        getDateStringValue: function () {
            if (this.mode === 'detail' && !this.model.has(this.name)) {
                return '...';
            }

            var value = this.model.get(this.name);

            return this.stringifyDateValue(value);
        },

        afterRender: function () {
            if (this.mode === 'edit' || this.mode === 'search') {
                this.$element = this.$el.find('[data-name="' + this.name + '"]');

                var wait = false;
                this.$element.on('change', function () {
                    if (!wait) {
                        this.trigger('change');
                        wait = true;
                        setTimeout(function () {
                            wait = false;
                        }, 100);
                    }
                }.bind(this));

                var options = {
                    format: this.getDateTime().dateFormat.toLowerCase(),
                    weekStart: this.getDateTime().weekStart,
                    autoclose: true,
                    todayHighlight: true,
                    keyboardNavigation: true,
                    todayBtn: this.getConfig().get('datepickerTodayButton') || false,
                };

                var language = this.getConfig().get('language');

                if (!(language in $.fn.datepicker.dates)) {
                    $.fn.datepicker.dates[language] = {
                        days: this.translate('dayNames', 'lists'),
                        daysShort: this.translate('dayNamesShort', 'lists'),
                        daysMin: this.translate('dayNamesMin', 'lists'),
                        months: this.translate('monthNames', 'lists'),
                        monthsShort: this.translate('monthNamesShort', 'lists'),
                        today: this.translate('Today'),
                        clear: this.translate('Clear'),
                    };
                }

                options.language = language;

                var $datePicker = this.$element.datepicker(options);

                if (this.mode === 'search') {
                    var $elAdd = this.$el.find('input.additional');

                    $elAdd.datepicker(options);
                    $elAdd.parent().find('button.date-picker-btn').on('click', function (e) {
                        $elAdd.datepicker('show');
                    });

                    this.$el.find('select.search-type').on('change', function () {
                        this.trigger('change');
                    }.bind(this));

                    $elAdd.on('change', function () {
                        this.trigger('change');
                    }.bind(this));
                }

                this.$element.parent().find('button.date-picker-btn').on('click', function (e) {
                    this.$element.datepicker('show');
                }.bind(this));


                if (this.mode === 'search') {
                    var $searchType = this.$el.find('select.search-type');
                    this.handleSearchType($searchType.val());
                }
            }
        },

        handleSearchType: function (type) {
            this.$el.find('div.primary').addClass('hidden');
            this.$el.find('div.additional').addClass('hidden');
            this.$el.find('div.additional-number').addClass('hidden');

            if (~['on', 'notOn', 'after', 'before'].indexOf(type)) {
                this.$el.find('div.primary').removeClass('hidden');
            }
            else if (~['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'].indexOf(type)) {
                this.$el.find('div.additional-number').removeClass('hidden');
            }
            else if (type === 'between') {
                this.$el.find('div.primary').removeClass('hidden');
                this.$el.find('div.additional').removeClass('hidden');
            }
        },

        parseDate: function (string) {
            return this.getDateTime().fromDisplayDate(string);
        },

        parse: function (string) {
            return this.parseDate(string);
        },

        fetch: function () {
            var data = {};

            data[this.name] = this.parse(this.$element.val());

            return data;
        },

        fetchSearch: function () {
            var value = this.parseDate(this.$element.val());

            var type = this.fetchSearchType();
            var data;

            if (type === 'between') {
                if (!value) {
                    return false;
                }

                var valueTo = this.parseDate(this.$el.find('input.additional').val());

                if (!valueTo) {
                    return false;
                }

                data = {
                    type: type,
                    value: [value, valueTo],
                    data: {
                        value: value,
                        valueTo: valueTo
                    }
                };
            } else if (~['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'].indexOf(type)) {
                var number = this.$el.find('input.number').val();

                data = {
                    type: type,
                    value: number
                };
            }
            else if (~['on', 'notOn', 'after', 'before'].indexOf(type)) {
                if (!value) {
                    return false;
                }
                data = {
                    type: type,
                    value: value,
                    data: {
                        value: value
                    }
                };
            }
            else if (type === 'isEmpty') {
                data = {
                    type: 'isNull',
                    data: {
                        type: type
                    }
                };
            }
            else {
                data = {
                    type: type
                };
            }

            return data;
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.typeFront || this.searchParams.type;
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.name) === null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg);

                    return true;
                }
            }
        },

        validateDate: function () {
            if (this.model.get(this.name) === -1) {
                var msg = this.translate('fieldShouldBeDate', 'messages').replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        },

        validateAfter: function () {
            var field = this.model.getFieldParam(this.name, 'after');

            if (field) {
                var value = this.model.get(this.name);
                var otherValue = this.model.get(field);

                if (value && otherValue) {
                    if (moment(value).unix() <= moment(otherValue).unix()) {
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
                var value = this.model.get(this.name);
                var otherValue = this.model.get(field);

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
    });
});

