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

/** @module views/fields/link */

import BaseFieldView from 'views/fields/base';
import RecordModal from 'helpers/record-modal';
import Autocomplete from 'ui/autocomplete';

/**
 * A link field (belongs-to relation).
 *
 * @extends BaseFieldView<module:views/fields/link~params>
 */
class LinkFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/link~options
     * @property {
     *     module:views/fields/link~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     * @property {boolean} [createDisabled] Disable create button in the select modal.
     */

    /**
     * @typedef {Object} module:views/fields/link~params
     * @property {boolean} [required] Required.
     * @property {boolean} [autocompleteOnEmpty] Autocomplete on empty input.
     * @property {boolean} [createButton] Show 'Create' button.
     * @property {string} [entity] An entity type. As of 9.1.0.
     */

    /**
     * @param {
     *     module:views/fields/link~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    /** @inheritDoc */
    type = 'link'

    /** @inheritDoc */
    listTemplate = 'fields/link/list'
    /** @inheritDoc */
    detailTemplate = 'fields/link/detail'
    /** @inheritDoc */
    editTemplate = 'fields/link/edit'
    /** @inheritDoc */
    searchTemplate = 'fields/link/search'

    /**
     * A name attribute name.
     *
     * @type {string}
     */
    nameName

    /**
     * An ID attribute name.
     *
     * @type {string}
     */
    idName

    /**
     * A foreign entity type.
     *
     * @type {string|null}
     */
    foreignScope = null

    /**
     * A select-record view.
     *
     * @protected
     * @type {string}
     */
    selectRecordsView = 'views/modals/select-records'

    /**
     * Autocomplete disabled.
     *
     * @protected
     * @type {boolean}
     */
    autocompleteDisabled = false

    /**
     * Create disabled.
     *
     * @protected
     * @type {boolean}
     */
    createDisabled = false

    /**
     * To display the create button.
     *
     * @protected
     * @type {boolean}
     */
    createButton = false

    /**
     * Force create button even is disabled in clientDefs > relationshipPanels.
     *
     * @protected
     * @type {boolean}
     */
    forceCreateButton = false

    /**
     * A search type list.
     *
     * @protected
     * @type {string[]}
     */
    searchTypeList = [
        'is',
        'isEmpty',
        'isNotEmpty',
        'isNot',
        'isOneOf',
        'isNotOneOf',
    ]

    /**
     * A primary filter list that will be available when selecting a record.
     *
     * @protected
     * @type {string[]|null}
     */
    selectFilterList = null

    /**
     * A select primary filter.
     *
     * @protected
     * @type {string|null}
     */
    selectPrimaryFilterName = null

    /**
     * A select bool filter list.
     *
     * @protected
     * @type {string[]|null}
     */
    selectBoolFilterList = null

    /**
     * An autocomplete max record number.
     *
     * @protected
     * @type {number|null}
     */
    autocompleteMaxCount = null

    /**
     * Select all attributes.
     *
     * @protected
     * @type {boolean}
     */
    forceSelectAllAttributes = false

    /**
     * @protected
     * @type {string[]|null}
     */
    mandatorySelectAttributeList = null

    /**
     * Trigger autocomplete on empty input.
     *
     * @protected
     * @type {boolean}
     */
    autocompleteOnEmpty = false

    /**
     * A link element class name. Applicable in the detail mode.
     *
     * @protected
     * @since 9.1.6
     */
    linkClass

    /** @inheritDoc */
    events = {
        /** @this LinkFieldView */
        'auxclick a[href]:not([role="button"])': function (e) {
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
        },
    }

    // noinspection JSCheckFunctionSignatures
    /** @inheritDoc */
    data() {
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

        // noinspection JSValidateTypes
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

    /**
     * @protected
     * @return {?string}
     */
    getUrl() {
        const id = this.model.get(this.idName);

        if (!id) {
            return null;
        }

        return '#' + this.foreignScope + '/view/' + id;
    }

    /**
     * Get advanced filters (field filters) to be applied when select a record.
     * Can be extended.
     *
     * @protected
     * @return {Object.<string,module:search-manager~advancedFilter>|null}
     */
    getSelectFilters() {
        return null;
    }

    /**
     * Get a select bool filter list. Applied when select a record.
     * Can be extended.
     *
     * @protected
     * @return {string[]|null}
     */
    getSelectBoolFilterList() {
        return this.selectBoolFilterList;
    }

    /**
     * Get a select primary filter. Applied when select a record.
     * Can be extended.
     *
     * @protected
     * @return {string|null}
     */
    getSelectPrimaryFilterName() {
        return this.selectPrimaryFilterName;
    }

    /**
     * Get a primary filter list that will be available when selecting a record.
     * Can be extended.
     *
     * @return {string[]|null}
     */
    getSelectFilterList() {
        return this.selectFilterList;
    }

    /**
     * Attributes to pass to a model when creating a new record.
     * Can be extended.
     *
     * @return {Object.<string,*>|null}
     */
    getCreateAttributes() {
        const attributeMap = this.getMetadata()
            .get(['clientDefs', this.entityType, 'relationshipPanels', this.name, 'createAttributeMap']) || {};

        const attributes = {};

        Object.keys(attributeMap).forEach(attr => attributes[attributeMap[attr]] = this.model.get(attr));

        return attributes;
    }

    /** @inheritDoc */
    setup() {
        this.nameName = this.name + 'Name';
        this.idName = this.name + 'Id';

        this.foreignScope = this.options.foreignScope || this.foreignScope;

        this.foreignScope =
            this.foreignScope ||
            this.params.entity ||
            this.model.getFieldParam(this.name, 'entity') ||
            this.model.getLinkParam(this.name, 'entity');

        if ('createDisabled' in this.options) {
            this.createDisabled = this.options.createDisabled;
        }

        if (!this.isListMode()) {
            this.addActionHandler('selectLink', () => this.actionSelect());
            this.addActionHandler('clearLink', () => this.clearLink());
        }

        if (this.isSearchMode()) {
            this.addActionHandler('selectLinkOneOf', () => this.actionSelectOneOf());

            this.events['click a[data-action="clearLinkOneOf"]'] = e =>{
                const id = $(e.currentTarget).data('id').toString();

                this.deleteLinkOneOf(id);
            };
        }

        this.autocompleteOnEmpty = this.params.autocompleteOnEmpty || this.autocompleteOnEmpty;

        this.createButton = this.params.createButton || this.createButton;

        if (this.createButton && !this.getAcl().checkScope(this.foreignScope, 'create')) {
            this.createButton = false;
        }

        if (this.createButton) {
            this.addActionHandler('createLink', () => this.actionCreateLink());
        }

        /** @type {Object.<string, *>} */
        this.panelDefs = this.getMetadata()
            .get(['clientDefs', this.entityType, 'relationshipPanels', this.name]) || {};

        if (this.panelDefs.createDisabled) {
            this.createDisabled = true;
        }
    }

    /**
     * Select.
     *
     * @param {module:model} model A model.
     * @protected
     */
    select(model) {
        this.$elementName.val(model.get('name') || model.id);
        this.$elementId.val(model.get('id'));

        if (this.mode === this.MODE_SEARCH) {
            this.searchData.idValue = model.get('id');
            this.searchData.nameValue = model.get('name') || model.id;
        }

        this.trigger('change');

        this.controlCreateButtonVisibility();

        const attributes = {};

        for (const [foreign, field] of Object.entries(this.getDependantForeignMap())) {
            attributes[field] = model.get(foreign);
        }

        this.getSelectFieldHandler().then(async handler => {
            this.model.set({
                ...attributes,
                ...(await handler.getAttributes(model)),
            }, {fromField: this.name});
        });
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
                    this.model.set(attributes, {fromField: this.name});
                });
        });
    }

    /** @private */
    controlCreateButtonVisibility() {
        if (!this.createButton) {
            return;
        }

        const $btn = this.$el.find('[data-action="createLink"]');

        this.model.get(this.idName) ?
            $btn.addClass('hidden') :
            $btn.removeClass('hidden');
    }

    /**
     * @private
     * @return {Promise<{
     *     getAttributes: function (module:model): Promise<Object.<string, *>>,
     *     getClearAttributes: function(): Promise<Object.<string, *>>,
     * }>}
     */
    getSelectFieldHandler() {
        if (!this.panelDefs.selectFieldHandler) {
            return Promise.resolve({
                getClearAttributes: () => Promise.resolve({}),
                getAttributes: () => Promise.resolve({}),
            });
        }

        return new Promise(resolve => {
            Espo.loader.requirePromise(this.panelDefs.selectFieldHandler)
                .then(Handler => {
                    // Model is passed as of v8.2.
                    const handler = new Handler(this.getHelper(), this.model);

                    resolve(handler);
                });
        });
    }

    /** @inheritDoc */
    setupSearch() {
        this.searchData.oneOfIdList = this.getSearchParamsData().oneOfIdList ||
            this.searchParams.oneOfIdList || [];

        this.searchData.oneOfNameHash = this.getSearchParamsData().oneOfNameHash ||
            this.searchParams.oneOfNameHash || {};

        if (~['is', 'isNot', 'equals'].indexOf(this.getSearchType())) {
            this.searchData.idValue = this.getSearchParamsData().idValue ||
                this.searchParams.idValue || this.searchParams.value;

            this.searchData.nameValue = this.getSearchParamsData().nameValue ||
                this.searchParams.nameValue || this.searchParams.valueName;
        }

        this.events['change select.search-type'] = e => {
            const type = $(e.currentTarget).val();

            this.handleSearchType(type);
        };
    }

    /**
     * Handle a search type.
     *
     * @protected
     * @param {string} type A type.
     */
    handleSearchType(type) {
        if (~['is', 'isNot', 'isNotAndIsNotEmpty'].indexOf(type)) {
            this.$el.find('div.primary').removeClass('hidden');
        }
        else {
            this.$el.find('div.primary').addClass('hidden');
        }

        if (~['isOneOf', 'isNotOneOf', 'isNotOneOfAndIsNotEmpty'].indexOf(type)) {
            this.$el.find('div.one-of-container').removeClass('hidden');
        }
        else {
            this.$el.find('div.one-of-container').addClass('hidden');
        }
    }

    /**
     * Get an autocomplete max record number. Can be extended.
     *
     * @protected
     * @return {number}
     */
    getAutocompleteMaxCount() {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage');
    }

    /**
     * @private
     * @return {string[]}
     */
    getMandatorySelectAttributeList() {
        const list = this.mandatorySelectAttributeList || this.panelDefs.selectMandatoryAttributeList || []

        const map = this.getDependantForeignMap();

        return [...list, ...Object.keys(map)];
    }

    /**
     * @private
     * @return {Record<string>}
     */
    getDependantForeignMap() {
        if (this._dependantForeignMap) {
            return this._dependantForeignMap;
        }

        const map = {};

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

    // noinspection JSUnusedLocalSymbols
    /**
     * Compose an autocomplete URL. Can be extended.
     *
     * @protected
     * @param {string} [q] A query.
     * @return {string|Promise<string>}
     */
    getAutocompleteUrl(q) {
        let url = this.foreignScope + '?maxSize=' + this.getAutocompleteMaxCount();

        if (!this.forceSelectAllAttributes) {
            const mandatorySelectAttributeList = this.getMandatorySelectAttributeList();

            let select = ['id', 'name'];

            if (mandatorySelectAttributeList) {
                select = select.concat(mandatorySelectAttributeList);
            }

            url += '&select=' + select.join(',');
        }

        if (this.panelDefs.selectHandler) {
            return new Promise(resolve => {
                this._getSelectFilters().then(filters => {
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
            });
        }

        const boolList = [
            ...(this.getSelectBoolFilterList() || []),
            ...(this.panelDefs.selectBoolFilterList || []),
        ];

        const primary = this.getSelectPrimaryFilterName() || this.panelDefs.selectPrimaryFilterName;

        if (boolList.length) {
            url += '&' + $.param({'boolFilterList': boolList});
        }

        if (primary) {
            url += '&' + $.param({'primaryFilter': primary});
        }

        if (this.panelDefs.selectOrderBy) {
            const direction = this.panelDefs.selectOrderDirection || 'asc';

            url += '&' + $.param({
                orderBy: this.panelDefs.selectOrderBy,
                order: direction,
            });
        }

        return url;
    }

    /** @inheritDoc */
    afterRender() {
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
                    if (this.mode === this.MODE_EDIT && this.model.has(this.nameName)) {
                        e.currentTarget.value = this.model.get(this.nameName) || this.model.get(this.idName);
                    }
                }, 100);
            });

            const $elementName = this.$elementName;

            if (!this.autocompleteDisabled) {
                /** @type {module:ajax.AjaxPromise & Promise<any>} */
                let lastAjaxPromise;

                const autocomplete = new Autocomplete(this.$elementName.get(0), {
                    name: this.name,
                    handleFocusMode: 2,
                    autoSelectFirst: true,
                    forceHide: true,
                    triggerSelectOnValidInput: false,
                    onSelect: item => {
                        this.getModelFactory().create(this.foreignScope, model => {
                            model.set(item.attributes);
                            this.select(model);

                            this.$elementName.focus();
                        });
                    },
                    lookupFunction: query => {
                        if (!this.autocompleteOnEmpty && query.length === 0) {
                            const onEmptyPromise = this.getOnEmptyAutocomplete();

                            if (onEmptyPromise) {
                                return onEmptyPromise.then(list => this._transformAutocompleteResult({list: list}));
                            }

                            return Promise.resolve([]);
                        }

                        return Promise.resolve(this.getAutocompleteUrl(query))
                            .then(url => {
                                if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                                    lastAjaxPromise.abort();
                                }

                                lastAjaxPromise = Espo.Ajax.getRequest(url, {q: query});

                                return lastAjaxPromise;
                            })
                            .then(response => this._transformAutocompleteResult(response));
                    },
                });

                this.once('render remove', () => autocomplete.dispose());

                if (this.isSearchMode()) {
                    const $elementOneOf = this.$el.find('input.element-one-of');

                    /** @type {module:ajax.AjaxPromise & Promise<any>} */
                    let lastAjaxPromise;

                    const autocomplete = new Autocomplete($elementOneOf.get(0), {
                        minChars: 1,
                        focusOnSelect: true,
                        handleFocusMode: 3,
                        autoSelectFirst: true,
                        triggerSelectOnValidInput: false,
                        forceHide: true,
                        onSelect: item => {
                            this.getModelFactory().create(this.foreignScope, model => {
                                model.set(item.attributes);

                                this.selectOneOf([model]);

                                $elementOneOf.val('');
                                setTimeout(() => $elementOneOf.focus(), 50);
                            });
                        },
                        lookupFunction: query => {
                            return Promise.resolve(this.getAutocompleteUrl(query))
                                .then(url => {
                                    if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                                        lastAjaxPromise.abort();
                                    }

                                    lastAjaxPromise = Espo.Ajax.getRequest(url, {q: query});

                                    return lastAjaxPromise;
                                })
                                .then(/** {list: Record[]} */response => {
                                    return response.list.map(item => ({
                                        value: item.name,
                                        attributes: item,
                                    }));
                                });
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

            if (~['isOneOf', 'isNotOneOf', 'isNotOneOfAndIsNotEmpty'].indexOf(type)) {
                this.searchData.oneOfIdList.forEach(id => {
                    this.addLinkOneOfHtml(id, this.searchData.oneOfNameHash[id]);
                });
            }
        }
    }

    /**
     * @private
     */
    _transformAutocompleteResult(response) {
        const list = [];

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

    /** @inheritDoc */
    getValueForDisplay() {
        return this.model.get(this.nameName);
    }

    /** @inheritDoc */
    validateRequired() {
        if (this.isRequired()) {
            if (this.model.get(this.idName) == null) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }
    }

    /**
     * Delete a one-of item. For search mode.
     *
     * @param {string} id An ID.
     */
    deleteLinkOneOf(id) {
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
     * @param {string} id An ID.
     * @param {string} name A name.
     */
    addLinkOneOf(id, name) {
        if (!~this.searchData.oneOfIdList.indexOf(id)) {
            this.searchData.oneOfIdList.push(id);
            this.searchData.oneOfNameHash[id] = name;
            this.addLinkOneOfHtml(id, name);

            this.trigger('change');
        }
    }

    /**
     * @protected
     * @param {string} id An ID.
     */
    deleteLinkOneOfHtml(id) {
        this.$el.find('.link-one-of-container .link-' + id).remove();
    }

    /**
     * @protected
     * @param {string} id An ID.
     * @param {string} name A name.
     * @return {JQuery}
     */
    addLinkOneOfHtml(id, name) {
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

    /** @inheritDoc */
    fetch() {
        const data = {};

        data[this.nameName] = this.$el.find('[data-name="'+this.nameName+'"]').val() || null;
        data[this.idName] = this.$el.find('[data-name="'+this.idName+'"]').val() || null;

        return data;
    }

    /** @inheritDoc */
    fetchSearch() {
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
                return false;
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
                return false;
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
            return false;
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
        return this.getSearchParamsData().type ||
            this.searchParams.typeFront ||
            this.searchParams.type;
    }

    /**
     * @protected
     */
    quickView() {
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

    /**
     * @protected
     * @return {function(): Promise<Object.<string, *>>}
     */
    getCreateAttributesProvider() {
        return () => {
            const attributes = this.getCreateAttributes() || {};

            if (!this.panelDefs.createHandler) {
                return Promise.resolve(attributes);
            }

            return new Promise(resolve => {
                Espo.loader.requirePromise(this.panelDefs.createHandler)
                    .then(Handler => new Handler(this.getHelper()))
                    .then(/** import('handlers/create-related').default */handler => {
                        handler.getAttributes(this.model, this.name)
                            .then(additionalAttributes => {
                                resolve({
                                    ...attributes,
                                    ...additionalAttributes,
                                });
                            });
                    });
            });
        };
    }

    /**
     * @protected
     */
    async actionSelect() {
        const viewName = this.panelDefs.selectModalView ||
            this.getMetadata().get(`clientDefs.${this.foreignScope}.modalViews.select`) ||
            this.selectRecordsView;

        const mandatorySelectAttributeList = this.getMandatorySelectAttributeList();

        const createButton = this.isEditMode() && (!this.createDisabled || this.forceCreateButton);

        const createAttributesProvider = createButton ?
            this.getCreateAttributesProvider() : null;

        Espo.Ui.notifyWait();

        const filters = await this._getSelectFilters();

        const orderBy = filters.orderBy || this.panelDefs.selectOrderBy;
        const orderDirection = filters.orderBy ? filters.order : this.panelDefs.selectOrderDirection;

        /** @type {module:views/modals/select-records~Options} */
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
            onSelect: models => {
                this.select(models[0]);
            },
        };

        const view = await this.createView('modal', viewName, options);

        await view.render();

        Espo.Ui.notify();
    }

    /**
     * @param {Object} advanced
     * @private
     */
    _applyAdditionalFilter(advanced) {
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

    /**
     * @private
     * @return {Promise<{
     *     bool?: string[],
     *     advanced?: Object,
     *     primary?: string,
     *     orderBy?: string,
     *     order?: 'asc'|'desc',
     * }>}
     */
    _getSelectFilters() {
        const handler = this.panelDefs.selectHandler;

        const localBoolFilterList = this.getSelectBoolFilterList();

        if (!handler || this.isSearchMode()) {
            const boolFilterList = (localBoolFilterList || this.panelDefs.selectBoolFilterList) ?
                [
                    ...(localBoolFilterList || []),
                    ...(this.panelDefs.selectBoolFilterList || []),
                ] :
                undefined;

            const advanced = this.getSelectFilters() || {};

            this._applyAdditionalFilter(advanced);

            return Promise.resolve({
                primary: this.getSelectPrimaryFilterName() || this.panelDefs.selectPrimaryFilterName,
                bool: boolFilterList,
                advanced: advanced,
            });
        }

        return new Promise(resolve => {
            Espo.loader.requirePromise(handler)
                .then(Handler => new Handler(this.getHelper()))
                .then(/** module:handlers/select-related */handler => {
                    return handler.getFilters(this.model);
                })
                .then(filters => {
                    const advanced = {...(this.getSelectFilters() || {}), ...(filters.advanced || {})};
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

    actionSelectOneOf() {
        Espo.Ui.notifyWait();

        const viewName = this.getMetadata().get(['clientDefs', this.foreignScope, 'modalViews', 'select']) ||
            this.selectRecordsView;

        this.createView('dialog', viewName, {
            scope: this.foreignScope,
            createButton: false,
            filters: this.getSelectFilters(),
            boolFilterList: this.getSelectBoolFilterList(),
            primaryFilterName: this.getSelectPrimaryFilterName(),
            multiple: true,
            layoutName: this.panelDefs.selectLayout,
        }, view => {
            view.render();

            Espo.Ui.notify(false);

            this.listenToOnce(view, 'select', models => {
                this.clearView('dialog');

                if (Object.prototype.toString.call(models) !== '[object Array]') {
                    models = [models];
                }

                this.selectOneOf(models);
            });
        });
    }

    /**
     * Get an empty autocomplete result.
     *
     * @protected
     * @return {Promise<[{name: ?string, id: string} & Record]>}
     */
    getOnEmptyAutocomplete() {
        return undefined;
    }

    /**
     * @protected
     */
    async actionCreateLink() {
        const helper = new RecordModal();

        const attributes = await this.getCreateAttributesProvider()();

        await helper.showCreate(this, {
            entityType: this.foreignScope,
            fullFormDisabled: true,
            attributes: attributes,
            afterSave: model => this.select(model),
        });
    }

    /**
     * @protected
     * @param {module:model[]} models
     * @since 8.0.4
     */
    selectOneOf(models) {
        models.forEach(model => {
            this.addLinkOneOf(model.id, model.get('name'));
        });
    }
}

export default LinkFieldView;
