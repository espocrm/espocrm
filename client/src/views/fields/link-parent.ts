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
import Select from 'ui/select';
import Autocomplete from 'ui/autocomplete';
import {AdvancedFilter} from 'search-manager';
import Model from 'model';
import Ajax from 'ajax';
import Ui from 'ui';
import Utils from 'utils';
import {AjaxPromise} from 'util/ajax';

/**
 * Parameters.
 */
export interface LinkParentParams extends BaseParams {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Autocomplete on empty input.
     */
    autocompleteOnEmpty?: boolean;
    /**
     * A foreign entity type list.
     */
    entityList: string[];
}

/**
 * Options.
 */
export interface LinkParentOptions extends BaseOptions {
    /**
     * Disable create button in the select modal.
     */
    createDisabled?: boolean;
    /**
     * A foreign entity type list.
     */
    foreignScopeList: string[];
}

/**
 * A link-parent field (belongs-to-parent relation).
 */
class LinkParentFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends LinkParentOptions = LinkParentOptions,
    P extends LinkParentParams = LinkParentParams,
> extends BaseFieldView<S, O, P> {

    readonly type: string = 'linkParent'

    protected listTemplate = 'fields/link-parent/list'
    protected detailTemplate = 'fields/link-parent/detail'
    protected editTemplate = 'fields/link-parent/edit'
    protected searchTemplate = 'fields/link-parent/search'
    protected listLinkTemplate = 'fields/link-parent/list-link'

    /**
     * A name attribute name.
     */
    protected nameName: string

    /**
     * An ID attribute name.
     */
    protected idName: string

    /**
     * A type attribute name.
     */
    protected typeName: string

    /**
     * A current foreign entity type.
     */
    protected foreignScope: string | null = null

    /**
     * A foreign entity type list.
     */
    protected foreignScopeList: string[]

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
     * A search type list.
     */
    protected searchTypeList: string[] = [
        'is',
        'isEmpty',
        'isNotEmpty',
    ]

    /**
     * A select primary filter.
     */
    protected selectPrimaryFilterName: string | null = null

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

    /**
     * Mandatory select attributes.
     */
    protected mandatorySelectAttributeList: string[] | null = null

    /**
     * @inheritDoc
     * @internal
     */
    initialSearchIsNotIdle: boolean = true

    /**
     * Trigger autocomplete on empty input.
     */
    protected autocompleteOnEmpty: boolean

    protected displayScopeColorInListMode: boolean = true

    protected displayEntityType: boolean

    private $elementName: JQuery
    private $elementId: JQuery
    private $elementType: JQuery

    protected data() {
        let nameValue = this.model.get(this.nameName);

        if (!nameValue && this.model.get(this.idName) && this.model.get(this.typeName)) {
            nameValue = this.translate(this.model.get(this.typeName), 'scopeNames');
        }

        let iconHtml = null;

        if (
            (
                this.mode === this.MODE_DETAIL ||
                this.mode === this.MODE_LIST && this.displayScopeColorInListMode
            ) &&
            this.foreignScope
        ) {
            iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
        }

        return {
            ...super.data(),
            idName: this.idName,
            nameName: this.nameName,
            typeName: this.typeName,
            idValue: this.model.get(this.idName),
            nameValue: nameValue,
            typeValue: this.model.get(this.typeName),
            foreignScope: this.foreignScope,
            foreignScopeList: this.foreignScopeList,
            valueIsSet: this.model.has(this.idName) || this.model.has(this.typeName),
            iconHtml: iconHtml,
            displayEntityType: this.displayEntityType && this.model.get(this.typeName),
        };
    }

    /**
     * Get advanced filters (field filters) to be applied when select a record.
     * Can be extended.
     */
    protected getSelectFilters(): Record<string, AdvancedFilter> | null  {
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
     * Attributes to pass to a model when creating a new record.
     * Can be extended.
     */
    protected getCreateAttributes(): Record<string, unknown> | null {
        return null;
    }

    protected setup() {
        this.addHandler('auxclick', 'a[href]:not([role="button"])', (e) => {
            this.onAuxClickLink(e as MouseEvent);
        });

        this.nameName = this.name + 'Name';
        this.typeName = this.name + 'Type';
        this.idName = this.name + 'Id';

        this.foreignScopeList = this.options.foreignScopeList || this.foreignScopeList;

        this.foreignScopeList = this.foreignScopeList ||
            this.params.entityList ||
            this.model.getLinkParam(this.name, 'entityList') || [];

        this.foreignScopeList = Utils.clone(this.foreignScopeList).filter(item => {
            if (!this.getMetadata().get(['scopes', item, 'disabled'])) {
                return true;
            }
        });

        this.foreignScope = this.model.get(this.typeName) || this.foreignScopeList[0];

        if (this.foreignScope && !~this.foreignScopeList.indexOf(this.foreignScope)) {
            this.foreignScopeList.unshift(this.foreignScope);
        }

        this.listenTo(this.model, `change:${this.typeName}`, () => {
            this.foreignScope = this.model.get(this.typeName) || this.foreignScopeList[0];
        });

        this.autocompleteOnEmpty = this.params.autocompleteOnEmpty || this.autocompleteOnEmpty;

        if ('createDisabled' in this.options) {
            this.createDisabled = this.options.createDisabled as boolean;
        }

        if (!this.isListMode()) {
            this.addActionHandler('selectLink', () => this.actionSelect());
            this.addActionHandler('clearLink', () => this.actionClearLink());

            this.addHandler('change', `select[data-name="${this.typeName}"]`, (_, target) => {
                this.foreignScope = (target as HTMLSelectElement).value;

                this.$elementName?.val('');
                this.$elementId?.val('');
            });
        }
    }

    protected actionClearLink() {
        if (this.foreignScopeList.length) {
            this.foreignScope = this.foreignScopeList[0];

            Select.setValue(this.$elementType, this.foreignScope);
        }

        this.$elementName.val('');
        this.$elementId.val('');

        this.trigger('change');
    }

    protected async actionSelect() {
        const viewName = this.getMetadata().get(`clientDefs.${this.foreignScope}.modalViews.select`) ||
            this.selectRecordsView;

        const createButton = !this.createDisabled && this.isEditMode();

        const options = {
            scope: this.foreignScope,
            createButton: createButton,
            filters: this.getSelectFilters(),
            boolFilterList: this.getSelectBoolFilterList(),
            primaryFilterName: this.getSelectPrimaryFilterName(),
            createAttributes: createButton ? this.getCreateAttributes() : null,
            mandatorySelectAttributeList: this.getMandatorySelectAttributeList(),
            forceSelectAllAttributes: this.isForceSelectAllAttributes(),
            layoutName: this.getSelectLayout(),
            onSelect: (models: Model[]) => {
                this.select(models[0]);
            },
        };

        Ui.notifyWait();

        const view = await this.createView('modal', viewName, options);

        await view.render();

        Ui.notify();
    }

    protected setupSearch() {
        const type = this.getSearchParamsData().type;

        if (type === 'is' || !type) {
            this.searchData.idValue = this.getSearchParamsData().idValue ??
                this.searchParams?.valueId;
            this.searchData.nameValue = this.getSearchParamsData().nameValue ??
                this.searchParams?.valueName;
            this.searchData.typeValue = this.getSearchParamsData().typeValue ??
                this.searchParams?.valueType;
        }

        this.addHandler('change', 'select.search-type', (_, target) => {
            this.handleSearchType((target as HTMLSelectElement).value);
        });
    }

    /**
     * Handle a search type.
     *
     * @param type A type.
     */
    protected handleSearchType(type: string) {
        if (['is'].includes(type)) {
            this.$el.find('div.primary').removeClass('hidden');
        } else {
            this.$el.find('div.primary').addClass('hidden');
        }
    }

    /**
     * Select.
     *
     * {module:model} model A model.
     */
    protected select(model: Model) {
        this.$elementName?.val(model.get('name') ?? model.id);
        this.$elementId?.val(model.id as string);

        this.trigger('change');
    }

    /**
     * Attributes to select regardless availability on a list layout.
     * Can be extended.
     */
    protected getMandatorySelectAttributeList(): string[] | null {
        return this.mandatorySelectAttributeList;
    }

    /**
     * Select all attributes. Can be extended.
     */
    protected isForceSelectAllAttributes(): boolean {
        return this.forceSelectAllAttributes;
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

    /**
     * Compose an autocomplete URL. Can be extended.
     *
     * @param q A query.
     */
    protected getAutocompleteUrl(q: string): Promise<string> | string {
        // noinspection BadExpressionStatementJS
        q;

        let url = this.foreignScope + '?maxSize=' + this.getAutocompleteMaxCount();

        if (!this.isForceSelectAllAttributes()) {
            let select = ['id', 'name'];

            const mandatory = this.getMandatorySelectAttributeList();

            if (mandatory) {
                select = select.concat(mandatory);
            }

            url += '&select=' + select.join(',');
        }

        const boolList = this.getSelectBoolFilterList();

        if (boolList) {
            url += '&' + $.param({'boolFilterList': boolList});
        }

        const primary = this.getSelectPrimaryFilterName();

        if (primary) {
            url += '&' + $.param({'primaryFilter': primary});
        }

        const advanced = this.getSelectFilters();

        if (advanced && Object.keys(advanced).length) {
            url += '&' + $.param({'where': advanced});
        }

        return url;
    }

    protected afterRender() {
        if (this.isEditMode() || this.isSearchMode()) {
            this.$elementId = this.$el.find(`input[data-name="${this.idName}"]`);
            this.$elementName = this.$el.find(`input[data-name="${this.nameName}"]`);
            this.$elementType = this.$el.find(`select[data-name="${this.typeName}"]`);

            this.$elementName.on('change', () => {
                if (this.$elementName.val() === '') {
                    this.$elementName.val('');
                    this.$elementId.val('');

                    this.trigger('change');
                }
            });

            this.$elementType.on('change', () => {
                this.$elementName.val('');
                this.$elementId.val('');

                this.trigger('change');
            });

            this.$elementName.on('blur', e => {
                setTimeout(() => {
                    if (this.mode === this.MODE_EDIT) {
                        (e.currentTarget as HTMLInputElement).value = this.model.get(this.nameName) || '';
                    }
                }, 100);
            });

            if (!this.autocompleteDisabled) {
                let lastAjaxPromise: AjaxPromise;

                const autocomplete = new Autocomplete(this.$elementName?.get(0) as HTMLInputElement, {
                    name: this.name,
                    focusOnSelect: true,
                    handleFocusMode: 2,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    forceHide: true,
                    minChars: this.autocompleteOnEmpty ? 0 : 1,
                    onSelect: async (item: {attributes: any}) => {
                        const entityType = this.foreignScope;

                        if (!entityType) {
                            throw new Error("No entity type selected.");
                        }

                        const model = await this.getModelFactory().create(entityType);
                        model.setMultiple(item.attributes);

                        this.select(model);
                        this.$elementName?.trigger('focus');
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

                Select.init(this.$elementType, {});

                this.$elementType.on('change', () => autocomplete.clear());
            }
        }

        if (this.isSearchMode()) {
            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });
        }
    }

    protected getValueForDisplay(): any {
        return this.model.get(this.nameName);
    }

    validateRequired() {
        if (this.isRequired()) {
            if (this.model.get(this.idName) === null || !this.model.get(this.typeName)) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }

        return false;
    }

    fetch(): Record<string, unknown> {
        const data = {} as Record<string, unknown>;

        data[this.typeName] = this.$elementType.val() || null;
        data[this.nameName] = this.$elementName.val() || null;
        data[this.idName] = this.$elementId.val() || null;

        if (data[this.idName] === null) {
            data[this.typeName] = null;
        }

        return data;
    }

    fetchSearch(): Record<string, unknown> | null {
        const type = this.$el.find('select.search-type').val();

        if (type === 'isEmpty') {
            return {
                type: 'isNull',
                field: this.idName,
                data: {
                    type: type,
                }
            };
        }

        if (type === 'isNotEmpty') {
            return {
                type: 'isNotNull',
                field: this.idName,
                data: {
                    type: type,
                }
            };
        }

        const entityType = this.$elementType.val();
        const entityName = this.$elementName.val();
        const entityId = this.$elementId.val();

        if (!entityType) {
            return null;
        }

        if (entityId) {
            return {
                type: 'and',
                attribute: this.idName,
                value: [
                    {
                        type: 'equals',
                        field: this.idName,
                        value: entityId,
                    },
                    {
                        type: 'equals',
                        field: this.typeName,
                        value: entityType,
                    }
                ],
                data: {
                    type: 'is',
                    idValue: entityId,
                    nameValue: entityName,
                    typeValue: entityType,
                }
            };
        }

        return {
            type: 'and',
            attribute: this.idName,
            value: [
                {
                    type: 'isNotNull',
                    field: this.idName,
                },
                {
                    type: 'equals',
                    field: this.typeName,
                    value: entityType,
                }
            ],
            data: {
                type: 'is',
                typeValue: entityType,
            }
        };
    }

    protected getSearchType(): string | null {
        return this.getSearchParamsData().type ?? this.searchParams?.typeFront ?? null;
    }

    protected quickView() {
        const id = this.model.get(this.idName);
        const entityType = this.model.get(this.typeName);

        if (!id || !entityType) {
            return;
        }

        const helper = new RecordModal();

        helper.showDetail(this, {
            id: id,
            entityType: entityType,
        });
    }

    /**
     * @since 9.1.0
     */
    protected getSelectLayout(): string | undefined {
        return undefined;
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
}

export default LinkParentFieldView;
