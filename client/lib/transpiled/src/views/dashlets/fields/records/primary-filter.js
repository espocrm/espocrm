define("views/dashlets/fields/records/primary-filter", ["exports", "views/fields/enum"], function (_exports, _enum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _enum = _interopRequireDefault(_enum);
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

  class _default extends _enum.default {
    setup() {
      super.setup();
      this.listenTo(this.model, 'change:entityType', () => {
        this.setupOptions();
        this.reRender();
      });
    }
    setupOptions() {
      const entityType = this.model.get('entityType');
      if (!entityType) {
        this.params.options = [];
        return;
      }
      const filterList = this.getMetadata().get(['clientDefs', entityType, 'filterList']) || [];
      this.params.options = [];
      filterList.forEach(item => {
        if (typeof item === 'object' && item.name) {
          if (item.accessDataList && !Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser(), null, true)) {
            return false;
          }
          this.params.options.push(item.name);
          return;
        }
        this.params.options.push(item);
      });
      this.params.options.unshift('all');
      if (this.getMetadata().get(`scopes.${entityType}.stars`)) {
        this.params.options.push('starred');
      }
      this.translatedOptions = {};
      this.params.options.forEach(item => {
        this.translatedOptions[item] = this.translate(item, 'presetFilters', entityType);
      });
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=primary-filter.js.map ;