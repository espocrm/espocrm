define("views/user/record/row-actions/relationship-followers", ["exports", "views/record/row-actions/relationship"], function (_exports, _relationship) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relationship = _interopRequireDefault(_relationship);
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

  // noinspection JSUnusedGlobalSymbols
  class UserRelationshipFollowersRowActionsView extends _relationship.default {
    getActionList() {
      const list = [];
      const model = /** @type {import('models/user').default} */this.model;
      list.push({
        action: 'quickView',
        label: 'View',
        data: {
          id: this.model.id
        },
        link: `#${this.model.entityType}/view/${this.model.id}`
      });
      if (this.getUser().isAdmin() || this.getAcl().getPermissionLevel('followerManagementPermission') !== 'no' || model.isPortal() && this.getAcl().getPermissionLevel('portalPermission') === 'yes' || this.model.id === this.getUser().id) {
        list.push({
          action: 'unlinkRelated',
          label: 'Unlink',
          data: {
            id: this.model.id
          }
        });
      }
      return list;
    }
  }
  _exports.default = UserRelationshipFollowersRowActionsView;
});
//# sourceMappingURL=relationship-followers.js.map ;