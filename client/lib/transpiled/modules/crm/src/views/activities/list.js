define("modules/crm/views/activities/list", ["exports", "views/list-related"], function (_exports, _listRelated) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _listRelated = _interopRequireDefault(_listRelated);
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

  class ActivitiesListView extends _listRelated.default {
    createButton = false;
    unlinkDisabled = true;
    filtersDisabled = true;
    allResultDisabled = true;
    setup() {
      this.rowActionsView = 'views/record/row-actions/default';
      super.setup();
      this.type = this.options.type;
    }
    getHeader() {
      const name = this.model.get('name') || this.model.id;
      const recordUrl = `#${this.scope}/view/${this.model.id}`;
      const $name = $('<a>').attr('href', recordUrl).addClass('font-size-flexible title').text(name).css('user-select', 'none');
      if (this.model.get('deleted')) {
        $name.css('text-decoration', 'line-through');
      }
      const headerIconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
      const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');
      let $root = $('<span>').text(scopeLabel);
      if (!this.rootLinkDisabled) {
        $root = $('<span>').append($('<a>').attr('href', '#' + this.scope).addClass('action').attr('data-action', 'navigateToRoot').text(scopeLabel));
      }
      $root.css('user-select', 'none');
      if (headerIconHtml) {
        $root.prepend(headerIconHtml);
      }
      const linkLabel = this.type === 'history' ? this.translate('History') : this.translate('Activities');
      const $link = $('<span>').text(linkLabel);
      $link.css('user-select', 'none');
      const $target = $('<span>').text(this.translate(this.foreignScope, 'scopeNamesPlural'));
      $target.css('user-select', 'none').css('cursor', 'pointer').attr('data-action', 'fullRefresh').attr('title', this.translate('clickToRefresh', 'messages'));
      return this.buildHeaderHtml([$root, $name, $link, $target]);
    }

    /**
     * @inheritDoc
     */
    updatePageTitle() {
      this.setPageTitle(this.translate(this.foreignScope, 'scopeNamesPlural'));
    }
  }
  _exports.default = ActivitiesListView;
});
//# sourceMappingURL=list.js.map ;