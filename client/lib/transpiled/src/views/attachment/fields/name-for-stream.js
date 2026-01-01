define("views/attachment/fields/name-for-stream", ["exports", "model", "views/fields/file", "views/fields/base"], function (_exports, _model, _file, _base) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _model = _interopRequireDefault(_model);
  _file = _interopRequireDefault(_file);
  _base = _interopRequireDefault(_base);
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
    // language=Handlebars
    listTemplateContent = `
        <span>{{{subField}}}</span>
    `;
    prepare() {
      const model = new _model.default({
        fileId: this.model.id,
        fileName: this.model.attributes.name,
        fileType: this.model.attributes.type
      });
      const view = new _file.default({
        name: 'file',
        model: model,
        params: {
          showPreview: true,
          listPreviewSize: 'small'
        },
        mode: 'list'
      });
      return this.assignView('subField', view);
    }
  }
  _exports.default = _default;
});
//# sourceMappingURL=name-for-stream.js.map ;