define("views/fields/range-currency", ["exports", "views/fields/range-float", "views/fields/currency", "ui/select"], function (_exports, _rangeFloat, _currency, _select) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _rangeFloat = _interopRequireDefault(_rangeFloat);
  _currency = _interopRequireDefault(_currency);
  _select = _interopRequireDefault(_select);
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

  class RangeCurrencyFieldView extends _rangeFloat.default {
    type = 'rangeCurrency';
    editTemplate = 'fields/range-currency/edit';
    data() {
      return {
        currencyField: this.currencyField,
        currencyValue: this.model.get(this.fromCurrencyField) || this.getPreferences().get('defaultCurrency') || this.getConfig().get('defaultCurrency'),
        currencyList: this.currencyList,
        ...super.data()
      };
    }
    setup() {
      super.setup();
      const ucName = Espo.Utils.upperCaseFirst(this.name);
      this.fromCurrencyField = 'from' + ucName + 'Currency';
      this.toCurrencyField = 'to' + ucName + 'Currency';
      this.currencyField = this.name + 'Currency';
      this.currencyList = this.getConfig().get('currencyList') || ['USD'];
      this.decimalPlaces = this.getConfig().get('currencyDecimalPlaces');
    }
    setupAutoNumericOptions() {
      this.autoNumericOptions = {
        digitGroupSeparator: this.thousandSeparator || '',
        decimalCharacter: this.decimalMark,
        modifyValueOnWheel: false,
        selectOnFocus: false,
        decimalPlaces: this.decimalPlaces,
        allowDecimalPadding: true,
        showWarnings: false,
        formulaMode: true
      };
      if (this.decimalPlaces === null) {
        this.autoNumericOptions.decimalPlaces = this.decimalPlacesRawValue;
        this.autoNumericOptions.decimalPlacesRawValue = this.decimalPlacesRawValue;
        this.autoNumericOptions.allowDecimalPadding = false;
      }
    }
    afterRender() {
      super.afterRender();
      if (this.mode === this.MODE_EDIT) {
        this.$currency = this.$el.find('[data-name="' + this.currencyField + '"]');
        _select.default.init(this.$currency);
      }
    }
    formatNumber(value) {
      return _currency.default.prototype.formatNumberDetail.call(this, value);
    }
    getValueForDisplay() {
      let fromValue = this.model.get(this.fromField);
      let toValue = this.model.get(this.toField);
      fromValue = isNaN(fromValue) ? null : fromValue;
      toValue = isNaN(toValue) ? null : toValue;
      const currencyValue = this.model.get(this.fromCurrencyField) || this.model.get(this.toCurrencyField);
      const symbol = this.getMetadata().get(['app', 'currency', 'symbolMap', currencyValue]) || currencyValue;
      if (fromValue !== null && toValue !== null) {
        return this.formatNumber(fromValue) + ' &#8211 ' + this.formatNumber(toValue) + ' ' + symbol + '';
      }
      if (fromValue) {
        return '&#62;&#61; ' + this.formatNumber(fromValue) + ' ' + symbol + '';
      }
      if (toValue) {
        return '&#60;&#61; ' + this.formatNumber(toValue) + ' ' + symbol + '';
      }
      return this.translate('None');
    }
    fetch() {
      const data = super.fetch();
      const currencyValue = this.$currency.val();
      if (data[this.fromField] !== null) {
        data[this.fromCurrencyField] = currencyValue;
      } else {
        data[this.fromCurrencyField] = null;
      }
      if (data[this.toField] !== null) {
        data[this.toCurrencyField] = currencyValue;
      } else {
        data[this.toCurrencyField] = null;
      }
      return data;
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = RangeCurrencyFieldView;
});
//# sourceMappingURL=range-currency.js.map ;