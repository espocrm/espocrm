define("views/dashboard-template/modals/deploy-to-team", ["exports", "views/modal", "model"], function (_exports, _modal, _model) {
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

  class _default extends _modal.default {
    className = 'dialog dialog-record';
    templateContent = `<div class="record">{{{record}}}</div>`;
    setup() {
      this.buttonList = [{
        name: 'deploy',
        text: this.translate('Deploy for Team', 'labels', 'DashboardTemplate'),
        style: 'danger',
        onClick: () => this.actionDeploy()
      }, {
        name: 'cancel',
        label: 'Cancel'
      }];
      this.headerText = this.model.get('name');
      this.formModel = new _model.default();
      this.formModel.name = 'None';
      this.formModel.setDefs({
        fields: {
          'team': {
            type: 'link',
            entity: 'Team',
            required: true
          },
          'append': {
            type: 'bool'
          }
        }
      });
      this.createView('record', 'views/record/edit-for-modal', {
        scope: 'None',
        model: this.formModel,
        selector: '.record',
        detailLayout: [{
          rows: [[{
            name: 'team',
            labelText: this.translate('team', 'links')
          }, {
            name: 'append',
            labelText: this.translate('append', 'fields', 'DashboardTemplate')
          }]]
        }]
      });
    }

    /**
     * @private
     * @return {import('views/record/edit').default}
     */
    getRecordView() {
      return this.getView('record');
    }

    /**
     * @private
     */
    actionDeploy() {
      if (this.getRecordView().processFetch()) {
        Espo.Ajax.postRequest('DashboardTemplate/action/deployToTeam', {
          id: this.model.id,
          teamId: this.formModel.get('teamId'),
          append: this.formModel.get('append')
        }).then(() => {
          Espo.Ui.success(this.translate('Done'));
          this.close();
        });
      }
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=deploy-to-team.js.map ;