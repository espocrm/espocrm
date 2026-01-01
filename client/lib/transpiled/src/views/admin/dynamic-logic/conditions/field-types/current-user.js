define("views/admin/dynamic-logic/conditions/field-types/current-user", ["exports", "views/admin/dynamic-logic/conditions/field-types/base", "model"], function (_exports, _base, _model) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _base = _interopRequireDefault(_base);
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

  class _default extends _base.default {
    getValueViewName() {
      return 'views/fields/user';
    }
    getValueFieldName() {
      return 'link';
    }
    createModel() {
      const model = new _model.default();
      model.setDefs({
        fields: {
          link: {
            type: 'link',
            entity: 'User'
          }
        }
      });
      return Promise.resolve(model);
    }
    populateValues() {
      if (this.itemData.attribute) {
        this.model.set('linkId', this.itemData.value);
      }
      const name = (this.additionalData.values || {}).name;
      this.model.set('linkName', name);
    }
    translateLeftString() {
      return '$' + this.translate('User', 'scopeNames');
    }
    fetch() {
      /** @type {import('views/fields/base').default} */
      const valueView = this.getView('value');
      valueView.fetchToModel();
      return {
        type: this.type,
        attribute: '$user.id',
        data: {
          values: {
            name: this.model.get('linkName')
          }
        },
        value: this.model.get('linkId')
      };
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=current-user.js.map ;