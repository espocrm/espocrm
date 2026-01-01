define("controllers/record-tree", ["exports", "controllers/record", "collections/tree"], function (_exports, _record, _tree) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _record = _interopRequireDefault(_record);
  _tree = _interopRequireDefault(_tree);
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

  class RecordTreeController extends _record.default {
    defaultAction = 'listTree';
    beforeView(options) {
      super.beforeView(options);
      options = options || {};
      if (options.model) {
        options.model.unset('childCollection');
        options.model.unset('childList');
      }
    }

    // noinspection JSUnusedGlobalSymbols
    beforeListTree() {
      this.handleCheckAccess('read');
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     *
     * @param {{
     *     currentId?: string,
     *     isReturn?: boolean,
     * }} options
     */
    async actionListTree(options) {
      const currentId = options.currentId;
      const collection = await this.getCollection();
      if (!(collection instanceof _tree.default)) {
        throw new Error("Wrong collection.");
      }
      collection.url = `${collection.entityType}/action/listTree`;
      collection.currentId = currentId ?? null;
      const isReturn = options.isReturn || this.getRouter().backProcessed;
      this.main(this.getViewName('listTree'), {
        scope: this.name,
        collection: collection
      }, undefined, {
        key: 'listTree',
        useStored: isReturn
      });
    }
    async create() {
      let options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      if (options.parentId) {
        options.attributes ??= {};
        options.attributes.parentId = options.parentId;
        options.attributes.parentName = options.parentName;
        delete options.parentId;
        delete options.parentName;
      }
      return super.create(options);
    }
  }
  var _default = _exports.default = RecordTreeController;
});
//# sourceMappingURL=record-tree.js.map ;