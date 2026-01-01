define("views/modals/followers-list", ["exports", "views/modals/related-list", "helpers/record/select-related"], function (_exports, _relatedList, _selectRelated) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relatedList = _interopRequireDefault(_relatedList);
  _selectRelated = _interopRequireDefault(_selectRelated);
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

  class FollowersListModalView extends _relatedList.default {
    massActionRemoveDisabled = true;
    massActionMassUpdateDisabled = true;
    mandatorySelectAttributeList = ['type'];
    setup() {
      if (!this.getUser().isAdmin() && this.getAcl().getPermissionLevel('followerManagementPermission') === 'no' && this.getAcl().getPermissionLevel('portalPermission') === 'no') {
        this.unlinkDisabled = true;
      }
      super.setup();
    }
    actionSelectRelated() {
      let filter = 'active';
      if (!this.getUser().isAdmin() && this.getAcl().getPermissionLevel('followerManagementPermission') === 'no' && this.getAcl().getPermissionLevel('portalPermission') === 'yes') {
        filter = 'activePortal';
      }
      const helper = new _selectRelated.default(this);
      helper.process(this.model, this.link, {
        primaryFilterName: filter,
        massSelect: false,
        foreignEntityType: 'User',
        viewKey: 'selectFollowers'
      });
    }
  }
  var _default = _exports.default = FollowersListModalView;
});
//# sourceMappingURL=followers-list.js.map ;