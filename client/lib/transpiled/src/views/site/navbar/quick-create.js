define("views/site/navbar/quick-create", ["exports", "view", "helpers/record-modal"], function (_exports, _view, _recordModal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _recordModal = _interopRequireDefault(_recordModal);
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

  class QuickCreateNavbarView extends _view.default {
    templateContent = `
        <a
            id="nav-quick-create-dropdown"
            class="dropdown-toggle"
            data-toggle="dropdown"
            role="button"
            tabindex="0"
            title="{{translate 'Create'}}"
        ><i class="fas fa-plus icon"></i></a>
        <ul class="dropdown-menu" role="menu" aria-labelledby="nav-quick-create-dropdown">
            <li class="dropdown-header">{{translate 'Create'}}</li>
            {{#each list}}
                <li><a
                    href="#{{./this}}/create"
                    data-name="{{./this}}"
                    data-action="quickCreate"
                >{{translate this category='scopeNames'}}</a></li>
            {{/each}}
        </ul>
    `;
    data() {
      return {
        list: this.list
      };
    }
    setup() {
      this.addActionHandler('quickCreate', (e, element) => {
        e.preventDefault();
        this.processCreate(element.dataset.name);
      });
      const scopes = this.getMetadata().get('scopes') || {};

      /** @type {string[]} */
      const list = this.getConfig().get('quickCreateList') || [];
      this.list = list.filter(scope => {
        if (!scopes[scope]) {
          return false;
        }
        if ((scopes[scope] || {}).disabled) {
          return;
        }
        if ((scopes[scope] || {}).acl) {
          return this.getAcl().check(scope, 'create');
        }
        return true;
      });
    }
    isAvailable() {
      return this.list.length > 0;
    }

    /**
     * @private
     * @param {string} scope
     */
    async processCreate(scope) {
      Espo.Ui.notifyWait();
      const type = this.getMetadata().get(`clientDefs.${scope}.quickCreateModalType`);
      if (type) {
        const viewName = this.getMetadata().get(`clientDefs.${scope}.modalViews.${type}`);
        if (viewName) {
          const view = await this.createView('modal', viewName, {
            scope: scope
          });
          await view.render();
          Espo.Ui.notify();
          return;
        }
      }
      const helper = new _recordModal.default();
      await helper.showCreate(this, {
        entityType: scope
      });
    }
  }
  var _default = _exports.default = QuickCreateNavbarView;
});
//# sourceMappingURL=quick-create.js.map ;