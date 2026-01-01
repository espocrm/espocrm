define("views/lead-capture/opt-in-confirmation-success", ["exports", "view", "model"], function (_exports, _view, _model) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
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

  class OptInConfirmationSuccessView extends _view.default {
    template = 'lead-capture/opt-in-confirmation-success';
    setup() {
      const model = new _model.default();
      this.resultData = this.options.resultData;
      if (this.resultData.message) {
        model.set('message', this.resultData.message);
        this.createView('messageField', 'views/fields/text', {
          selector: '.field[data-name="message"]',
          mode: 'detail',
          inlineEditDisabled: true,
          model: model,
          name: 'message'
        });
      }
    }
    data() {
      return {
        resultData: this.options.resultData,
        defaultMessage: this.getLanguage().translate('optInIsConfirmed', 'messages', 'LeadCapture')
      };
    }
  }
  var _default = _exports.default = OptInConfirmationSuccessView;
});
//# sourceMappingURL=opt-in-confirmation-success.js.map ;