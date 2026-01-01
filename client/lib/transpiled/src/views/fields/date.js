define("views/fields/date", ["exports", "views/fields/base", "moment", "ui/datepicker"], function (_exports, _base, _moment, _datepicker) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
  _moment = _interopRequireDefault(_moment);
  _datepicker = _interopRequireDefault(_datepicker);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /**
   * A date field.
   *
   * @extends BaseFieldView<module:views/fields/date~params>
   */
  class DateFieldView extends _base.default {
    /**
     * @typedef {Object} module:views/fields/date~options
     * @property {
     *     module:views/fields/date~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     * @property {string} [otherFieldLabelText] A label text of other field. Used in before/after validations.
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
    type = 'date';
    listTemplate = 'fields/date/list';
    listLinkTemplate = 'fields/date/list-link';
    detailTemplate = 'fields/date/detail';
    editTemplate = 'fields/date/edit';
    searchTemplate = 'fields/date/search';

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = ['required', 'date', 'after', 'before'];

    /**
     * @protected
     * @type {string[]}
     */
    searchTypeList = ['lastSevenDays', 'ever', 'isEmpty', 'currentMonth', 'lastMonth', 'nextMonth', 'currentQuarter', 'lastQuarter', 'currentYear', 'lastYear', 'today', 'past', 'future', 'lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays', 'on', 'after', 'before', 'between'];

    /**
     * @protected
     * @type {string[]}
     */
    searchWithPrimaryTypeList = ['on', 'notOn', 'after', 'before'];

    /**
     * @protected
     * @type {string[]}
     */
    searchWithRangeTypeList = ['between'];

    /**
     * @protected
     * @type {string[]}
     */
    searchWithAdditionalNumberTypeList = ['lastXDays', 'nextXDays', 'olderThanXDays', 'afterXDays'];

    /**
     * @inheritDoc
     */
    initialSearchIsNotIdle = true;

    /**
     * @private
     * @type {import('ui/datepicker').default}
     */
    datepicker;

    /**
     * @protected
     * @type {boolean}
     */
    useNumericFormat;
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

          // Timeout prevents the picker popping one when the duration field adjusts the date end.
          setTimeout(() => {
            this.onAfterChange();
            this.datepicker.setStartDate(this.getStartDateForDatePicker());
          }, 100);
        });
      }
      this.useNumericFormat = this.getConfig().get('readableDateFormatDisabled') || this.params.useNumericFormat;
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
        if (this.searchWithAdditionalNumberTypeList.includes(this.getSearchType())) {
          data.number = this.searchParams.value;
        }
      }
      if (this.isListMode()) {
        data.titleDateValue = data.dateValue;
      }
      if (this.useNumericFormat) {
        data.useNumericFormat = true;
      }

      // noinspection JSValidateTypes
      return data;
    }
    setupSearch() {
      this.addHandler('change', 'select.search-type', (e, /** HTMLSelectElement */target) => {
        this.handleSearchType(target.value);
        this.trigger('change');
      });
      this.addHandler('change', 'input.number', () => this.trigger('change'));
    }
    stringifyDateValue(value) {
      if (!value) {
        if (this.mode === this.MODE_EDIT || this.mode === this.MODE_SEARCH || this.mode === this.MODE_LIST || this.mode === this.MODE_LIST_LINK) {
          return '';
        }
        return null;
      }
      if (this.mode === this.MODE_LIST || this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST_LINK) {
        return this.convertDateValueForDetail(value);
      }
      return this.getDateTime().toDisplayDate(value);
    }
    convertDateValueForDetail(value) {
      if (this.useNumericFormat) {
        return this.getDateTime().toDisplayDate(value);
      }
      const timezone = this.getDateTime().getTimeZone();
      const internalDateTimeFormat = this.getDateTime().internalDateTimeFormat;
      const readableFormat = this.getDateTime().getReadableDateFormat();
      const valueWithTime = value + ' 00:00:00';
      const today = _moment.default.tz(timezone).startOf('day');
      let dateTime = _moment.default.tz(valueWithTime, internalDateTimeFormat, timezone);
      const temp = today.clone();
      const ranges = {
        'today': [temp.unix(), temp.add(1, 'days').unix()],
        'tomorrow': [temp.unix(), temp.add(1, 'days').unix()],
        'yesterday': [temp.add(-3, 'days').unix(), temp.add(1, 'days').unix()]
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
      dateTime = _moment.default.utc(valueWithTime, internalDateTimeFormat);
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
        [date] = date.split(' ');
        return date;
      }
      return this.getDateTime().toDisplayDate(date);
    }
    afterRender() {
      if (this.isEditMode() || this.isSearchMode()) {
        var _this$element, _this$mainInputElemen;
        this.mainInputElement = (_this$element = this.element) === null || _this$element === void 0 ? void 0 : _this$element.querySelector(`[data-name="${this.name}"]`);
        this.$element = $(this.mainInputElement);
        const options = {
          format: this.getDateTime().dateFormat,
          weekStart: this.getDateTime().weekStart,
          startDate: this.getStartDateForDatePicker(),
          todayButton: this.getConfig().get('datepickerTodayButton') || false
        };
        this.datepicker = undefined;
        if (this.mainInputElement instanceof HTMLInputElement) {
          this.datepicker = new _datepicker.default(this.mainInputElement, {
            ...options,
            onChange: () => this.trigger('change')
          });
        }
        if (this.isSearchMode()) {
          var _this$element2;
          const additionalGroup = (_this$element2 = this.element) === null || _this$element2 === void 0 ? void 0 : _this$element2.querySelector('.input-group.additional');
          if (additionalGroup) {
            new _datepicker.default(additionalGroup, options);
            this.initDatePickerEventHandlers('input.filter-from');
            this.initDatePickerEventHandlers('input.filter-to');
          }
        }
        const button = (_this$mainInputElemen = this.mainInputElement) === null || _this$mainInputElemen === void 0 ? void 0 : _this$mainInputElemen.parentNode.querySelector('button.date-picker-btn');
        if (button instanceof HTMLElement) {
          button.addEventListener('click', () => this.datepicker.show());
        }
        if (this.isSearchMode()) {
          const type = this.fetchSearchType();
          this.handleSearchType(type);
        }
      }
    }

    /**
     * @private
     * @param {string} selector
     */
    initDatePickerEventHandlers(selector) {
      var _this$element3;
      const input = (_this$element3 = this.element) === null || _this$element3 === void 0 ? void 0 : _this$element3.querySelector(selector);
      if (!(input instanceof HTMLInputElement)) {
        return;
      }
      $(input).on('change', /** Record */e => {
        this.trigger('change');
        if (e.isTrigger) {
          if (document.activeElement !== input) {
            input.focus({
              preventScroll: true
            });
          }
        }
      });
    }

    /**
     * @protected
     * @param {string} type
     */
    handleSearchType(type) {
      var _this$element4, _this$element5, _this$element6;
      const primary = (_this$element4 = this.element) === null || _this$element4 === void 0 ? void 0 : _this$element4.querySelector('div.primary');
      const additional = (_this$element5 = this.element) === null || _this$element5 === void 0 ? void 0 : _this$element5.querySelector('div.additional');
      const additionalNumber = (_this$element6 = this.element) === null || _this$element6 === void 0 ? void 0 : _this$element6.querySelector('div.additional-number');
      primary === null || primary === void 0 || primary.classList.add('hidden');
      additional === null || additional === void 0 || additional.classList.add('hidden');
      additionalNumber === null || additionalNumber === void 0 || additionalNumber.classList.add('hidden');
      if (this.searchWithPrimaryTypeList.includes(type)) {
        primary === null || primary === void 0 || primary.classList.remove('hidden');
        return;
      }
      if (this.searchWithAdditionalNumberTypeList.includes(type)) {
        additionalNumber === null || additionalNumber === void 0 || additionalNumber.classList.remove('hidden');
        return;
      }
      if (this.searchWithRangeTypeList.includes(type)) {
        additional === null || additional === void 0 || additional.classList.remove('hidden');
      }
    }

    /**
     * @protected
     * @param {string} string
     * @return {string|-1}
     */
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

    /**
     * @inheritDoc
     */
    fetch() {
      var _this$mainInputElemen2;
      const data = {};
      data[this.name] = this.parse(((_this$mainInputElemen2 = this.mainInputElement) === null || _this$mainInputElemen2 === void 0 ? void 0 : _this$mainInputElemen2.value) ?? '');
      return data;
    }

    /**
     * @inheritDoc
     */
    fetchSearch() {
      const type = this.fetchSearchType();
      if (this.searchWithRangeTypeList.includes(type)) {
        var _this$element7, _this$element8;
        const inputFrom = (_this$element7 = this.element) === null || _this$element7 === void 0 ? void 0 : _this$element7.querySelector('input.filter-from');
        const inputTo = (_this$element8 = this.element) === null || _this$element8 === void 0 ? void 0 : _this$element8.querySelector('input.filter-to');
        const valueFrom = inputFrom instanceof HTMLInputElement ? this.parseDate(inputFrom.value) : undefined;
        const valueTo = inputTo instanceof HTMLInputElement ? this.parseDate(inputTo.value) : undefined;
        if (!valueFrom || !valueTo) {
          return null;
        }
        return {
          type: type,
          value: [valueFrom, valueTo],
          data: {
            value: valueFrom,
            valueTo: valueTo
          }
        };
      }
      if (this.searchWithAdditionalNumberTypeList.includes(type)) {
        var _this$element9;
        const input = (_this$element9 = this.element) === null || _this$element9 === void 0 ? void 0 : _this$element9.querySelector('input.number');
        const number = input instanceof HTMLInputElement ? input.value : undefined;
        return {
          type: type,
          value: number,
          date: true
        };
      }
      if (this.searchWithPrimaryTypeList.includes(type)) {
        var _this$element0;
        const input = (_this$element0 = this.element) === null || _this$element0 === void 0 ? void 0 : _this$element0.querySelector(`[data-name="${this.name}"]`);
        const value = input instanceof HTMLInputElement ? this.parseDate(input.value) : undefined;
        if (!value) {
          return null;
        }
        return {
          type: type,
          value: value,
          data: {
            value: value
          }
        };
      }
      if (type === 'isEmpty') {
        return {
          type: 'isNull',
          data: {
            type: type
          }
        };
      }
      return {
        type: type,
        date: true
      };
    }
    getSearchType() {
      return this.getSearchParamsData().type || this.searchParams.typeFront || this.searchParams.type;
    }
    validateRequired() {
      if (!this.isRequired()) {
        return;
      }
      if (this.model.get(this.name) === null) {
        const msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
        this.showValidationMessage(msg);
        return true;
      }
    }

    // noinspection JSUnusedGlobalSymbols
    validateDate() {
      if (this.model.get(this.name) === -1) {
        const msg = this.translate('fieldShouldBeDate', 'messages').replace('{field}', this.getLabelText());
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
      const unix = (0, _moment.default)(value).unix();
      const otherUnix = (0, _moment.default)(otherValue).unix();
      if (this.params.afterOrEqual && unix === otherUnix) {
        return false;
      }
      if (unix <= otherUnix) {
        const otherFieldLabelText = this.options.otherFieldLabelText || this.translate(field, 'fields', this.entityType);
        const msg = this.translate('fieldShouldAfter', 'messages').replace('{field}', this.getLabelText()).replace('{otherField}', otherFieldLabelText);
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
      if ((0, _moment.default)(value).unix() >= (0, _moment.default)(otherValue).unix()) {
        const msg = this.translate('fieldShouldBefore', 'messages').replace('{field}', this.getLabelText()).replace('{otherField}', this.translate(field, 'fields', this.entityType));
        this.showValidationMessage(msg);
        return true;
      }
    }

    /**
     * @protected
     * @since 9.2.0
     */
    onAfterChange() {
      /** @type {string} */
      const from = this.model.attributes[this.params.after];
      /** @type {string} */
      const currentValue = this.model.attributes[this.name];
      if (!from || !currentValue || from.length !== currentValue.length) {
        return;
      }
      if (this.getDateTime().toMomentDate(currentValue).isBefore(this.getDateTime().toMomentDate(from))) {
        this.model.set(this.name, from);
      }
    }
  }
  var _default = _exports.default = DateFieldView;
});
//# sourceMappingURL=date.js.map ;