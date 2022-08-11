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

define('views/fields/multi-enum', ['views/fields/array', 'helpers/reg-exp-pattern', 'ui/multi-select'],
function (Dep, RegExpPattern, /** module:ui/multi-select*/MultiSelect) {

    /**
     * A multi-enum field.
     *
     * @class
     * @name Class
     * @extends module:views/fields/array.Class
     * @memberOf module:views/fields/multi-enum
     */
    return Dep.extend(/** @lends module:views/fields/multi-enum.Class# */{

        type: 'multiEnum',

        listTemplate: 'fields/array/list',

        detailTemplate: 'fields/array/detail',

        editTemplate: 'fields/multi-enum/edit',

        /**
         * @const
         */
        MAX_ITEM_LENGTH: 100,

        /**
         * @protected
         * @type {boolean}
         */
        restoreOnBackspace: false,

        events: {},

        /**
         * @inheritDoc
         */
        data: function () {
            return _.extend({
                optionList: this.params.options || []
            }, Dep.prototype.data.call(this));
        },

        getTranslatedOptions: function () {
            return (this.params.options || []).map((item) => {
                if (this.translatedOptions !== null) {
                    if (item in this.translatedOptions) {
                        return this.translatedOptions[item];
                    }
                }

                return item;
            });
        },

        translateValueToEditLabel: function (value) {
            var label = value;

            if (~(this.params.options || []).indexOf(value)) {
                label = this.getLanguage().translateOption(value, this.name, this.scope);
            }

            if (this.translatedOptions) {
                if (value in this.translatedOptions) {
                    label = this.translatedOptions[value];
                }
            }

            if (label === '') {
                label = this.translate('None');
            }

            return label;
        },

        afterRender: function () {
            if (this.isEditMode()) {
                this.$element = this.$el.find('[data-name="' + this.name + '"]');

                let items = [];

                let valueList = Espo.Utils.clone(this.selected);

                for (let i in valueList) {
                    var value = valueList[i];
                    var originalValue = value;

                    if (value === '') {
                        value = valueList[i] = '__emptystring__';
                    }

                    if (!~(this.params.options || []).indexOf(value)) {
                        items.push({
                            value: value,
                            label: this.translateValueToEditLabel(originalValue),
                        });
                    }
                }

                this.$element.val(valueList.join(this.itemDelimiter));

                (this.params.options || []).forEach(value => {
                    let originalValue = value;

                    if (value === '') {
                        value = '__emptystring__';
                    }

                    items.push({
                        value: value,
                        label: this.translateValueToEditLabel(originalValue),
                    });
                });

                /** @type {module:ui/multi-select~Options} */
                let multiSelectOptions = {
                    items: items,
                    delimiter: this.itemDelimiter,
                    matchAnyWord: this.matchAnyWord,
                    draggable: true,
                    allowCustomOptions: this.allowCustomOptions,
                    create: input => this.createCustomOptionCallback(input),
                };

                MultiSelect.init(this.$element, multiSelectOptions);

                this.$element.on('change', () => {
                    this.trigger('change');
                });
            }

            if (this.isSearchMode()) {
                this.renderSearch();
            }
        },

        /**
         * @protected
         * @param {string} input
         * @return {{label: string, value: string}|null}
         */
        createCustomOptionCallback: function (input) {
            if (input.length > this.MAX_ITEM_LENGTH) {
                let message = this.translate('arrayItemMaxLength', 'messages')
                    .replace('{max}', this.MAX_ITEM_LENGTH.toString())

                this.showValidationMessage(message, '.selectize-control')

                return null;
            }

            if (this.params.pattern) {
                /** @type module:helpers/reg-exp-pattern.Class */
                let helper = new RegExpPattern(this.getMetadata(), this.getLanguage());

                let result = helper.validate(this.params.pattern, input, this.name, this.entityType);

                if (result) {
                    this.showValidationMessage(result.message, '.selectize-control')

                    return null;
                }
            }

            return {
                value: input,
                label: input,
            };
        },

        focusOnInlineEdit: function () {
            this.$element[0].selectize.focus();
        },

        fetch: function () {
            let list = this.$element.val().split(this.itemDelimiter);

            if (list.length === 1 && list[0] === '') {
                list = [];
            }

            for (let i in list) {
                if (list[i] === '__emptystring__') {
                    list[i] = '';
                }
            }

            if (this.params.isSorted && this.translatedOptions) {
                list = list.sort((v1, v2) => {
                     return (this.translatedOptions[v1] || v1)
                         .localeCompare(this.translatedOptions[v2] || v2);
                });
            }

            let data = {};

            data[this.name] = list;

            return data;
        },

        validateRequired: function () {
            if (this.isRequired()) {
                let value = this.model.get(this.name);

                if (!value || value.length === 0) {
                    let msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg, '.selectize-control');

                    return true;
                }
            }
        },

        validateMaxCount: function () {
            if (this.params.maxCount) {
                let itemList = this.model.get(this.name) || [];

                if (itemList.length > this.params.maxCount) {
                    let msg =
                        this.translate('fieldExceedsMaxCount', 'messages')
                            .replace('{field}', this.getLabelText())
                            .replace('{maxCount}', this.params.maxCount.toString());

                    this.showValidationMessage(msg, '.selectize-control');

                    return true;
                }
            }
        },
    });
});
