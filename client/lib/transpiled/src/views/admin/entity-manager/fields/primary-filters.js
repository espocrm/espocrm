define("views/admin/entity-manager/fields/primary-filters", ["exports", "views/fields/array"], function (_exports, _array) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _array = _interopRequireDefault(_array);
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

  class EntityManagerPrimaryFiltersFieldView extends _array.default {
    // language=Handlebars
    detailTemplateContent = `
        {{#unless isEmpty}}
            <table class="table table-bordered">
                <tbody>
                    {{#each dateList}}
                        <tr>
                            <td style="width: 42%">{{name}}</td>
                            <td style="width: 42%">{{label}}</td>
                            <td style="width: 16%; text-align: center;">
                                <a
                                    role="button"
                                    data-action="copyToClipboard"
                                    data-name="{{name}}"
                                    class="text-soft"
                                    title="{{translate 'Copy to Clipboard'}}"
                                ><span class="far fa-copy"></span></a>
                            </td>
                        </tr>
                    {{/each}}
                </tbody>
            </table>
        {{else}}
            {{#if valueIsSet}}
                <span class="none-value">{{translate 'None'}}</span>
            {{else}}
                <span class="loading-value"></span>
            {{/if}}
        {{/unless}}
    `;

    // noinspection JSCheckFunctionSignatures
    data() {
      // noinspection JSValidateTypes
      return {
        ...super.data(),
        dateList: this.getValuesItems()
      };
    }
    constructor(options) {
      super(options);
      this.targetEntityType = options.targetEntityType;
    }
    getValuesItems() {
      return (this.model.get(this.name) || []).map(/** string */item => {
        return {
          name: item,
          label: this.translate(item, 'presetFilters', this.targetEntityType)
        };
      });
    }
    setup() {
      super.setup();
      this.addActionHandler('copyToClipboard', (e, target) => this.copyToClipboard(target.dataset.name));
    }

    /**
     * @private
     * @param {string} name
     */
    copyToClipboard(name) {
      const urlPart = `#${this.targetEntityType}/list/primaryFilter=${name}`;
      navigator.clipboard.writeText(urlPart).then(() => {
        const msg = this.translate('urlHashCopiedToClipboard', 'messages', 'EntityManager').replace('{name}', name);
        Espo.Ui.notify(msg, 'success', undefined, {
          closeButton: true
        });
      });
    }
  }
  var _default = _exports.default = EntityManagerPrimaryFiltersFieldView;
});
//# sourceMappingURL=primary-filters.js.map ;