define("views/stream/notes/status", ["exports", "views/stream/note"], function (_exports, _note) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _note = _interopRequireDefault(_note);
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

  /**
   * Legacy as of v9.2.0.
   */
  class StatusNoteStreamView extends _note.default {
    template = 'stream/notes/status';
    messageName = 'status';
    data() {
      return {
        ...super.data(),
        style: this.style,
        statusText: this.statusText,
        iconHtml: this.getIconHtml()
      };
    }
    init() {
      if (this.getUser().isAdmin()) {
        this.isRemovable = true;
      }
      super.init();
    }
    setup() {
      const data = this.model.get('data');
      const parentType = this.model.attributes.parentType;
      const field = this.getMetadata().get(`scopes.${parentType}.statusField`) ?? '';
      const value = data.value;
      this.style = data.style || 'default';
      this.statusText = this.getLanguage().translateOption(value, field, parentType);
      this.statusStyle = this.getMetadata().get(`entityDefs.${parentType}.fields.${field}.style.${value}`) || 'default';
      let fieldLabel = this.translate(field, 'fields', parentType);
      if (!this.isToUpperCaseStringItems()) {
        fieldLabel = fieldLabel.toLowerCase();
      }
      this.messageData['field'] = fieldLabel;
      this.createMessage();
    }
  }
  var _default = _exports.default = StatusNoteStreamView;
});
//# sourceMappingURL=status.js.map ;