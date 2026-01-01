define("views/admin/link-manager/fields/foreign-link-entity-type-list", ["exports", "views/fields/checklist"], function (_exports, _checklist) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _checklist = _interopRequireDefault(_checklist);
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

  class _default extends _checklist.default {
    setup() {
      this.params.translation = 'Global.scopeNames';
      super.setup();
    }
    afterRender() {
      super.afterRender();
      this.controlOptionsAvailability();
    }
    controlOptionsAvailability() {
      this.params.options.forEach(item => {
        const link = this.model.get('link');
        const linkForeign = this.model.get('linkForeign');
        const entityType = this.model.get('entity');
        const linkDefs = this.getMetadata().get(['entityDefs', item, 'links']) || {};
        let isFound = false;
        for (const i in linkDefs) {
          if (linkDefs[i].foreign === link && !linkDefs[i].isCustom && linkDefs[i].entity === entityType) {
            isFound = true;
          } else if (i === linkForeign && linkDefs[i].type !== 'hasChildren') {
            isFound = true;
          }
        }
        if (isFound) {
          this.$el.find(`input[data-name="checklistItem-foreignLinkEntityTypeList-${item}"]`).attr('disabled', 'disabled');
        }
      });
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=foreign-link-entity-type-list.js.map ;