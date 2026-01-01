define("views/user/record/panels/stream", ["exports", "views/stream/panel"], function (_exports, _panel) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _panel = _interopRequireDefault(_panel);
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

  class _default extends _panel.default {
    setup() {
      const model = /** @type import('models/user').default */this.model;
      if (this.model.id === this.getUser().id) {
        this.placeholderText = this.translate('writeMessageToSelf', 'messages');
      } else {
        this.placeholderText = this.translate('writeMessageToUser', 'messages').replace('{user}', this.model.get('name'));
      }
      super.setup();
      this.setupPermission(model);
    }

    /**
     * @private
     * @param {import('models/user').default} model
     */
    setupPermission(model) {
      const permission = this.getAcl().checkPermission('message', model);
      if (permission) {
        return;
      }
      this.postDisabled = true;
      if (permission !== null) {
        return;
      }
      this.listenToOnce(this.model, 'sync', async () => {
        if (!this.getAcl().checkPermission('message', model)) {
          return;
        }
        this.postDisabled = false;
        await this.whenRendered();
        const container = this.element.querySelector('.post-container');
        if (container) {
          container.classList.remove('hidden');
        }
      });
    }
    prepareNoteForPost(model) {
      const userIdList = [this.model.id];
      const userNames = {};
      userNames[userIdList] = this.model.get('name');
      model.set('usersIds', userIdList);
      model.set('usersNames', userNames);
      model.set('targetType', 'users');
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=stream.js.map ;