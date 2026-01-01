define("views/settings/fields/address-preview", ["exports", "views/fields/address"], function (_exports, _address) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _address = _interopRequireDefault(_address);
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

  class _default extends _address.default {
    setup() {
      super.setup();
      const mainModel = this.model;
      const model = mainModel.clone();
      model.entityType = mainModel.entityType;
      model.name = mainModel.name;
      model.set({
        addressPreviewStreet: 'Street',
        addressPreviewPostalCode: 'PostalCode',
        addressPreviewCity: 'City',
        addressPreviewState: 'State',
        addressPreviewCountry: 'Country'
      });
      this.listenTo(mainModel, 'change:addressFormat', () => {
        model.set('addressFormat', mainModel.get('addressFormat'));
        this.reRender();
      });
      this.model = model;
    }
    getAddressFormat() {
      return this.model.get('addressFormat') || 1;
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=address-preview.js.map ;