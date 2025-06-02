/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import $ from 'jquery';
import Handlebars from 'handlebars';

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
     * }} module:ui/autocomplete~options
     */

    /**
     * @param {HTMLInputElement} element
     * @param {module:ui/autocomplete~options} options
     */
    constructor(element, options) {
        /** @private */
        this.$element = $(element);

        this.$element.on('keydown', e => {
            if (e.code === 'Tab' && !this.$element.val()) {
                e.stopImmediatePropagation();
            }
        });

        const lookup = options.lookupFunction ?
            (query, done) => {
                options.lookupFunction(query)
                    .then(items => {
                        done({suggestions: items})
                    });
            } :
            options.lookup;

        const lookupFilter = !options.lookupFunction ?
            (/** (module:ui/autocomplete~item */suggestion, /** string */query, /** string */queryLowerCase) => {
                if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                    return suggestion.value.length !== queryLowerCase.length;
                }

                return false;
            } :
            undefined;

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

                return Handlebars.Utils.escapeExpression(item.value);
            },
            lookupFilter: lookupFilter,
            onSelect: item => {
                if (options.onSelect) {
                    options.onSelect(item);
                }

                if (options.focusOnSelect) {
                    this.$element.focus();
                }
            },
            triggerSelectOnValidInput: options.triggerSelectOnValidInput || false,
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

export default Autocomplete;
