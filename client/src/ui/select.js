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

define('ui/select', ['lib!Selectize'], (Selectize) => {

    /**
     * @typedef module:ui/select~Options
     * @type {Object}
     * @property {boolean} [selectOnTab=false] To select on tab.
     * @property {boolean} [matchAnyWord=false] To match any word when searching.
     * @property {function(string, module:ui/select~OptionItemsCallback): void} [load] Loads additional items
     *   when typing in search.
     * @property {function(string, string, string): Number} [score] A score function scoring searched items.
     */

    /**
     * @callback  module:ui/select~OptionItemsCallback
     * @param {module:ui/select~OptionItem[]} list An option item list.
     */

    /**
     * @typedef module:ui/select~OptionItem
     * @type {Object}
     * @property {string} value A value.
     * @property {string} text A label.
     */

    /**
     * @module ui/select
     *
     * Important. Selectize library is heavily customized to fix multitude of UIX issues.
     * Upgrading is not advisable. Consider forking.
     */
    let Select = {
        /**
         * @param {JQuery} $el An element.
         * @param {module:ui/select~Options} [options] Options.
         */
        init: function ($el, options) {
            options = Select.applyDefaultOptions(options || {});

            let plugins = [];

            Select.loadEspoSelectPlugin();

            plugins.push('auto_position');
            plugins.push('espo_select');

            let itemClasses = {};

            let allowedValues = $el.children().toArray().map(item => {
                let value = item.getAttributeNode('value').value;

                if (item.classList) {
                    itemClasses[value] = item.classList.toString();
                }

                return value;
            });

            let selectizeOptions = {
                load: options.load,
                plugins: plugins,
                highlight: false,
                selectOnTab: options.selectOnTab,
                copyClassesToDropdown: false,
                allowEmptyOption: allowedValues.includes(''),
                showEmptyOptionInDropdown: true,
                render: {
                    item: function (data) {
                        return $('<div>')
                            .addClass('item')
                            .addClass(itemClasses[data.value] || '')
                            .text(data.text)
                            .get(0).outerHTML;
                    },
                    option: function (data) {
                        let $div = $('<div>')
                            .addClass('option')
                            .addClass(data.value === '' ? 'selectize-dropdown-emptyoptionlabel' : '')
                            .addClass(itemClasses[data.value] || '')
                            .val(data.value)
                            .text(data.text);

                        if (data.text === '') {
                            $div.html('&nbsp;');
                        }

                        return $div.get(0).outerHTML;
                    },
                },
                onDelete: function (values) {
                    while (values.length) {
                        this.removeItem(values.pop(), true);
                    }

                    this.showInput();
                    this.positionDropdown();
                    this.refreshOptions(true);
                },
            };

            if (!options.matchAnyWord) {
                /** @this Selectize */
                selectizeOptions.score = function (search) {
                    let score = this.getScoreFunction(search);

                    search = search.toLowerCase();

                    return function (item) {
                        if (item.text.toLowerCase().indexOf(search) === 0) {
                            return score(item);
                        }

                        return 0;
                    };
                };
            }

            if (options.score) {
                let score = options.score;

                selectizeOptions.score = function (search) {
                    return function (item) {
                        return score(search, item.value, item.text);
                    };
                };
            }

            $el.selectize(selectizeOptions);
        },

        /**
         * Focus.
         *
         * @param {JQuery} $el An element.
         * @param {{noTrigger?: boolean}} [options] Options.
         */
        focus: function ($el, options) {
            options = options || {};

            if (
                !$el[0] ||
                !$el[0].selectize
            ) {
                return;
            }

            let selectize = $el[0].selectize;

            if (options.noTrigger) {
                selectize.focusNoTrigger = true;
            }

            selectize.focus();

            if (options.noTrigger) {
                setTimeout(() => selectize.focusNoTrigger = false, 100);
            }
        },

        /**
         * Set options.
         *
         * @param {JQuery} $el An element.
         * @param {{value: string, label: string}[]} options Options.
         */
        setOptions: function ($el, options) {
            let selectize = $el.get(0).selectize;

            selectize.clearOptions(true);
            selectize.load(callback => {
                callback(
                    options.map(item => {
                        return {
                            value: item.value,
                            text: item.label,
                        };
                    })
                );
            });
        },

        /**
         * Set value.
         *
         * @param {JQuery} $el An element.
         * @param {string} value A value.
         */
        setValue: function ($el, value) {
            let selectize = $el.get(0).selectize;

            selectize.setValue(value, true);
        },

        /**
         * @private
         * @param {module:ui/select~Options} options
         * @return {module:ui/select~Options}
         */
        applyDefaultOptions: function (options) {
            options = Espo.Utils.clone(options);

            let defaults = {
                selectOnTab: false,
                matchAnyWord: false,
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
        loadEspoSelectPlugin: function () {
            if ('espo_select' in Selectize.plugins) {
                return;
            }

            const IS_MAC = /Mac/.test(navigator.userAgent);
            const KEY_BACKSPACE = 8;

            Selectize.define('espo_select', function () {
                let self = this;

                this.setup = (function () {
                    let original = self.setup;

                    return function () {
                        original.apply(this, arguments);

                        self.selectedValue = self.items[0];

                        self.$dropdown
                            .on('mouseup', '[data-selectable]', function () {
                                $(document).off('mouseup.select');

                                return self.onOptionSelect.apply(self, arguments);
                            });

                        self.$dropdown
                            .on('mousedown', '[data-selectable]', function () {
                                // Prevent issue when down inside, up outside.
                                $(document).one('mouseup.select', function () {
                                    self.focusOnControlSilently();
                                });
                            });
                    };
                })();

                this.focusOnControlSilently = function () {
                    self.preventReOpenOnFocus = true;
                    self.$control_input[0].focus();
                    self.preventReOpenOnFocus = false;
                };

                this.positionDropdown = (function () {
                    let original = self.positionDropdown;

                    return function () {
                        original.apply(this, arguments);

                        this.$dropdown.css({margin: 'unset'});
                    };
                })();

                this.refreshOptions = (function () {
                    let original = self.refreshOptions;

                    return function () {
                        if (self.focusNoTrigger) {
                            original.apply(this, [false]);
                            return;
                        }

                        original.apply(this, arguments);
                    };
                })();

                this.blur = (function () {
                    let original = self.blur;

                    return function () {
                        // Prevent closing on mouse down.
                        if (self.preventClose) {
                            return;
                        }

                        original.apply(this, arguments);
                    };
                })();

                this.close = (function () {
                    let original = self.close;

                    return function () {
                        if (self.preventClose) {
                            return;
                        }

                        original.apply(this, arguments);
                    };
                })();

                this.onOptionSelect = (function () {
                    let original = self.onOptionSelect;

                    return function (e) {
                        if (e.type === 'mousedown' || e.type === 'click') {
                            self.preventClose = true;
                            setTimeout(() => self.preventClose = false, 100);

                            return;
                        }

                        self.preventClose = false;

                        if (e.type === 'mouseup') {
                            setTimeout(() => self.focusOnControlSilently(), 50);
                        }

                        original.apply(this, arguments);

                        self.selectedValue = $(e.currentTarget).attr('data-value');
                    };
                })();

                this.open = (function() {
                    let original = self.open;

                    return function () {
                        let toProcess = !(self.isLocked || self.isOpen);

                        original.apply(this, arguments);

                        if (!toProcess) {
                            return;
                        }

                        let $selected = self.$dropdown.find('.selected');

                        if (!$selected.length) {
                            return;
                        }

                        self.$dropdown
                            .find('.selectize-dropdown-content')
                            .scrollTop($selected.get(0).offsetTop);
                    };
                })();

                this.onMouseDown = (function() {
                    let original = self.onMouseDown;

                    return function (e) {
                        // @todo Prevent flicking when clicking on input.
                        if (!self.isOpen && !self.isInputHidden) {
                        }

                        if (self.isOpen) {
                            self.closedByMouseDown = true;
                        }

                        return original.apply(this, arguments);
                    };
                })();

                this.onFocus = (function() {
                    let original = self.onFocus;

                    return function (e) {
                        if (self.preventReOpenOnFocus) {
                            return;
                        }

                        if (self.closedByMouseDown) {
                            self.closedByMouseDown = false;

                            return;
                        }

                        self.closedByMouseDown = false;

                        return original.apply(this, arguments);
                    };
                })();

                this.restoreSelectedValue = function () {
                    if (this.preventRevertLoop) {
                        return;
                    }

                    this.preventRevertLoop = true;
                    setTimeout(() => this.preventRevertLoop = false, 10);

                    this.setValue(this.selectedValue, true);
                };

                this.onBlur = (function() {
                    let original = self.onBlur;

                    return function () {
                        // Prevent closing on mouse down.
                        if (self.preventClose) {
                            return;
                        }

                        self.restoreSelectedValue();

                        self.$control_input.css({width: '4px'});

                        return original.apply(this, arguments);
                    };
                })();

                this.onKeyDown = (function() {
                    let original = self.onKeyDown;

                    return function (e) {
                        if (e.code === 'Enter' && (IS_MAC ? e.metaKey : e.ctrlKey)) {
                            return;
                        }

                        if (e.code === 'Escape') {
                            if (self.isOpen || !self.isInputHidden) {
                                e.stopPropagation();
                            }

                            if (self.isOpen) {
                                self.close();
                            }

                            if (!self.isInputHidden) {
                                self.hideInput();
                            }

                            self.addItem(this.selectedValue, true);
                        }

                        if (self.isFull() || self.isInputHidden) {
                            if (
                                e.key.length === 1 &&
                                (
                                    e.key.match(/[a-z]/i) ||
                                    e.key.match(/[0-9]/)
                                )
                            ) {
                                let keyCode = e.keyCode;
                                e.keyCode = KEY_BACKSPACE;
                                self.deleteSelection(e);
                                e.keyCode = keyCode;
                            }
                        }

                        return original.apply(this, arguments);
                    };
                })();
            });
        },
    };

    return Select;
});
