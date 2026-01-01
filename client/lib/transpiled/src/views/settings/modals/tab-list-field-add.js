define("views/settings/modals/tab-list-field-add", ["exports", "views/modals/array-field-add"], function (_exports, _arrayFieldAdd) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _arrayFieldAdd = _interopRequireDefault(_arrayFieldAdd);
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

  class TabListFieldAddSettingsModalView extends _arrayFieldAdd.default {
    setup() {
      super.setup();
      if (!this.options.noGroups) {
        this.buttonList.push({
          name: 'addGroup',
          text: this.translate('Group Tab', 'labels', 'Settings'),
          onClick: () => this.actionAddGroup(),
          position: 'right',
          iconClass: 'fas fa-plus fa-sm'
        });
      }
      this.buttonList.push({
        name: 'addDivider',
        text: this.translate('Divider', 'labels', 'Settings'),
        onClick: () => this.actionAddDivider(),
        position: 'right',
        iconClass: 'fas fa-plus fa-sm'
      });
      this.addButton({
        name: 'addUrl',
        text: this.translate('URL', 'labels', 'Settings'),
        onClick: () => this.actionAddUrl(),
        position: 'right',
        iconClass: 'fas fa-plus fa-sm'
      });
    }
    actionAddGroup() {
      this.trigger('add', {
        type: 'group',
        text: this.translate('Group Tab', 'labels', 'Settings'),
        iconClass: null,
        color: null
      });
    }
    actionAddDivider() {
      this.trigger('add', {
        type: 'divider',
        text: null
      });
    }
    actionAddUrl() {
      this.trigger('add', {
        type: 'url',
        text: this.translate('URL', 'labels', 'Settings'),
        url: null,
        iconClass: null,
        color: null,
        aclScope: null,
        onlyAdmin: false
      });
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = TabListFieldAddSettingsModalView;
});
//# sourceMappingURL=tab-list-field-add.js.map ;