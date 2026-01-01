define("views/fields/range-float", ["exports", "views/fields/range-int", "views/fields/float"], function (_exports, _rangeInt, _float) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _rangeInt = _interopRequireDefault(_rangeInt);
  _float = _interopRequireDefault(_float);
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

  class RangeFloatFieldView extends _rangeInt.default {
    type = 'rangeFloat';
    validations = ['required', 'float', 'range', 'order'];
    decimalPlacesRawValue = 10;
    setupAutoNumericOptions() {
      this.autoNumericOptions = {
        digitGroupSeparator: this.thousandSeparator || '',
        decimalCharacter: this.decimalMark,
        modifyValueOnWheel: false,
        selectOnFocus: false,
        decimalPlaces: this.decimalPlacesRawValue,
        decimalPlacesRawValue: this.decimalPlacesRawValue,
        allowDecimalPadding: false,
        showWarnings: false,
        formulaMode: true
      };
    }

    // noinspection JSUnusedGlobalSymbols
    validateFloat() {
      const validate = name => {
        if (isNaN(this.model.get(name))) {
          const msg = this.translate('fieldShouldBeFloat', 'messages').replace('{field}', this.getLabelText());
          this.showValidationMessage(msg, '[data-name="' + name + '"]');
          return true;
        }
      };
      let result = false;
      result = validate(this.fromField) || result;
      result = validate(this.toField) || result;
      return result;
    }
    parse(value) {
      return _float.default.prototype.parse.call(this, value);
    }
    formatNumber(value) {
      return _float.default.prototype.formatNumberDetail.call(this, value);
    }
  }
  var _default = _exports.default = RangeFloatFieldView;
});
//# sourceMappingURL=range-float.js.map ;