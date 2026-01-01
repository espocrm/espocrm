define("modules/crm/controllers/activities", ["exports", "controller"], function (_exports, _controller) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
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

  class ActivitiesController extends _controller.default {
    checkAccess(action) {
      if (this.getAcl().check('Activities')) {
        return true;
      }
      return false;
    }

    // noinspection JSUnusedGlobalSymbols
    actionActivities(options) {
      this.processList('activities', options.entityType, options.id, options.targetEntityType);
    }
    actionHistory(options) {
      this.processList('history', options.entityType, options.id, options.targetEntityType);
    }

    /**
     * @param {'activities'|'history'} type
     * @param {string} entityType
     * @param {string} id
     * @param {string} targetEntityType
     */
    processList(type, entityType, id, targetEntityType) {
      let viewName = 'crm:views/activities/list';
      let model;
      this.modelFactory.create(entityType).then(m => {
        model = m;
        model.id = id;
        return model.fetch({
          main: true
        });
      }).then(() => {
        return this.collectionFactory.create(targetEntityType);
      }).then(collection => {
        collection.url = 'Activities/' + model.entityType + '/' + id + '/' + type + '/list/' + targetEntityType;
        this.main(viewName, {
          scope: entityType,
          model: model,
          collection: collection,
          link: type + '_' + targetEntityType,
          type: type
        });
      });
    }
  }
  var _default = _exports.default = ActivitiesController;
});
//# sourceMappingURL=activities.js.map ;