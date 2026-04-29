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

/** @module views/fields/array */

import BaseFieldView, {Options as BaseOptions, Params as BaseParams, ViewSchema} from 'views/fields/base';
import RegExpPattern from 'helpers/reg-exp-pattern';
import MultiSelect from 'ui/multi-select';
import ModalView, {ModalOptions} from 'views/modal';
import Model from 'model';
import EditForModalRecordView from 'views/record/edit-for-modal';
import VarcharFieldView from 'views/fields/varchar';
import _ from 'underscore';
import JQuery from 'jquery'

const $ = JQuery;

type StyleMap = Record<string, 'warning' | 'danger' | 'success' | 'info' | 'primary' | 'default'>;

/**
 * Parameters.
 */
export interface Params extends BaseParams {
    /**
     *  A translation string. E.g. `Global.scopeNames`.
     */
    translation?: string;
    /**
     * Select options.
     */
    options?: string[];
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Display as list (line breaks).
     */
    displayAsList?: boolean;
    /**
     * Display as label.
     */
    displayAsLabel?: boolean;
    /**
     * A label type.
     */
    labelType?: string | 'state';
    /**
     * No empty string.
     */
    noEmptyString?: boolean;
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
     * Option translations.
     */
    translatedOptions?: Record<string, string>;
    /**
     * A style map.
     */
    style?: StyleMap;
    /**
     *  A max number of items.
     */
    maxCount?: number;
    /**
     * Allow custom options.
     */
    allowCustomOptions?: boolean;
    /**
     * A regular expression pattern.
     */
    pattern?: string;
    /**
     * Disable the ability to add or remove items. Reordering is allowed.
     */
    keepItems?: boolean;
    /**
     * Max item length. If not specified, 100 is used.
     * @since 9.1.0
     */
    maxItemLength?: number;
    /**
     * Items are editable.
     * @since 9.2.0
     */
    itemsEditable?: boolean;
}

/**
 * Options.
 */
export interface Options extends BaseOptions {
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
 * An array field.
 */
class ArrayFieldView<
    S extends ViewSchema = ViewSchema,
    P extends Params = Params,
    O extends Options = Options,
> extends BaseFieldView<S, O, P> {

    readonly type = 'array'

    protected listTemplate = 'fields/array/list'
    protected listLinkTemplate = 'fields/array/list-link'
    protected detailTemplate = 'fields/array/detail'
    protected editTemplate = 'fields/array/edit'
    protected searchTemplate = 'fields/array/search'

    protected searchTypeList = [
        'anyOf',
        'noneOf',
        'allOf',
        'isEmpty',
        'isNotEmpty',
    ]

    protected maxItemLength: number | null = null

    protected validations = ['required', 'maxCount']

    protected readonly MAX_ITEM_LENGTH = 100

    /**
     * An add-item model view.
     */
    protected addItemModalView: string = 'views/modals/array-field-add'

    protected itemDelimiter: string = ':,:'

    protected matchAnyWord: boolean = true

    protected translatedOptions: Record<string, string> | null = null

    /**
     * @since 9.2.0
     */
    protected noDragHandle: boolean = false

    protected selected: string[]

    private allowCustomOptions: boolean

    private noEmptyString: boolean

    protected styleMap: StyleMap

    protected displayAsLabel: boolean = false

    protected displayAsList: boolean = false

    private originalOptionList: string[] | null = null

    private $select: JQuery
    private $addButton: JQuery;
    private $list: JQuery;

    protected data() {
        const itemHtmlList: string[] = [];

        (this.selected ?? []).forEach(value => {
            itemHtmlList.push(this.getItemHtml(value || ''));
        });

        // noinspection JSValidateTypes
        return {
            ...super.data(),
            selected: this.selected,
            translatedOptions: this.translatedOptions,
            hasAdd: !!this.params.options && !this.params.keepItems,
            keepItems: this.params.keepItems,
            itemHtmlList: itemHtmlList,
            isEmpty: (this.selected || []).length === 0,
            valueIsSet: this.model.has(this.name),
            maxItemLength: this.maxItemLength || this.MAX_ITEM_LENGTH,
            allowCustomOptions: this.allowCustomOptions,
        };
    }

    protected setup() {
        super.setup();

        this.setupFieldEvents();

        this.noEmptyString = this.params.noEmptyString ?? false;

        if (this.params.maxItemLength != null) {
            this.maxItemLength = this.params.maxItemLength;
        }

        this.maxItemLength = this.maxItemLength || this.MAX_ITEM_LENGTH;

        this.listenTo(this.model, 'change:' + this.name, () => {
            this.selected = Espo.Utils.clone(this.model.get(this.name)) || [];
        });

        this.selected = Espo.Utils.clone(this.model.get(this.name) || []);

        if (Object.prototype.toString.call(this.selected) !== '[object Array]') {
            this.selected = [];
        }

        this.styleMap = this.params.style ?? {};

        let optionsPath = this.params.optionsPath;

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
            this.translatedOptions = this.options.translatedOptions ?? null;
        }

        if ('translatedOptions' in this.params) {
            this.translatedOptions = this.params.translatedOptions ?? null;
        }

        if (!this.translatedOptions) {
            this.setupTranslation();
        }

        this.displayAsLabel = this.params.displayAsLabel || this.displayAsLabel;
        this.displayAsList = this.params.displayAsList || this.displayAsList;

        const translatedOptions = this.translatedOptions;

        if (this.params.isSorted && translatedOptions) {
            this.params.options = Espo.Utils.clone(this.params.options);
            this.params.options = this.params.options?.sort((v1, v2) => {
                 return (translatedOptions[v1] || v1).localeCompare(translatedOptions[v2] || v2);
            });
        }

        if (this.options.customOptionList) {
            this.setOptionList(this.options.customOptionList, true);
        }

        if (this.params.allowCustomOptions || !this.params.options) {
            this.allowCustomOptions = true;
        }

        if (this.params.allowCustomOptions === false) {
            this.allowCustomOptions = false;
        }

        if (this.type === 'array') {
            this.validations.push('noInputValue')
        }
    }

    /**
     * @internal
     */
    protected setupFieldEvents() {
        this.addActionHandler('removeValue', (_e, target) => {
            const value = target.dataset.value as string;

            this.removeValue(value);
            this.focusOnElement();
        });

        this.addActionHandler('showAddModal', () => {
            this.actionAddItem();
        });

        this.addActionHandler('editItem', (_e, target) => {
            this.actionEditItem(target.dataset.value as string);
        });
    }

    protected focusOnElement() {
        const $button = this.$el.find('button[data-action="showAddModal"]');

        if ($button[0]) {
            // noinspection JSUnresolvedReference
            $button[0].focus({preventScroll: true});

            return;
        }

        const $input = this.$el.find('input.main-element');

        if ($input[0]) {
            // noinspection JSUnresolvedReference
            $input[0].focus({preventScroll: true});
        }
    }

    protected setupSearch() {
        this.addHandler('change', 'select.search-type', (_e, target) => {
            this.handleSearchType((target as HTMLSelectElement).value);
        });
    }

    protected handleSearchType(type: string) {
        const $inputContainer = this.$el.find('div.input-container');

        if (['anyOf', 'noneOf', 'allOf'].includes(type)) {
            $inputContainer.removeClass('hidden');
        } else {
            $inputContainer.addClass('hidden');
        }
    }

    protected setupTranslation() {
        let obj = {};

        let translation = this.params.translation;

        const optionsReference = this.params.optionsReference;

        if (!translation && optionsReference) {
            const [refEntityType, refField] = optionsReference.split('.');

            translation = `${refEntityType}.options.${refField}`;
        }

        this.translatedOptions = null;

        if (!this.params.options) {
            return;
        }

        obj = translation ?
            this.getLanguage().translatePath(translation) :
            this.translate(this.name, 'options', this.model.name);

        const map = {} as Record<string, string>;

        this.params.options.forEach(o => {
            if (typeof obj === 'object' && o in obj) {
                map[o] = (obj as any)[o] as string;

                return;
            }

            map[o] = o;
        });

        this.translatedOptions = map;
    }

    protected setupOptions() {}

    setOptionList(optionList: string[], silent: boolean) {
        const previousOptions = this.params.options;

        if (!this.originalOptionList) {
            this.originalOptionList = this.params.options ?? [];
        }

        this.params.options = Espo.Utils.clone(optionList);

        const isChanged = !_(previousOptions).isEqual(optionList);

        if (this.isEditMode() && !silent && isChanged) {
            const selectedOptionList = [] as string[];

            this.selected.forEach(option => {
                if (optionList.includes(option)) {
                    selectedOptionList.push(option);
                }
            });

            this.selected = selectedOptionList;

            if (this.isRendered()) {
                this.reRender();

                this.trigger('change');
            }
            else {
                this.once('after:render', () => {
                    this.trigger('change');
                });
            }
        }
    }

    setTranslatedOptions(translatedOptions: Record<string, string>) {
        this.translatedOptions = translatedOptions;
    }

    resetOptionList() {
        if (!this.originalOptionList) {
            return;
        }

        const previousOptions = this.params.options;

        this.params.options = Espo.Utils.clone(this.originalOptionList);

        const isChanged = !_(previousOptions).isEqual(this.originalOptionList);

        if (!this.isEditMode() || !isChanged) {
            return;
        }

        if (this.isRendered()) {
            this.reRender();
        }
    }

    private controlAddItemButton() {
        const $select = this.$select;

        if (!$select) {
            return;
        }

        if (!$select.get(0)) {
            return;
        }

        const value = $select.val()?.toString().trim();

        if (!value && this.params.noEmptyString) {
            this.$addButton.addClass('disabled').attr('disabled', 'disabled');
        } else {
            this.$addButton.removeClass('disabled').removeAttr('disabled');
        }
    }

    protected afterRender() {
        if (this.isEditMode()) {
            this.$list = this.$el.find('.list-group');

            const $select = this.$select = this.$el.find('.select');

            if (this.allowCustomOptions) {
                this.$addButton = this.$el.find('button[data-action="addItem"]');

                this.$addButton.on('click', () => {
                    const value = $select.val().toString();

                    this.addValueFromUi(value);

                    this.focusOnElement();
                });

                $select.on('input', () => this.controlAddItemButton());

                $select.on('keydown', (e: any) => {
                    const key = Espo.Utils.getKeyFromKeyEvent(e);

                    if (key === 'Enter') {
                        const value = $select.val().toString();

                        this.addValueFromUi(value);
                    }
                });

                this.controlAddItemButton();
            }

            // @ts-ignore
            this.$list.sortable({
                stop: () => {
                    this.fetchFromDom();
                    this.trigger('change');
                },
                distance: 5,
                cancel: 'input,textarea,button,select,option,a[role="button"]',
                cursor: 'grabbing',
                handle: !this.noDragHandle ? '.drag-handle' : undefined,
            });
        }

        if (this.isSearchMode()) {
            this.renderSearch();
        }
    }

    protected addValueFromUi(value: string) {
        value = value.trim();

        if (this.noEmptyString && value === '') {
            return;
        }

        if (this.params.pattern) {
            const helper = new RegExpPattern();

            const result = helper.validate(this.params.pattern, value, this.name, this.entityType);

            if (result) {
                setTimeout(() => this.showValidationMessage(result.message, 'input.select'), 10);

                return;
            }
        }

        this.addValue(value);

        this.$select.val('');

        this.controlAddItemButton();
    }

    protected renderSearch() {
        this.$element = this.$el.find('.main-element');

        const valueList: string[] = this.getSearchParamsData().valueList ?? this.searchParams?.valueFront ?? [];

        this.$element?.val(valueList.join(this.itemDelimiter));

        const items = [] as Record<string, any>[];

        (this.params.options ?? []).forEach(value => {
            let label = this.getLanguage().translateOption(value, this.name, this.entityType);

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
                style: this.styleMap[value] || undefined,
            });
        });

        const options = this.params.options ?? [] as string[];

        valueList
            .filter(item => !options.includes(item))
            .forEach(item => {
                items.push({
                    value: item,
                    text: item,
                });
            });

        const multiSelectOptions = {
            items: items,
            delimiter: this.itemDelimiter,
            matchAnyWord: this.matchAnyWord,
            allowCustomOptions: this.allowCustomOptions,
            create: (input: string) => {
                return {
                    value: input,
                    text: input,
                };
            },
        };

        MultiSelect.init(this.$element as any, multiSelectOptions);

        this.$el.find('.selectize-dropdown-content').addClass('small');

        const type = this.$el.find('select.search-type').val();

        this.handleSearchType(type);

        this.$el.find('select.search-type').on('change', () => {
            this.trigger('change');
        });

        this.$element?.on('change', () => {
            this.trigger('change');
        });
    }

    protected fetchFromDom() {
        const selected = [] as string[];

        this.$el.find('.list-group .list-group-item').each((_i: number, el: HTMLElement) => {
            const value = ($(el)?.attr('data-value') as any).toString();

            selected.push(value);
        });

        this.selected = selected;
    }

    getValueForDisplay() {
        // Do not use the `html` method to avoid XSS.

        const list = this.selected.map(item => {
            let label = null;

            if (this.translatedOptions !== null) {
                if (item in this.translatedOptions) {
                    label = this.translatedOptions[item];
                }
            }

            if (label === null) {
                label = item;
            }

            if (label === '') {
                label = this.translate('None');
            }

            const style = this.styleMap[item] || 'default';

            if (this.displayAsLabel) {
                let className = 'label label-md label-' + style;

                if (this.params.labelType === 'state') {
                    className += ' label-state'
                }

                return $('<span>')
                    .addClass(className)
                    .text(label)
                    .get(0)?.outerHTML;
            }

            if (style && style !== 'default') {
                return $('<span>')
                    .addClass('text-' + style)
                    .text(label)
                    .get(0)?.outerHTML;
            }

            return $('<span>')
                .text(label)
                .get(0)?.outerHTML;
        });

        if (this.displayAsList) {
            if (!list.length) {
                return '';
            }

            let itemClassName = 'multi-enum-item-container';

            if (this.displayAsLabel) {
                itemClassName += ' multi-enum-item-label-container';
            }

            return list
                .map(item =>
                    $('<div>')
                        .addClass(itemClassName)
                        .html(item as string)
                        .get(0)?.outerHTML
                )
                .join('');
        }

        if (this.displayAsLabel) {
            return list.join(' ');
        }

        return list.join(', ');
    }

    protected getItemHtml(value: string): string {
        // Do not use the `html` method to avoid XSS.

        if (this.translatedOptions !== null) {
            for (const item in this.translatedOptions) {
                if (this.translatedOptions[item] === value) {
                    value = item;

                    break;
                }
            }
        }

        value = value.toString();

        const text = this.translatedOptions && value in this.translatedOptions ?
            this.translatedOptions[value].toString() :
            value;

        const div = document.createElement('div');
        div.className = 'list-group-item';
        div.dataset.value = value;
        div.style.cursor = 'default';

        if (!this.params.keepItems) {
            const a = document.createElement('a');
            a.role = 'button';
            a.tabIndex = 0;
            a.classList.add('pull-right');
            a.dataset.value = value;
            a.dataset.action = 'removeValue';
            a.append(
                (() => {
                    const span = document.createElement('span');
                    span.className = 'fas fa-times'

                    return span;
                })(),
            );

            div.append(a);
        }

        div.append(
            (() => {
                const span = document.createElement('span');
                span.className = 'drag-handle';
                span.append(
                    (() => {
                        const span = document.createElement('span');
                        span.className = 'fas fa-grip fa-sm';

                        return span;
                    })(),
                );

                return span;
            })(),
        );

        if (this.params.itemsEditable && this.allowCustomOptions) {
            div.append(
                (() => {
                    const span = document.createElement('span');
                    span.className = 'item-button'
                    span.append(
                        (() => {
                            const a = document.createElement('a');
                            a.role = 'button';
                            a.tabIndex = 0;
                            a.dataset.value = value;
                            a.dataset.action = 'editItem';
                            a.append(
                                (() => {
                                    const span = document.createElement('span');
                                    span.className = 'fas fa-pencil-alt fa-sm';

                                    return span;
                                })(),
                            );

                            return a;
                        })(),
                    )

                    return span;
                })(),
            );
        }

        div.append(
            (() => {
                const span = document.createElement('span');
                span.classList.add('text');
                span.textContent = text;

                return span;
            })(),
        );

        return div.outerHTML;
    }

    protected addValue(value: string) {
        if (this.selected.indexOf(value) === -1) {
            const html = this.getItemHtml(value);

            this.$list.append(html);
            this.selected.push(value);
            this.trigger('change');
        }
    }

    protected removeValue(value: string) {
        const valueInternal = CSS.escape(value);

        this.$list.children('[data-value="' + valueInternal + '"]').remove();

        const index = this.selected.indexOf(value);

        this.selected.splice(index, 1);
        this.trigger('change');
    }

    fetch() {
        const data = {} as Record<string, any>;

        let list = Espo.Utils.clone(this.selected || []);

        const translations = this.translatedOptions;

        if (this.params.isSorted && translations) {
            list = list.sort((v1, v2) => {
                 return (translations[v1] || v1)
                     .localeCompare(translations[v2] || v2);
            });
        }

        data[this.name] = list;

        return data;
    }

    fetchSearch(): Record<string, any> | null {
        const type = this.$el.find('select.search-type').val() || 'anyOf';

        let valueList: string[] = [];

        if (['anyOf', 'noneOf', 'allOf'].includes(type)) {
            valueList = (this.$element?.val() as string).split(this.itemDelimiter);

            if (valueList.length === 1 && valueList[0] === '') {
                valueList = [];
            }

            if (valueList.length === 0) {
               if (type === 'anyOf') {
                   return {
                       type: 'any',
                       data: {
                           type: type,
                           valueList: valueList,
                       },
                   };
               }

               if (type === 'noneOf') {
                   return {
                       type: 'any',
                       data: {
                           type: type,
                           valueList: valueList,
                       },
                   };
               }

               if (type === 'allOf') {
                   return {
                       type: 'any',
                       data: {
                           type: type,
                           valueList: valueList,
                       },
                   };
               }
           }
        }

        if (type === 'anyOf') {
            const data = {
                type: 'arrayAnyOf',
                value: valueList,
                data: {
                    type: 'anyOf',
                    valueList: valueList,
                },
            } as any;

            if (!valueList.length) {
                data.value = null;
            }

            return data;
        }

        if (type === 'noneOf') {
            return {
                type: 'arrayNoneOf',
                value: valueList,
                data: {
                    type: 'noneOf',
                    valueList: valueList,
                },
            };
        }

        if (type === 'allOf') {
            const data = {
                type: 'arrayAllOf',
                value: valueList,
                data: {
                    type: 'allOf',
                    valueList: valueList,
                },
            } as any;

            if (!valueList.length) {
                data.value = null;
            }

            return data;
        }

        if (type === 'isEmpty') {
            return {
                type: 'arrayIsEmpty',
                data: {
                    type: 'isEmpty',
                },
            };
        }

        if (type === 'isNotEmpty') {
            return {
                type: 'arrayIsNotEmpty',
                data: {
                    type: 'isNotEmpty',
                },
            };
        }

        return null;
    }

    validateRequired() {
        if (this.isRequired()) {
            const value = this.model.get(this.name);

            if (!value || value.length === 0) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg, '.array-control-container');

                return true;
            }
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    validateMaxCount() {
        if (this.params.maxCount) {
            const itemList = this.model.get(this.name) || [];

            if (itemList.length > this.params.maxCount) {
                const msg =
                    this.translate('fieldExceedsMaxCount', 'messages')
                        .replace('{field}', this.getLabelText())
                        .replace('{maxCount}', this.params.maxCount.toString());

                this.showValidationMessage(msg, '.array-control-container');

                return true;
            }
        }

        return false;
    }

    getSearchType() {
        return this.getSearchParamsData().type || 'anyOf';
    }

    protected getAddItemModalOptions(): {
        translatedOptions: Record<string, any> | null,
        options: string[],
    } | Record<string, any> {

        const options: string[] = [];

        this.params.options?.forEach(item => {
            if (!this.selected.includes(item)) {
                options.push(item);
            }
        });

        return {
            options: options,
            translatedOptions: this.translatedOptions,
        };
    }

    protected async actionAddItem(): Promise<import('views/modals/array-field-add').default> {
        const view = await this.createView('dialog', this.addItemModalView, this.getAddItemModalOptions()) as
            import('views/modals/array-field-add').default;

        view.render().then(() => {});

        view.once('add', (item: string) => {
            this.addValue(item);
            view.close();
        });

        view.once('add-mass', (items: string[]) => {
            items.forEach(item => this.addValue(item));
            view.close();
        });

        return view;
    }

    protected async actionEditItem(value: string) {
        const view = new EditItemModalView({
            value: value,
            required: this.noEmptyString,
            maxLength: this.maxItemLength,
            onApply: async (data: {value: string}) => {
                const index = this.selected.findIndex(it => it === value);

                if (index < 0) {
                    return;
                }

                this.selected[index] = data.value;

                this.selected = this.selected.filter((it, i) => this.selected.indexOf(it) === i);

                await this.reRender();
                this.trigger('change');
            },
        });

        await this.assignView('dialog', view);
        await view.render();

    }

    // noinspection JSUnusedGlobalSymbols
    protected validateNoInputValue(): boolean {
        if (!this.element) {
            return false;
        }

        const input = this.element.querySelector('input.select');

        if (!(input instanceof HTMLInputElement)) {
            return false;
        }

        if (!input.value) {
            return false;
        }

        const message = this.translate('arrayInputNotEmpty', 'messages');

        this.showValidationMessage(message, 'input.select');

        return true;
    }
}

export default ArrayFieldView;

interface EditModalOptions extends ModalOptions {
    value: string;
    maxLength: number | null;
    required: boolean;
    onApply: (item: {value: string}) => void;
}

class EditItemModalView extends ModalView<{model: Model, options: EditModalOptions}> {

    // language=Handlebars
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `

    private recordView: EditForModalRecordView

    options: EditModalOptions

    constructor(options: EditModalOptions) {
        super(options);
    }

    protected setup() {
        this.buttonList = [
            {
                name: 'apply',
                label: 'Apply',
                style: 'danger',
                onClick: () => this.actionApply(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.actionCancel(),
            },
        ];

        this.shortcutKeys = {
            'Control+Enter': () => this.actionApply(),
        };

        this.headerText = this.translate('Edit Item');

        this.model = new Model({
            value: this.options.value,
        });

        this.recordView = new EditForModalRecordView({
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'value',
                                    labelText: this.translate('Value'),
                                    params: {
                                        required: this.options.required,
                                        maxLength: this.options.maxLength,
                                    },
                                })
                            },
                            false
                        ]
                    ],
                },
            ],
        });

        this.assignView('record', this.recordView);
    }

    private actionApply() {
        const data = this.recordView.processFetch();

        if (!data) {
            return;
        }

        const value = this.model.attributes.value ?? '';

        this.options.onApply({value});

        this.close();
    }
}
