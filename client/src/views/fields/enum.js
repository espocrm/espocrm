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

/** @module views/fields/enumeration */

import BaseFieldView from 'views/fields/base';
import MultiSelect from 'ui/multi-select';
import Select from 'ui/select'

/**
 * An enum field (select-box).
 *
 * @extends BaseFieldView<module:views/fields/enumeration~params>
 */
class EnumFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/enumeration~options
     * @property {
     *     module:views/fields/enumeration~params &
     *     module:views/fields/base~params &
     *     Object.<string, *>
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/enumeration~params
     * @property {string[]} [options] Select options.
     * @property {boolean} [required] Required.
     * @property {string} [translation] A translation string. E.g. `Global.scopeNames`.
     * @property {boolean} [displayAsLabel] Display as label.
     * @property {string|'state'} [labelType] A label type.
     * @property {'regular'|'state'} [labelType] A label type.
     * @property {string} [optionsReference] A reference to options. E.g. `Account.industry`.
     * @property {string} [optionsPath] An options metadata path.
     * @property {boolean} [isSorted] To sort options.
     * @property {Object.<string, 'warning'|'danger'|'success'|'info'|'primary'>} [style] A style map.
     * @property {Object.<string, string>} [translatedOptions] Option translations.
     */

    /**
     * @param {
     *     module:views/fields/enumeration~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'enum'

    listTemplate = 'fields/enum/list'
    listLinkTemplate = 'fields/enum/list-link'
    detailTemplate = 'fields/enum/detail'
    editTemplate = 'fields/enum/edit'
    searchTemplate = 'fields/enum/search'

    translatedOptions = null

    /**
     * @todo Remove? Always treat as true.
     */
    fetchEmptyValueAsNull = true

    searchTypeList = [
        'anyOf',
        'noneOf',
        'isEmpty',
        'isNotEmpty',
    ]

    validationElementSelector = '.selectize-control'

    /**
     * @protected
     * @type {boolean}
     */
    nativeSelect = false;

    // noinspection JSCheckFunctionSignatures
    /** @inheritDoc */
    data() {
        const data = super.data();

        data.translatedOptions = this.translatedOptions;

        const value = this.model.get(this.name);

        if (this.isReadMode() && this.styleMap) {
            data.style = this.styleMap[value || ''] || 'default';
        }

        data.styleMap = this.styleMap;

        if (this.isReadMode()) {
            if (!this.params.displayAsLabel) {
                data.class = 'text';
            } else {
                if (this.params.labelType === 'state') {
                    data.class = 'label label-md label-state label';
                } else {
                    data.class = data.style && data.style !== 'default' ?
                        'label label-md label' :
                        'text';
                }
            }
        }

        const translationKey = value || '';

        if (
            typeof value !== 'undefined' && value !== null && value !== ''
            ||
            translationKey === '' && (
                translationKey in (this.translatedOptions || {}) &&
                (this.translatedOptions || {})[translationKey] !== ''
            )
        ) {
            data.isNotEmpty = true;
        }

        data.valueIsSet = this.model.has(this.name);

        if (data.isNotEmpty) {
            data.valueTranslated =
                this.translatedOptions ?
                    (this.translatedOptions[translationKey] || value) :
                    this.getLanguage().translateOption(translationKey, this.name, this.entityType);

        }

        if (this.isEditMode()) {
            data.nativeSelect = this.nativeSelect;
        }

        // noinspection JSValidateTypes
        return data;
    }

    setup() {
        if (!this.params.options) {
            const methodName = 'get' + Espo.Utils.upperCaseFirst(this.name) + 'Options';

            if (typeof this.model[methodName] === 'function') {
                this.params.options = this.model[methodName].call(this.model);
            }
        }

        this.styleMap = this.params.style || this.model.getFieldParam(this.name, 'style') || {};

        let optionsPath = this.params.optionsPath;
        /** @type {string|null} */
        const optionsReference = this.params.optionsReference;

        if (!optionsPath && optionsReference) {
            const [refEntityType, refField] = optionsReference.split('.');

            optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;

            if (Object.keys(this.styleMap).length === 0) {
                this.styleMap = this.getMetadata().get(`entityDefs.${refEntityType}.fields.${refField}.style`) || {};
            }
        }

        if (optionsPath) {
            this.params.options = Espo.Utils.clone(this.getMetadata().get(optionsPath)) || [];
        }

        this.setupOptions();

        if ('translatedOptions' in this.options) {
            this.translatedOptions = this.options.translatedOptions;
        }

        if ('translatedOptions' in this.params) {
            this.translatedOptions = this.params.translatedOptions;
        }

        this.setupTranslation();

        if (this.translatedOptions === null) {
            this.translatedOptions = this.getLanguage()
                .translate(this.name, 'options', this.model.name) || {};

            if (this.translatedOptions === this.name) {
                this.translatedOptions = null;
            }
        }

        if (this.params.isSorted && this.translatedOptions) {
            this.params.options = Espo.Utils.clone(this.params.options) || [];

            this.params.options = this.params.options.sort((v1, v2) => {
                return (this.translatedOptions[v1] || v1)
                    .localeCompare(this.translatedOptions[v2] || v2);
            });
        }

        if (this.options.customOptionList) {
            this.setOptionList(this.options.customOptionList);
        }
    }

    setupTranslation() {
        let translation = this.params.translation;
        /** @type {?string} */
        const optionsReference = this.params.optionsReference;

        if (!translation && optionsReference) {
            const [refEntityType, refField] = optionsReference.split('.');

            translation = `${refEntityType}.options.${refField}`;
        }

        if (!translation) {
            return;
        }

        this.translatedOptions = null;

        if (!this.params.options) {
            return;
        }

        const obj = this.getLanguage().translatePath(translation);

        const map = {};

        this.params.options.forEach(item => {
            if (typeof obj === 'object' && item in obj) {
                map[item] = obj[item];

                return;
            }

            if (
                Array.isArray(obj) &&
                typeof item === 'number' &&
                typeof obj[item] !== 'undefined'
            ) {
                map[item.toString()] = obj[item];

                return;
            }

            map[item] = item;
        });

        const value = this.model.get(this.name);

        if ((value || value === '') && !(value in map)) {
            if (typeof obj === 'object' && value in obj) {
                map[value] = obj[value];
            }
        }

        this.translatedOptions = map;
    }

    /**
     * Set up options.
     */
    setupOptions() {}

    /**
     * Set translated options.
     *
     * @param {Record} translatedOptions
     * @since 8.4.0
     */
    setTranslatedOptions(translatedOptions) {
        this.translatedOptions = translatedOptions;
    }

    /**
     * Set an option list.
     *
     * @param {string[]} optionList An option list.
     * @return {Promise}
     */
    setOptionList(optionList) {
        const previousOptions = this.params.options;

        if (!this.originalOptionList) {
            this.originalOptionList = this.params.options;
        }

        const newOptions = Espo.Utils.clone(optionList) || [];

        this.params.options = newOptions;

        const isChanged = !_(previousOptions).isEqual(optionList);

        if (!this.isEditMode() || !isChanged) {
            return Promise.resolve();
        }

        let triggerChange = false;
        const currentValue = this.model.get(this.name);

        if (!newOptions.includes(currentValue) && this.isReady) {
            this.model.set(this.name, newOptions[0] ?? null, {silent: true});

            triggerChange = true;
        }

        return this.reRender()
            .then(() => {
                if (triggerChange) {
                    this.trigger('change');
                }
            });
    }

    /**
     * Reset a previously set option list.
     *
     * @return {Promise}
     */
    resetOptionList() {
        if (!this.originalOptionList) {
            return Promise.resolve();
        }

        const previousOptions = this.params.options;

        this.params.options = Espo.Utils.clone(this.originalOptionList);

        const isChanged = !_(previousOptions).isEqual(this.originalOptionList);

        if (!this.isEditMode() || !isChanged) {
            return Promise.resolve();
        }

        if (this.isRendered()) {
            return this.reRender();
        }

        return Promise.resolve();
    }

    setupSearch() {
        this.events = _.extend({
            'change select.search-type': (e) => {
                this.handleSearchType($(e.currentTarget).val());
            },
        }, this.events || {});
    }

    handleSearchType(type) {
        const $inputContainer = this.$el.find('div.input-container');

        if (~['anyOf', 'noneOf'].indexOf(type)) {
            $inputContainer.removeClass('hidden');
        } else {
            $inputContainer.addClass('hidden');
        }
    }

    afterRender() {
        super.afterRender();

        if (this.isSearchMode()) {
            this.$element = this.$el.find('.main-element');

            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            const valueList = this.getSearchParamsData().valueList || this.searchParams.value || [];

            this.$element.val(valueList.join(':,:'));

            const items = [];

            (this.params.options || []).forEach(value => {
                let label = this.getLanguage().translateOption(value, this.name, this.scope);

                if (this.translatedOptions) {
                    if (value in this.translatedOptions) {
                        label = this.translatedOptions[value];
                    }
                }

                if (label === '') {
                    return;
                }

                items.push({
                    value: value,
                    text: label,
                });
            });

            /** @type {module:ui/multi-select~Options} */
            const multiSelectOptions = {
                items: items,
                delimiter: ':,:',
                matchAnyWord: true,
            };

            MultiSelect.init(this.$element, multiSelectOptions);

            this.$el.find('.selectize-dropdown-content').addClass('small');
            this.$el.find('select.search-type').on('change', () => this.trigger('change'));
            this.$element.on('change', () => this.trigger('change'));
        }

        if ((this.isEditMode() || this.isSearchMode()) && !this.nativeSelect) {
            Select.init(this.$element, {
                matchAnyWord: true,
            });
        }
    }

    focusOnInlineEdit() {
        Select.focus(this.$element);
    }

    validateRequired() {
        if (this.isRequired()) {
            if (!this.model.get(this.name)) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }
    }

    fetch() {
        let value = this.$element.val();

        if (this.fetchEmptyValueAsNull && !value) {
            value = null;
        }

        const data = {};

        data[this.name] = value;

        return data;
    }

    parseItemForSearch(item) {
        return item;
    }

    fetchSearch() {
        const type = this.fetchSearchType();

        let list = this.$element.val().split(':,:');

        if (list.length === 1 && list[0] === '') {
            list = [];
        }

        list.forEach((item, i) => {
            list[i] = this.parseItemForSearch(item);
        });

        if (type === 'anyOf') {
            if (list.length === 0) {
                return {
                    type: 'any',
                    data: {
                        type: 'anyOf',
                        valueList: list,
                    },
                };
            }

            return {
                type: 'in',
                value: list,
                data: {
                    type: 'anyOf',
                    valueList: list,
                },
            };
        }

        if (type === 'noneOf') {
            if (list.length === 0) {
                return {
                    type: 'any',
                    data: {
                        type: 'noneOf',
                        valueList: list,
                    },
                };
            }

            return {
                type: 'or',
                value: [
                    // Don't change order.
                    {
                        type: 'notIn',
                        value: list,
                        attribute: this.name,
                    },
                    {
                        type: 'isNull',
                        attribute: this.name,
                    },
                ],
                data: {
                    type: 'noneOf',
                    valueList: list,
                },
            };
        }

        if (type === 'isEmpty') {
            return {
                type: 'or',
                value: [
                    {
                        type: 'isNull',
                        attribute: this.name,
                    },
                    {
                        type: 'equals',
                        value: '',
                        attribute: this.name,
                    }
                ],
                data: {
                    type: 'isEmpty',
                },
            };
        }

        if (type === 'isNotEmpty') {
            const value = [
                {
                    type: 'isNotNull',
                    attribute: this.name,
                },
            ];

            if (!this.model.getFieldParam(this.name, 'notStorable')) {
                value.push({
                    type: 'notEquals',
                    value: '',
                    attribute: this.name,
                });
            }

            return {
                type: 'and',
                value: value,
                data: {
                    type: 'isNotEmpty',
                },
            };
        }

        return null;
    }

    getSearchType() {
        return this.getSearchParamsData().type || 'anyOf';
    }
}

export default EnumFieldView;
