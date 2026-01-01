define("views/admin/app-secret/fields/value", ["exports", "views/fields/text"], function (_exports, _text) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _text = _interopRequireDefault(_text);
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

  class _default extends _text.default {
    detailTemplateContent = `**********`;
    validations = ['required'];
    changingMode = false;
    data() {
      return {
        isNew: this.model.isNew(),
        ...super.data()
      };
    }
    afterRenderEdit() {
      super.afterRenderEdit();
      if (!this.model.isNew() && !this.changingMode) {
        this.element.innerHTML = '';
        const a = document.createElement('a');
        a.role = 'button';
        a.onclick = () => this.changePassword();
        a.textContent = this.translate('change');
        this.element.appendChild(a);
      }
    }
    onDetailModeSet() {
      this.changingMode = false;
      return super.onDetailModeSet();
    }
    fetch() {
      if (!this.model.isNew() && !this.changingMode) {
        return {};
      }
      return super.fetch();
    }
    async changePassword() {
      this.changingMode = true;
      await this.reRender();
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=value.js.map ;