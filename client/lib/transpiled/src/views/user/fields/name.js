define("views/user/fields/name", ["exports", "views/fields/person-name"], function (_exports, _personName) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _personName = _interopRequireDefault(_personName);
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

  class _default extends _personName.default {
    listTemplate = 'user/fields/name/list-link';
    listLinkTemplate = 'user/fields/name/list-link';
    data() {
      const model = /** @type {import('models/user').default} */this.model;

      // noinspection JSValidateTypes
      return {
        ...super.data(),
        avatar: this.getAvatarHtml(),
        frontScope: model.isPortal() ? 'PortalUser' : 'User',
        isOwn: this.model.id === this.getUser().id
      };
    }
    getAvatarHtml() {
      return this.getHelper().getAvatarHtml(this.model.id, 'small', 20, 'avatar-link');
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=name.js.map ;