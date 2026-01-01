define("views/personal-data/modals/personal-data", ["exports", "views/modal"], function (_exports, _modal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
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

  class _default extends _modal.default {
    template = 'personal-data/modals/personal-data';
    className = 'dialog dialog-record';
    backdrop = true;
    setup() {
      super.setup();
      this.buttonList = [{
        name: 'cancel',
        label: 'Close'
      }];
      this.headerText = this.getLanguage().translate('Personal Data');
      this.headerText += ': ' + this.model.get('name');
      if (this.getAcl().check(this.model, 'edit')) {
        this.buttonList.unshift({
          name: 'erase',
          label: 'Erase',
          style: 'danger',
          disabled: true,
          onClick: () => this.actionErase()
        });
      }
      this.fieldList = [];
      this.scope = this.model.entityType;
      this.createView('record', 'views/personal-data/record/record', {
        selector: '.record',
        model: this.model
      }, view => {
        this.listenTo(view, 'check', fieldList => {
          this.fieldList = fieldList;
          if (fieldList.length) {
            this.enableButton('erase');
          } else {
            this.disableButton('erase');
          }
        });
        if (!view.fieldList.length) {
          this.disableButton('export');
        }
      });
    }
    actionErase() {
      this.confirm({
        message: this.translate('erasePersonalDataConfirmation', 'messages'),
        confirmText: this.translate('Erase')
      }, () => {
        this.disableButton('erase');
        Espo.Ajax.postRequest('DataPrivacy/action/erase', {
          fieldList: this.fieldList,
          entityType: this.scope,
          id: this.model.id
        }).then(() => {
          Espo.Ui.success(this.translate('Done'));
          this.trigger('erase');
        }).catch(() => {
          this.enableButton('erase');
        });
      });
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=personal-data.js.map ;