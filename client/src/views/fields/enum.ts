/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import BaseFieldView, {Options as BaseOptions, Params as BaseParams, ViewSchema} from 'views/fields/base';
import MultiSelect from 'ui/multi-select';
import Select from 'ui/select'
import _ from 'underscore';

type OptionItemHandler = (item: {value: string}) => {
    text?: string,
    style?: 'default' | 'danger' | 'success' | 'warning' | 'info' | null,
    color?: string | null,
};

type StyleMap = Record<string, 'warning' | 'danger' | 'success' | 'info' | 'primary'>;

interface Params extends BaseParams {
    /**
     * Select options.
     */
    options?: string[];
    /**
     * Required.
     */
    required?: boolean;
    /**
     * A translation string. E.g. `Global.scopeNames`.
     */
    translation?: string;
    /**
     * Display as label.
     */
    displayAsLabel?: boolean;
    /**
     * A label type.
     */
    labelType?: 'regular' | 'state';
    /**
     * A reference to options. E.g. `Account.industry`.
     */
    optionsReference?: string;
    /**
     * An options metadata path.
     */
    optionsPath?: string;
    /**
     * To sort options.
     */
    isSorted?: boolean;
    /**
     * A style map.
     */
    style?: StyleMap;
    /**
     * Option translations.
     */
    translatedOptions?: Record<string, string>;
}

/**
 * Options.
 */
interface Options extends BaseOptions {
    /**
     * Handles an option item to override the label or add a style.
     * @since 10.0.0
     */
    optionItemHandler?: OptionItemHandler;
    /**
     * Option translations.
     */
    translatedOptions?: Record<string, string>;
    /**
     * @internal
     */
    customOptionList?: string[];
}

/**
 * An enum field (select-box).
 */
class EnumFieldView<
    S extends ViewSchema = ViewSchema,
    P extends Params = Params,
    O extends Options = Options,
> extends BaseFieldView<S, O, P> {

    readonly type = 'enum'

    protected listTemplate = 'fields/enum/list'
    protected listLinkTemplate = 'fields/enum/list-link'
    protected detailTemplate = 'fields/enum/detail'
    protected editTemplate = 'fields/enum/edit'
    protected searchTemplate = 'fields/enum/search'

    protected translatedOptions: Record<string, string> | null = null

    /**
     * @todo Remove? Always treat as true.
     * @internal
     */
    protected fetchEmptyValueAsNull = true

    protected searchTypeList = [
        'anyOf',
        'noneOf',
        'isEmpty',
        'isNotEmpty',
    ]

    protected validationElementSelector = '.selectize-control'

    protected nativeSelect: boolean = false;

    protected optionItemHandler: OptionItemHandler | null = null

    private styleMap: StyleMap | null = null

    private originalOptionList: string[] | null = null

    protected data() {
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

        if (this.isReadMode() && this.optionItemHandler && this.model.attributes[this.name]) {
            const item = this.optionItemHandler({value: this.model.attributes[this.name]});

            if (item.color != null) {
                data.color = item.color;
                data.hasColor = true;
            }
        }

        return data;
    }

    protected setup() {
        if (!this.params.options) {
            // @todo Revise.
            const methodName = 'get' + Espo.Utils.upperCaseFirst(this.name) + 'Options';

            if (typeof (this.model as any)[methodName] === 'function') {
                this.params.options = (((this.model as any)[methodName] as any).call(this.model)) as string[];
            }
        }

        if (this.options.optionItemHandler) {
            this.optionItemHandler = this.options.optionItemHandler;
        }

        this.styleMap = this.params.style ?? this.model.getFieldParam(this.name, 'style') ?? {};

        let optionsPath = this.params.optionsPath;
        const optionsReference = this.params.optionsReference ?? null;

        if (!optionsPath && optionsReference) {
            const [refEntityType, refField] = optionsReference.split('.');

            optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;

            if (!this.styleMap || Object.keys(this.styleMap).length === 0) {
                this.styleMap = this.getMetadata().get(`entityDefs.${refEntityType}.fields.${refField}.style`) || {};
            }
        }

        if (optionsPath) {
            this.params.options = Espo.Utils.clone(this.getMetadata().get(optionsPath)) || [];
        }

        this.setupOptions();

        if ('translatedOptions' in this.options) {
            this.translatedOptions = this.options.translatedOptions ?? null;
        }

        if ('translatedOptions' in this.params) {
            this.translatedOptions = this.params.translatedOptions ?? null;
        }

        this.setupTranslation();

        if (this.translatedOptions === null) {
            const translations = (this.getLanguage().translate(this.name, 'options', this.model.name) ?? {}) as any;

            if (translations === this.name) {
                this.translatedOptions = null;
            } else {
                this.translatedOptions = translations;
            }
        }

        if (this.params.isSorted && this.translatedOptions) {
            const translations = this.translatedOptions;

            this.params.options = Espo.Utils.clone(this.params.options) || [];

            this.params.options = this.params.options.sort((v1, v2) => {
                return (translations[v1] || v1)
                    .localeCompare(translations[v2] || v2);
            });
        }

        if (this.options.customOptionList) {
            this.setOptionList(this.options.customOptionList);
        }
    }

    protected setupTranslation() {
        let translation = this.params.translation;
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

        const obj = this.getLanguage().translatePath(translation) as Record<string, string>;

        const map = {} as Record<string, any>;

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
                map[(item as number).toString()] = obj[item];

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
     * @param translatedOptions Translations.
     * @since 8.4.0
     */
    setTranslatedOptions(translatedOptions: Record<string, string>) {
        this.translatedOptions = translatedOptions;
    }

    /**
     * Set an option list.
     *
     * @param optionList An option list.
     */
    async setOptionList(optionList: string[]) {
        const previousOptions = this.params.options;

        if (!this.originalOptionList) {
            this.originalOptionList = this.params.options ?? null
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

        await this.reRender();

        if (triggerChange) {
            this.trigger('change');
        }
    }

    /**
     * Reset a previously set option list.
     */
    resetOptionList(): Promise<unknown> {
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

    protected setupSearch() {
        this.addHandler('change', 'select.search-type', (_e, target) => {
            this.handleSearchType((target as HTMLSelectElement).value);
        });
    }

    protected handleSearchType(type: string) {
        const $inputContainer = this.$el.find('div.input-container');

        if (['anyOf', 'noneOf'].includes(type)) {
            $inputContainer.removeClass('hidden');
        } else {
            $inputContainer.addClass('hidden');
        }
    }

    protected afterRender() {
        super.afterRender();

        if (this.isSearchMode()) {
            this.$element = this.$el.find('.main-element');

            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            const valueList = this.getSearchParamsData().valueList ?? this.searchParams?.value ?? [];

            this.$element?.val(valueList.join(':,:'));

            const items = [] as Record<string, any>[];

            (this.params.options ?? []).forEach(value => {
                let label = this.getLanguage().translateOption(value, this.name, this.entityType);

                if (this.translatedOptions && value in this.translatedOptions) {
                    label = this.translatedOptions[value];
                }

                if (label === '') {
                    return;
                }

                items.push({
                    value: value,
                    text: label,
                });
            });

            const multiSelectOptions = {
                items: items,
                delimiter: ':,:',
                matchAnyWord: true,
            };

            MultiSelect.init(this.$element as any, multiSelectOptions);

            this.$el.find('.selectize-dropdown-content').addClass('small');
            this.$el.find('select.search-type').on('change', () => this.trigger('change'));
            this.$element?.on('change', () => this.trigger('change'));
        }

        if ((this.isEditMode() || this.isSearchMode()) && !this.nativeSelect) {
            Select.init(this.$element as any, {
                matchAnyWord: true,
                itemHandler: this.optionItemHandler,
            });
        }
    }

    focusOnInlineEdit() {
        Select.focus(this.$element as any);
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

        return false;
    }

    fetch() {
        let value = this.$element?.val() as string | null;

        if (this.fetchEmptyValueAsNull && !value) {
            value = null;
        }

        const data = {} as Record<string, any>;

        data[this.name] = value;

        return data;
    }

    protected parseItemForSearch(item: string): string {
        return item;
    }

    fetchSearch() {
        const type = this.fetchSearchType();

        let list = ((this.$element?.val() ?? '') as string).split(':,:');

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
            ] as Record<string, any>[];

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

    protected getSearchType(): string {
        return this.getSearchParamsData().type ?? 'anyOf';
    }
}

export default EnumFieldView;
