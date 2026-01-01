define("views/admin/formula/fields/attribute", ["exports", "views/fields/multi-enum", "ui/multi-select"], function (_exports, _multiEnum, _multiSelect) {
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

  class FormulaAttributeFieldView extends _multiEnum.default {
    setupOptions() {
      super.setupOptions();
      if (this.options.attributeList) {
        this.params.options = this.options.attributeList;
        return;
      }
      const attributeList = this.getFieldManager().getEntityTypeAttributeList(this.options.scope).concat(['id']).sort();
      const links = this.getMetadata().get(['entityDefs', this.options.scope, 'links']) || {};
      const linkList = [];
      Object.keys(links).forEach(link => {
        const type = links[link].type;
        const scope = links[link].entity;
        if (!type) {
          return;
        }
        if (!scope) {
          return;
        }
        if (links[link].disabled || links[link].utility) {
          return;
        }
        if (~['belongsToParent', 'hasOne', 'belongsTo'].indexOf(type)) {
          linkList.push(link);
        }
      });
      linkList.sort();
      linkList.forEach(link => {
        const scope = links[link].entity;
        const linkAttributeList = this.getFieldManager().getEntityTypeAttributeList(scope).sort();
        linkAttributeList.forEach(item => {
          attributeList.push(link + '.' + item);
        });
      });
      this.params.options = attributeList;
    }
    afterRender() {
      super.afterRender();
      if (this.$element) {
        _multiSelect.default.focus(this.$element);
      }
    }
  }
  _exports.default = FormulaAttributeFieldView;
});
//# sourceMappingURL=attribute.js.map ;