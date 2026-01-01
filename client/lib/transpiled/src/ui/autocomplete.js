define("ui/autocomplete", ["exports", "jquery", "handlebars"], function (_exports, _jquery, _handlebars) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _jquery = _interopRequireDefault(_jquery);
  _handlebars = _interopRequireDefault(_handlebars);
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
   * An autocomplete.
   */
  class Autocomplete {
    /** @module ui/autocomplete */

    /**
     * @typedef {Object} module:ui/autocomplete~item
     * @property {string} value
     */

    /**
     * @typedef {{
     *     name?: string,
     *     forceHide?: boolean,
     *     lookup?: string[],
     *     lookupFunction?: function (string): Promise<Array<module:ui/autocomplete~item & Record>>,
     *     minChars?: Number,
     *     formatResult?: function (module:ui/autocomplete~item & Record): string,
     *     onSelect?: function (module:ui/autocomplete~item & Record): void,
     *     beforeRender?: function (HTMLElement): void,
     *     triggerSelectOnValidInput?: boolean,
     *     autoSelectFirst?: boolean,
     *     handleFocusMode?: 1|2|3,
     *     focusOnSelect?: boolean,
     *     catchFastEnter?: boolean,
     * }} module:ui/autocomplete~options
     */

    /**
     * @param {HTMLInputElement} element
     * @param {module:ui/autocomplete~options} options
     */
    constructor(element, options) {
      /** @private */
      this.$element = (0, _jquery.default)(element);
      let deferredEnter = false;
      let catchEnter = false;
      let catchEnterTimeout = null;
      this.$element.on('keydown', e => {
        if (e.code === 'Tab' && !this.$element.val()) {
          e.stopImmediatePropagation();
        }

        // Scanner input.
        if (options.catchFastEnter) {
          if (e.code !== 'Enter') {
            catchEnter = true;
            if (catchEnterTimeout) {
              clearTimeout(catchEnterTimeout);
            }
            catchEnterTimeout = setTimeout(() => catchEnter = false, 40);
          }
          if (catchEnter && e.code === 'Enter' && this.$element.val()) {
            deferredEnter = true;
          } else {
            deferredEnter = false;
          }
        }
      });
      const lookup = options.lookupFunction ? (query, done) => {
        options.lookupFunction(query).then(items => {
          done({
            suggestions: items
          });
        });
      } : options.lookup;
      const lookupFilter = !options.lookupFunction ? (/** (module:ui/autocomplete~item */suggestion, /** string */query, /** string */queryLowerCase) => {
        if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
          return suggestion.value.length !== queryLowerCase.length;
        }
        return false;
      } : undefined;
      const $modalBody = this.$element.closest('.modal-body');
      const isModal = !!$modalBody.length;
      this.$element.autocomplete({
        beforeRender: $container => {
          if (options.beforeRender) {
            options.beforeRender($container.get(0));
          }
          if (this.$element.hasClass('input-sm')) {
            $container.addClass('small');
          }
          if (options.forceHide) {
            // Prevent an issue that suggestions are shown and not hidden
            // when clicking outside the window and then focusing back on the document.
            if (this.$element.get(0) !== document.activeElement) {
              setTimeout(() => this.$element.autocomplete('hide'), 30);
            }
          }
          if (isModal) {
            // Fixes dropdown dissapearing when clicking scrollbar.
            $container.on('mousedown', e => {
              e.preventDefault();
            });
          }
          if (deferredEnter) {
            setTimeout(() => {
              element.dispatchEvent(new KeyboardEvent("keydown", {
                key: 'Enter',
                code: 'Enter',
                keyCode: 13,
                which: 13,
                bubbles: true,
                cancelable: true
              }));
            }, 100);
          }
          catchEnter = false;
          deferredEnter = false;
        },
        lookup: lookup,
        minChars: options.minChars || 0,
        noCache: true,
        autoSelectFirst: options.autoSelectFirst,
        appendTo: $modalBody.length ? $modalBody : 'body',
        forceFixPosition: true,
        maxHeight: 308,
        formatResult: item => {
          if (options.formatResult) {
            return options.formatResult(item);
          }
          return _handlebars.default.Utils.escapeExpression(item.value);
        },
        lookupFilter: lookupFilter,
        onSelect: item => {
          if (options.onSelect) {
            options.onSelect(item);
          }
          if (options.focusOnSelect) {
            this.$element.focus();
          }
          catchEnter = false;
          deferredEnter = false;
        },
        triggerSelectOnValidInput: options.triggerSelectOnValidInput || false
      });
      this.$element.attr('autocomplete', 'espo-' + (options.name || 'dummy'));
      if (options.handleFocusMode) {
        this.initHandleFocus(options);
      }
    }

    /**
     * @private
     * @param {module:ui/autocomplete~options} options
     */
    initHandleFocus(options) {
      this.$element.off('focus.autocomplete');
      this.$element.on('focus', () => {
        if (options.handleFocusMode === 1) {
          if (this.$element.val()) {
            return;
          }
          this.$element.autocomplete('onValueChange');
          return;
        }
        if (this.$element.val()) {
          // noinspection JSUnresolvedReference
          this.$element.get(0).select();
          return;
        }
        this.$element.autocomplete('onFocus');
      });
      if (options.handleFocusMode === 3) {
        this.$element.on('change', () => this.$element.val(''));
      }
    }

    /**
     * Dispose.
     */
    dispose() {
      this.$element.autocomplete('dispose');
    }

    /**
     * Hide.
     */
    hide() {
      this.$element.autocomplete('hide');
    }

    /**
     * Clear.
     */
    clear() {
      this.$element.autocomplete('clear');
    }
  }
  var _default = _exports.default = Autocomplete;
});
//# sourceMappingURL=autocomplete.js.map ;