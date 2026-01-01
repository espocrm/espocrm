define("modules/crm/views/lead/detail", ["exports", "views/detail"], function (_exports, _detail) {
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

  class LeadDetailView extends _detail.default {
    setup() {
      super.setup();
      this.addMenuItem('buttons', {
        name: 'convert',
        action: 'convert',
        label: 'Convert',
        acl: 'edit',
        hidden: !this.isConvertable(),
        onClick: () => this.actionConvert()
      });
      this.listenTo(this.model, 'sync', () => {
        this.isConvertable() ? this.showHeaderActionItem('convert') : this.hideHeaderActionItem('convert');
      });
    }
    isConvertable() {
      const notActualList = [...(this.getMetadata().get(`entityDefs.Lead.fields.status.notActualOptions`) || []), 'Converted'];
      return !notActualList.includes(this.model.get('status')) && this.model.has('status');
    }
    actionConvert() {
      this.getRouter().navigate(`${this.model.entityType}/convert/${this.model.id}`, {
        trigger: true
      });
    }
  }
  var _default = _exports.default = LeadDetailView;
});
//# sourceMappingURL=detail.js.map ;