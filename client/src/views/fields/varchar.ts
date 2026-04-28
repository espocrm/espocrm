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

/** @module views/fields/varchar */

import BaseFieldView, {Options as BaseOptions, Params as BaseParams, ViewSchema} from 'views/fields/base';
import RegExpPattern from 'helpers/reg-exp-pattern';
import Autocomplete from 'ui/autocomplete';
import MultiSelect from 'ui/multi-select';
import Ajax from 'ajax';
import Ui from 'ui';

interface Params extends BaseParams {
    /**
     * Max length.
     */
    maxLength?: number;
    /**
     * Autocomplete options.
     */
    options?: string[];
    /**
     * Is required.
     */
    required?: boolean;
    /**
     * Options path.
     */
    optionsPath?: string;
    /**
     * No spell check.
     */
    noSpellCheck?: boolean;
    /**
     * A pattern.
     */
    pattern?: string;
    /**
     * Show 'Copy to Clipboard' button.
     */
    copyToClipboard?: boolean;
}

interface Options extends BaseOptions {
    /**
     * Custom autocomplete options.
     */
    customOptionList?: string[];
}

type AutocompleteReturn = Promise<import('ui/autocomplete').AutocompleteItem & Record<string, unknown>>;

/**
 * A varchar field.
 */
class VarcharFieldView<
    S extends ViewSchema = ViewSchema,
    P extends Params = Params,
    O extends Options = Options,
> extends BaseFieldView<S, O, P> {

    readonly type = 'varchar'

    protected listTemplate = 'fields/varchar/list'
    protected detailTemplate = 'fields/varchar/detail'
    protected searchTemplate = 'fields/varchar/search'

    protected searchTypeList = [
        'startsWith',
        'contains',
        'equals',
        'endsWith',
        'like',
        'notContains',
        'notEquals',
        'notLike',
        'anyOf',
        'noneOf',
        'isEmpty',
        'isNotEmpty',
    ]

    protected validations = [
        'required',
        'pattern',
    ]

    /**
     * Use an autocomplete requesting data from the backend.
     */
    protected useAutocompleteUrl: boolean = false

    /**
     * No spell-check.
     */
    protected noSpellCheck: boolean = false

    private searchMultiSelectInputElement: HTMLInputElement | undefined

    private originalOptionList: string[] | null = null

    setup() {
        this.setupOptions();

        this.noSpellCheck = this.noSpellCheck || this.params.noSpellCheck || false;

        if (this.params.optionsPath) {
            this.params.options = Espo.Utils.clone(
                this.getMetadata().get(this.params.optionsPath) || []);
        }

        if (this.options.customOptionList) {
            this.setOptionList(this.options.customOptionList);
        }

        if (this.mode === this.MODE_DETAIL) {
            if (this.params.copyToClipboard) {
                this.events['click [data-action="copyToClipboard"]'] = () => this.copyToClipboard();
            }
        }

        this.on('remove', () => {
            this.searchMultiSelectInputElement = undefined;
        });
    }

    /**
     * Set up options.
     */
    setupOptions() {}

    /**
     * Set options.
     *
     * @param optionList Options.
     */
    setOptionList(optionList: string[]) {
        if (!this.originalOptionList) {
            this.originalOptionList = this.params.options || [];
        }

        this.params.options = Espo.Utils.clone(optionList);

        if (this.isEditMode()) {
            if (this.isRendered()) {
                this.reRender();
            }
        }
    }

    /**
     * Reset options.
     */
    resetOptionList() {
        if (this.originalOptionList) {
            this.params.options = Espo.Utils.clone(this.originalOptionList);
        }

        if (this.isEditMode()) {
            if (this.isRendered()) {
                this.reRender();
            }
        }
    }

    /**
     * @protected
     */
    copyToClipboard() {
        const value = this.model.get(this.name);

        navigator.clipboard.writeText(value).then(() => {
            Ui.success(this.translate('Copied to clipboard'));
        });
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * Compose an autocomplete URL.
     *
     * @param query A query.
     * @return {string} A URL.
     */
    getAutocompleteUrl(query: string): string {
        // noinspection BadExpressionStatementJS
        query;

        return '';
    }

    protected transformAutocompleteResult(
        response: any,
    ): (import('ui/autocomplete').AutocompleteItem & Record<string, any>)[] {

        const responseParsed = typeof response === 'string' ?
            JSON.parse(response) :
            response;

        const list = [] as (import('ui/autocomplete').AutocompleteItem & Record<string, any>)[];

        responseParsed.list.forEach((item: {id: string, name?: string}) => {
            list.push({
                value: item.name || item.id,
                attributes: item,
            });
        });

        return list;
    }

    setupSearch() {
        this.addHandler('change', 'select.search-type', (_e, target: HTMLElement) => {
            this.handleSearchType((target as HTMLInputElement).value);
        });
    }

    data() {
        const data = super.data();

        if (
            this.model.get(this.name) !== null &&
            this.model.get(this.name) !== '' &&
            this.model.has(this.name)
        ) {
            data.isNotEmpty = true;
        }

        data.valueIsSet = this.model.has(this.name);

        if (this.isSearchMode()) {
            if (typeof this.searchParams?.value === 'string') {
                this.searchData.value = this.searchParams?.value;
            }

            if (this.searchParams?.data && typeof this.searchParams?.data.value === 'string') {
                this.searchData.value = this.searchParams?.data.value;
            }

            if (!this.searchParams?.value && !this.searchParams?.data) {
                this.searchData.value = null;
            }
        }

        data.noSpellCheck = this.noSpellCheck;
        data.copyToClipboard = this.params.copyToClipboard;
        data.textClass = null;

        return data;
    }

    protected handleSearchType(type: string) {
        const mainElement = this.element.querySelector('input.main-element');
        const multiSelectContainer = this.element.querySelector('div[data-role="multi-select-container"]');

        if (['isEmpty', 'isNotEmpty', 'anyOf', 'noneOf'].includes(type)) {
            mainElement?.classList.add('hidden');
        } else {
            mainElement?.classList.remove('hidden');
        }

        if (multiSelectContainer) {
            if (['anyOf', 'noneOf'].includes(type)) {
                multiSelectContainer.classList.remove('hidden');
            } else {
                multiSelectContainer.classList.add('hidden');
            }
        }
    }

    protected afterRender() {
        super.afterRender();

        if (this.isSearchMode()) {
            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);
            this.initSearchMultiSelect();
        }

        if (
            (this.isEditMode() || this.isSearchMode()) &&
            (
                this.params.options && this.params.options.length ||
                this.useAutocompleteUrl
            )
        ) {
            let lookupFunction = this.getAutocompleteLookupFunction() as any ;

            if (this.useAutocompleteUrl) {
                lookupFunction = async (query: string) => {
                    const response = await Ajax.getRequest(this.getAutocompleteUrl(query));

                    return this.transformAutocompleteResult(response);
                };
            }

            const autocomplete = new Autocomplete(this.$element?.get(0) as HTMLInputElement, {
                name: this.name,
                triggerSelectOnValidInput: true,
                autoSelectFirst: true,
                handleFocusMode: 1,
                focusOnSelect: true,
                onSelect: () => this.trigger('change'),
                lookup: this.params.options,
                lookupFunction: lookupFunction,
            });

            this.once('render remove', () => autocomplete.dispose());
        }

        if (this.isSearchMode()) {
            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });

            this.$element?.on('input', () => this.trigger('change'));
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validatePattern() {
        const pattern = this.params.pattern;

        return this.fieldValidatePattern(this.name, pattern);
    }

    /**
     * Used by other field views.
     *
     * @internal
     */
    fieldValidatePattern(name: string, pattern?: string) {
        pattern = pattern || this.model.getFieldParam(name, 'pattern');
        /** @var {string|null} value */
        const value = this.model.get(name);

        if (!pattern) {
            return false;
        }

        const helper = new RegExpPattern();
        const result = helper.validate(pattern, value, name, this.entityType ?? undefined);

        if (!result) {
            return false;
        }

        const message = result.message.replace('{field}', this.getLanguage().translate(this.getLabelText()));

        this.showValidationMessage(message, '[data-name="' + name + '"]');

        return true;
    }

    fetch() {
        const data = {} as Record<string, any>;

        const value = (this.$element?.val() as string)?.trim();

        data[this.name] = value || null;

        return data;
    }

    fetchSearch() {
        const type = this.fetchSearchType() || 'startsWith';

        if (['isEmpty', 'isNotEmpty'].includes(type)) {
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
                            attribute: this.name,
                            value: '',
                        },
                    ],
                    data: {
                        type: type,
                    },
                };
            }
            const value = [
                {
                    type: 'isNotNull',
                    attribute: this.name,
                    value: null,
                } as Record<string, any>,
            ];

            if (!this.model.getFieldParam(this.name, 'notStorable')) {
                value.push({
                    type: 'notEquals',
                    attribute: this.name,
                    value: '',
                });
            }

            return {
                type: 'and',
                value: value,
                data: {
                    type: type,
                },
            };
        }

        if (type === 'anyOf' || type === 'noneOf') {
            let list = this.searchMultiSelectInputElement ?
                this.searchMultiSelectInputElement.value.split(MultiSelect.defaultDelimiter) : [];

            if (list.length === 1 && list[0] === '') {
                list = [];
            }

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

        const value = this.$element?.val()?.toString()?.trim();

        if (!value) {
            return null;
        }

        return {
            value: value,
            type: type,
            data: {
                type: type,
            },
        };
    }

    getSearchType() {
        return this.getSearchParamsData()?.type || this.searchParams?.typeFront ||
            this.searchParams?.type;
    }

    /**
     * Get an autocomplete lookup function.
     */
    protected getAutocompleteLookupFunction(): ((query: string) => AutocompleteReturn) | undefined {
        return undefined;
    }

    private initSearchMultiSelect() {
        this.searchMultiSelectInputElement =
            this.element?.querySelector('input[data-role="multi-select-input"]') as HTMLInputElement;

        if (!this.searchMultiSelectInputElement) {
            throw new Error("No element.");
        }

        MultiSelect.init(this.searchMultiSelectInputElement, {
            items: (this.params.options || []).map(it => ({value: it, text: it})),
            allowCustomOptions: true,
            create: (input: any) => {
                return {
                    value: input,
                    text: input,
                };
            },
            values: this.getSearchParamsData().valueList || [],
        });

        this.element.querySelector('.selectize-dropdown-content')
            ?.classList.add('small');
    }
}

export default VarcharFieldView;
