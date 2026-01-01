define("views/dashlets/stream", ["exports", "views/dashlets/abstract/base"], function (_exports, _base) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
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

  class StreamDashletView extends _base.default {
    name = 'Stream';
    templateContent = '<div class="list-container">{{{list}}}</div>';
    actionRefresh() {
      this.refreshInternal();
    }
    autoRefresh() {
      this.refreshInternal({
        skipNotify: true
      });
    }
    /**
     * @private
     * @param {{skipNotify?: boolean}} [options]
     * @return {Promise<void>}
     */
    async refreshInternal() {
      let options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      if (!this.getRecordView()) {
        return;
      }
      if (!options.skipNotify) {
        Espo.Ui.notifyWait();
      }
      await this.getRecordView().showNewRecords();
      if (!options.skipNotify) {
        Espo.Ui.notify();
      }
    }
    afterRender() {
      this.getCollectionFactory().create('Note', collection => {
        this.collection = collection;
        collection.url = 'Stream';
        collection.maxSize = this.getOption('displayRecords');
        if (this.getOption('skipOwn')) {
          collection.data.skipOwn = true;
        }
        collection.fetch().then(() => {
          this.createView('list', 'views/stream/record/list', {
            selector: '> .list-container',
            collection: collection,
            isUserStream: true,
            noEdit: false
          }, view => {
            view.render();
          });
        });
      });
    }

    /**
     * @return {module:views/stream/record/list}
     */
    getRecordView() {
      return this.getView('list');
    }
    setupActionList() {
      this.actionList.unshift({
        name: 'viewList',
        text: this.translate('View'),
        iconHtml: '<span class="fas fa-align-justify"></span>',
        url: '#Stream'
      });
      if (!this.getUser().isPortal()) {
        this.actionList.unshift({
          name: 'create',
          text: this.translate('Create Post', 'labels'),
          iconHtml: '<span class="fas fa-plus"></span>'
        });
      }
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreate() {
      this.createView('dialog', 'views/stream/modals/create-post', {}, view => {
        view.render();
        this.listenToOnce(view, 'after:save', () => {
          view.close();
          this.actionRefresh();
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionViewList() {
      this.getRouter().navigate('#Stream', {
        trigger: true
      });
    }
  }
  var _default = _exports.default = StreamDashletView;
});
//# sourceMappingURL=stream.js.map ;