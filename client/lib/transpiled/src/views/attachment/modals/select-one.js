define("views/attachment/modals/select-one", ["exports", "views/modal"], function (_exports, _modal) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _modal = _interopRequireDefault(_modal);
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

  class SelectOneAttachmentModalView extends _modal.default {
    backdrop = true;

    // language=Handlebars
    templateContent = `<ul class="list-group no-side-margin">
            {{#each viewObject.options.dataList}}
                <li
                    class="list-group-item"
                ><a
                    role="button"
                    class="action"
                    data-action="select"
                    data-id="{{id}}"
                >{{name}}</a></li>
            {{/each}}
        </ul>
        `;

    /**
     *
     * @param {{
     *     fieldLabel?: string,
     *     dataList: {id: string, name: string}[],
     *     onSelect: function(string),
     * }} options
     */
    constructor(options) {
      super(options);
      this.options = options;
    }
    setup() {
      this.headerText = this.translate('Select');
      if (this.options.fieldLabel) {
        this.headerText += ' · ' + this.options.fieldLabel;
      }
      this.addActionHandler('select', (e, target) => {
        this.options.onSelect(target.dataset.id);
        this.close();
      });
    }
  }
  _exports.default = SelectOneAttachmentModalView;
});
//# sourceMappingURL=select-one.js.map ;