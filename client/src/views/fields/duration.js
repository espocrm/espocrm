/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/duration', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        type: 'duration',

        listTemplate: 'fields/base/detail',

        detailTemplate: 'fields/varchar/detail',

        editTemplate: 'fields/duration/edit',

        data: function () {
            let valueIsSet = this.model.has(this.startField) && this.model.has(this.endField);

            return _.extend({
                valueIsSet: valueIsSet,
                durationOptions: this.durationOptions,
            }, Dep.prototype.data.call(this));
        },

        calculateSeconds: function () {
            this.seconds = 0;

            var start = this.model.get(this.startField);
            var end = this.model.get(this.endField);

            if (this.mode === 'edit' || this.mode === 'detail') {
                if (this.model.isNew()) {
                    this.seconds = this.model.getFieldParam(this.name, 'default') || 0;
                }
            }

            if (this.model.get('isAllDay')) {
                var startDate = this.model.get(this.startField + 'Date');
                var endDate = this.model.get(this.endField + 'Date');

                if (startDate && endDate) {
                    this.seconds = moment(endDate).add(1,'days').unix() - moment(startDate).unix();

                    return;
                }
            }

            if (start && end) {
                this.seconds = moment(this.model.get(this.endField)).unix() -
                    moment(this.model.get(this.startField)).unix();

                return;
            }

            if (start) {
                var end = this._getDateEnd();

                this.model.set(this.endField, end, {silent: true});
            }
        },

        init: function () {
            Dep.prototype.init.call(this);

            this.listenTo(this, 'render', () => {
                this.calculateSeconds();

                var durationOptions = '';

                var options = this.defaultOptions = _.clone(this.model.getFieldParam(this.name, 'options'));

                if (!this.model.get('isAllDay') && options.indexOf(this.seconds) === -1) {
                    options.push(this.seconds);
                }

                options.sort((a, b) => {
                    return a - b;
                });

                options.forEach((d) => {
                    durationOptions += '<option value="' + d + '" ' +
                        (d === this.seconds ? 'selected' : '') + '>' +
                        this.stringifyDuration(d) + '</option>';
                });

                this.durationOptions = durationOptions;

                this.stringValue = this.stringifyDuration(this.seconds);
            });
        },

        setup: function () {
            this.startField = this.model.getFieldParam(this.name, 'start');
            this.endField = this.model.getFieldParam(this.name, 'end');

            if (!this.startField || !this.endField) {
                throw new Error('Bad definition for field \'' + this.name + '\'.');
            }

            this.calculateSeconds();

            this.blockDateEndChangeListener = false;

            this.listenTo(this.model, 'change:' + this.endField, () => {
                if (this.blockDateEndChangeListener) {
                    return;
                }

                var start = this.model.get(this.startField);
                var end = this.model.get(this.endField);

                if (!end || !start) {
                    return;
                }

                this.seconds = moment(end).unix() - moment(start).unix();

                this.updateDuration();
            });

            this.listenTo(this.model, 'change:' + this.startField, (m, v, o) => {
                if (o.ui) {
                    let isAllDay = this.model.get(this.startField + 'Date');

                    if (isAllDay) {
                        let remainder = this.seconds % (3600 * 24);

                        if (remainder !== 0) {
                            this.seconds = this.seconds - remainder + 3600 * 24;
                        }
                    }

                    this.blockDateEndChangeListener = true;
                    setTimeout(() => this.blockDateEndChangeListener = false, 100);

                    this.updateDateEnd();

                    setTimeout(() => this.updateDuration(), 50);

                    return;
                }

                this.updateDateEnd();
            });
        },

        getValueForDisplay: function () {
            return this.stringValue;
        },

        stringifyDuration: function (secondsTotal) {
            if (!secondsTotal) {
                return '0';
            }

            if (secondsTotal < 60) {
                return '0';
            }

            var d = secondsTotal;

            var days = Math.floor(d / (86400));

            d = d % (86400);

            var hours = Math.floor(d / (3600));

            d = d % (3600);

            var minutes = Math.floor(d / (60));

            var parts = [];

            if (days) {
                parts.push(days + '' + this.getLanguage().translate('d', 'durationUnits'));
            }

            if (hours) {
                parts.push(hours + '' + this.getLanguage().translate('h', 'durationUnits'));
            }

            if (minutes) {
                parts.push(minutes + '' + this.getLanguage().translate('m', 'durationUnits'));
            }

            return parts.join(' ');
        },

        afterRender: function () {
            var parentView = this.getParentView();

            if (parentView && 'getView' in parentView) {
                this.startFieldView = parentView.getView(this.startField);
                this.endFieldView = parentView.getView(this.endField);
            }

            if (this.mode === 'edit') {
                this.$duration = this.$el.find('.main-element');

                this.$duration.on('change', () => {
                    this.seconds = parseInt(this.$duration.val());

                    this.updateDateEnd();

                    this.$duration.find('option.custom').remove();
                });
            }

            if (this.mode === 'edit') {
                var start = this.model.get(this.startField);
                var end = this.model.get(this.endField);

                var seconds = this.$duration.val();

                if (!end && start && seconds) {
                    if (this.endFieldView) {
                        if (this.endFieldView.isRendered()) {
                            this.updateDateEnd();
                        }
                        else {
                            this.endFieldView.once('after:render', () => {
                                this.updateDateEnd();
                            });
                        }
                    }
                }
            }
        },

        _getDateEndDate: function () {
            let seconds = this.seconds;

            let start = this.model.get(this.startField + 'Date');

            if (!start) {
                return;
            }

            if (!seconds) {
                return start;
            }

            let endUnix = moment.utc(start).unix() + seconds;

            let end = moment.unix(endUnix)
                .utc()
                .add(-1, 'day')
                .format(this.getDateTime().internalDateFormat);

            return end;
        },

        _getDateEnd: function () {
            var seconds = this.seconds;

            var start = this.model.get(this.startField);

            if (!start) {
                return;
            }

            var endUnix;
            var end;

            if (seconds) {
                endUnix = moment.utc(start).unix() + seconds;

                end = moment.unix(endUnix).utc().format(this.getDateTime().internalDateTimeFormat);
            }
            else {
                end = start;
            }

            return end;
        },

        updateDateEnd: function () {
            if (this.model.get('isAllDay')) {
                var end = this._getDateEndDate();

                setTimeout(() => {
                    this.model.set(this.endField + 'Date', end, {updatedByDuration: true});
                }, 1);

                return;
            }

            var end = this._getDateEnd();

            setTimeout(() => {
                this.model.set(this.endField, end, {updatedByDuration: true});
                this.model.set(this.endField + 'Date', null);
            }, 1);
        },

        updateDuration: function () {
            var seconds = this.seconds;

            if (seconds < 0) {
                if (this.mode === 'edit') {
                    this.$duration.val('');

                    return;
                }

                this.setup();
                this.render();

                return;
            }

            if (this.mode === 'edit' && this.$duration && this.$duration.length) {
                this.$duration.find('option.custom').remove();

                var $o = $('<option>')
                    .val(seconds)
                    .text(this.stringifyDuration(seconds))
                    .addClass('custom');

                var $found = this.$duration.find('option')
                    .filter((i, el) => {
                        return $(el).val() >= seconds;
                    })
                    .first();

                if ($found.length) {
                    if (parseInt($found.val()) !== seconds) {
                        $o.insertBefore($found);
                    };
                }
                else {
                    $o.appendTo(this.$duration);
                }

                this.$duration.val(seconds);

                return;
            }

            this.setup();
            this.render();
        },

        fetch: function () {
            return {};
        },
    });
});
