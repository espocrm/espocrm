define("views/lead-capture/record/detail", ["exports", "views/record/detail"], function (_exports, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _detail = _interopRequireDefault(_detail);
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

  class _default extends _detail.default {
    setupActionItems() {
      super.setupActionItems();
      this.addDropdownItem({
        label: 'Generate New API Key',
        name: 'generateNewApiKey',
        onClick: () => this.actionGenerateNewApiKey()
      });
      this.addDropdownItem({
        label: 'Generate New Form ID',
        name: 'generateNewFormId',
        onClick: () => this.actionGenerateNewFormId()
      });
    }
    actionGenerateNewApiKey() {
      this.confirm(this.translate('confirmation', 'messages'), () => {
        Espo.Ajax.postRequest('LeadCapture/action/generateNewApiKey', {
          id: this.model.id
        }).then(data => {
          this.model.set(data);
          Espo.Ui.success(this.translate('Done'));
        });
      });
    }
    async actionGenerateNewFormId() {
      await this.confirm(this.translate('confirmation', 'messages'));
      const data = await Espo.Ajax.postRequest('LeadCapture/action/generateNewFormId', {
        id: this.model.id
      });
      this.model.set(data);
      Espo.Ui.success(this.translate('Done'));
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=detail.js.map ;