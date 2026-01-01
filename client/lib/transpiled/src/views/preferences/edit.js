define("views/preferences/edit", ["exports", "views/edit"], function (_exports, _edit) {
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
    userName = '';
    setup() {
      super.setup();
      this.userName = this.model.get('name');
    }
    getHeader() {
      return this.buildHeaderHtml([$('<span>').text(this.translate('Preferences')), $('<span>').text(this.userName)]);
    }
    updatePageTitle() {
      let title = this.getLanguage().translate(this.scope, 'scopeNames');
      if (this.model.id !== this.getUser().id && this.userName) {
        title += ' · ' + this.userName;
      }
      this.setPageTitle(title);
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=edit.js.map ;