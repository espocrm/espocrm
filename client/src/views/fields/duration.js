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

import EnumFieldView from 'views/fields/enum';
import Select from 'ui/select';
import moment from 'moment';

class DurationFieldView extends EnumFieldView {

    type = 'duration'

    listTemplate = 'fields/base/detail'
    detailTemplate = 'fields/varchar/detail'
    editTemplate = 'fields/duration/edit'

    /**
     * @private
     * @type {boolean}
     */
    _justFocused = false

    data() {
        const valueIsSet = this.model.has(this.startField) && this.model.has(this.endField);

        return {
            valueIsSet: valueIsSet,
            durationOptions: this.durationOptions,
            ...super.data(),
        };
    }

    /**
     * @private
     */
    calculateSeconds() {
        this.seconds = 0;

        const start = this.model.get(this.startField);
        let end = this.model.get(this.endField);

        if (this.isEditMode() || this.isDetailMode()) {
            if (this.model.isNew()) {
                this.seconds = this.model.getFieldParam(this.name, 'default') || 0;
            }
        }

        if (this.model.attributes.isAllDay && this.hasAllDay) {
            const startDate = this.model.attributes[this.startDateField];
            const endDate = this.model.attributes[this.endDateField];

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

            this.getOptions().forEach(duration => {
                const option = document.createElement('option');
                option.value = duration.toString();
                option.text = this.stringifyDuration(duration);

                if (duration === this.seconds) {
                    option.setAttribute('selected', 'selected');
                }

                this.durationOptions += option.outerHTML;
            });

            this.stringValue = this.stringifyDuration(this.seconds);
        });
    }

    /**
     * @return {Number[]}
     */
    getOptions() {
        const options = Espo.Utils.clone(this.model.getFieldParam(this.name, 'options') ?? []);

        if (!this.model.attributes.isAllDay && options.indexOf(this.seconds) === -1) {
            options.push(this.seconds);
        }

        options.sort((a, b) => a - b);

        return options;
    }

    setup() {
        this.startField = this.model.getFieldParam(this.name, 'start');
        this.endField = this.model.getFieldParam(this.name, 'end');

        this.startDateField = this.startField + 'Date';
        this.endDateField = this.endField + 'Date';

        this.hasAllDay = this.model.getFieldType(this.startField) === 'datetimeOptional';

        if (!this.startField || !this.endField) {
            throw new Error(`Bad definition for field '${this.name}'.`);
        }

        this.calculateSeconds();

        this.blockDateEndChangeListener = false;

        this.listenTo(this.model, `change:${this.endField}`, (m, v, o) => {
            if (this.blockDateEndChangeListener) {
                return;
            }

            const start = this.model.get(this.startField);
            const end = this.model.get(this.endField);

            if (!end || !start) {
                return;
            }

            this.seconds = moment.utc(end).unix() - moment.utc(start).unix();

            if (o.updatedByDuration) {
                return;
            }

            this.updateDuration();
        });

        this.listenTo(this.model, `change:${this.startField}`, (m, v, o) => {
            if (o.ui) {
                const isAllDay = this.model.attributes[this.startDateField];

                if (isAllDay && this.hasAllDay) {
                    const remainder = this.seconds % (3600 * 24);

                    if (remainder !== 0) {
                        this.seconds = this.seconds - remainder + 3600 * 24;
                    }
                }

                this.blockDateEndChangeListener = true;
                setTimeout(() => this.blockDateEndChangeListener = false, 100);

                this.updateDateEnd(this.startField);

                setTimeout(() => this.updateDuration(), 110);

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

    /**
     * @private
     * @param {number} secondsTotal
     * @return {string}
     */
    stringifyDuration(secondsTotal) {
        if (!secondsTotal) {
            return '0';
        }

        if (secondsTotal < 60) {
            return '0';
        }

        let d = secondsTotal;
        const days = Math.floor(d / (86400));
        d = d % (86400);

        const hours = Math.floor(d / (3600));
        d = d % (3600);
        const minutes = Math.floor(d / (60));

        const parts = [];

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
        const parentView = this.getParentView();

        if (parentView && 'getView' in parentView) {
            this.endFieldView = parentView.getView(this.endField);
        }

        if (this.isEditMode()) {
            this.$duration = this.$el.find('.main-element');

            this.$duration.on('change', () => {
                this.seconds = parseInt(this.$duration.val());

                this.updateDateEnd();
            });

            const start = this.model.get(this.startField);
            const end = this.model.get(this.endField);

            const seconds = this.$duration.val();

            if (!end && start && seconds) {
                if (this.endFieldView) {
                    if (this.endFieldView.isRendered()) {
                        this.updateDateEnd();
                    } else {
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
                    const num = parseInt(item.value);
                    const searchNum = parseInt(search);

                    if (isNaN(searchNum)) {
                        return 0;
                    }

                    const numOpposite = Number.MAX_SAFE_INTEGER - num;

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
                    const num = parseInt(item);

                    if (isNaN(num) || num <= 0) {
                        return;
                    }

                    if (num > 59) {
                        return;
                    }

                    const list = [];

                    const mSeconds = num * 60;

                    list.push({
                        value: mSeconds.toString(),
                        text: this.stringifyDuration(mSeconds),
                    });

                    if (num <= 9) {
                        const hSeconds = num * 3600;

                        list.push({
                            value: hSeconds.toString(),
                            text: this.stringifyDuration(hSeconds),
                        });
                    }

                    callback(list);
                },
                onFocus: () => {
                    this._justFocused = true;
                    setTimeout(() => this._justFocused = false, 150);
                },
            });
        }
    }

    /**
     * @private
     * @return {string}
     */
    _getDateEndDate() {
        const seconds = this.seconds;
        const start = this.model.attributes[this.startDateField];

        if (!start) {
            return undefined;
        }

        if (!seconds) {
            return start;
        }

        const endUnix = moment.utc(start).unix() + seconds;

        return moment.unix(endUnix)
            .utc()
            .add(-1, 'day')
            .format(this.getDateTime().internalDateFormat);
    }

    /**
     * @private
     * @return {string}
     */
    _getDateEnd() {
        const seconds = this.seconds;
        const start = this.model.get(this.startField);

        if (!start) {
            return undefined;
        }

        let endUnix;
        let end;

        if (seconds) {
            endUnix = moment.utc(start).unix() + seconds;

            end = moment.unix(endUnix).utc().format(this.getDateTime().internalDateTimeFormat);
        } else {
            end = start;
        }

        return end;
    }

    /**
     * @private
     * @param {string} [fromField]
     */
    updateDateEnd(fromField) {
        if (this.model.attributes.isAllDay && this.hasAllDay) {
            const end = this._getDateEndDate();

            setTimeout(() => {
                this.model.set(this.endDateField, end, {
                    updatedByDuration: true,
                    fromField: fromField,
                });
            }, 1);

            return;
        }

        const end = this._getDateEnd();

        // Smaller timeouts produce a js error in timepicker.
        setTimeout(() => {
            this.model.set(this.endField, end, {
                updatedByDuration: true,
                fromField: fromField,
            });

            if (this.hasAllDay) {
                this.model.set(this.endDateField, null, {fromField: fromField});
            }
        }, 100);
    }

    /**
     * @private
     */
    updateDuration() {
        const seconds = this.seconds;

        if (this.isEditMode() && this.$duration && this.$duration.length && !this._justFocused) {
            const options = this.getOptions().map(value => {
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
