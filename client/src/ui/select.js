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

/** @module module:ui/select */

import Selectize from 'lib!selectize';

/**
 * @typedef module:ui/select~Options
 * @type {Object}
 * @property {boolean} [selectOnTab=false] To select on tab.
 * @property {boolean} [matchAnyWord=false] To match any word when searching.
 * @property {function(string, module:ui/select~OptionItemsCallback): void} [load] Loads additional items
 *   when typing in search.
 * @property {function(string, module:ui/select~OptionItemFull): Number} [score] A score function scoring
 *   searched items.
 * @property {'value'|'text'|'$order'|'$score'} [sortBy='$order'] Item sorting.
 * @property {'asc'|'desc'} [sortDirection='asc'] Sort direction.
 * @property {function()} [onFocus] On-focus callback.
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
 * @typedef module:ui/select~OptionItemFull
 * @type {Object}
 * @property {string} value A value.
 * @property {string} text A label.
 * @property {Number} $order An order index.
 */

/**
 * @module ui/select
 *
 * Important. The Selectize library is heavily customized to fix multitude of UIX issues.
 * Upgrading is not advisable. Consider forking.
 */
const Select = {
    /**
     * @param {Element|JQuery} element An element.
     * @param {module:ui/select~Options} [options] Options.
     */
    init: function (element, options = {}) {
        const score = options.score;
        const $el = $(element);

        options = Select.applyDefaultOptions(options || {});

        const plugins = [];

        Select.loadEspoSelectPlugin();

        plugins.push('espo_select');

        const itemClasses = {};

        const allowedValues = $el.children().toArray().map(item => {
            const value = item.getAttributeNode('value').value;

            if (item.classList) {
                itemClasses[value] = item.classList.toString();
            }

            return value;
        });

        let $relativeParent = null;

        const $modalBody = $el.closest('.modal-body');

        if ($modalBody.length) {
            $relativeParent = $modalBody;
        }

        // noinspection JSUnusedGlobalSymbols
        const selectizeOptions = {
            sortField: [{field: options.sortBy, direction: options.sortDirection}],
            load: options.load,
            loadThrottle: 1,
            plugins: plugins,
            highlight: false,
            selectOnTab: options.selectOnTab,
            copyClassesToDropdown: false,
            allowEmptyOption: allowedValues.includes(''),
            showEmptyOptionInDropdown: true,
            $relativeParent: $relativeParent,
            render: {
                item: function (data) {
                    return $('<div>')
                        .addClass('item')
                        .addClass(itemClasses[data.value] || '')
                        .text(data.text)
                        .get(0).outerHTML;
                },
                option: function (data) {
                    const $div = $('<div>')
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

                // noinspection JSUnresolvedReference
                this.showInput();
                this.positionDropdown();
                this.refreshOptions(true);
            },
        };

        if (options.onFocus) {
            selectizeOptions.onFocus = function () {
                options.onFocus();
            };
        }

        if (!options.matchAnyWord) {
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

        if (options.score) {

            selectizeOptions.score = function (search) {
                return function (item) {
                    return score(search, item);
                };
            };
        }

        $el.selectize(selectizeOptions);
    },

    /**
     * Focus.
     *
     * @param {Element|JQuery} element An element.
     * @param {{noTrigger?: boolean}} [options] Options.
     */
    focus: function (element, options) {
        const $el = $(element);

        options = options || {};

        if (
            !$el[0] ||
            !$el[0].selectize
        ) {
            return;
        }

        const selectize = $el[0].selectize;

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
     * @todo Side effects may occur if called multiple times. Workaround is to clone and re-initialize. To be fixed.
     *
     * @param {Element|JQuery} element An element.
     * @param {{value: string, text: string}[]} options Options.
     */
    setOptions: function (element, options) {
        const $el = $(element);

        const selectize = $el.get(0).selectize;

        selectize.clearOptions(true);
        selectize.load(callback => {
            callback(
                options.map(item => {
                    return {
                        value: item.value,
                        text: item.text || item.label,
                    };
                })
            );
        });
    },

    /**
     * Set value.
     *
     * @param {HTMLElement|JQuery} element An element.
     * @param {string} value A value.
     */
    setValue: function (element, value) {
        if (!(element instanceof HTMLElement)) {
            element = $(element).get(0);
        }

        const selectize = element.selectize;

        selectize.setValue(value, true);
    },

    /**
     * Destroy.
     *
     * @param {HTMLElement|JQuery} element An element.
     */
    destroy: function (element) {
        if (!element) {
            return;
        }

        if (!(element instanceof HTMLElement)) {
            element = $(element).get(0);
        }

        if (!element || !element.selectize) {
            return;
        }

        element.selectize.destroy();
    },

    /**
     * @private
     * @param {module:ui/select~Options} options
     * @return {module:ui/select~Options}
     */
    applyDefaultOptions: function (options) {
        options = Espo.Utils.clone(options);

        const defaults = {
            selectOnTab: false,
            matchAnyWord: false,
            sortBy: '$order',
            sortDirection: 'asc',
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
    loadEspoSelectPlugin: function () {
        if ('espo_select' in Selectize.plugins) {
            return;
        }

        const IS_MAC = /Mac/.test(navigator.userAgent);
        const KEY_BACKSPACE = 8;

        Selectize.define('espo_select', function () {
            const self = this;

            this.setup = (function () {
                const original = self.setup;

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


                    self.$control_input.css({'width': '4px'});
                };
            })();

            this.focusOnControlSilently = function () {
                self.preventReOpenOnFocus = true;
                self.$control_input[0].focus();
                self.preventReOpenOnFocus = false;
            };

            /*this.positionDropdown = (function () {
                let original = self.positionDropdown;

                return function () {
                    original.apply(this, arguments);

                    this.$dropdown.css({margin: 'unset'});
                };
            })();*/

            this.refreshOptions = (function () {
                const original = self.refreshOptions;

                return function () {
                    if (self.focusNoTrigger) {
                        original.apply(this, [false]);
                        return;
                    }

                    original.apply(this, arguments);
                };
            })();

            this.blur = (function () {
                const original = self.blur;

                return function () {
                    // Prevent closing on mouse down.
                    if (self.preventClose) {
                        return;
                    }

                    original.apply(this, arguments);
                };
            })();

            this.close = (function () {
                const original = self.close;

                return function () {
                    if (self.preventClose) {
                        return;
                    }

                    original.apply(this, arguments);
                };
            })();

            this.onOptionSelect = (function () {
                const original = self.onOptionSelect;

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
                const original = self.open;

                return function () {
                    const toProcess = !(self.isLocked || self.isOpen);

                    original.apply(this, arguments);

                    if (!toProcess) {
                        return;
                    }

                    const $dropdownContent = self.$dropdown.children().first();
                    const $selected = $dropdownContent.find('.selected');

                    if (!$selected.length) {
                        return;
                    }

                    let scrollTo = $selected.get(0).offsetTop - $dropdownContent.get(0).clientHeight;
                    scrollTo = scrollTo >= 0 ? scrollTo : 0;

                    $dropdownContent
                        .find('.selectize-dropdown-content')
                        .scrollTop(scrollTo);
                };
            })();

            this.onMouseDown = (function() {
                const original = self.onMouseDown;

                return function (e) {
                    // Prevent flicking when clicking on input.
                    if (!self.isOpen && !self.isInputHidden && self.$control_input.val()) {
                        return;
                    }

                    if (self.isOpen) {
                        self.closedByMouseDown = true;
                    }

                    return original.apply(this, arguments);
                };
            })();

            this.onFocus = (function() {
                const original = self.onFocus;

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
                const original = self.onBlur;

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
                const original = self.onKeyDown;

                return function (e) {
                    if (IS_MAC ? e.metaKey : e.ctrlKey) {
                        if (!self.items.length) {
                            self.restoreSelectedValue();
                            self.focus();
                        }

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
                                e.code.match(/Key[A-Z]/i) ||
                                e.key.match(/[0-9]/) ||
                                RegExp(/^\p{L}/, 'u').test(e.key) // is letter
                            )
                        ) {
                            const keyCode = e.keyCode;
                            e.keyCode = KEY_BACKSPACE;
                            self.deleteSelection(e);
                            e.keyCode = keyCode;

                            self.$control_input.width(15);
                        }
                    }

                    return original.apply(this, arguments);
                };
            })();

            this.positionDropdown = (function() {
                const POSITION = {
                    top: 'top',
                    bottom: 'bottom',
                };

                return function() {
                    const $control = self.$control;

                    const offset = this.settings.dropdownParent === 'body' ?
                        $control.offset() :
                        $control.position();

                    offset.top += $control.outerHeight(true);

                    const dropdownHeight = self.$dropdown.prop('scrollHeight') + 5;
                    const controlPosTop = self.$control.get(0).getBoundingClientRect().top;
                    const wrapperHeight = self.$wrapper.height();

                    const controlPosBottom = self.$control.get(0).getBoundingClientRect().bottom;

                    const boundaryTop = !this.settings.$relativeParent ? 0 :
                        this.settings.$relativeParent.get(0).getBoundingClientRect().top;

                    const position =
                        controlPosTop + dropdownHeight + wrapperHeight > window.innerHeight &&
                        controlPosBottom - dropdownHeight - wrapperHeight >= boundaryTop ?
                            POSITION.top :
                            POSITION.bottom;

                    const styles = {
                        width: $control.outerWidth(),
                        left: offset.left,
                    };

                    if (position === POSITION.top) {
                        Object.assign(styles, {
                            bottom: offset.top,
                            top: 'unset',
                            margin: '0 0 0 0',
                        });

                        self.$dropdown.addClass('selectize-position-top');
                    } else {
                        Object.assign(styles, {
                            top: offset.top,
                            bottom: 'unset',
                            margin: '0 0 0 0',
                        });

                        self.$dropdown.removeClass('selectize-position-top');
                    }

                    self.$dropdown.css(styles);
                }
            })();
        });
    },
};

export default Select;
