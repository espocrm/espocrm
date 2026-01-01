define("views/preferences/fields/calendar-scroll-hour", ["exports", "views/fields/enum-int", "moment"], function (_exports, _enumInt, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _enumInt = _interopRequireDefault(_enumInt);
  _moment = _interopRequireDefault(_moment);
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

  class PreferencesCalendarScrollHourView extends _enumInt.default {
    setupOptions() {
      super.setupOptions();
      this.translatedOptions = {};
      this.translatedOptions[''] = this.translate('Default');
      const timeFormat = this.getDateTime().getTimeFormat();
      const today = this.getDateTime().getToday();
      this.params.options.forEach(item => {
        if (item === '') {
          return;
        }
        const itemString = today + ' ' + item.toString().padStart(2, '0') + ':00';
        this.translatedOptions[item] = _moment.default.utc(itemString).format(timeFormat);
      });
    }
  }

  // noinspection JSUnusedGlobalSymbols
  var _default = _exports.default = PreferencesCalendarScrollHourView;
});
//# sourceMappingURL=calendar-scroll-hour.js.map ;