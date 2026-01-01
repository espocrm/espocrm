define("views/admin/field-manager/fields/foreign/link", ["exports", "views/fields/enum"], function (_exports, _enum) {
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
      if (!this.model.isNew()) {
        this.wait(this.setReadOnly(true));
      }
    }
    setupOptions() {
      /** @type {Record<string, Record>} */
      const links = this.getMetadata().get(['entityDefs', this.options.scope, 'links']) || {};
      this.params.options = Object.keys(Espo.Utils.clone(links)).filter(item => {
        if (links[item].type !== 'belongsTo' && links[item].type !== 'hasOne') {
          return;
        }
        if (links[item].noJoin) {
          return;
        }
        if (links[item].disabled) {
          return;
        }
        if (links[item].utility) {
          return;
        }
        return true;
      });
      const scope = this.options.scope;
      this.translatedOptions = {};
      this.params.options.forEach(item => {
        this.translatedOptions[item] = this.translate(item, 'links', scope);
      });
      this.params.options = this.params.options.sort((v1, v2) => {
        return this.translate(v1, 'links', scope).localeCompare(this.translate(v2, 'links', scope));
      });
      this.params.options.unshift('');
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=link.js.map ;