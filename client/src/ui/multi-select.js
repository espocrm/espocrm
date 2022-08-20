/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('ui/multi-select', ['lib!Selectize'], (Selectize) => {

    /**
     * @typedef module:ui/multi-select~Options
     * @type {Object}
     * @property {{value: string, label: string}[]} items
     * @property {string} [delimiter=':,:']
     * @property {boolean} [restoreOnBackspace=false]
     * @property {boolean} [removeButton=true]
     * @property {boolean} [draggable=false]
     * @property {boolean} [selectOnTab=false]
     * @property {boolean} [matchAnyWord=false]
     * @property {boolean} [allowCustomOptions=false]
     * @property {function (string): {value: string, label: string}|null} create
     */

    /**
     * @module ui/multi-select
     */
    let MultiSelect = {
        /**
         * @param {JQuery} $el An element.
         * @param {module:ui/multi-select~Options} options Options.
         */
        init: function ($el, options) {
            options = MultiSelect.applyDefaultOptions(options);

            let plugins = [];

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

            let selectizeOptions = {
                options: options.items,
                plugins: plugins,
                delimiter: options.delimiter,
                labelField: 'label',
                valueField: 'value',
                searchField: ['label'],
                highlight: false,
                selectOnTab: options.selectOnTab,
            };

            if (!options.matchAnyWord) {
                /** @this Selectize */
                selectizeOptions.score = function (search) {
                    let score = this.getScoreFunction(search);

                    search = search.toLowerCase();

                    return function (item) {
                        if (item.label.toLowerCase().indexOf(search) === 0) {
                            return score(item);
                        }

                        return 0;
                    };
                };
            }

            if (options.allowCustomOptions) {
                selectizeOptions.persist = false;
                selectizeOptions.create = options.create;
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
         * @private
         * @param {module:ui/multi-select~Options} options
         * @return {module:ui/multi-select~Options}
         */
        applyDefaultOptions: function (options) {
            options = Espo.Utils.clone(options);

            let defaults = {
                removeButton: true,
                draggable: false,
                selectOnTab: false,
                delimiter: ':,:',
                matchAnyWord: false,
                allowCustomOptions: false,
            };

            for (let key in defaults) {
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

            Selectize.define('bypass_ctrl_enter', function (options) {
                let self = this;

                this.onKeyDown = (function() {
                    let original = self.onKeyDown;

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

                let self = this;

                this.onKeyDown = (function() {
                    let original = self.onKeyDown;

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
                                    label: option.value,
                                };

                                if (this.deleteSelection(e)) {
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

    return MultiSelect;
});
