define("views/fields/foreign-enum", ["exports", "views/fields/enum", "helpers/misc/foreign-field"], function (_exports, _enum, _foreignField) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _enum = _interopRequireDefault(_enum);
  _foreignField = _interopRequireDefault(_foreignField);
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

  class ForeignEnumFieldView extends _enum.default {
    type = 'foreign';

    /**
     * @private
     * @type {string}
     */
    foreignEntityType;
    setup() {
      const helper = new _foreignField.default(this);
      const foreignParams = helper.getForeignParams();
      for (const param in foreignParams) {
        this.params[param] = foreignParams[param];
      }
      this.foreignEntityType = helper.getEntityType();
      super.setup();
    }
    setupOptions() {
      const field = this.params.field;
      const link = this.params.link;
      if (!field || !link) {
        return;
      }
      let optionsPath = this.params.optionsPath;
      const optionsReference = this.params.optionsReference;
      let options = this.params.options;
      const style = this.params.style;
      if (!optionsPath && optionsReference) {
        const [refEntityType, refField] = optionsReference.split('.');
        optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;
      }
      if (optionsPath) {
        options = this.getMetadata().get(optionsPath);
      }
      this.params.options = Espo.Utils.clone(options) ?? [];
      this.styleMap = style ?? {};
      const pairs = this.params.options.map(item => [item, this.getLanguage().translateOption(item, field, this.foreignEntityType)]);
      this.translatedOptions = Object.fromEntries(pairs);
    }
  }
  var _default = _exports.default = ForeignEnumFieldView;
});
//# sourceMappingURL=foreign-enum.js.map ;