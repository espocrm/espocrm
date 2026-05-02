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

import BaseFieldView, {BaseOptions, BaseParams, BaseViewSchema} from 'views/fields/base';
import RecordModal from 'helpers/record-modal';
import Autocomplete from 'ui/autocomplete';
import CascadeLinksHelper from 'helpers/field/cascade-links';
import Ajax from 'ajax';
import Ui from 'ui';
import {AdvancedFilter} from 'search-manager';
import Model from 'model';
import {AjaxPromise} from 'util/ajax';

/**
 * Parameters.
 */
export interface LinkParams extends BaseParams {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Autocomplete on empty input.
     */
    autocompleteOnEmpty?: boolean;
    /**
     * Show 'Create' button.
     */
    createButton?: boolean;
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
export interface LinkOptions extends BaseOptions {
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

interface SelectFieldHandler {
    getAttributes: (model: Model) => Promise<Record<string, unknown>>;
    getClearAttributes: () => Promise<Record<string, unknown>>;
}

interface SelectFilters {
    bool?: string[];
    advanced?: Object;
    primary?: string;
    orderBy?: string;
    order?: 'asc'|'desc';
}

/**
 * A link field (belongs-to relation).
 */
class LinkFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends LinkOptions = LinkOptions,
    P extends LinkParams = LinkParams,
> extends BaseFieldView<S, O, P> {

    readonly type: string = 'link'

    protected listTemplate = 'fields/link/list'
    protected detailTemplate = 'fields/link/detail'
    protected editTemplate = 'fields/link/edit'
    protected searchTemplate = 'fields/link/search'

    /**
     * A name attribute name.
     */
    protected nameName: string

    /**
     * An ID attribute name.
     */
    protected idName: string

    /**
     * A foreign entity type.
     */
    protected foreignScope: string

    /**
     * A select-record view.
     */
    protected selectRecordsView: string = 'views/modals/select-records'

    /**
     * Autocomplete disabled.
     */
    protected autocompleteDisabled: boolean = false

    /**
     * Create disabled.
     */
    protected createDisabled: boolean = false

    /**
     * To display the create button.
     */
    protected createButton: boolean = false

    /**
     * Force create button even is disabled in clientDefs > relationshipPanels.
     */
    protected forceCreateButton: boolean = false

    /**
     * A search type list.
     */
    protected searchTypeList: string[] = [
        'is',
        'isEmpty',
        'isNotEmpty',
        'isNot',
        'isOneOf',
        'isNotOneOf',
    ]

    /**
     * A primary filter list that will be available when selecting a record.
     */
    protected selectFilterList: string[] | null = null

    /**
     * A select primary filter.
     */
    protected selectPrimaryFilterName: string[] | null = null

    /**
     * A select bool filter list.
     */
    protected selectBoolFilterList: string[] | null = null

    /**
     * An autocomplete max record number.
     */
    protected autocompleteMaxCount: number | null = null

    /**
     * Select all attributes.
     */
    protected forceSelectAllAttributes: boolean = false

    protected mandatorySelectAttributeList: string[] | null = null

    /**
     * Trigger autocomplete on empty input.
     */
    protected autocompleteOnEmpty: boolean = false

    /**
     * A link element class name. Applicable in the detail mode.
     *
     * @since 9.1.6
     */
    protected linkClass: string | null = null

    /**
     * @since 9.2.5
     */
    protected foreignNameAttribute: string

    /**
     * Panel definitions.
     */
    protected panelDefs: Record<string, any>

    private $elementName: JQuery
    private $elementId: JQuery

    private _dependantForeignMap: Record<string, string>

    protected data(): Record<string, any> {
        let nameValue = this.model.has(this.nameName) ?
            this.model.get(this.nameName) :
            this.model.get(this.idName);

        if (nameValue === null) {
            nameValue = this.model.get(this.idName);
        }

        if (this.isReadMode() && !nameValue && this.model.get(this.idName)) {
            nameValue = this.translate(this.foreignScope, 'scopeNames');
        }

        let iconHtml = null;

        if (this.isDetailMode() || this.isListMode()) {
            iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
        }

        const createButton = this.createButton && (!this.createDisabled || this.forceCreateButton);

        return {
            ...super.data(),
            idName: this.idName,
            nameName: this.nameName,
            idValue: this.model.get(this.idName),
            nameValue: nameValue,
            foreignScope: this.foreignScope,
            valueIsSet: this.model.has(this.idName),
            iconHtml: iconHtml,
            url: this.getUrl(),
            createButton: createButton,
            linkClass: this.linkClass,
        };
    }

    protected getUrl(): string | null {
        const id = this.model.get(this.idName);

        if (!id) {
            return null;
        }

        return `#${this.foreignScope}/view/${id}`;
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
    protected getSelectPrimaryFilterName(): string[] | null {
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

        const attributes = {} as Record<string, unknown>;

        Object.keys(attributeMap)
            .forEach(attribute => {
                attributes[attributeMap[attribute]] = this.model.get(attribute);
            });

        return {
            ...attributes,
            ...this._getCascadingCreateAttributes(),
        };
    }

    protected setup() {
        this.addHandler('auxclick', 'a[href]:not([role="button"])', (e) => {
            this.onAuxClickLink(e as MouseEvent);
        });

        this.nameName = this.name + 'Name';
        this.idName = this.name + 'Id';

        this.foreignScope = this.options.foreignScope || this.foreignScope;

        this.foreignScope =
            this.foreignScope ??
            this.params.entity ??
            this.model.getFieldParam(this.name, 'entity') ??
            this.model.getLinkParam(this.name, 'entity');

        this.foreignNameAttribute = this.model.getLinkParam(this.name, 'foreignName') ??
            this.getMetadata().get(`clientDefs.${this.foreignScope}.nameAttribute`) ??
            'name';

        if ('createDisabled' in this.options) {
            this.createDisabled = this.options.createDisabled as boolean;
        }

        if (!this.isListMode()) {
            this.addActionHandler('selectLink', () => this.actionSelect());
            this.addActionHandler('clearLink', () => this.clearLink());
        }

        if (this.isSearchMode()) {
            this.addActionHandler('selectLinkOneOf', () => this.actionSelectOneOf());
            this.addActionHandler('clearLinkOneOf', (_e, target) => this.deleteLinkOneOf(target.dataset.id as string));
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

        if (this.panelDefs.createDisabled) {
            this.createDisabled = true;
        }
    }

    private onAuxClickLink(e: MouseEvent) {
        if (!this.isReadMode()) {
            return;
        }

        const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

        if (!isCombination) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.quickView();
    }

    /**
     * Select.
     *
     * @param model A model.
     */
    protected select(model: Model): Promise<void> {
        this.$elementName.val(model.get(this.foreignNameAttribute) ?? model.id);
        this.$elementId.val(model.id as string);

        if (this.mode === this.MODE_SEARCH) {
            this.searchData.idValue = model.id;
            this.searchData.nameValue = model.get(this.foreignNameAttribute) ?? model.id;
        }

        this.trigger('change');

        this.controlCreateButtonVisibility();

        const attributes = {} as Record<string, any>;

        for (const [foreign, field] of Object.entries(this.getDependantForeignMap())) {
            attributes[field] = model.get(foreign);
        }

        this.getSelectFieldHandler().then(async handler => {
            this.model.setMultiple({
                ...attributes,
                ...(await handler.getAttributes(model)),
            }, {fromField: this.name});
        });

        return Promise.resolve();
    }

    /**
     * Clear.
     */
    clearLink() {
        this.$elementName.val('');
        this.$elementId.val('');

        this.trigger('change');

        this.controlCreateButtonVisibility();

        for (const [, field] of Object.entries(this.getDependantForeignMap())) {
            this.model.unset(field, {fromField: this.name})
        }

        this.getSelectFieldHandler().then(handler => {
            handler.getClearAttributes()
                .then(attributes => {
                    this.model.setMultiple(attributes, {fromField: this.name});
                });
        });
    }

    private controlCreateButtonVisibility() {
        if (!this.createButton) {
            return;
        }

        const $btn = this.$el.find('[data-action="createLink"]');

        this.model.get(this.idName) ?
            $btn.addClass('hidden') :
            $btn.removeClass('hidden');
    }

    private getSelectFieldHandler(): Promise<SelectFieldHandler> {
        if (!this.panelDefs.selectFieldHandler) {
            return Promise.resolve({
                getClearAttributes: () => Promise.resolve({}),
                getAttributes: () => Promise.resolve({}),
            });
        }

        return new Promise(resolve => {
            Espo.loader.requirePromise(this.panelDefs.selectFieldHandler)
                .then((Handler: any) => {
                    // Model is passed as of v8.2.
                    const handler = new Handler(this.getHelper(), this.model);

                    resolve(handler);
                });
        });
    }

    protected setupSearch() {
        this.searchData.oneOfIdList = this.getSearchParamsData().oneOfIdList ||
            this.searchParams?.oneOfIdList ?? [];

        this.searchData.oneOfNameHash = this.getSearchParamsData().oneOfNameHash ||
            this.searchParams?.oneOfNameHash ?? {};

        if (['is', 'isNot', 'equals'].includes(this.getSearchType())) {
            this.searchData.idValue = this.getSearchParamsData().idValue ??
                this.searchParams?.idValue ??
                this.searchParams?.value;

            this.searchData.nameValue = this.getSearchParamsData().nameValue ??
                this.searchParams?.nameValue ??
                this.searchParams?.valueName;
        }

        this.addHandler('change', 'select.search-type', (_, target) => {
            this.handleSearchType((target as HTMLSelectElement).value);
        });
    }

    /**
     * Handle a search type.
     *
     * @param {string} type A type.
     */
    protected handleSearchType(type: string) {
        if (['is', 'isNot', 'isNotAndIsNotEmpty'].includes(type)) {
            this.$el.find('div.primary').removeClass('hidden');
        } else {
            this.$el.find('div.primary').addClass('hidden');
        }

        if (['isOneOf', 'isNotOneOf', 'isNotOneOfAndIsNotEmpty'].includes(type)) {
            this.$el.find('div.one-of-container').removeClass('hidden');
        } else {
            this.$el.find('div.one-of-container').addClass('hidden');
        }
    }

    /**
     * Get an autocomplete max record number. Can be extended.
     */
    protected getAutocompleteMaxCount(): number {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage') as number;
    }

    private getMandatorySelectAttributeList(): string[] {
        const list = this.mandatorySelectAttributeList || this.panelDefs.selectMandatoryAttributeList || []

        const map = this.getDependantForeignMap();

        return [...list, ...Object.keys(map)];
    }

    private getDependantForeignMap(): Record<string, string> {
        if (this._dependantForeignMap) {
            return this._dependantForeignMap;
        }

        const map = {} as Record<string, string>;

        this.model.getFieldList()
            .filter(it => {
                return this.model.getFieldType(it) === 'foreign' &&
                    this.model.getFieldParam(it, 'link') === this.name;
            })
            .forEach(field => {
                const foreign = this.model.getFieldParam(field, 'field');

                if (!foreign) {
                    return;
                }

                map[foreign] = field ;
            });

        this._dependantForeignMap = map;

        return map;
    }

    /**
     * Compose an autocomplete URL. Can be extended.
     *
     * @param q A query.
     */
    protected getAutocompleteUrl(q: string): string | Promise<string> {
        // noinspection BadExpressionStatementJS
        q;

        let url = this.foreignScope + '?maxSize=' + this.getAutocompleteMaxCount();

        if (!this.forceSelectAllAttributes) {
            const mandatorySelectAttributeList = this.getMandatorySelectAttributeList();

            let select = ['id', this.foreignNameAttribute];

            if (mandatorySelectAttributeList) {
                select = select.concat(mandatorySelectAttributeList);
            }

            url += '&select=' + select.join(',');
        }

        return new Promise(async resolve => {
            const filters = await this._getSelectFilters();

            if (filters.bool) {
                url += '&' + $.param({'boolFilterList': filters.bool});
            }

            if (filters.primary) {
                url += '&' + $.param({'primaryFilter': filters.primary});
            }

            if (filters.advanced && Object.keys(filters.advanced).length) {
                url += '&' + $.param({'where': filters.advanced});
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
            this.$elementId = this.$el.find('input[data-name="' + this.idName + '"]');
            this.$elementName = this.$el.find('input[data-name="' + this.nameName + '"]');

            this.$elementName.on('change', () => {
                if (this.$elementName.val() === '') {
                    this.clearLink();
                }
            });

            this.$elementName.on('blur', e => {
                setTimeout(() => {
                    if (!(this.mode === this.MODE_EDIT && this.model.has(this.nameName))) {
                        return;
                    }

                    (e.currentTarget as any).value =
                        this.model.get(this.nameName) || this.model.get(this.idName);
                }, 100);
            });

            const $elementName = this.$elementName;

            if (!this.autocompleteDisabled) {
                let lastAjaxPromise: AjaxPromise;

                const autocomplete = new Autocomplete(this.$elementName?.get(0) as HTMLInputElement, {
                    name: this.name,
                    handleFocusMode: 2,
                    autoSelectFirst: true,
                    forceHide: true,
                    triggerSelectOnValidInput: false,
                    catchFastEnter: true,
                    onSelect: async (item: any) => {
                        const model = await this.getModelFactory().create(this.foreignScope);

                        model.setMultiple(item.attributes);

                        await this.select(model);

                        this.$elementName.trigger('focus');
                    },
                    lookupFunction: async (query: string) => {
                        if (!this.autocompleteOnEmpty && query.length === 0) {
                            const list = await this.getOnEmptyAutocomplete();

                            if (!list) {
                                return [];
                            }

                            return this._transformAutocompleteResult({list: list});
                        }

                        const url = await this.getAutocompleteUrl(query);

                        if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                            lastAjaxPromise.abort();
                        }

                        lastAjaxPromise = Ajax.getRequest(url, {q: query});

                        const response = await lastAjaxPromise;

                        return this._transformAutocompleteResult(response);
                    },
                });

                this.once('render remove', () => autocomplete.dispose());

                if (this.isSearchMode()) {
                    const $elementOneOf = this.$el.find('input.element-one-of');

                    let lastAjaxPromise: AjaxPromise;

                    const autocomplete = new Autocomplete($elementOneOf.get(0), {
                        minChars: 1,
                        focusOnSelect: true,
                        handleFocusMode: 3,
                        autoSelectFirst: true,
                        triggerSelectOnValidInput: false,
                        forceHide: true,
                        onSelect: async (item: {attributes: Record<string, any>}) => {
                           const model = await this.getModelFactory().create(this.foreignScope);

                            model.setMultiple(item.attributes);

                            this.selectOneOf([model]);

                            $elementOneOf.val('');
                            setTimeout(() => $elementOneOf.focus(), 50);
                        },
                        lookupFunction: async (query: string) => {
                            const url = await this.getAutocompleteUrl(query);

                            if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                                lastAjaxPromise.abort();
                            }

                            lastAjaxPromise = Ajax.getRequest(url, {q: query});

                            const response: {list: any[]} = await lastAjaxPromise;

                            return response.list.map(item => ({
                                value: item.name,
                                attributes: item,
                            }));
                        },
                    });

                    this.once('render remove', () => autocomplete.dispose());

                    this.$el.find('select.search-type').on('change', () => {
                        this.trigger('change');
                    });
                }
            }

            $elementName.on('change', () => {
                if (!this.isSearchMode() && !this.model.get(this.idName)) {
                    $elementName.val(this.model.get(this.nameName));
                }
            });
        }

        if (this.isSearchMode()) {
            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            if (['isOneOf', 'isNotOneOf', 'isNotOneOfAndIsNotEmpty'].includes(type)) {
                this.searchData.oneOfIdList.forEach((id: string) => {
                    this.addLinkOneOfHtml(id, this.searchData.oneOfNameHash[id]);
                });
            }
        }
    }

    private _transformAutocompleteResult(response: {list: Record<string, any>[]}): Record<string, any[]> {
        const list: Record<string, any> = [];

        response.list.forEach(item => {
            const name = item[this.foreignNameAttribute] || item.name || item.id;

            const attributes = item;

            if (this.foreignNameAttribute !== 'name') {
                attributes[this.foreignNameAttribute] = name;
            }

            list.push({
                id: item.id,
                name: name,
                data: item.id,
                value: name,
                attributes: attributes,
            });
        });

        return list;
    }

    protected getValueForDisplay(): any {
        return this.model.get(this.nameName);
    }

    validateRequired() {
        if (this.isRequired()) {
            if (this.model.get(this.idName) == null) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }

        return false;
    }

    /**
     * Delete a one-of item. For search mode.
     */
    private deleteLinkOneOf(id: string) {
        this.deleteLinkOneOfHtml(id);

        const index = this.searchData.oneOfIdList.indexOf(id);

        if (index > -1) {
            this.searchData.oneOfIdList.splice(index, 1);
        }

        delete this.searchData.oneOfNameHash[id];

        this.trigger('change');
    }

    /**
     * Add a one-of item. For search mode.
     *
     * @param id An ID.
     * @param name A name.
     */
    private addLinkOneOf(id: string, name: string) {
        const ids = this.searchData.oneOfIdList as string[];

        if (ids.includes(id)) {
            return;
        }

        ids.push(id);
        this.searchData.oneOfNameHash[id] = name;

        this.addLinkOneOfHtml(id, name);
        this.trigger('change');
    }

    /**
     * @param id An ID.
     */
    private deleteLinkOneOfHtml(id: string) {
        this.$el.find('.link-one-of-container .link-' + id).remove();
    }

    /**
     * @param id An ID.
     * @param name A name.
     */
    private addLinkOneOfHtml(id: string, name: string): JQuery {
        const $container = this.$el.find('.link-one-of-container');

        const $el = $('<div>')
            .addClass('link-' + id)
            .addClass('list-group-item');

        $el.append(
            $('<a>')
                .attr('role', 'button')
                .addClass('pull-right')
                .attr('data-id', id)
                .attr('data-action', 'clearLinkOneOf')
                .append(
                    $('<span>').addClass('fas fa-times')
                ),
            $('<span>').text(name),
            ' '
        );

        $container.append($el);

        return $el;
    }

    fetch(): Record<string, any>  {
        const data = {} as any;

        data[this.nameName] = this.$el.find(`[data-name="${this.nameName}"]`).val() || null;
        data[this.idName] = this.$el.find(`[data-name="${this.idName}"]`).val() || null;

        return data;
    }

    fetchSearch(): Record<string, any> | null {
        const type = this.$el.find('select.search-type').val();
        const value = this.$el.find('[data-name="' + this.idName + '"]').val();

        if (~['isOneOf', 'isNotOneOf'].indexOf(type) && !this.searchData.oneOfIdList.length) {
            return {
                type: 'isNotNull',
                attribute: 'id',
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isEmpty') {
            return {
                type: 'isNull',
                attribute: this.idName,
                data: {
                    type: type,
                }
            };
        }

        if (type === 'isNotEmpty') {
            return {
                type: 'isNotNull',
                attribute: this.idName,
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isOneOf') {
            return {
                type: 'in',
                attribute: this.idName,
                value: this.searchData.oneOfIdList,
                data: {
                    type: type,
                    oneOfIdList: this.searchData.oneOfIdList,
                    oneOfNameHash: this.searchData.oneOfNameHash,
                },
            };
        }

        if (type === 'isNotOneOf') {
            return {
                type: 'or',
                value: [
                    {
                        type: 'notIn',
                        attribute: this.idName,
                        value: this.searchData.oneOfIdList,
                    },
                    {
                        type: 'isNull',
                        attribute: this.idName,
                    },
                ],
                data: {
                    type: type,
                    oneOfIdList: this.searchData.oneOfIdList,
                    oneOfNameHash: this.searchData.oneOfNameHash,
                }
            };
        }

        if (type === 'isNotOneOfAndIsNotEmpty') {
            return {
                type: 'notIn',
                attribute: this.idName,
                value: this.searchData.oneOfIdList,
                data: {
                    type: type,
                    oneOfIdList: this.searchData.oneOfIdList,
                    oneOfNameHash: this.searchData.oneOfNameHash,
                },
            };
        }

        if (type === 'isNot') {
            if (!value) {
                return null;
            }

            const nameValue = this.$el.find('[data-name="' + this.nameName + '"]').val();

            return {
                type: 'or',
                value: [
                    {
                        type: 'notEquals',
                        attribute: this.idName,
                        value: value
                    },
                    {
                        type: 'isNull',
                        attribute: this.idName,
                    }
                ],
                data: {
                    type: type,
                    idValue: value,
                    nameValue: nameValue,
                }
            };
        }

        if (type === 'isNotAndIsNotEmpty') {
            if (!value) {
                return null;
            }

            const nameValue = this.$el.find('[data-name="' + this.nameName + '"]').val();

            return {
                type: 'notEquals',
                attribute: this.idName,
                value: value,
                data: {
                    type: type,
                    idValue: value,
                    nameValue: nameValue,
                },
            };
        }

        if (!value) {
            return null;
        }

        const nameValue = this.$el.find('[data-name="' + this.nameName + '"]').val();

        return {
            type: 'equals',
            attribute: this.idName,
            value: value,
            data: {
                type: type,
                idValue: value,
                nameValue: nameValue,
            }
        };
    }

    /** @inheritDoc */
    getSearchType() {
        return this.getSearchParamsData().type ??
            this.searchParams?.typeFront ??
            this.searchParams?.type;
    }

    protected quickView() {
        const id = this.model.get(this.idName);

        if (!id) {
            return;
        }

        const entityType = this.foreignScope;

        const helper = new RecordModal();

        helper.showDetail(this, {
            id: id,
            entityType: entityType,
        });
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
    protected async actionSelect() {
        const viewName = this.panelDefs.selectModalView ||
            this.getMetadata().get(`clientDefs.${this.foreignScope}.modalViews.select`) ||
            this.selectRecordsView;

        const mandatorySelectAttributeList = this.getMandatorySelectAttributeList();

        const createButton = this.isEditMode() && (!this.createDisabled || this.forceCreateButton);

        const createAttributesProvider = createButton ?
            this.getCreateAttributesProvider() : null;

        Ui.notifyWait();

        const filters = await this._getSelectFilters();

        const orderBy = filters.orderBy || this.panelDefs.selectOrderBy;
        const orderDirection = filters.orderBy ? filters.order : this.panelDefs.selectOrderDirection;

        const options = {
            entityType: this.foreignScope,
            createButton: createButton,
            filters: filters.advanced,
            boolFilterList: filters.bool,
            primaryFilterName: filters.primary,
            mandatorySelectAttributeList: mandatorySelectAttributeList,
            forceSelectAllAttributes: this.forceSelectAllAttributes,
            filterList: this.getSelectFilterList(),
            createAttributesProvider: createAttributesProvider,
            layoutName: this.panelDefs.selectLayout,
            orderBy: orderBy,
            orderDirection: orderDirection,
            onSelect: (models: Model[]) => {
                this.select(models[0]);
            },
        };

        const view = await this.createView('modal', viewName, options);

        await view.render();

        Ui.notify();
    }

    private _applyAdditionalFilter(advanced: Record<string, any>) {
        const foreignLink = this.model.getLinkParam(this.name, 'foreign');

        if (!foreignLink) {
            return;
        }

        if (advanced[foreignLink]) {
            return;
        }

        const linkType = this.model.getLinkParam(this.name, 'type');
        const foreignLinkType = this.getMetadata()
            .get(['entityDefs', this.foreignScope, 'links', foreignLink, 'type']);
        const foreignFieldType = this.getMetadata()
            .get(['entityDefs', this.foreignScope, 'fields', foreignLink, 'type']);

        if (!foreignFieldType) {
            return;
        }

        const isOneToOne =
            (linkType === 'hasOne' || foreignLinkType === 'hasOne') &&
            ['link', 'linkOne'].includes(foreignFieldType);

        if (!isOneToOne) {
            return;
        }

        advanced[foreignLink] = {
            type: 'isNull',
            attribute: foreignLink + 'Id',
            data: {
                type: 'isEmpty',
            },
        };
    }

    private _getSelectFilters(): Promise<SelectFilters> {
        const handler = this.panelDefs.selectHandler;

        const localBoolFilterList = this.getSelectBoolFilterList();

        if (!handler || this.isSearchMode()) {
            const boolFilterList = (localBoolFilterList || this.panelDefs.selectBoolFilterList) ?
                [
                    ...(localBoolFilterList || []),
                    ...(this.panelDefs.selectBoolFilterList || []),
                ] :
                undefined;

            const advanced = {
                ...(this.getSelectFilters() ?? {}),
                ...(!this.isSearchMode() ? this._getCascadingFilters() : {}),
            };

            this._applyAdditionalFilter(advanced);

            return Promise.resolve({
                primary: this.getSelectPrimaryFilterName() ?? this.panelDefs.selectPrimaryFilterName,
                bool: boolFilterList,
                advanced: advanced,
            });
        }

        return new Promise(resolve => {
            Espo.loader.requirePromise(handler)
                .then((Handler: any) => new Handler(this.getHelper()))
                .then(/** module:handlers/select-related */handler => {
                    return handler.getFilters(this.model);
                })
                .then(filters => {
                    const advanced = {
                        ...(this.getSelectFilters() ?? {}),
                        ...(filters.advanced ?? {}),
                        ...this._getCascadingFilters(),
                    };

                    const primaryFilter = this.getSelectPrimaryFilterName() ||
                        filters.primary || this.panelDefs.selectPrimaryFilterName;

                    const boolFilterList = (localBoolFilterList || filters.bool || this.panelDefs.selectBoolFilterList) ?
                        [
                            ...(localBoolFilterList || []),
                            ...(filters.bool || []),
                            ...(this.panelDefs.selectBoolFilterList || []),
                        ] :
                        undefined;

                    this._applyAdditionalFilter(advanced);

                    const orderBy = filters.orderBy;
                    const order = orderBy ? filters.order : undefined;

                    resolve({
                        bool: boolFilterList,
                        primary: primaryFilter,
                        advanced: advanced,
                        orderBy: orderBy,
                        order: order,
                    });
                });
        });
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

    private _getCascadingCreateAttributes(): Record<string, unknown> {
        return this._createCascadeLinksHelper()?.prepareCreateAttributes() ?? {};
    }

    private async actionSelectOneOf() {
        Espo.Ui.notifyWait();

        const viewName = this.getMetadata().get(['clientDefs', this.foreignScope, 'modalViews', 'select']) ||
            this.selectRecordsView;

        const view = await this.createView('dialog', viewName, {
            scope: this.foreignScope,
            createButton: false,
            filters: this.getSelectFilters(),
            boolFilterList: this.getSelectBoolFilterList(),
            primaryFilterName: this.getSelectPrimaryFilterName(),
            multiple: true,
            layoutName: this.panelDefs.selectLayout,
        });

        this.listenToOnce(view, 'select', models => {
            this.clearView('dialog');

            if (Object.prototype.toString.call(models) !== '[object Array]') {
                models = [models];
            }

            this.selectOneOf(models);
        });

        view.render().then(() => {});

        Espo.Ui.notify(false);
    }

    /**
     * Get an empty autocomplete result.
     */
    protected getOnEmptyAutocomplete(): Promise<[{name: string | null, id: string} & Record<string, any>]> | null {
        return null;
    }

    protected async actionCreateLink() {
        const helper = new RecordModal();

        const attributes = await this.getCreateAttributesProvider()();

        await helper.showCreate(this, {
            entityType: this.foreignScope,
            fullFormDisabled: true,
            attributes: attributes,
            afterSave: (model: Model) => this.select(model),
        });
    }

    /**
     * @since 8.0.4
     */
    private selectOneOf(models: Model[]) {
        models.forEach(model => {
            this.addLinkOneOf(model.id as string, model.get(this.foreignNameAttribute));
        });
    }
}

export default LinkFieldView;
