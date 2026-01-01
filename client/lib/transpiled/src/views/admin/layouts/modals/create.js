define("views/admin/layouts/modals/create", ["exports", "views/modal", "views/record/edit-for-modal", "model", "views/fields/enum", "views/fields/varchar"], function (_exports, _modal, _editForModal, _model, _enum, _varchar) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _editForModal = _interopRequireDefault(_editForModal);
  _model = _interopRequireDefault(_model);
  _enum = _interopRequireDefault(_enum);
  _varchar = _interopRequireDefault(_varchar);
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

  /** @module views/admin/layouts/modals/create */

  class LayoutCreateModalView extends _modal.default {
    // language=Handlebars
    templateContent = `
        <div class="complex-text-container">{{complexText info}}</div>
        <div class="record no-side-margin">{{{record}}}</div>
    `;
    className = 'dialog dialog-record';

    /**
     * @typedef {Object} module:views/admin/layouts/modals/create~data
     * @property {string} type
     * @property {string} name
     * @property {string} label
     */

    /**
     * @param {{scope: string}} options
     */
    constructor(options) {
      super();
      this.scope = options.scope;
    }
    data() {
      return {
        info: this.translate('createInfo', 'messages', 'LayoutManager')
      };
    }
    setup() {
      this.headerText = this.translate('Create');
      this.buttonList = [{
        name: 'create',
        style: 'danger',
        label: 'Create',
        onClick: () => this.actionCreate()
      }, {
        name: 'cancel',
        label: 'Cancel'
      }];
      this.model = new _model.default({
        type: 'list',
        name: 'listForMyEntityType',
        label: 'List (for MyEntityType)'
      });
      this.recordView = new _editForModal.default({
        model: this.model,
        detailLayout: [{
          columns: [[{
            view: new _enum.default({
              name: 'type',
              params: {
                readOnly: true,
                translation: 'Admin.layouts',
                options: ['list']
              },
              labelText: this.translate('type', 'fields', 'Admin')
            })
          }, {
            view: new _varchar.default({
              name: 'name',
              params: {
                required: true,
                noSpellCheck: true,
                pattern: '$latinLetters'
              },
              labelText: this.translate('name', 'fields')
            })
          }, {
            view: new _varchar.default({
              name: 'label',
              params: {
                required: true,
                pattern: '$noBadCharacters'
              },
              labelText: this.translate('label', 'fields', 'Admin')
            })
          }], []]
        }]
      });
      this.assignView('record', this.recordView, '.record');
    }
    actionCreate() {
      this.recordView.fetch();
      if (this.recordView.validate()) {
        return;
      }
      this.disableButton('create');
      Espo.Ui.notifyWait();
      Espo.Ajax.postRequest('Layout/action/create', {
        scope: this.scope,
        type: this.model.get('type'),
        name: this.model.get('name'),
        label: this.model.get('label')
      }).then(() => {
        this.reRender();
        Espo.Ui.success('Created', {
          suppress: true
        });
        this.trigger('done');
        this.close();
      }).catch(() => {
        this.enableButton('create');
      });
    }
  }
  var _default = _exports.default = LayoutCreateModalView;
});
//# sourceMappingURL=create.js.map ;