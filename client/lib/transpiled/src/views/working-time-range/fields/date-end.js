define("views/working-time-range/fields/date-end", ["exports", "views/fields/date", "moment"], function (_exports, _date, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _date = _interopRequireDefault(_date);
  _moment = _interopRequireDefault(_moment);
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

  class _default extends _date.default {
    setup() {
      super.setup();
      this.validations.push(() => this.validateAfterOrSame());
    }
    validateAfterOrSame() {
      const field = 'dateStart';
      const value = this.model.get(this.name);
      const otherValue = this.model.get(field);
      if (value && otherValue) {
        if ((0, _moment.default)(value).unix() < (0, _moment.default)(otherValue).unix()) {
          const msg = this.translate('fieldShouldAfter', 'messages').replace('{field}', this.getLabelText()).replace('{otherField}', this.translate(field, 'fields', this.model.entityType));
          this.showValidationMessage(msg);
          return true;
        }
      }
      return false;
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=date-end.js.map ;