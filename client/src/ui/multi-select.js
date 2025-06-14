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

/** @module module:ui/multi-select */

import Selectize from 'lib!selectize';

/**
 * @typedef module:ui/multi-select~Options
 * @type {Object}
 * @property {{value: string, text: string, style?: string, small?: boolean}[]} items
 * @property {string} [delimiter=':,:']
 * @property {boolean} [restoreOnBackspace=false]
 * @property {boolean} [removeButton=true]
 * @property {boolean} [draggable=false]
 * @property {boolean} [selectOnTab=false]
 * @property {boolean} [matchAnyWord=false]
 * @property {boolean} [allowCustomOptions=false]
 * @property {function (string): {value: string, text: string}|null} [create]
 * @property {string[]} [values] Values. As of v9.1.0.
 */

/**
 * @module ui/multi-select
 */
const MultiSelect = {

    /**
     * @const
     */
    defaultDelimiter: ':,:',

    /**
     * @param {Element|JQuery} element An element.
     * @param {module:ui/multi-select~Options} options Options.
     */
    init: function (element, options) {
        const $el = $(element);

        const delimiter = options.delimiter || this.defaultDelimiter;

        if (options.values) {
            $el.val(options.values.join(delimiter));
        }

        options = MultiSelect.applyDefaultOptions(options);

        const plugins = {};

        if (options.removeButton) {
            plugins.remove_button = {title: ''};
        }

        if (options.draggable) {
            plugins.drag_drop = {};
        }

        if (options.restoreOnBackspace) {
            MultiSelect.loadRestoreOnBackspacePlugin();

            plugins.restore_on_backspace_espo = {};
        }

        MultiSelect.loadBypassCtrlEnterPlugin();

        plugins.bypass_ctrl_enter = {};

        const selectizeOptions = {
            options: options.items,
            plugins: plugins,
            delimiter: delimiter,
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

        selectizeOptions.render = {};

        if (options.allowCustomOptions) {
            selectizeOptions.persist = false;
            selectizeOptions.create = options.create;

            selectizeOptions.render.option_create = data => {
                return $('<div>')
                    .addClass('create')
                    .append(
                        $('<span>')
                            .text(data.input)
                            .addClass('text-bold')
                    )
                    .append('&hellip;')
                    .get(0).outerHTML;
            }
        }

        const classMap = {};
        const styleMap = {};

        (options.items || []).forEach(it => {
            if (it.small) {
                classMap[it.value] = 'small';
            }

            if (it.style) {
                styleMap[it.value] = it.style;
            }

        });


        (options.items || []).forEach(it => {
            if (it.small) {
                classMap[it.value] = 'small';
            }
        });

        selectizeOptions.render.item = (/** {text: string, value: string} */data, escape) => {
            const text = escape(data.text);
            const style = escape(styleMap[data.value] || '');
            const classString = escape(classMap[data.value] || '');

            return `<div class="item ${style}"><span class="${classString}">${text}</span> ` +
                `<a href="javascript:" class="remove">&times;</a></div>`;
        };

        selectizeOptions.render.option = (/** {text: string, value: string} */data, escape) => {
            const value = data.value;

            let classes = '';

            if (value === '') {
                classes += ' selectize-dropdown-emptyoptionlabel';
            }

            const style = styleMap[data.value];

            if (style) {
                classes += ' ' + escape('text-' + style);
            }

            const text = escape(data.text);

            return `<div class="option ${classes}">${text}</div>`;
        }

        $el.selectize(selectizeOptions);

        if ($el[0]) {
            // Check for compatibility with existing code where initialing is called against non-existing element.

            $el[0].selectize.on('item_before_remove', (v, $item) => {
                // Otherwise, the item is left active.
                $item.removeClass('active');
            });
        }
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
            delimiter: MultiSelect.defaultDelimiter,
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
