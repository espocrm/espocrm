define("modules/crm/views/campaign/record/panels/campaign-log-records", ["exports", "views/record/panels/relationship", "helpers/record-modal"], function (_exports, _relationship, _recordModal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _relationship = _interopRequireDefault(_relationship);
  _recordModal = _interopRequireDefault(_recordModal);
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
  class CampaignLogRecordsPanelView extends _relationship.default {
    filterList = ["all", "sent", "opened", "optedOut", "bounced", "clicked", "optedIn", "leadCreated"];
    setup() {
      if (this.getAcl().checkScope('TargetList', 'create')) {
        this.actionList.push({
          action: 'createTargetList',
          label: 'Create Target List'
        });
      }
      this.filterList = Espo.Utils.clone(this.filterList);
      if (!this.getConfig().get('massEmailOpenTracking')) {
        const i = this.filterList.indexOf('opened');
        if (i >= 0) {
          this.filterList.splice(i, 1);
        }
      }
      super.setup();
    }
    actionCreateTargetList() {
      const attributes = {
        sourceCampaignId: this.model.id,
        sourceCampaignName: this.model.attributes.name
      };
      if (!this.collection.data.primaryFilter) {
        attributes.includingActionList = [];
      } else {
        const status = Espo.Utils.upperCaseFirst(this.collection.data.primaryFilter).replace(/([A-Z])/g, ' $1');
        attributes.includingActionList = [status];
      }
      const helper = new _recordModal.default();
      helper.showCreate(this, {
        entityType: 'TargetList',
        attributes: attributes,
        fullFormDisabled: true,
        layoutName: 'createFromCampaignLog',
        afterSave: () => {
          Espo.Ui.success(this.translate('Done'));
        },
        beforeRender: view => {
          view.getRecordView().setFieldRequired('includingActionList');
        }
      });
    }
  }
  _exports.default = CampaignLogRecordsPanelView;
});
//# sourceMappingURL=campaign-log-records.js.map ;