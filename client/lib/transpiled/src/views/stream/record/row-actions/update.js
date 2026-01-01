define("views/stream/record/row-actions/update", ["exports", "views/stream/record/row-actions/default"], function (_exports, _default2) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _default2 = _interopRequireDefault(_default2);
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

  class StreamUpdateNoteRowActionsView extends _default2.default {
    setup() {
      super.setup();
      this.addActionHandler('restore', () => this.actionRestore());
    }
    getActionList() {
      const list = super.getActionList();
      if (this.hasRestore()) {
        list.unshift({
          label: 'Restore',
          data: {
            id: this.model.id
          },
          action: 'restore'
        });
      }
      return list;
    }
    hasRestore() {
      if (this.options.listType !== 'listAuditLog') {
        return false;
      }
      const entityType = this.model.get('parentType');
      if (this.getMetadata().get(`clientDefs.${entityType}.editDisabled`)) {
        return false;
      }
      if (!this.getAcl().checkScope(entityType, 'edit')) {
        return false;
      }
      const fieldList = this.getFieldList();
      if (!fieldList.length) {
        return false;
      }
      for (const field of fieldList) {
        if (!this.getAcl().checkField(entityType, field, 'edit')) {
          return false;
        }
      }
      return true;
    }
    async actionRestore() {
      await this.confirm({
        message: this.translate('confirmRestoreFromAudit', 'messages'),
        confirmText: this.translate('Proceed')
      });
      const entityType = this.model.get('parentType');
      const entityId = this.model.get('parentId');
      this.getRouter().dispatch(entityType, 'edit', {
        id: entityId,
        attributes: this.getPreviousAttributes(),
        highlightFieldList: this.getFieldList()
      });
      this.getRouter().navigate(`#${entityType}/edit/${entityId}`, {
        trigger: false
      });
    }

    /**
     * @return {string[]}
     */
    getFieldList() {
      const data = /** @type {Record} */this.model.get('data') || {};
      return /** @type {string[]} */data.fields || [];
    }

    /**
     * @return {Record}
     */
    getPreviousAttributes() {
      const data = /** @type {Record} */this.model.get('data') || {};
      const attributes = /** @type {Record} */data.attributes || {};
      return attributes.was || {};
    }
  }
  var _default = _exports.default = StreamUpdateNoteRowActionsView;
});
//# sourceMappingURL=update.js.map ;