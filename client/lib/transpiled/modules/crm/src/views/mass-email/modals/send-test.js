define("modules/crm/views/mass-email/modals/send-test", ["exports", "views/modal", "model", "views/record/edit-for-modal", "views/fields/link-multiple"], function (_exports, _modal, _model, _editForModal, _linkMultiple) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _model = _interopRequireDefault(_model);
  _editForModal = _interopRequireDefault(_editForModal);
  _linkMultiple = _interopRequireDefault(_linkMultiple);
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

  class MassEmailSendTestModalView extends _modal.default {
    // language=Handlebars
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `;

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView;

    /**
     * @private
     * @type {Model}
     */
    formModel;

    /**
     * @param {{model: import('model').default}} options
     */
    constructor(options) {
      super(options);
      this.model = options.model;
    }
    setup() {
      super.setup();
      this.headerText = this.translate('Send Test', 'labels', 'MassEmail');
      const formModel = this.formModel = new _model.default();
      formModel.set('usersIds', [this.getUser().id]);
      const usersNames = {};
      usersNames[this.getUser().id] = this.getUser().get('name');
      formModel.set('usersNames', usersNames);
      this.recordView = new _editForModal.default({
        model: formModel,
        detailLayout: [{
          rows: [[{
            view: new _linkMultiple.default({
              name: 'users',
              labelText: this.translate('users', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'User'
              }
            })
          }, false], [{
            view: new _linkMultiple.default({
              name: 'contacts',
              labelText: this.translate('contacts', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'Contact'
              }
            })
          }, false], [{
            view: new _linkMultiple.default({
              name: 'leads',
              labelText: this.translate('leads', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'Lead'
              }
            })
          }, false], [{
            view: new _linkMultiple.default({
              name: 'accounts',
              labelText: this.translate('accounts', 'links', 'TargetList'),
              mode: 'edit',
              params: {
                entity: 'Account'
              }
            })
          }, false]]
        }]
      });
      this.assignView('record', this.recordView);
      this.buttonList.push({
        name: 'sendTest',
        label: 'Send Test',
        style: 'danger',
        onClick: () => this.actionSendTest()
      });
      this.buttonList.push({
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.actionClose()
      });
    }
    actionSendTest() {
      const list = [];
      if (Array.isArray(this.formModel.attributes.usersIds)) {
        this.formModel.attributes.usersIds.forEach(id => {
          list.push({
            id: id,
            type: 'User'
          });
        });
      }
      if (Array.isArray(this.formModel.attributes.contactsIds)) {
        this.formModel.attributes.contactsIds.forEach(id => {
          list.push({
            id: id,
            type: 'Contact'
          });
        });
      }
      if (Array.isArray(this.formModel.attributes.leadsIds)) {
        this.formModel.attributes.leadsIds.forEach(id => {
          list.push({
            id: id,
            type: 'Lead'
          });
        });
      }
      if (Array.isArray(this.formModel.attributes.accountsIds)) {
        this.formModel.attributes.accountsIds.forEach(id => {
          list.push({
            id: id,
            type: 'Account'
          });
        });
      }
      if (list.length === 0) {
        Espo.Ui.error(this.translate('selectAtLeastOneTarget', 'messages', 'MassEmail'));
        return;
      }
      this.disableButton('sendTest');
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('MassEmail/action/sendTest', {
        id: this.model.id,
        targetList: list
      }).then(() => {
        Espo.Ui.success(this.translate('testSent', 'messages', 'MassEmail'));
        this.close();
      }).catch(() => {
        this.enableButton('sendTest');
      });
    }
  }
  _exports.default = MassEmailSendTestModalView;
});
//# sourceMappingURL=send-test.js.map ;