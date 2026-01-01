define("views/notification/items/collaborating", ["exports", "views/notification/items/base"], function (_exports, _base) {
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

  // noinspection JSUnusedGlobalSymbols
  class CollaboratingNotificationItemView extends _base.default {
    // language=Handlebars
    templateContent = `
        <div class="stream-head-container">
            <div class="pull-left">
                {{{avatar}}}
            </div>
            <div class="stream-head-text-container text-muted">
                {{#if iconHtml}}{{{iconHtml}}}{{/if}}<span class="message"">{{{message}}}</span>
            </div>
        </div>

        <div class="stream-date-container">
            <span class="text-muted small">{{{createdAt}}}</span>
        </div>
    `;
    messageName = 'addedToCollaborators';
    data() {
      const iconHtml = this.model.attributes.relatedType && this.model.attributes.relatedId ? this.getIconHtml(this.model.attributes.relatedType, this.model.attributes.relatedId) : null;
      return {
        ...super.data(),
        iconHtml: iconHtml
      };
    }
    setup() {
      this.userId = this.model.attributes.createdById;
      this.messageData['user'] = (() => {
        const element = document.createElement('a');
        element.href = `#User/view/${this.model.attributes.createdById}`;
        element.dataset.id = this.model.attributes.createdById;
        element.dataset.scope = 'User';
        element.textContent = this.model.attributes.createdByName;
        return element;
      })();
      this.messageData['entityType'] = this.translateEntityType(this.model.attributes.relatedType);
      this.messageData['entity'] = 'field:related';
      this.createMessage();
    }
  }
  _exports.default = CollaboratingNotificationItemView;
});
//# sourceMappingURL=collaborating.js.map ;