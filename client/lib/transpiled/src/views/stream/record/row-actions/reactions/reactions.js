define("views/stream/record/row-actions/reactions/reactions", ["exports", "view"], function (_exports, _view) {
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

  class ReactionsRowActionView extends _view.default {
    // language=Handlebars
    templateContent = `
        <div class="item-icon-grid">
            {{#each reactions}}
                <a
                    role="button"
                    {{#if isReacted}}
                        data-action="unReact"
                    {{else}}
                        data-action="react"
                    {{/if}}
                    data-type="{{type}}"
                    title="{{label}}"
                    class=" {{#if isReacted}} text-primary {{else}} text-soft {{/if}}"
                ><span class="{{iconClass}}"></span></a>
            {{/each}}
        </div>
    `;
    /**
     * @param {{
     *     reactions: {
     *         type: string,
     *         iconClass: string|null,
     *         label: string,
     *         isReacted: boolean,
     *     }[]
     * }} options
     */
    constructor(options) {
      super(options);
      this.reactions = options.reactions;
    }
    data() {
      return {
        reactions: this.reactions
      };
    }
  }
  _exports.default = ReactionsRowActionView;
});
//# sourceMappingURL=reactions.js.map ;