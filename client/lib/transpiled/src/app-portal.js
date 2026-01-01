define("app-portal", ["exports", "app", "acl-portal-manager"], function (_exports, _app, _aclPortalManager) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _app = _interopRequireDefault(_app);
  _aclPortalManager = _interopRequireDefault(_aclPortalManager);
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

  /** @module app-portal */

  /**
   * A portal application class.
   */
  class AppPortal extends _app.default {
    aclName = 'aclPortal';
    masterView = 'views/site-portal/master';
    createAclManager() {
      return new _aclPortalManager.default(this.user, null, this.settings.get('aclAllowDeleteCreated'));
    }
  }
  var _default = _exports.default = AppPortal;
});
//# sourceMappingURL=app-portal.js.map ;