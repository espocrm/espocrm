define("views/email-account/record/edit", ["exports", "views/record/edit", "views/email-account/record/detail"], function (_exports, _edit, _detail) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _edit = _interopRequireDefault(_edit);
  _detail = _interopRequireDefault(_detail);
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

  class _default extends _edit.default {
    setup() {
      super.setup();
      _detail.default.prototype.setupFieldsBehaviour.call(this);
      _detail.default.prototype.initSslFieldListening.call(this);
      _detail.default.prototype.initSmtpFieldsControl.call(this);
      if (this.getUser().isAdmin()) {
        this.setFieldNotReadOnly('assignedUser');
      } else {
        this.setFieldReadOnly('assignedUser');
      }
    }
    modifyDetailLayout(layout) {
      _detail.default.prototype.modifyDetailLayout.call(this, layout);
    }
    setupFieldsBehaviour() {
      _detail.default.prototype.setupFieldsBehaviour.call(this);
    }
    controlStatusField() {
      _detail.default.prototype.controlStatusField.call(this);
    }
    controlSmtpFields() {
      _detail.default.prototype.controlSmtpFields.call(this);
    }
    controlSmtpAuthField() {
      _detail.default.prototype.controlSmtpAuthField.call(this);
    }
    wasFetched() {
      _detail.default.prototype.wasFetched.call(this);
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=edit.js.map ;