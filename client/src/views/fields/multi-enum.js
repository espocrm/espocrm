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

/** @module views/fields/multi-enumeration */

import ArrayFieldView from 'views/fields/array';
import RegExpPattern from 'helpers/reg-exp-pattern';
import MultiSelect from 'ui/multi-select';

/**
 * A multi-enum field.
 */
class MultiEnumFieldView extends ArrayFieldView {

    /**
     * @typedef {Object} module:views/fields/multi-enumeration~options
     * @property {
     *     module:views/fields/multi-enumeration~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/multi-enumeration~params
     * @property {string} [translation] A translation string. E.g. `Global.scopeNames`.
     * @property {string[]} [options] Select options.
     * @property {boolean} [required] Required.
     * @property {boolean} [displayAsLabel] Display as label.
     * @property {boolean} [displayAsList] Display as list (line breaks).
     * @property {string|'state'} [labelType] A label type.
     * @property {string} [optionsReference] A reference to options. E.g. `Account.industry`.
     * @property {string} [optionsPath] An options metadata path.
     * @property {boolean} [isSorted] To sort options.
     * @property {Object.<string, string>} [translatedOptions] Option translations.
     * @property {Object.<string, 'warning'|'danger'|'success'|'info'|'primary'>} [style] A style map.
     * @property {number} [maxCount] A max number of items.
     * @property {boolean} [allowCustomOptions] Allow custom options.
     * @property {string} [pattern] A regular expression pattern.
     */

    /**
     * @param {
     *     module:views/fields/multi-enumeration~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'multiEnum'

    listTemplate = 'fields/array/list'
    detailTemplate = 'fields/array/detail'
    editTemplate = 'fields/multi-enum/edit'

    /** @const */
    MAX_ITEM_LENGTH = 100

    /**
     * @protected
     * @type {boolean}
     */
    restoreOnBackspace = false

    validationElementSelector = '.selectize-control'

    events = {}

    // noinspection JSCheckFunctionSignatures
    /** @inheritDoc */
    data() {
        // noinspection JSValidateTypes
        return {
            ...super.data(),
            optionList: this.params.options || [],
        };
    }

    /**
     * @deprecated As of v8.3.0.
     * @todo Remove.
     */
    getTranslatedOptions() {
        return (this.params.options || []).map(item => {
            if (this.translatedOptions !== null) {
                if (item in this.translatedOptions) {
                    return this.translatedOptions[item];
                }
            }

            return item;
        });
    }

    translateValueToEditLabel(value) {
        let label = value;

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
    }

    afterRender() {
        if (this.isSearchMode()) {
            this.renderSearch();

            return;
        }

        if (this.isEditMode()) {
            this.$element = this.$el.find('[data-name="' + this.name + '"]');

            const items = [];
            const valueList = Espo.Utils.clone(this.selected);

            for (const i in valueList) {
                let value = valueList[i];
                const originalValue = value;

                if (value === '') {
                    value = valueList[i] = '__emptystring__';
                }

                if (!~(this.params.options || []).indexOf(value)) {
                    items.push({
                        value: value,
                        text: this.translateValueToEditLabel(originalValue),
                    });
                }
            }

            this.$element.val(valueList.join(this.itemDelimiter));

            (this.params.options || []).forEach(value => {
                const originalValue = value;

                if (value === '') {
                    value = '__emptystring__';
                }

                items.push({
                    value: value,
                    text: this.translateValueToEditLabel(originalValue),
                    style: this.styleMap[value] || undefined,
                });
            });

            /** @type {module:ui/multi-select~Options} */
            const multiSelectOptions = {
                items: items,
                delimiter: this.itemDelimiter,
                matchAnyWord: this.matchAnyWord,
                draggable: true,
                allowCustomOptions: this.allowCustomOptions,
                restoreOnBackspace: this.restoreOnBackspace,
                create: input => this.createCustomOptionCallback(input),
            };

            MultiSelect.init(this.$element, multiSelectOptions);

            this.$element.on('change', () => {
                this.trigger('change');
            });
        }
    }

    /**
     * @protected
     * @param {string} input
     * @return {{text: string, value: string}|null}
     */
    createCustomOptionCallback(input) {
        if (input.length > this.MAX_ITEM_LENGTH) {
            const message = this.translate('arrayItemMaxLength', 'messages')
                .replace('{max}', this.MAX_ITEM_LENGTH.toString());

            this.showValidationMessage(message, '.selectize-control')

            return null;
        }

        if (this.params.pattern) {
            const helper = new RegExpPattern();

            const result = helper.validate(this.params.pattern, input, this.name, this.entityType);

            if (result) {
                this.showValidationMessage(result.message, '.selectize-control')

                return null;
            }
        }

        return {
            value: input,
            text: input,
        };
    }

    focusOnInlineEdit() {
        MultiSelect.focus(this.$element);
    }

    fetch() {
        let list = this.$element.val().split(this.itemDelimiter);

        if (list.length === 1 && list[0] === '') {
            list = [];
        }

        for (const i in list) {
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

        const data = {};

        data[this.name] = list;

        return data;
    }

    validateRequired() {
        if (!this.isRequired()) {
            return;
        }

        const value = this.model.get(this.name);

        if (!value || value.length === 0) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, '.selectize-control');

            return true;
        }
    }

    validateMaxCount() {
        if (!this.params.maxCount) {
            return;
        }

        const itemList = this.model.get(this.name) || [];

        if (itemList.length > this.params.maxCount) {
            const msg =
                this.translate('fieldExceedsMaxCount', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{maxCount}', this.params.maxCount.toString());

            this.showValidationMessage(msg, '.selectize-control');

            return true;
        }
    }
}

export default MultiEnumFieldView;
