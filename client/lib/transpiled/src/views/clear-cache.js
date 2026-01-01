define("views/clear-cache", ["exports", "view"], function (_exports, _view) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
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

  class ClearCacheView extends _view.default {
    template = 'clear-cache';
    el = '> body';
    events = {
      /** @this ClearCacheView */
      'click .action[data-action="clearLocalCache"]': function () {
        this.clearLocalCache();
      },
      /** @this ClearCacheView */
      'click .action[data-action="returnToApplication"]': function () {
        this.returnToApplication();
      }
    };
    data() {
      return {
        cacheIsEnabled: !!this.options.cache
      };
    }
    clearLocalCache() {
      this.options.cache.clear();
      this.$el.find('.action[data-action="clearLocalCache"]').remove();
      this.$el.find('.message-container').removeClass('hidden');
      this.$el.find('.message-container span').html(this.translate('Cache has been cleared'));
      this.$el.find('.action[data-action="returnToApplication"]').removeClass('hidden');
    }
    returnToApplication() {
      this.getRouter().navigate('', {
        trigger: true
      });
    }
  }
  var _default = _exports.default = ClearCacheView;
});
//# sourceMappingURL=clear-cache.js.map ;