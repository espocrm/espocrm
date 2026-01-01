define("views/notification/items/email-inbox", ["exports", "views/notification/items/base"], function (_exports, _base) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
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

  class EmailInboxNotificationItemView extends _base.default {
    messageName = 'emailInbox';

    // language=Handlebars
    templateContent = `
        <div class="stream-head-container">
            <div class="pull-left">{{{avatar}}}</div>
            <div class="stream-head-text-container">
                <span
                    class="fas fa-envelope text-muted action icon"
                    style="cursor: pointer;"
                    title="{{translate 'View'}}"
                    data-action="quickView"
                    data-id="{{model.attributes.relatedId}}"
                    data-scope="Email"
                ></span><span class="text-muted message">{{{message}}}</span>
            </div>
        </div>
        <div class="stream-date-container">
            <span class="text-muted small">{{{createdAt}}}</span>
        </div>
    `;
    setup() {
      /** @type {{userId: string, userName: string, emailName: string}} */
      const data = this.model.attributes.data || {};
      this.userId = data.userId;
      this.messageData['entityType'] = this.translateEntityType('Email');
      const entity = document.createElement('a');
      entity.href = `#Email/view/${this.model.attributes.relatedId}`;
      entity.dataset.id = this.model.attributes.relatedId;
      entity.dataset.scope = 'Email';
      entity.innerText = data.emailName;
      const user = document.createElement('a');
      user.href = `#User/view/${data.userId}`;
      user.dataset.id = data.userId;
      user.dataset.scope = 'User';
      user.innerText = data.userName;
      this.messageData['entity'] = entity;
      this.messageData['user'] = user;
      this.createMessage();
    }
  }
  var _default = _exports.default = EmailInboxNotificationItemView;
});
//# sourceMappingURL=email-inbox.js.map ;