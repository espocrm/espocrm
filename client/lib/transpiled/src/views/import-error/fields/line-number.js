define("views/import-error/fields/line-number", ["exports", "views/fields/int"], function (_exports, _int) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _int = _interopRequireDefault(_int);
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

  class _default extends _int.default {
    disableFormatting = true;
    data() {
      const data = super.data();
      data.valueIsSet = this.model.has(this.sourceName);
      data.isNotEmpty = this.model.has(this.sourceName);
      return data;
    }
    setup() {
      super.setup();
      this.sourceName = this.name === 'exportLineNumber' ? 'exportRowIndex' : 'rowIndex';
    }
    getAttributeList() {
      return [this.sourceName];
    }
    getValueForDisplay() {
      let value = this.model.get(this.sourceName);
      value++;
      return this.formatNumber(value);
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=line-number.js.map ;