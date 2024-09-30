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

/** @module views/fields/date */

import BaseFieldView from 'views/fields/base';
import moment from 'moment';

/**
 * A date field.
 *
 * @extends BaseFieldView<module:views/fields/date~params>
 */
class DateFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/date~options
     * @property {
     *     module:views/fields/date~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/date~params
     * @property {boolean} [required] Required.
     * @property {boolean} [useNumericFormat] Use numeric format.
     * @property {string} [after] Validate to be after another date field.
     * @property {string} [before] Validate to be before another date field.
     * @property {boolean} [afterOrEqual] Allow an equal date for 'after' validation.
     */

    /**
     * @param {
     *     module:views/fields/date~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'date'

    listTemplate = 'fields/date/list'
    listLinkTemplate = 'fields/date/list-link'
    detailTemplate = 'fields/date/detail'
    editTemplate = 'fields/date/edit'
    searchTemplate = 'fields/date/search'

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = [
        'required',
        'date',
        'after',
        'before',
    ]

    searchTypeList = [
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

    initialSearchIsNotIdle = true

    setup() {
        super.setup();

        if (this.getConfig().get('fiscalYearShift')) {
            this.searchTypeList = Espo.Utils.clone(this.searchTypeList);

            if (this.getConfig().get('fiscalYearShift') % 3 !== 0) {
                this.searchTypeList.push('currentFiscalQuarter');
                this.searchTypeList.push('lastFiscalQuarter');
            }

            this.searchTypeList.push('currentFiscalYear');
            this.searchTypeList.push('lastFiscalYear');
        }

        if (this.params.after) {
            this.listenTo(this.model, `change:${this.params.after}`, async () => {
                if (!this.isEditMode()) {
                    return;
                }

                await this.whenRendered();

                // noinspection JSUnresolvedReference
                this.$element.datepicker('setStartDate', this.getStartDateForDatePicker());
            });
        }
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        data.dateValue = this.getDateStringValue();

        data.isNone = data.dateValue === null;

        if (data.dateValue === -1) {
            data.dateValue = null;
            data.isLoading = true;
        }

        if (this.isSearchMode()) {
            const value = this.getSearchParamsData().value || this.searchParams.dateValue;
            const valueTo = this.getSearchParamsData().valueTo || this.searchParams.dateValueTo;

            data.dateValue = this.getDateTime().toDisplayDate(value);
            data.dateValueTo = this.getDateTime().toDisplayDate(valueTo);

            if (['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'].includes(this.getSearchType())) {
                data.number = this.searchParams.value;
            }
        }

        // noinspection JSValidateTypes
        return data;
    }

    setupSearch() {
        this.addHandler('change', 'select.search-type', (e, /** HTMLSelectElement */target) => {
            this.handleSearchType(target.value);
        });
    }

    stringifyDateValue(value) {
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
            return this.convertDateValueForDetail(value);
        }

        return this.getDateTime().toDisplayDate(value);
    }

    convertDateValueForDetail(value) {
        if (this.getConfig().get('readableDateFormatDisabled') || this.params.useNumericFormat) {
            return this.getDateTime().toDisplayDate(value);
        }

        const timezone = this.getDateTime().getTimeZone();
        const internalDateTimeFormat = this.getDateTime().internalDateTimeFormat;
        const readableFormat = this.getDateTime().getReadableDateFormat();
        const valueWithTime = value + ' 00:00:00';

        const today = moment().tz(timezone).startOf('day');
        let dateTime = moment.tz(valueWithTime, internalDateTimeFormat, timezone);

        const temp = today.clone();

        const ranges = {
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
        dateTime = moment.utc(valueWithTime, internalDateTimeFormat);

        if (dateTime.format('YYYY') === today.format('YYYY')) {
            return dateTime.format(readableFormat);
        }

        return dateTime.format(readableFormat + ', YYYY');
    }

    getDateStringValue() {
        if (this.mode === this.MODE_DETAIL && !this.model.has(this.name)) {
            return -1;
        }

        const value = this.model.get(this.name);

        return this.stringifyDateValue(value);
    }

    /**
     * @protected
     * @return {string|undefined}
     */
    getStartDateForDatePicker() {
        if (!this.isEditMode() || !this.params.after) {
            return undefined;
        }

        /** @type {string} */
        let date = this.model.attributes[this.params.after];

        if (date == null) {
            return undefined;
        }

        if (date.length > 10) {
            date = this.getDateTime().toDisplay(date);
            [date,] = date.split(' ');

            return date;
        }

        return this.getDateTime().toDisplayDate(date);
    }

    afterRender() {
        if (this.mode === this.MODE_EDIT || this.mode === this.MODE_SEARCH) {
            this.$element = this.$el.find('[data-name="' + this.name + '"]');

            let wait = false;

            // @todo Introduce ui/date-picker.

            this.$element.on('change', /** Record */e => {
                if (!wait) {
                    this.trigger('change');
                    wait = true;
                    setTimeout(() => wait = false, 100);
                }

                if (e.isTrigger) {
                    if (document.activeElement !== this.$element.get(0)) {
                        this.$element.focus();
                    }
                }
            });

            this.$element.on('click', () => {
                // noinspection JSUnresolvedReference
                this.$element.datepicker('show');
            });

            const options = {
                format: this.getDateTime().dateFormat.toLowerCase(),
                weekStart: this.getDateTime().weekStart,
                autoclose: true,
                todayHighlight: true,
                keyboardNavigation: true,
                assumeNearbyYear: true,
                todayBtn: this.getConfig().get('datepickerTodayButton') || false,
                orientation: 'bottom auto',
                templates: {
                    leftArrow: '<span class="fas fa-chevron-left fa-sm"></span>',
                    rightArrow: '<span class="fas fa-chevron-right fa-sm"></span>',
                },
                container: this.$el.closest('.modal-body').length ?
                    this.$el.closest('.modal-body') :
                    'body',
                startDate: this.getStartDateForDatePicker(),
            };

            const language = this.getConfig().get('language');

            // noinspection JSUnresolvedReference
            if (!(language in $.fn.datepicker.dates)) {
                // noinspection JSUnresolvedReference
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

            // noinspection JSUnresolvedReference
            this.$element.datepicker(options);

            if (this.mode === this.MODE_SEARCH) {
                this.$el.find('select.search-type').on('change', () => this.trigger('change'));
                this.$el.find('input.number').on('change', () => this.trigger('change'));

                // noinspection JSUnresolvedReference
                this.$el.find('.input-group.additional').datepicker(options);

                this.initDatePickerEventHandlers('input.filter-from');
                this.initDatePickerEventHandlers('input.filter-to');
            }

            this.$element.parent().find('button.date-picker-btn').on('click', () => {
                // noinspection JSUnresolvedReference
                this.$element.datepicker('show');
            });

            if (this.mode === this.MODE_SEARCH) {
                const $searchType = this.$el.find('select.search-type');

                this.handleSearchType($searchType.val());
            }
        }
    }

    /**
     * @private
     * @param {string} selector
     */
    initDatePickerEventHandlers(selector) {
        const $input = this.$el.find(selector);

        $input.on('change', /** Record */e => {
            this.trigger('change');

            if (e.isTrigger) {
                if (document.activeElement !== $input.get(0)) {
                    $input.focus();
                }
            }
        });

        $input.on('click', () => {
            // noinspection JSUnresolvedReference
            $input.datepicker('show');
        });
    }

    handleSearchType(type) {
        this.$el.find('div.primary').addClass('hidden');
        this.$el.find('div.additional').addClass('hidden');
        this.$el.find('div.additional-number').addClass('hidden');

        if (['on', 'notOn', 'after', 'before'].includes(type)) {
            this.$el.find('div.primary').removeClass('hidden');
        }
        else if (['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'].includes(type)) {
            this.$el.find('div.additional-number').removeClass('hidden');
        }
        else if (type === 'between') {
            this.$el.find('div.primary').addClass('hidden');
            this.$el.find('div.additional').removeClass('hidden');
        }
    }

    parseDate(string) {
        return this.getDateTime().fromDisplayDate(string);
    }

    /**
     * @param {string} string
     * @return {string|-1|null}
     */
    parse(string) {
        if (!string) {
            return null;
        }

        return this.parseDate(string);
    }

    /** @inheritDoc */
    fetch() {
        const data = {};

        data[this.name] = this.parse(this.$element.val());

        return data;
    }

    /** @inheritDoc */
    fetchSearch() {
        const type = this.fetchSearchType();

        if (type === 'between') {
            const valueFrom = this.parseDate(this.$el.find('input.filter-from').val());
            const valueTo = this.parseDate(this.$el.find('input.filter-to').val());

            if (!valueFrom || !valueTo) {
                return null;
            }

            return {
                type: type,
                value: [valueFrom, valueTo],
                data: {
                    value: valueFrom,
                    valueTo: valueTo,
                },
            };
        }

        let data;
        const value = this.parseDate(this.$element.val());

        if (['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'].includes(type)) {
            const number = this.$el.find('input.number').val();

            data = {
                type: type,
                value: number,
                date: true,
            };
        }
        else if (['on', 'notOn', 'after', 'before'].includes(type)) {
            if (!value) {
                return null;
            }

            data = {
                type: type,
                value: value,
                data: {
                    value: value,
                },
            };
        }
        else if (type === 'isEmpty') {
            data = {
                type: 'isNull',
                data: {
                    type: type,
                },
            };
        }
        else {
            data = {
                type: type,
                date: true,
            };
        }

        return data;
    }

    getSearchType() {
        return this.getSearchParamsData().type || this.searchParams.typeFront || this.searchParams.type;
    }

    validateRequired() {
        if (!this.isRequired()) {
            return;
        }

        if (this.model.get(this.name) === null) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validateDate() {
        if (this.model.get(this.name) === -1) {
            const msg = this.translate('fieldShouldBeDate', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validateAfter() {
        const field = this.params.after;

        if (!field) {
            return false;
        }

        const value = this.model.get(this.name);
        const otherValue = this.model.get(field);

        if (!(value && otherValue)) {
            return false;
        }

        const unix = moment(value).unix();
        const otherUnix = moment(otherValue).unix();

        if (this.params.afterOrEqual && unix === otherUnix) {
            return false;
        }

        if (unix <= otherUnix) {
            const msg = this.translate('fieldShouldAfter', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{otherField}', this.translate(field, 'fields', this.entityType));

            this.showValidationMessage(msg);

            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    validateBefore() {
        const field = this.params.before;

        if (!field) {
            return false;
        }

        const value = this.model.get(this.name);
        const otherValue = this.model.get(field);

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
}

export default DateFieldView;
