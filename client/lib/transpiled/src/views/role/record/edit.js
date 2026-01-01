define("views/role/record/edit", ["exports", "views/record/edit"], function (_exports, _edit) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
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

  class RoleEditRecordView extends _edit.default {
    tableView = 'views/role/record/table';
    sideView = false;
    isWide = true;
    stickButtonsContainerAllTheWay = true;
    fetch() {
      const data = super.fetch();
      data['data'] = this.getTableView().fetchScopeData();
      data['fieldData'] = this.getTableView().fetchFieldData();
      return data;
    }
    setup() {
      super.setup();
      this.createView('extra', this.tableView, {
        mode: 'edit',
        selector: '.extra',
        model: this.model
      }, view => {
        this.listenTo(view, 'change', () => {
          const data = this.fetch();
          this.model.set(data);
        });
      });
    }

    /**
     * @return {import('./table').default}
     */
    getTableView() {
      return this.getView('extra');
    }
  }
  var _default = _exports.default = RoleEditRecordView;
});
//# sourceMappingURL=edit.js.map ;