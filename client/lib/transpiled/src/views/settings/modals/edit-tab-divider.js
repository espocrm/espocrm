define("views/settings/modals/edit-tab-divider", ["exports", "views/modal", "model"], function (_exports, _modal, _model) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
  _model = _interopRequireDefault(_model);
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

  class EditTabDividerSettingsModalView extends _modal.default {
    className = 'dialog dialog-record';
    templateContent = '<div class="record no-side-margin">{{{record}}}</div>';
    setup() {
      super.setup();
      this.headerText = this.translate('Divider', 'labels', 'Settings');
      this.buttonList.push({
        name: 'apply',
        label: 'Apply',
        style: 'danger'
      });
      this.buttonList.push({
        name: 'cancel',
        label: 'Cancel'
      });
      this.shortcutKeys = {
        'Control+Enter': () => this.actionApply()
      };
      let detailLayout = [{
        rows: [[{
          name: 'text',
          labelText: this.options.parentType === 'Preferences' ? this.translate('label', 'tabFields', 'Preferences') : this.translate('label', 'fields', 'Admin')
        }, false]]
      }];
      let model = this.model = new _model.default({}, {
        entityType: 'Dummy'
      });
      model.set(this.options.itemData);
      model.setDefs({
        fields: {
          text: {
            type: 'varchar'
          }
        }
      });
      this.createView('record', 'views/record/edit-for-modal', {
        detailLayout: detailLayout,
        model: model,
        selector: '.record'
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionApply() {
      let recordView = /** @type {module:views/record/edit}*/this.getView('record');
      if (recordView.validate()) {
        return;
      }
      let data = recordView.fetch();
      this.trigger('apply', data);
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = EditTabDividerSettingsModalView;
});
//# sourceMappingURL=edit-tab-divider.js.map ;