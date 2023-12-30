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

import EnumFieldView from 'views/fields/enum';
import Select from 'ui/select';
import moment from 'moment';

class DurationFieldView extends EnumFieldView {

    type = 'duration'

    listTemplate = 'fields/base/detail'
    detailTemplate = 'fields/varchar/detail'
    editTemplate = 'fields/duration/edit'

    data() {
        let valueIsSet = this.model.has(this.startField) && this.model.has(this.endField);

        return {
            valueIsSet: valueIsSet,
            durationOptions: this.durationOptions,
            ...super.data(),
        };
    }

    calculateSeconds() {
        this.seconds = 0;

        let start = this.model.get(this.startField);
        let end = this.model.get(this.endField);

        if (this.isEditMode() || this.isDetailMode()) {
            if (this.model.isNew()) {
                this.seconds = this.model.getFieldParam(this.name, 'default') || 0;
            }
        }

        if (this.model.get('isAllDay')) {
            let startDate = this.model.get(this.startField + 'Date');
            let endDate = this.model.get(this.endField + 'Date');

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
            end = this._getDateEnd();

            this.model.set(this.endField, end, {silent: true});
        }
    }

    init() {
        super.init();

        this.listenTo(this, 'render', () => {
            this.calculateSeconds();

            this.durationOptions = '';

            this.getOptions().forEach(d => {
                let $o = $('<option>')
                    .val(d)
                    .text(this.stringifyDuration(d));

                if (d === this.seconds) {
                    $o.attr('selected', 'selected')
                }

                this.durationOptions += $o.get(0).outerHTML;
            });

            this.stringValue = this.stringifyDuration(this.seconds);
        });
    }

    /**
     * @return {Number[]}
     */
    getOptions() {
        let options = Espo.Utils.clone(this.model.getFieldParam(this.name, 'options') ?? []);

        if (!this.model.get('isAllDay') && options.indexOf(this.seconds) === -1) {
            options.push(this.seconds);
        }

        options.sort((a, b) => a - b);

        return options;
    }

    setup() {
        this.startField = this.model.getFieldParam(this.name, 'start');
        this.endField = this.model.getFieldParam(this.name, 'end');

        if (!this.startField || !this.endField) {
            throw new Error('Bad definition for field \'' + this.name + '\'.');
        }

        this.calculateSeconds();

        this.blockDateEndChangeListener = false;

        this.listenTo(this.model, 'change:' + this.endField, (m, v, o) => {
            if (this.blockDateEndChangeListener) {
                return;
            }

            let start = this.model.get(this.startField);
            let end = this.model.get(this.endField);

            if (!end || !start) {
                return;
            }

            this.seconds = moment(end).unix() - moment(start).unix();

            if (o.updatedByDuration) {
                return;
            }

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

            if (!this.isEditMode() && o.xhr) {
                return;
            }

            this.updateDateEnd();
        });
    }

    getValueForDisplay() {
        return this.stringValue;
    }

    stringifyDuration(secondsTotal) {
        if (!secondsTotal) {
            return '0';
        }

        if (secondsTotal < 60) {
            return '0';
        }

        let d = secondsTotal;
        let days = Math.floor(d / (86400));
        d = d % (86400);

        let hours = Math.floor(d / (3600));
        d = d % (3600);
        let minutes = Math.floor(d / (60));

        let parts = [];

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
    }

    focusOnInlineEdit() {
        Select.focus(this.$duration);
    }

    afterRender() {
        let parentView = this.getParentView();

        if (parentView && 'getView' in parentView) {
            this.endFieldView = parentView.getView(this.endField);
        }

        if (this.isEditMode()) {
            this.$duration = this.$el.find('.main-element');

            this.$duration.on('change', () => {
                this.seconds = parseInt(this.$duration.val());

                this.updateDateEnd();
            });

            let start = this.model.get(this.startField);
            let end = this.model.get(this.endField);

            let seconds = this.$duration.val();

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

            Select.init(this.$duration, {
                sortBy: '$score',
                sortDirection: 'desc',
                /**
                 * @param {string} search
                 * @param {{value: string}} item
                 * @return {number}
                 */
                score: (search, item) => {
                    let num = parseInt(item.value);
                    let searchNum = parseInt(search);

                    if (isNaN(searchNum)) {
                        return 0;
                    }

                    let numOpposite = Number.MAX_SAFE_INTEGER - num;

                    if (searchNum === 0 && num === 0) {
                        return numOpposite;
                    }

                    if (searchNum * 60 === num) {
                        return numOpposite;
                    }

                    if (searchNum * 60 * 60 === num) {
                        return numOpposite;
                    }

                    if (searchNum * 60 * 60 * 24 === num) {
                        return numOpposite;
                    }

                    return 0;
                },
                load: (item, callback) => {
                    let num = parseInt(item);

                    if (isNaN(num) || num <= 0) {
                        return;
                    }

                    if (num > 59) {
                        return;
                    }

                    let list = [];

                    let mSeconds = num * 60;

                    list.push({
                        value: mSeconds.toString(),
                        text: this.stringifyDuration(mSeconds),
                    });

                    if (num <= 9) {
                        let hSeconds = num * 3600;

                        list.push({
                            value: hSeconds.toString(),
                            text: this.stringifyDuration(hSeconds),
                        });
                    }

                    callback(list);
                },
            });
        }
    }

    _getDateEndDate() {
        let seconds = this.seconds;
        let start = this.model.get(this.startField + 'Date');

        if (!start) {
            return;
        }

        if (!seconds) {
            return start;
        }

        let endUnix = moment.utc(start).unix() + seconds;

        return moment.unix(endUnix)
            .utc()
            .add(-1, 'day')
            .format(this.getDateTime().internalDateFormat);
    }

    _getDateEnd() {
        let seconds = this.seconds;
        let start = this.model.get(this.startField);

        if (!start) {
            return;
        }

        let endUnix;
        let end;

        if (seconds) {
            endUnix = moment.utc(start).unix() + seconds;

            end = moment.unix(endUnix).utc().format(this.getDateTime().internalDateTimeFormat);
        }
        else {
            end = start;
        }

        return end;
    }

    updateDateEnd() {
        let end;

        if (this.model.get('isAllDay')) {
            end = this._getDateEndDate();

            setTimeout(() => {
                this.model.set(this.endField + 'Date', end, {updatedByDuration: true});
            }, 1);

            return;
        }

        end = this._getDateEnd();

        setTimeout(() => {
            this.model.set(this.endField, end, {updatedByDuration: true});
            this.model.set(this.endField + 'Date', null);
        }, 1);
    }

    updateDuration() {
        let seconds = this.seconds;

        if (this.isEditMode() && this.$duration && this.$duration.length) {
            let options = this.getOptions().map(value => {
                return {
                    value: value.toString(),
                    text: this.stringifyDuration(value),
                };
            });

            Select.setValue(this.$duration, '');
            Select.setOptions(this.$duration, options);
            Select.setValue(this.$duration, seconds.toString());

            return;
        }

        this.reRender();
    }

    fetch() {
        // noinspection JSValidateTypes
        return {};
    }
}

export default DurationFieldView;
