define("views/layout-set/fields/layout-list", ["exports", "views/fields/multi-enum", "views/admin/layouts/index"], function (_exports, _multiEnum, _index) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _multiEnum = _interopRequireDefault(_multiEnum);
  _index = _interopRequireDefault(_index);
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
    typeList = ['list', 'detail', 'listSmall', 'detailSmall', 'bottomPanelsDetail', 'filters', 'massUpdate', 'sidePanelsDetail', 'sidePanelsEdit', 'sidePanelsDetailSmall', 'sidePanelsEditSmall', 'defaultSidePanel'];
    setupOptions() {
      this.params.options = [];
      this.translatedOptions = {};
      this.scopeList = Object.keys(this.getMetadata().get('scopes')).filter(item => this.getMetadata().get(['scopes', item, 'layouts'])).sort((v1, v2) => {
        return this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames'));
      });
      const dataList = _index.default.prototype.getLayoutScopeDataList.call(this);
      dataList.forEach(item1 => {
        item1.typeList.forEach(type => {
          const item = item1.scope + '.' + type;
          if (type.substr(-6) === 'Portal') {
            return;
          }
          this.params.options.push(item);
          this.translatedOptions[item] = this.translate(item1.scope, 'scopeNames') + ' . ' + this.translate(type, 'layouts', 'Admin');
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    translateLayoutName(type, scope) {
      return _index.default.prototype.translateLayoutName.call(this, type, scope);
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=layout-list.js.map ;