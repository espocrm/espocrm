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

import BaseFieldView, {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';
import RecordModal from 'helpers/record-modal';
import Autocomplete from 'ui/autocomplete';
import CascadeLinksHelper from 'helpers/field/cascade-links';
import {AdvancedFilter} from 'search-manager';
import Model from 'model';
import {AjaxPromise} from 'util/ajax';
import Ajax from 'ajax';
import Ui from 'ui';


interface LinkMultipleParams extends BaseParams{
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Autocomplete on empty input.
     */
    autocompleteOnEmpty?: boolean;
    /**
     * Sortable
     */
    sortable?: boolean;
    /**
     * Show 'Create' button.
     */
    createButton?: boolean;
    /**
     * A max number of items.
     */
    maxCount?: number;
    /**
     * An entity type.
     *
     * @since 9.1.0
     */
    entity?: string;
}

/**
 * Options.
 */
export interface LinkMultipleOptions extends BaseOptions {
    /**
     * Disable create button in the select modal.
     */
    createDisabled?: boolean;
    /**
     * Cascading fields logic.
     *
     * @since 10.0.0
     */
    cascadingLogic?: CascadingLogic;
    /**
     * A foreign entity type.
     */
    foreignScope?: string;
}

interface CascadingLogic {
    items: {
        localField: string,
        foreignField: string,
        matchRequired: boolean,
    }[]
}

interface SelectFilters {
    bool?: string[];
    advanced?: Object;
    primary?: string;
    orderBy?: string;
    order?: 'asc'|'desc';
}

/**
 * A link-multiple field (for has-many relations).
 *
 */
class LinkMultipleFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends LinkMultipleOptions = LinkMultipleOptions,
    P extends LinkMultipleParams = LinkMultipleParams,
> extends BaseFieldView<S, O, P> {

    readonly type: string = 'linkMultiple'

    protected listTemplate = 'fields/link-multiple/list'
    protected detailTemplate = 'fields/link-multiple/detail'
    protected editTemplate = 'fields/link-multiple/edit'
    protected searchTemplate = 'fields/link-multiple/search'

    // noinspection JSUnusedGlobalSymbols
    protected listLinkTemplateContent = `
        {{#if value}}
            <a
                href="#{{scope}}/view/{{model.id}}"
                class="link"
                data-id="{{model.id}}"
            >{{{value}}}</a>
        {{/if}}
    `

    protected validations: (FieldValidator | string)[] = [
        'required',
        'maxCount',
    ]

    /**
     * A name-hash attribute name.
     */
    protected nameHashName: string

    /**
     * A IDs attribute name.
     */
    protected idsName: string

    protected nameHash: Record<string, string> | null = null

    protected ids: string[] | null = null

    /**
     * A foreign entity type.
     */
    protected foreignScope: string

    /**
     * Autocomplete disabled.
     */
    protected autocompleteDisabled: boolean = false

    /**
     * A select-record view.
     */
    protected selectRecordsView: string = 'views/modals/select-records'

    /**
     * Create disabled.
     */
    protected createDisabled: boolean = false

    /**
     * Force create button even is disabled in clientDefs > relationshipPanels.
     */
    protected forceCreateButton: boolean = false

    /**
     * To display the create button.
     */
    protected createButton: boolean = false

    protected sortable: boolean = false

    /**
     * A search type list.
     */
    protected searchTypeList: string[] = [
        'anyOf',
        'isEmpty',
        'isNotEmpty',
        'noneOf',
        'allOf',
    ]

    /**
     * A primary filter list that will be available when selecting a record.
     */
    protected selectFilterList: string[] | null = null

    /**
     * A select bool filter list.
     */
    protected selectBoolFilterList: string[] | null= null

    /**
     * A select primary filter.
     */
    protected selectPrimaryFilterName: string | null = null

    /**
     * An autocomplete max record number.
     */
    protected autocompleteMaxCount: number | null = null

    /**
     * Trigger autocomplete on empty input.
     */
    protected autocompleteOnEmpty: boolean = false

    /**
     * Select all attributes.
     */
    protected forceSelectAllAttributes: boolean= false

    protected iconHtml: string = ''

    /**
     * A link element class name. Applicable in the detail mode.
     *
     * @protected
     * @since 9.1.6
     */
    protected linkClass: string

    /**
     * Panel definitions.
     */
    protected panelDefs: Record<string, any>

    protected mandatorySelectAttributeList: string[] | null = null

    protected data() {
        const ids = this.model.get(this.idsName);
        const createButton = this.createButton && (!this.createDisabled || this.forceCreateButton);

        return {
            ...super.data(),
            idValues: this.model.get(this.idsName),
            idValuesString: ids ? ids.join(',') : '',
            nameHash: this.model.get(this.nameHashName),
            foreignScope: this.foreignScope,
            valueIsSet: this.model.has(this.idsName),
            createButton: createButton,
        };
    }

    /**
     * Get advanced filters (field filters) to be applied when select a record.
     * Can be extended.
     */
    protected getSelectFilters(): Record<string, AdvancedFilter> | null {
        return null;
    }

    /**
     * Get a select bool filter list. Applied when select a record.
     * Can be extended.
     */
    protected getSelectBoolFilterList(): string[] | null {
        return this.selectBoolFilterList;
    }

    /**
     * Get a select primary filter. Applied when select a record.
     * Can be extended.
     */
    protected getSelectPrimaryFilterName(): string | null {
        return this.selectPrimaryFilterName;
    }

    /**
     * Get a primary filter list that will be available when selecting a record.
     * Can be extended.
     */
    protected getSelectFilterList(): string[] | null {
        return this.selectFilterList;
    }

    /**
     * Attributes to pass to a model when creating a new record.
     * Can be extended.
     */
    protected getCreateAttributes(): Record<string, unknown> | null {
        const attributeMap: Record<string, string> = this.panelDefs.createAttributeMap ?? {};

        const attributes: Record<string, unknown> = {};

        Object.keys(attributeMap).forEach(attr => attributes[attributeMap[attr]] = this.model.get(attr));

        return {
            ...attributes,
            ...this._getCascadingCreateAttributes(),
        };
    }

    protected setup() {
        this.addHandler('auxclick', 'a[href]:not([role="button"])', (e, target) => {
            this.onAuxClickLink(e as MouseEvent, target);
        });

        this.nameHashName = this.name + 'Names';
        this.idsName = this.name + 'Ids';

        this.foreignScope =
            this.options.foreignScope ||
            this.foreignScope ||
            this.params.entity ||
            this.model.getFieldParam(this.name, 'entity') ||
            this.model.getLinkParam(this.name, 'entity');

        if ('createDisabled' in this.options) {
            this.createDisabled = this.options.createDisabled as boolean;
        }

        if (this.isSearchMode()) {
            const nameHash = this.getSearchParamsData().nameHash ?? this.searchParams?.nameHash ?? {};
            const idList = this.getSearchParamsData().idList ?? this.searchParams?.value ?? [];

            this.nameHash = Espo.Utils.clone(nameHash);
            this.ids = Espo.Utils.clone(idList);
        } else {
            this.copyValuesFromModel();
        }

        this.listenTo(this.model, 'change:' + this.idsName, () => {
            this.copyValuesFromModel();
        });

        this.sortable = this.sortable || this.params.sortable || false;

        this.iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);

        if (!this.isListMode()) {
            this.addActionHandler('selectLink', () => this.actionSelect());
            this.addActionHandler('clearLink', (_, target) => this.actionDeleteLink(target.dataset.id as string));
        }

        this.autocompleteOnEmpty = this.params.autocompleteOnEmpty || this.autocompleteOnEmpty;

        this.createButton = this.params.createButton || this.createButton;

        if (this.createButton && !this.getAcl().checkScope(this.foreignScope, 'create')) {
            this.createButton = false;
        }

        if (this.createButton) {
            this.addActionHandler('createLink', () => this.actionCreateLink());
        }

        if (this.entityType) {
            this.panelDefs = this.getMetadata()
                .get(`clientDefs.${this.entityType}.relationshipPanels.${this.name}`) ?? {};
        } else {
            this.panelDefs = {};
        }
    }

    private actionDeleteLink(id: string) {
        this.deleteLink(id);

        // noinspection JSUnresolvedReference
        this.$element?.get(0)?.focus({preventScroll: true});

        // Timeout prevents autocomplete from disappearing.
        setTimeout(() => {
            // noinspection JSUnresolvedReference
            this.$element?.get(0)?.focus({preventScroll: true});
        }, 140);
    }

    /**
     * Copy values from a model to view properties.
     */
    private copyValuesFromModel() {
        this.ids = Espo.Utils.clone(this.model.get(this.idsName) ?? []);
        this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});
    }

    /**
     * Handle a search type.
     *
     * @param type A type.
     */
    protected handleSearchType(type: string) {
        if (['anyOf', 'noneOf', 'allOf'].includes(type)) {
            this.$el.find('div.link-group-container').removeClass('hidden');
        } else {
            this.$el.find('div.link-group-container').addClass('hidden');
        }
    }

    protected setupSearch() {
        this.addHandler('change', 'select.search-type', (_, target) => {
            this.handleSearchType((target as HTMLSelectElement).value);
        });
    }

    /**
     * Get an autocomplete max record number. Can be extended.
     */
    protected getAutocompleteMaxCount(): number {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage');
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * Compose an autocomplete URL. Can be extended.
     *
     * @param q query.
     */
    protected getAutocompleteUrl(q: string): string | Promise<string> {
        // noinspection BadExpressionStatementJS
        q;

        let url = this.foreignScope + '?&maxSize=' + this.getAutocompleteMaxCount();

        if (!this.forceSelectAllAttributes) {
            const mandatorySelectAttributeList = this.mandatorySelectAttributeList ??
                this.panelDefs.selectMandatoryAttributeList;

            let select = ['id', 'name'];

            if (mandatorySelectAttributeList) {
                select = select.concat(mandatorySelectAttributeList);
            }

            url += '&select=' + select.join(',')
        }

        const notSelectedFilter = this.ids && this.ids.length ?
            {
                // Prefix to prevent conflict when the 'id' field is in filters.
                _id: {
                    type: 'notIn',
                    attribute: 'id',
                    value: this.ids,
                }
            } :
            {};

        return new Promise(async resolve => {
            const filters = await this._getSelectFilters();

            if (filters.bool) {
                url += '&' + $.param({boolFilterList: filters.bool});
            }

            if (filters.primary) {
                url += '&' + $.param({primaryFilter: filters.primary});
            }

            const advanced = {
                ...notSelectedFilter,
                ...(filters.advanced ?? {}),
            };

            if (Object.keys(advanced).length) {
                url += '&' + $.param({where: advanced});
            }

            const orderBy = filters.orderBy || this.panelDefs.selectOrderBy;
            const orderDirection = filters.orderBy ? filters.order : this.panelDefs.selectOrderDirection;

            if (orderBy) {
                url += '&' + $.param({
                    orderBy: orderBy,
                    order: orderDirection || 'asc',
                });
            }

            resolve(url);
        });
    }

    protected afterRender() {
        if (this.isEditMode() || this.isSearchMode()) {
            this.$element = this.$el.find('input.main-element');

            if (!this.autocompleteDisabled) {
                let lastAjaxPromise: AjaxPromise;

                const autocomplete = new Autocomplete(this.$element?.get(0) as HTMLInputElement, {
                    focusOnSelect: true,
                    handleFocusMode: 3,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    forceHide: true,
                    onSelect: (item: any) => {
                        this.getModelFactory().create(this.foreignScope, (model: Model) => {
                            model.setMultiple(item.attributes);

                            this.select([model])

                            this.$element?.val('');
                            this.$element?.trigger('focus');
                        });
                    },
                    lookupFunction: async (query: string) => {
                        if (!this.autocompleteOnEmpty && query.length === 0) {
                            const onEmptyPromise = this.getOnEmptyAutocomplete();

                            if (onEmptyPromise) {
                                const list = await onEmptyPromise;

                                return this._transformAutocompleteResult({list: list});
                            }

                            return [];
                        }

                        const url = await this.getAutocompleteUrl(query);

                        if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                            lastAjaxPromise.abort();
                        }

                        lastAjaxPromise = Ajax.getRequest(url, {q: query});

                        const response: {list: Record<string, unknown>[]} = await lastAjaxPromise;

                        return response.list.map(item => ({
                            value: item.name,
                            attributes: item,
                        }));
                    },
                });

                this.once('render remove', () => autocomplete.dispose());
            }

            this.renderLinks();

            if (this.isEditMode() && this.sortable) {
                // noinspection JSUnresolvedReference
                this.$el.find('.link-container').sortable({
                    stop: () => {
                        this.fetchFromDom();
                        this.trigger('change');
                    },
                });
            }

            if (this.isSearchMode()) {
                const type = this.$el.find('select.search-type').val();

                this.handleSearchType(type);

                this.$el.find('select.search-type').on('change', () => {
                    this.trigger('change');
                });
            }
        }
    }

    /**
     * Render items.
     *
     * @protected
     */
    renderLinks() {
        (this.ids ?? []).forEach(id => {
            this.addLinkHtml(id, this.nameHash?.[id]);
        });
    }

    /**
     * Delete an item.
     *
     * @param id An ID.
     */
    protected deleteLink(id: string) {
        if (!this.nameHash) {
            throw new Error("No nameHash.");
        }

        this.trigger('delete-link', id);
        this.trigger(`delete-link:${id}`);

        this.deleteLinkHtml(id);

        const ids = this.ids as string[];

        const index = ids.indexOf(id);

        if (index > -1) {
            ids.splice(index, 1);
        }

        delete this.nameHash[id];

        this.afterDeleteLink(id);
        this.trigger('change');
    }

    /**
     * Add an item.
     *
     * @param id An ID.
     * @param name A name.
     */
    protected addLink(id: string, name: string) {
        if (!this.ids || !this.nameHash) {
            throw new Error("No ids or nameHash.");
        }

        if (!this.ids.includes(id)) {
            this.ids.push(id);

            this.nameHash[id] = name;

            this.addLinkHtml(id, name);
            this.afterAddLink(id);

            this.trigger('add-link', id);
            this.trigger('add-link:' + id);
        }

        this.trigger('change');
    }

    /**
     * @param id An ID.
     */
    protected afterDeleteLink(id: string) {
        // noinspection BadExpressionStatementJS
        id;
    }

    /**
     * @param id An ID.
     */
    protected afterAddLink(id: string) {
        // noinspection BadExpressionStatementJS
        id;
    }

    /**
     * @param id An ID.
     */
    protected deleteLinkHtml(id: string) {
        this.$el.find(`.link-${id}`).remove();
    }

    /**
     * Add an item for edit mode.
     *
     * @param id An ID.
     * @param name A name.
     * @internal
     */
    protected addLinkHtml(id: string, name?: string): JQuery | null {
        // Beware of XSS.

        name = name ?? id;

        const $container = this.$el.find('.link-container');

        const itemElement = this.prepareEditItemElement(id, name);

        $container.append(itemElement);

        return $(itemElement);
    }

    /**
     * @param id An ID.
     * @param name A name.
     * @return {HTMLElement}
     */
    protected prepareEditItemElement(id: string, name: string): HTMLElement {
        const item = document.createElement('div');
        item.classList.add('link-' + id);
        item.classList.add('list-group-item');
        item.dataset.id = id;

        item.append(
            (() => {
                const a = document.createElement('a');
                a.role = 'button';
                a.tabIndex = 0;
                a.classList.add('pull-right');
                a.dataset.id = id;
                a.dataset.action = 'clearLink';
                a.append(
                    (() => {
                        const span = document.createElement('span');
                        span.classList.add('fas', 'fa-times');

                        return span;
                    })(),
                );

                return a;
            })()
        );

        item.append(
            (() => {
                const span = document.createElement('span');
                span.classList.add('text');
                span.textContent = name;

                return span;
            })()
        );

        return item;
    }

    /**
     * @param id An ID.
     */
    getIconHtml(id: string): string {
        // noinspection BadExpressionStatementJS
        id;

        return this.iconHtml;
    }

    /**
     * Get an item HTML for detail mode.
     *
     * @param id An ID.
     * @param name A name.
     */
    getDetailLinkHtml(id: string, name?: string): string {
        // Do not use the `html` method to avoid XSS.

        name = name ?? this.nameHash?.[id] ?? id;

        if (!name && id) {
            name = this.translate(this.foreignScope, 'scopeNames');
        }

        const iconHtml = this.isDetailMode() ?
            this.getIconHtml(id) : '';

        const $a = $('<a>')
            .attr('href', this.getUrl(id))
            .attr('data-id', id)
            .text(name);

        if (this.mode === this.MODE_LIST) {
            $a.addClass('text-default');
        } else if (this.linkClass) {
            $a.addClass(this.linkClass);
        }

        if (iconHtml) {
            $a.prepend(iconHtml)
        }

        return $a.get(0)?.outerHTML as string;
    }

    /**
     * @param id An ID.
     */
    protected getUrl(id: string): string {
        return `#${this.foreignScope}/view/${id}`;
    }

    protected getValueForDisplay(): string | null {
        if (!this.isDetailMode() && !this.isListMode()) {
            return null;
        }

        if (this.mode === this.MODE_LIST_LINK) {
            const div = document.createElement('div');

            (this.ids ?? []).forEach(id => {
                const itemDiv = document.createElement('div');
                itemDiv.classList.add('link-multiple-item');
                itemDiv.textContent = this.nameHash?.[id] ?? id;

                div.append(itemDiv);
            });

            return div.outerHTML;
        }

        const itemList: string[] = [];

        (this.ids ?? []).forEach(id => {
            itemList.push(this.getDetailLinkHtml(id));
        });

        if (!itemList.length) {
            return null;
        }

        return itemList
            .map(item => $('<div>')
                .addClass('link-multiple-item')
                .html(item)
                .wrap('<div />').parent().html()
            )
            .join('');
    }

    /** @inheritDoc */
    validateRequired() {
        if (!this.isRequired()) {
            return false;
        }

        const idList = this.model.get(this.idsName) || [];

        if (idList.length === 0) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    validateMaxCount() {
        const maxCount = this.params.maxCount;

        if (!maxCount) {
            return false;
        }

        const idList = this.model.get(this.idsName) || [];

        if (idList.length === 0) {
            return false;
        }

        if (idList.length <= maxCount) {
            return false;
        }

        const msg = this.translate('fieldExceedsMaxCount', 'messages')
            .replace('{field}', this.getLabelText())
            .replace('{maxCount}', maxCount.toString());

        this.showValidationMessage(msg);

        return true;
    }

    fetch(): Record<string, unknown> {
        const data: Record<string, any> = {};

        data[this.idsName] = Espo.Utils.clone(this.ids);
        data[this.nameHashName] = Espo.Utils.clone(this.nameHash);

        return data;
    }

    protected fetchFromDom() {
        this.ids = [];

        const items = this.element.querySelectorAll<HTMLElement>('.link-container > *');

        for (const item of items) {
            const id = item.dataset.id;

            if (!id) {
                return;
            }

            this.ids.push(id);
        }
    }

    fetchSearch():  Record<string, unknown> | null  {
        const type = this.$el.find('select.search-type').val();
        const idList = this.ids ?? [];

        if (['anyOf', 'allOf', 'noneOf'].includes(type) && !idList.length) {
            return {
                type: 'isNotNull',
                attribute: 'id',
                data: {
                    type: type,
                },
            };
        }

        let data: any;

        if (type === 'anyOf') {
            data = {
                type: 'linkedWith',
                value: idList,
                data: {
                    type: type,
                    nameHash: this.nameHash,
                },
            };

            return data;
        }

        if (type === 'allOf') {
            data = {
                type: 'linkedWithAll',
                value: idList,
                data: {
                    type: type,
                    nameHash: this.nameHash,
                },
            };

            if (!idList.length) {
                data.value = null;
            }

            return data;
        }

        if (type === 'noneOf') {
            data = {
                type: 'notLinkedWith',
                value: idList,
                data: {
                    type: type,
                    nameHash: this.nameHash,
                },
            };

            return data;
        }

        if (type === 'isEmpty') {
            data = {
                type: 'isNotLinked',
                data: {
                    type: type,
                },
            };

            return data;
        }

        if (type === 'isNotEmpty') {
            data = {
                type: 'isLinked',
                data: {
                    type: type,
                },
            };

            return data;
        }

        return null;
    }

    protected getSearchType() {
        return this.getSearchParamsData().type ??
            this.searchParams?.typeFront ??
            this.searchParams?.type ??
            'anyOf';
    }

    protected quickView(id: string) {
        const entityType = this.foreignScope;

        const helper = new RecordModal();

        helper.showDetail(this, {
            id: id,
            entityType: entityType,
        });
    }

    protected async actionSelect() {
        Ui.notifyWait();

        const viewName = this.panelDefs.selectModalView ??
            this.getMetadata().get(`clientDefs.${this.foreignScope}.modalViews.select`) ??
            this.selectRecordsView;

        const mandatorySelectAttributeList = this.mandatorySelectAttributeList ||
            this.panelDefs.selectMandatoryAttributeList;

        const createButton = this.isEditMode() &&
            (!this.createDisabled && !this.panelDefs.createDisabled || this.forceCreateButton);

        const createAttributesProvider = createButton ?
            this.getCreateAttributesProvider() :
            null;

        const filters = await this._getSelectFilters();

        const orderBy = filters.orderBy || this.panelDefs.selectOrderBy;
        const orderDirection = filters.orderBy ? filters.order : this.panelDefs.selectOrderDirection;

        const options = {
            entityType: this.foreignScope,
            createButton: createButton,
            filters: filters.advanced,
            boolFilterList: filters.bool,
            primaryFilterName: filters.primary,
            filterList: this.getSelectFilterList(),
            multiple: true,
            mandatorySelectAttributeList: mandatorySelectAttributeList,
            forceSelectAllAttributes: this.forceSelectAllAttributes,
            createAttributesProvider: createAttributesProvider,
            layoutName: this.panelDefs.selectLayout,
            orderBy: orderBy,
            orderDirection: orderDirection,
            onSelect: (models: Model[]) => {
                this.select(models);
            },
        };

        const view = await this.createView('modal', viewName, options);

        await view.render();

        Ui.notify();
    }

    protected getCreateAttributesProvider(): () => Promise<Record<string, unknown>> {
        return () => {
            const attributes = this.getCreateAttributes() ?? {};

            if (!this.panelDefs.createHandler) {
                return Promise.resolve(attributes);
            }

            return new Promise(resolve => {
                Espo.loader.requirePromise(this.panelDefs.createHandler)
                    .then((Handler: any) => new Handler(this.getHelper()))
                    .then(async /** import('handlers/create-related').default */handler => {
                        const additionalAttributes = await handler.getAttributes(this.model, this.name);

                        resolve({
                            ...attributes,
                            ...additionalAttributes,
                        });
                    });
            });
        };
    }

    /**
     * On records select.
     *
     * @since 8.0.4
     */
    protected select(models: Model[]) {
        models.forEach(model => {
            this.addLink(model.id as any, model.attributes.name);
        });
    }

    private _getSelectFilters(): Promise<SelectFilters>  {
        const handler = this.panelDefs.selectHandler;

        const localBoolFilterList = this.getSelectBoolFilterList();

        if (!handler || this.isSearchMode()) {
            const boolFilterList = (localBoolFilterList || this.panelDefs.selectBoolFilterList) ?
                [
                    ...(localBoolFilterList ?? []),
                    ...(this.panelDefs.selectBoolFilterList ?? []),
                ] :
                undefined;

            const advanced = {
                ...(this.getSelectFilters() ?? {}),
                ...(!this.isSearchMode() ? this._getCascadingFilters() : {}),
            };

            return Promise.resolve({
                primary: this.getSelectPrimaryFilterName() ?? this.panelDefs.selectPrimaryFilterName,
                bool: boolFilterList,
                advanced: advanced,
            });
        }

        return new Promise(async resolve => {
            Espo.loader.requirePromise(handler)
                .then((Handler: any) => new Handler(this.getHelper()))
                .then(/** module:handlers/select-related */handler => {
                    return handler.getFilters(this.model);
                })
                .then(filters => {
                    const advanced = {
                        ...(this.getSelectFilters() || {}),
                        ...(filters.advanced || {}),
                        ...this._getCascadingFilters(),
                    };

                    const primaryFilter = this.getSelectPrimaryFilterName() ||
                        filters.primary || this.panelDefs.selectPrimaryFilterName;

                    const boolFilterList =
                        (localBoolFilterList || filters.bool || this.panelDefs.selectBoolFilterList) ?
                        [
                            ...(localBoolFilterList ?? []),
                            ...(filters.bool ?? []),
                            ...(this.panelDefs.selectBoolFilterList ?? []),
                        ] :
                        undefined;

                    resolve({
                        bool: boolFilterList,
                        primary: primaryFilter,
                        advanced: advanced,
                    });
                });
        });
    }

    protected async actionCreateLink() {
        const helper = new RecordModal();

        const attributes = await this.getCreateAttributesProvider()();

        await helper.showCreate(this, {
            entityType: this.foreignScope,
            fullFormDisabled: true,
            attributes: attributes,
            afterSave: (model: Model) => this.select([model]),
        });
    }

    protected _transformAutocompleteResult(response: {list: Record<string, any>[]}): Record<string, any>[] {
        const list: any = [];

        response.list.forEach(item => {
            list.push({
                id: item.id,
                name: item.name || item.id,
                data: item.id,
                value: item.name || item.id,
                attributes: item,
            });
        });

        return list;
    }

    /**
     * Get an empty autocomplete result.
     */
    protected getOnEmptyAutocomplete(): Promise<[{name: string | null, id: string} & Record<string, any>]> | null {
        return null;
    }

    private _createCascadeLinksHelper(): CascadeLinksHelper | null {
        const items = this.options.cascadingLogic?.items ?? [];

        if (!items.length) {
            return null;
        }

        if (!this.foreignScope) {
            return null;
        }

        return new CascadeLinksHelper({
            model: this.model,
            foreignEntityType: this.foreignScope,
            items: items,
        });
    }

    private _getCascadingFilters(): Record<string, AdvancedFilter> {
        return this._createCascadeLinksHelper()?.prepareFilters() ?? {};
    }

    private _getCascadingCreateAttributes(): Record<string, any> {
        return this._createCascadeLinksHelper()?.prepareCreateAttributes() ?? {};
    }

    private onAuxClickLink(e: MouseEvent, target: HTMLElement) {
        if (!this.isReadMode()) {
            return;
        }

        const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

        if (!isCombination) {
            return;
        }

        const id = target.dataset.id;

        if (!id) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.quickView(id);
    }
}

export default LinkMultipleFieldView;
