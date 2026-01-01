define("views/email/modals/import-eml", ["exports", "views/modal", "views/record/edit-for-modal", "model", "views/fields/file"], function (_exports, _modal, _editForModal, _model, _file) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _editForModal = _interopRequireDefault(_editForModal);
  _model = _interopRequireDefault(_model);
  _file = _interopRequireDefault(_file);
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

  class ImportEmlModal extends _modal.default {
    // language=Handlebars
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `;
    setup() {
      this.headerText = this.translate('Import EML', 'labels', 'Email');
      this.addButton({
        name: 'import',
        label: 'Proceed',
        style: 'danger',
        onClick: () => this.actionImport()
      });
      this.addButton({
        name: 'cancel',
        label: 'Cancel',
        onClick: () => this.close()
      });
      this.model = new _model.default({}, {
        entityType: 'ImportEml'
      });
      this.recordView = new _editForModal.default({
        model: this.model,
        detailLayout: [{
          rows: [[{
            view: new _file.default({
              name: 'file',
              params: {
                required: true,
                accept: ['.eml']
              },
              labelText: this.translate('file', 'otherFields', 'Email')
            })
          }]]
        }]
      });
      this.assignView('record', this.recordView, '.record');
    }
    actionImport() {
      if (this.recordView.validate()) {
        return;
      }
      this.disableButton('import');
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('Email/importEml', {
        fileId: this.model.attributes.fileId
      }).then(/** {id: string} */response => {
        Espo.Ui.notify(false);
        this.getRouter().navigate(`Email/view/${response.id}`, {
          trigger: true
        });
      }).catch(() => this.enableButton('import'));
    }
  }
  var _default = _exports.default = ImportEmlModal;
});
//# sourceMappingURL=import-eml.js.map ;