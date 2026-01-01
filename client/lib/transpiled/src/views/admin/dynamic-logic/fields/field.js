define("views/admin/dynamic-logic/fields/field", ["exports", "views/fields/multi-enum", "ui/multi-select"], function (_exports, _multiEnum, _multiSelect) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _multiEnum = _interopRequireDefault(_multiEnum);
  _multiSelect = _interopRequireDefault(_multiSelect);
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

  class _default extends _multiEnum.default {
    getFieldList() {
      /** @type {Record<string, Record>} */
      const fields = this.getMetadata().get(`entityDefs.${this.options.scope}.fields`);
      const filterList = Object.keys(fields).filter(field => {
        const fieldType = fields[field].type || null;
        if (fields[field].disabled || fields[field].utility) {
          return;
        }
        if (!fieldType) {
          return;
        }
        if (!this.getMetadata().get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType])) {
          return;
        }
        return true;
      });
      filterList.push('id');
      filterList.sort((v1, v2) => {
        return this.translate(v1, 'fields', this.options.scope).localeCompare(this.translate(v2, 'fields', this.options.scope));
      });
      return filterList;
    }
    setupTranslatedOptions() {
      this.translatedOptions = {};
      this.params.options.forEach(item => {
        this.translatedOptions[item] = this.translate(item, 'fields', this.options.scope);
      });
    }
    setupOptions() {
      super.setupOptions();
      this.params.options = this.getFieldList();
      this.setupTranslatedOptions();
    }
    afterRender() {
      super.afterRender();
      if (this.$element) {
        _multiSelect.default.focus(this.$element);
      }
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=field.js.map ;