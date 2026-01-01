define("views/email-account/fields/test-connection", ["exports", "views/fields/base"], function (_exports, _base) {
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

  class _default extends _base.default {
    readOnly = true;
    templateContent = `
        <button class="btn btn-default disabled" data-action="testConnection"
        >{{translate 'Test Connection' scope='EmailAccount'}}</button>
    `;
    url = 'EmailAccount/action/testConnection';
    setup() {
      super.setup();
      this.addActionHandler('testConnection', () => this.test());
    }
    fetch() {
      return {};
    }
    checkAvailability() {
      if (this.model.get('host')) {
        this.$el.find('button').removeClass('disabled').removeAttr('disabled');
      } else {
        this.$el.find('button').addClass('disabled').attr('disabled', 'disabled');
      }
    }
    afterRender() {
      this.checkAvailability();
      this.stopListening(this.model, 'change:host');
      this.listenTo(this.model, 'change:host', () => {
        this.checkAvailability();
      });
    }
    getData() {
      return {
        host: this.model.get('host'),
        port: this.model.get('port'),
        security: this.model.get('security'),
        username: this.model.get('username'),
        password: this.model.get('password') || null,
        id: this.model.id,
        emailAddress: this.model.get('emailAddress'),
        userId: this.model.get('assignedUserId')
      };
    }
    test() {
      const data = this.getData();
      const $btn = this.$el.find('button');
      $btn.addClass('disabled');
      Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
      Espo.Ajax.postRequest(this.url, data).then(() => {
        $btn.removeClass('disabled');
        Espo.Ui.success(this.translate('connectionIsOk', 'messages', 'EmailAccount'));
      }).catch(xhr => {
        let statusReason = xhr.getResponseHeader('X-Status-Reason') || '';
        statusReason = statusReason.replace(/ $/, '');
        statusReason = statusReason.replace(/,$/, '');
        let msg = this.translate('Error');
        if (parseInt(xhr.status) !== 200) {
          msg += ' ' + xhr.status;
        }
        if (statusReason) {
          msg += ': ' + statusReason;
        }
        Espo.Ui.error(msg, true);
        console.error(msg);
        xhr.errorIsHandled = true;
        $btn.removeClass('disabled');
      });
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=test-connection.js.map ;