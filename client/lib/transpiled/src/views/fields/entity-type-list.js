define("views/fields/entity-type-list", ["exports", "views/fields/multi-enum"], function (_exports, _multiEnum) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _multiEnum = _interopRequireDefault(_multiEnum);
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

  class EntityTypeListFieldView extends _multiEnum.default {
    checkAvailability(entityType) {
      const defs = this.scopesMetadataDefs[entityType] || {};
      if (defs.entity && defs.object) {
        return true;
      }
    }
    setupOptions() {
      const scopes = this.scopesMetadataDefs = this.getMetadata().get('scopes');
      this.params.options = Object.keys(scopes).filter(scope => {
        if (this.checkAvailability(scope)) {
          return true;
        }
      }).sort((v1, v2) => {
        return this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames'));
      });
    }
    setup() {
      if (!this.params.translation) {
        this.params.translation = 'Global.scopeNames';
      }
      this.setupOptions();
      super.setup();
    }
  }
  var _default = _exports.default = EntityTypeListFieldView;
});
//# sourceMappingURL=entity-type-list.js.map ;