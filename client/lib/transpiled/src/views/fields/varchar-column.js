define("views/fields/varchar-column", ["exports", "views/fields/varchar"], function (_exports, _varchar) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _varchar = _interopRequireDefault(_varchar);
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

  class VarcharColumnFieldView extends _varchar.default {
    searchTypeList = ['startsWith', 'contains', 'equals', 'endsWith', 'like', 'isEmpty', 'isNotEmpty'];
    fetchSearch() {
      const type = this.fetchSearchType() || 'startsWith';
      if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
        if (type === 'isEmpty') {
          return {
            typeFront: type,
            where: {
              type: 'or',
              value: [{
                type: 'columnIsNull',
                field: this.name
              }, {
                type: 'columnEquals',
                field: this.name,
                value: ''
              }]
            }
          };
        }
        return {
          typeFront: type,
          where: {
            type: 'and',
            value: [{
              type: 'columnNotEquals',
              field: this.name,
              value: ''
            }, {
              type: 'columnIsNotNull',
              field: this.name,
              value: null
            }]
          }
        };
      }
      let value = this.$element.val().toString().trim();
      value = value.trim();
      if (value) {
        return {
          value: value,
          type: 'column'.Espo.Utils.upperCaseFirst(type),
          data: {
            type: type,
            value: value
          }
        };
      }
      return null;
    }
  }
  var _default = _exports.default = VarcharColumnFieldView;
});
//# sourceMappingURL=varchar-column.js.map ;