define("views/admin/system-requirements/index", ["exports", "view", "ajax"], function (_exports, _view, _ajax) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _ajax = _interopRequireDefault(_ajax);
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

  class _default extends _view.default {
    template = 'admin/system-requirements/index';

    /** @type {Record} */
    requirements;

    /**
     * @private
     * @type {boolean}
     */
    noAccess = false;
    data() {
      return {
        phpRequirementList: this.requirements.php,
        databaseRequirementList: this.requirements.database,
        permissionRequirementList: this.requirements.permission,
        noAccess: this.noAccess
      };
    }
    setup() {
      this.requirements = {};
      this.wait(this.loadData());
    }
    async loadData() {
      Espo.Ui.notifyWait();
      try {
        this.requirements = await Espo.Ajax.getRequest('Admin/action/systemRequirementList');
      } catch (e) {
        this.noAccess = true;
        if ('errorIsHandled' in e) {
          e.errorIsHandled = true;
        }
      }
      Espo.Ui.notify();
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=index.js.map ;