define("views/fields/foreign-checklist", ["exports", "views/fields/checklist", "helpers/misc/foreign-field", "views/fields/foreign-array"], function (_exports, _checklist, _foreignField, _foreignArray) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _checklist = _interopRequireDefault(_checklist);
  _foreignField = _interopRequireDefault(_foreignField);
  _foreignArray = _interopRequireDefault(_foreignArray);
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

  class ForeignChecklistFieldView extends _checklist.default {
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
      _foreignArray.default.prototype.setupOptions.call(this);
    }
  }
  var _default = _exports.default = ForeignChecklistFieldView;
});
//# sourceMappingURL=foreign-checklist.js.map ;