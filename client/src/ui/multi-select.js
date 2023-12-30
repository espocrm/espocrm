/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module module:ui/multi-select */

import Selectize from 'lib!selectize';

/**
 * @typedef module:ui/multi-select~Options
 * @type {Object}
 * @property {{value: string, text: string}[]} items
 * @property {string} [delimiter=':,:']
 * @property {boolean} [restoreOnBackspace=false]
 * @property {boolean} [removeButton=true]
 * @property {boolean} [draggable=false]
 * @property {boolean} [selectOnTab=false]
 * @property {boolean} [matchAnyWord=false]
 * @property {boolean} [allowCustomOptions=false]
 * @property {function (string): {value: string, text: string}|null} [create]
 */

/**
 * @module ui/multi-select
 */
const MultiSelect = {
    /**
     * @param {Element|JQuery} element An element.
     * @param {module:ui/multi-select~Options} options Options.
     */
    init: function (element, options) {
        const $el = $(element);

        options = MultiSelect.applyDefaultOptions(options);

        const plugins = [];

        if (options.removeButton) {
            plugins.push('remove_button');
        }

        if (options.draggable) {
            plugins.push('drag_drop');
        }

        if (options.restoreOnBackspace) {
            MultiSelect.loadRestoreOnBackspacePlugin();
            plugins.push('restore_on_backspace_espo')
        }

        MultiSelect.loadBypassCtrlEnterPlugin();
        plugins.push('bypass_ctrl_enter');

        const selectizeOptions = {
            options: options.items,
            plugins: plugins,
            delimiter: options.delimiter,
            labelField: 'text',
            valueField: 'value',
            searchField: ['text'],
            highlight: false,
            selectOnTab: options.selectOnTab,
        };

        if (!options.matchAnyWord) {
            // noinspection JSUnresolvedReference
            /** @this Selectize */
            selectizeOptions.score = function (search) {
                // noinspection JSUnresolvedReference
                const score = this.getScoreFunction(search);

                search = search.toLowerCase();

                return function (item) {
                    if (item.text.toLowerCase().indexOf(search) === 0) {
                        return score(item);
                    }

                    return 0;
                };
            };
        }

        if (options.matchAnyWord) {
            /** @this Selectize */
            selectizeOptions.score = function (search) {
                // noinspection JSUnresolvedReference
                const score = this.getScoreFunction(search);

                search = search.toLowerCase();

                return function (item) {
                    const text = item.text.toLowerCase();

                    if (
                        !text.split(' ').find(item => item.startsWith(search)) &&
                        !text.startsWith(search)
                    ) {
                        return 0;
                    }

                    return score(item);
                };
            };
        }

        if (options.allowCustomOptions) {
            selectizeOptions.persist = false;
            selectizeOptions.create = options.create;
            // noinspection JSUnusedGlobalSymbols
            selectizeOptions.render = {
                option_create: data => {
                    return $('<div>')
                        .addClass('create')
                        .append(
                            $('<span>')
                                .text(data.input)
                                .addClass('text-bold')
                        )
                        .append('&hellip;')
                        .get(0).outerHTML;
                },
            };
        }

        $el.selectize(selectizeOptions);
    },

    /**
     * Focus.
     *
     * @param {Element|JQuery} element An element.
     */
    focus: function (element) {
        const $el = $(element);

        if (
            !$el[0] ||
            !$el[0].selectize
        ) {
            return;
        }

        const selectize = $el[0].selectize;

        selectize.focus();
    },

    /**
     * @private
     * @param {module:ui/multi-select~Options} options
     * @return {module:ui/multi-select~Options}
     */
    applyDefaultOptions: function (options) {
        options = Espo.Utils.clone(options);

        const defaults = {
            removeButton: true,
            draggable: false,
            selectOnTab: false,
            delimiter: ':,:',
            matchAnyWord: false,
            allowCustomOptions: false,
        };

        for (const key in defaults) {
            if (key in options) {
                continue;
            }

            options[key] = defaults[key];
        }

        return options;
    },

    /**
     * @private
     */
    loadBypassCtrlEnterPlugin: function () {
        if ('bypass_ctrl_enter' in Selectize.plugins) {
            return;
        }

        const IS_MAC = /Mac/.test(navigator.userAgent);

        Selectize.define('bypass_ctrl_enter', function () {
            const self = this;

            this.onKeyDown = (function() {
                const original = self.onKeyDown;

                return function (e) {
                    if (e.code === 'Enter' && (IS_MAC ? e.metaKey : e.ctrlKey)) {
                        return;
                    }

                    return original.apply(this, arguments);
                };
            })();
        });
    },

    /**
     * @private
     */
    loadRestoreOnBackspacePlugin: function () {
        if ('restore_on_backspace_espo' in Selectize.plugins) {
            return;
        }

        Selectize.define('restore_on_backspace_espo', function (options) {
            options.text = options.text || function (option) {
                return option[this.settings.labelField];
            };

            const self = this;

            this.onKeyDown = (function() {
                const original = self.onKeyDown;

                return function (e) {
                    let index, option;

                    if (
                        e.code === 'Backspace' &&
                        this.$control_input.val() === '' &&
                        !this.$activeItems.length
                    ) {
                        index = this.caretPos - 1;

                        if (index >= 0 && index < this.items.length) {
                            option = this.options[this.items[index]];

                            option = {
                                value: option.value,
                                $order: option.$order,
                                text: option.value,
                            };

                            // noinspection JSUnresolvedReference
                            if (this.deleteSelection(e)) {
                                // noinspection JSUnresolvedReference
                                this.setTextboxValue(options.text.apply(this, [option]));
                                this.refreshOptions(true);
                            }

                            e.preventDefault();

                            return;
                        }
                    }

                    return original.apply(this, arguments);
                };
            })();
        });
    },
};

export default MultiSelect;
