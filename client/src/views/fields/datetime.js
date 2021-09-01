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

define('views/fields/datetime', ['views/fields/date', 'lib!moment'], function (Dep, moment) {

    return Dep.extend({

        type: 'datetime',

        editTemplate: 'fields/datetime/edit',

        validations: ['required', 'datetime', 'after', 'before'],

        searchTypeList: [
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
        ],

        timeFormatMap: {
            'HH:mm': 'H:i',
            'hh:mm A': 'h:i A',
            'hh:mm a': 'h:i a',
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.date = data.time = '';

            var value = this.getDateTime().toDisplay(this.model.get(this.name));

            if (value) {
                let pair = this.splitDatetime(value);

                data.date = pair[0];
                data.time = pair[1];
            }

            return data;
        },

        getDateStringValue: function () {
            if (this.mode === 'detail' && !this.model.has(this.name)) {
                return '...';
            }

            var value = this.model.get(this.name);

            if (!value) {
                if (
                    this.mode === 'edit' |
                    this.mode === 'search' |
                    this.mode === 'list' ||
                    this.mode === 'listLink'
                ) {
                    return '';
                }

                return this.translate('None');
            }

            if (this.mode === 'list' || this.mode === 'detail' || this.mode === 'listLink') {
                if (this.getConfig().get('readableDateFormatDisabled') || this.params.useNumericFormat) {
                    return this.getDateTime().toDisplayDateTime(value);
                }

                var timeFormat = this.getDateTime().timeFormat;

                if (this.params.hasSeconds) {
                    timeFormat = timeFormat.replace(/:mm/, ':mm:ss');
                }

                var d = this.getDateTime().toMoment(value);
                var now = moment().tz(this.getDateTime().timeZone || 'UTC');
                var dt = now.clone().startOf('day');

                var ranges = {
                    'today': [dt.unix(), dt.add(1, 'days').unix()],
                    'tomorrow': [dt.unix(), dt.add(1, 'days').unix()],
                    'yesterday': [dt.add(-3, 'days').unix(), dt.add(1, 'days').unix()]
                };

                if (d.unix() > ranges['today'][0] && d.unix() < ranges['today'][1]) {
                    return this.translate('Today') + ' ' + d.format(timeFormat);
                }
                else if (d.unix() > ranges['tomorrow'][0] && d.unix() < ranges['tomorrow'][1]) {
                    return this.translate('Tomorrow') + ' ' + d.format(timeFormat);
                }
                else if (d.unix() > ranges['yesterday'][0] && d.unix() < ranges['yesterday'][1]) {
                    return this.translate('Yesterday') + ' ' + d.format(timeFormat);
                }

                var readableFormat = this.getDateTime().getReadableDateFormat();

                if (d.format('YYYY') === now.format('YYYY')) {
                    return d.format(readableFormat) + ' ' + d.format(timeFormat);
                }
                else {
                    return d.format(readableFormat + ', YYYY') + ' ' + d.format(timeFormat);
                }
            }

            return this.getDateTime().toDisplay(value);
        },

        initTimepicker: function () {
            var $time = this.$time;

            $time.timepicker({
                step: this.params.minuteStep || 30,
                scrollDefaultNow: true,
                timeFormat: this.timeFormatMap[this.getDateTime().timeFormat]
            });

            $time
                .parent()
                .find('button.time-picker-btn')
                .on('click', () => {
                    $time.timepicker('show');
                });
        },

        setDefaultTime: function () {
            var dtString = moment('2014-01-01 00:00').format(this.getDateTime().getDateTimeFormat()) || '';

            var pair = this.splitDatetime(dtString);

            if (pair.length === 2) {
                this.$time.val(pair[1]);
            }
        },

        splitDatetime: function (value) {
            let m = moment(value, this.getDateTime().getDateTimeFormat());

            let dateValue = m.format(this.getDateTime().getDateFormat());

            let timeValue = value.substr(dateValue.length + 1);

            return [dateValue, timeValue];
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                this.$date = this.$element;
                var $time = this.$time = this.$el.find('input.time-part');

                this.initTimepicker();

                this.$element.on('change.datetime', (e) => {
                    if (this.$element.val() && !$time.val()) {
                        this.setDefaultTime();
                        this.trigger('change');
                    }
                });

                var timeout = false;

                $time.on('change', () => {
                    if (!timeout) {
                        this.trigger('change');
                    }

                    timeout = true;

                    setTimeout(() => timeout = false, 100);
                });
            }
        },

        update: function (value) {
            if (this.mode === 'edit') {
                var pair = this.splitDatetime(value);

                this.$date.val(pair[0]);
                this.$time.val(pair[1]);

                return;
            }

            this.setup();
            this.render();
        },

        parse: function (string) {
            return this.getDateTime().fromDisplay(string);
        },

        fetch: function () {
            var data = {};

            var date = this.$date.val();
            var time = this.$time.val();

            var value = null;

            if (date !== '' && time !== '') {
                value = this.parse(date + ' ' + time);
            }

            data[this.name] = value;

            return data;
        },

        validateDatetime: function () {
            if (this.model.get(this.name) === -1) {
                var msg = this.translate('fieldShouldBeDatetime', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        },

        fetchSearch: function () {
            var data = Dep.prototype.fetchSearch.call(this);

            if (data) {
                data.dateTime = true;
            }

            return data;
        },

    });
});
