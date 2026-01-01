define("views/admin/user-interface", ["exports", "views/settings/record/edit"], function (_exports, _edit) {
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

  class _default extends _edit.default {
    layoutName = 'userInterface';
    saveAndContinueEditingAction = false;
    setup() {
      super.setup();
      this.controlColorsField();
      this.listenTo(this.model, 'change:scopeColorsDisabled', () => this.controlColorsField());
      this.on('save', initialAttributes => {
        if (this.model.get('theme') !== initialAttributes.theme || (this.model.get('themeParams').navbar || {}) !== initialAttributes.themeParams.navbar) {
          this.setConfirmLeaveOut(false);
          window.location.reload();
        }
      });
    }
    controlColorsField() {
      if (this.model.get('scopeColorsDisabled')) {
        this.hideField('tabColorsDisabled');
      } else {
        this.showField('tabColorsDisabled');
      }
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=user-interface.js.map ;