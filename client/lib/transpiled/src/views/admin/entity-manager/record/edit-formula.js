define("views/admin/entity-manager/record/edit-formula", ["exports", "views/record/base"], function (_exports, _base) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
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

  class EntityManagerEditFormulaRecordView extends _base.default {
    template = 'admin/entity-manager/record/edit-formula';
    data() {
      return {
        field: this.field,
        fieldKey: this.field + 'Field'
      };
    }
    setup() {
      super.setup();
      this.field = this.options.type;
      let additionalFunctionDataList = null;
      if (this.options.type === 'beforeSaveApiScript') {
        additionalFunctionDataList = this.getRecordServiceFunctionDataList();
      }
      this.createField(this.field, 'views/fields/formula', {
        targetEntityType: this.options.targetEntityType,
        height: 504
      }, 'edit', false, {
        additionalFunctionDataList: additionalFunctionDataList
      });
    }
    getRecordServiceFunctionDataList() {
      return [{
        name: 'recordService\\skipDuplicateCheck',
        insertText: 'recordService\\skipDuplicateCheck()',
        returnType: 'bool'
      }, {
        name: 'recordService\\throwDuplicateConflict',
        insertText: 'recordService\\throwDuplicateConflict(RECORD_ID)'
      }, {
        name: 'recordService\\throwBadRequest',
        insertText: 'recordService\\throwBadRequest(MESSAGE)'
      }, {
        name: 'recordService\\throwForbidden',
        insertText: 'recordService\\throwForbidden(MESSAGE)'
      }, {
        name: 'recordService\\throwConflict',
        insertText: 'recordService\\throwConflict(MESSAGE)'
      }];
    }
  }
  var _default = _exports.default = EntityManagerEditFormulaRecordView;
});
//# sourceMappingURL=edit-formula.js.map ;