define("views/fields/url-multiple", ["exports", "views/fields/array"], function (_exports, _array) {
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

  /**
   * An Url-Multiple field.
   */
  class UrlMultipleFieldView extends _array.default {
    type = 'urlMultiple';
    maxItemLength = 255;
    displayAsList = true;
    defaultProtocol = 'https:';
    setup() {
      super.setup();
      this.noEmptyString = true;
      this.params.pattern = '$uriOptionalProtocol';
    }
    addValueFromUi(value) {
      value = value.trim();
      if (this.params.strip) {
        value = this.strip(value);
      }
      try {
        if (value === decodeURI(value)) {
          value = encodeURI(value);
        }
      } catch (e) {
        console.warn(`Malformed URI ${value}.`);
      }
      super.addValueFromUi(value);
    }

    /**
     * @private
     * @param {string} value
     * @return {string}
     */
    decodeURI(value) {
      try {
        return decodeURI(value);
      } catch (e) {
        console.warn(`Malformed URI ${value}.`);
        return value;
      }
    }

    /**
     * @param {string} value
     * @return {string}
     */
    strip(value) {
      if (value.indexOf('//') !== -1) {
        value = value.substring(value.indexOf('//') + 2);
      }
      value = value.replace(/\/+$/, '');
      return value;
    }
    prepareUrl(url) {
      if (url.indexOf('//') === -1) {
        url = this.defaultProtocol + '//' + url;
      }
      return url;
    }
    getValueForDisplay() {
      /** @type {JQuery[]} */
      const $list = this.selected.map(value => {
        return $('<a>').attr('href', this.prepareUrl(value)).attr('target', '_blank').text(this.decodeURI(value));
      });
      return $list.map($item => $('<div>').addClass('multi-enum-item-container').append($item).get(0).outerHTML).join('');
    }
    getItemHtml(value) {
      const html = super.getItemHtml(value);
      const $item = $(html);
      $item.find('span.text').html($('<a>').attr('href', this.prepareUrl(value)).css('user-drag', 'none').attr('target', '_blank').text(this.decodeURI(value)));
      return $item.get(0).outerHTML;
    }
  }
  var _default = _exports.default = UrlMultipleFieldView;
});
//# sourceMappingURL=url-multiple.js.map ;