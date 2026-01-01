define("controllers/preferences", ["exports", "controllers/record", "models/preferences"], function (_exports, _record, _preferences) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _record = _interopRequireDefault(_record);
  _preferences = _interopRequireDefault(_preferences);
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

  class PreferencesController extends _record.default {
    defaultAction = 'own';
    getModel(callback, context) {
      const model = new _preferences.default({}, {
        defs: this.getMetadata().get('entityDefs.Preferences') || {}
      });
      model.setSettings(this.getConfig());
      if (callback) {
        callback.call(this, model);
      }
      return new Promise(resolve => {
        resolve(model);
      });
    }
    checkAccess(action) {
      return true;
    }

    // noinspection JSUnusedGlobalSymbols
    actionOwn() {
      this.actionEdit({
        id: this.getUser().id
      });
    }
    actionList(options) {}
  }
  var _default = _exports.default = PreferencesController;
});
//# sourceMappingURL=preferences.js.map ;