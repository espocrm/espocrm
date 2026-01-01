define("views/record/panel-actions", ["exports", "view"], function (_exports, _view) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
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

  class PanelActionsView extends _view.default {
    template = 'record/panel-actions';
    data() {
      return {
        defs: this.options.defs,
        buttonList: this.getButtonList(),
        actionList: this.getActionList(),
        entityType: this.options.entityType,
        scope: this.options.scope
      };
    }
    setup() {
      this.buttonList = this.options.defs.buttonList || [];
      this.actionList = this.options.defs.actionList || [];
      this.defs = this.options.defs;
    }
    getButtonList() {
      const list = [];
      this.buttonList.forEach(item => {
        if (item.hidden) {
          return;
        }
        list.push(item);
      });
      return list;
    }
    getActionList() {
      return this.actionList.filter(item => !item.hidden).map(item => {
        item = Espo.Utils.clone(item);
        if (item.action) {
          item.data = Espo.Utils.clone(item.data || {});
          item.data.panel = this.options.defs.name;
        }
        return item;
      });
    }
  }
  var _default = _exports.default = PanelActionsView;
});
//# sourceMappingURL=panel-actions.js.map ;