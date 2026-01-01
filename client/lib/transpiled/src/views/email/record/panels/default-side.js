define("views/email/record/panels/default-side", ["exports", "views/record/panels/default-side"], function (_exports, _defaultSide) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _defaultSide = _interopRequireDefault(_defaultSide);
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

  class _default extends _defaultSide.default {
    setupFields() {
      super.setupFields();
      this.fieldList.push({
        name: 'isAutoReply'
      });
      this.fieldList.push({
        name: 'hasAttachment',
        view: 'views/email/fields/has-attachment',
        noLabel: true
      });
      this.controlHasAttachmentField();
      this.listenTo(this.model, 'change:hasAttachment', () => this.controlHasAttachmentField());
      this.controlIsAutoReply();
      this.listenTo(this.model, 'change:isAutoReply', () => this.controlIsAutoReply());
    }

    /**
     * @private
     */
    controlHasAttachmentField() {
      if (this.model.attributes.hasAttachment) {
        this.recordViewObject.showField('hasAttachment');
        return;
      }
      this.recordViewObject.hideField('hasAttachment');
    }

    /**
     * @private
     */
    controlIsAutoReply() {
      if (this.model.attributes.isAutoReply) {
        this.recordViewObject.showField('isAutoReply');
        return;
      }
      this.recordViewObject.hideField('isAutoReply');
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=default-side.js.map ;