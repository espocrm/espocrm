define("views/admin/field-manager/fields/link-multiple/default", ["exports", "views/fields/link-multiple"], function (_exports, _linkMultiple) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _linkMultiple = _interopRequireDefault(_linkMultiple);
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

  class _default extends _linkMultiple.default {
    data() {
      const defaultAttributes = this.model.get('defaultAttributes') || {};
      const nameHash = defaultAttributes[this.options.field + 'Names'] || {};
      const idValues = defaultAttributes[this.options.field + 'Ids'] || [];
      const data = super.data();
      data.nameHash = nameHash;
      data.idValues = idValues;
      return data;
    }
    setup() {
      super.setup();
      const entityType = this.options.scope;
      const field = this.options.field;
      this.foreignScope = this.getMetadata().get(['entityDefs', entityType, 'links', field, 'entity']) ?? this.getMetadata().get(`entityDefs.${entityType}.fields.${field}.entity`);
    }
    fetch() {
      const data = super.fetch();
      let defaultAttributes = {};
      defaultAttributes[this.options.field + 'Ids'] = data[this.idsName];
      defaultAttributes[this.options.field + 'Names'] = data[this.nameHashName];
      if (data[this.idsName] === null || data[this.idsName].length === 0) {
        defaultAttributes = null;
      }
      return {
        defaultAttributes: defaultAttributes
      };
    }
    copyValuesFromModel() {
      const defaultAttributes = this.model.get('defaultAttributes') || {};
      const idValues = defaultAttributes[this.options.field + 'Ids'] || [];
      const nameHash = defaultAttributes[this.options.field + 'Names'] || {};
      this.ids = idValues;
      this.nameHash = nameHash;
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=default.js.map ;