/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/fields/link-multiple */

import BaseFieldView from 'views/fields/base';
import RecordModal from 'helpers/record-modal';

/**
 * A link-multiple field (for has-many relations).
 */
class LinkMultipleFieldView extends BaseFieldView {

    type = 'linkMultiple'

    listTemplate = 'fields/link-multiple/list'
    detailTemplate = 'fields/link-multiple/detail'
    editTemplate = 'fields/link-multiple/edit'
    searchTemplate = 'fields/link-multiple/search'

    /**
     * A name-hash attribute name.
     *
     * @protected
     * @type {string}
     */
    nameHashName

    /**
     * A IDs attribute name.
     *
     * @protected
     * @type {string}
     */
    idsName

    /**
     * @protected
     * @type {Object.<string,string>|null}
     */
    nameHash = null

    /**
     * @protected
     * @type {string[]|null}
     */
    ids = null

    /**
     * A foreign entity type.
     *
     * @protected
     * @type {string}
     */
    foreignScope

    /**
     * Autocomplete disabled.
     *
     * @protected
     * @type {boolean}
     */
    autocompleteDisabled = false

    /**
     * A select-record view.
     *
     * @protected
     * @type {string}
     */
    selectRecordsView = 'views/modals/select-records'

    /**
     * Create disabled.
     *
     * @protected
     * @type {boolean}
     */
    createDisabled = false

    /**
     * Force create button even is disabled in clientDefs > relationshipPanels.
     *
     * @protected
     * @type {boolean}
     */
    forceCreateButton = false

    /**
     * To display the create button.
     *
     * @protected
     * @type {boolean}
     */
    createButton = false

    /**
     * @protected
     * @type {boolean}
     */
    sortable = false

    /**
     * A search type list.
     *
     * @protected
     * @type {string[]}
     */
    searchTypeList = [
        'anyOf',
        'isEmpty',
        'isNotEmpty',
        'noneOf',
        'allOf',
    ]

    /**
     * A primary filter list that will be available when selecting a record.
     *
     * @protected
     * @type {string[]|null}
     */
    selectFilterList = null

    /**
     * A select bool filter list.
     *
     * @protected
     * @type {string[]|null}
     */
    selectBoolFilterList = null

    /**
     * A select primary filter.
     *
     * @protected
     * @type {string|null}
     */
    selectPrimaryFilterName = null

    /**
     * An autocomplete max record number.
     *
     * @protected
     * @type {number|null}
     */
    autocompleteMaxCount = null

    /**
     * Trigger autocomplete on empty input.
     *
     * @protected
     * @type {boolean}
     */
    autocompleteOnEmpty = false

    /**
     * Select all attributes.
     *
     * @protected
     * @type {boolean}
     */
    forceSelectAllAttributes = false

    /**
     * @protected
     * @type {string}
     */
    iconHtml = ''

    /** @inheritDoc */
    events = {
        /** @this LinkMultipleFieldView */
        'auxclick a[href]:not([role="button"])': function (e) {
            if (!this.isReadMode()) {
                return;
            }

            const isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            const id = $(e.currentTarget).attr('data-id');

            if (!id) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.quickView(id);
        },
    }

    // noinspection JSCheckFunctionSignatures
    /** @inheritDoc */
    data() {
        const ids = this.model.get(this.idsName);
        const createButton = this.createButton && (!this.createDisabled || this.forceCreateButton);

        // noinspection JSValidateTypes
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
     *
     * @protected
     * @return {Object.<string, module:search-manager~advancedFilter>|null}
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
     * @return {Object.<string, *>|null}
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
        this.nameHashName = this.name + 'Names';
        this.idsName = this.name + 'Ids';

        this.foreignScope = this.options.foreignScope ||
            this.foreignScope ||
            this.model.getFieldParam(this.name, 'entity') ||
            this.model.getLinkParam(this.name, 'entity');

        if ('createDisabled' in this.options) {
            this.createDisabled = this.options.createDisabled;
        }

        if (this.isSearchMode()) {
            const nameHash = this.getSearchParamsData().nameHash || this.searchParams.nameHash || {};
            const idList = this.getSearchParamsData().idList || this.searchParams.value || [];

            this.nameHash = Espo.Utils.clone(nameHash);
            this.ids = Espo.Utils.clone(idList);
        }
        else {
            this.copyValuesFromModel();
        }

        this.listenTo(this.model, 'change:' + this.idsName, () => {
            this.copyValuesFromModel();
        });

        this.sortable = this.sortable || this.params.sortable;

        this.iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);

        if (!this.isListMode()) {
            this.addActionHandler('selectLink', () => this.actionSelect());

            this.events['click a[data-action="clearLink"]'] = (e) => {
                const id = $(e.currentTarget).attr('data-id');

                this.deleteLink(id);

                // noinspection JSUnresolvedReference
                this.$element.get(0).focus({preventScroll: true});
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
    }

    /**
     * Copy values from a model to view properties.
     */
    copyValuesFromModel() {
        this.ids = Espo.Utils.clone(this.model.get(this.idsName) || []);
        this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});
    }

    /**
     * Handle a search type.
     *
     * @protected
     * @param {string} type A type.
     */
    handleSearchType(type) {
        if (~['anyOf', 'noneOf', 'allOf'].indexOf(type)) {
            this.$el.find('div.link-group-container').removeClass('hidden');
        }
        else {
            this.$el.find('div.link-group-container').addClass('hidden');
        }
    }

    /** @inheritDoc */
    setupSearch() {
        this.events = _.extend({
            'change select.search-type': (e) => {
                const type = $(e.currentTarget).val();

                this.handleSearchType(type);
            },
        }, this.events || {});
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

    // noinspection JSUnusedLocalSymbols
    /**
     * Compose an autocomplete URL. Can be extended.
     *
     * @protected
     * @param {string} [q] A query.
     * @return {string|Promise<string>}
     */
    getAutocompleteUrl(q) {
        let url = this.foreignScope + '?&maxSize=' + this.getAutocompleteMaxCount();

        if (!this.forceSelectAllAttributes) {
            /** @var {Object.<string, *>} */
            const panelDefs = this.getMetadata()
                .get(['clientDefs', this.entityType, 'relationshipPanels', this.name]) || {};

            const mandatorySelectAttributeList = this.mandatorySelectAttributeList ||
                panelDefs.selectMandatoryAttributeList;

            let select = ['id', 'name'];

            if (mandatorySelectAttributeList) {
                select = select.concat(mandatorySelectAttributeList);
            }

            url += '&select=' + select.join(',')
        }

        const notSelectedFilter = this.ids && this.ids.length ?
            {
                id: {
                    type: 'notIn',
                    attribute: 'id',
                    value: this.ids,
                }
            } :
            {};

        if (this.panelDefs.selectHandler) {
            return new Promise(resolve => {
                this._getSelectFilters().then(filters => {
                    if (filters.bool) {
                        url += '&' + $.param({boolFilterList: filters.bool});
                    }

                    if (filters.primary) {
                        url += '&' + $.param({primaryFilter: filters.primary});
                    }

                    const advanced = {
                        ...notSelectedFilter,
                        ...(filters.advanced || {}),
                    };

                    if (Object.keys(advanced).length) {
                        url += '&' + $.param({where: advanced});
                    }

                    resolve(url);
                });
            });
        }

        const boolList = [
            ...(this.getSelectBoolFilterList() || []),
            ...(this.panelDefs.selectBoolFilterList || []),
        ];

        if (boolList.length) {
            url += '&' + $.param({'boolFilterList': boolList});
        }

        const primary = this.getSelectPrimaryFilterName() || this.panelDefs.selectPrimaryFilterName;

        if (primary) {
            url += '&' + $.param({'primaryFilter': primary});
        }

        if (Object.keys(notSelectedFilter).length) {
            url += '&' + $.param({'where': notSelectedFilter});
        }

        return url;
    }

    /** @inheritDoc */
    afterRender() {
        if (this.isEditMode() || this.isSearchMode()) {
            this.$element = this.$el.find('input.main-element');

            const $element = this.$element;

            if (!this.autocompleteDisabled) {
                // Does not work well with autocompleteOnEmpty.
                /*this.$element.on('blur', () => {
                    setTimeout(() => this.$element.autocomplete('clear'), 300);
                });*/

                const minChar = this.autocompleteOnEmpty ? 0 : 1;

                this.$element.autocomplete({
                    lookup: (q, callback) => {
                        Promise.resolve(this.getAutocompleteUrl(q))
                            .then(url => {
                                Espo.Ajax
                                    .getRequest(url, {q: q})
                                    .then(response => {
                                        callback(this._transformAutocompleteResult(response));
                                    });
                            });
                    },
                    minChars: minChar,
                    paramName: 'q',
                    noCache: true,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    beforeRender: $c => {
                        if (this.$element.hasClass('input-sm')) {
                            $c.addClass('small');
                        }

                        // Prevent an issue that suggestions are shown and not hidden
                        // when clicking outside the window and then focusing back on the document.
                        if (this.$element.get(0) !== document.activeElement) {
                            setTimeout(() => this.$element.autocomplete('hide'), 30);
                        }
                    },
                    formatResult: suggestion => {
                        // noinspection JSUnresolvedReference
                        return this.getHelper().escapeString(suggestion.name);
                    },
                    transformResult: response => {
                        response = JSON.parse(response);

                        const list = [];

                        response.list.forEach((item) => {
                            list.push({
                                id: item.id,
                                name: item.name || item.id,
                                data: item.id,
                                value: item.name || item.id,
                            });
                        });

                        return {
                            suggestions: list
                        };
                    },
                    onSelect: s => {
                        this.getModelFactory().create(this.foreignScope, model => {
                            // noinspection JSUnresolvedReference
                            model.set(s.attributes);

                            this.select([model])

                            this.$element.val('');
                            this.$element.focus();
                        });
                    },
                });

                this.$element.attr('autocomplete', 'espo-' + this.name);

                this.once('render', () => {
                    $element.autocomplete('dispose');
                });

                this.once('remove', () => {
                    $element.autocomplete('dispose');
                });
            }

            $element.on('change', () => {
                $element.val('');
            });

            this.renderLinks();

            if (this.isEditMode()) {
                if (this.sortable) {
                    // noinspection JSUnresolvedReference
                    this.$el.find('.link-container').sortable({
                        stop: () => {
                            this.fetchFromDom();
                            this.trigger('change');
                        },
                    });
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
    }

    /**
     * Render items.
     *
     * @protected
     */
    renderLinks() {
        this.ids.forEach(id => {
            this.addLinkHtml(id, this.nameHash[id]);
        });
    }

    /**
     * Delete an item.
     *
     * @protected
     * @param {string} id An ID.
     */
    deleteLink(id) {
        this.trigger('delete-link', id);
        this.trigger('delete-link:' + id);

        this.deleteLinkHtml(id);

        const index = this.ids.indexOf(id);

        if (index > -1) {
            this.ids.splice(index, 1);
        }

        delete this.nameHash[id];

        this.afterDeleteLink(id);
        this.trigger('change');
    }

    /**
     * Add an item.
     *
     * @protected
     * @param {string} id An ID.
     * @param {string} name A name.
     */
    addLink(id, name) {
        if (!~this.ids.indexOf(id)) {
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
     * @protected
     * @param {string} id An ID.
     */
    afterDeleteLink(id) {}

    /**
     * @protected
     * @param {string} id An ID.
     */
    afterAddLink(id) {}

    /**
     * @protected
     * @param {string} id An ID.
     */
    deleteLinkHtml(id) {
        this.$el.find('.link-' + id).remove();
    }

    /**
     * Add an item for edit mode.
     *
     * @protected
     * @param {string} id An ID.
     * @param {string} name A name.
     * @return {JQuery|null}
     */
    addLinkHtml(id, name) {
        // Do not use the `html` method to avoid XSS.

        name = name || id;

        const $container = this.$el.find('.link-container');

        const $el = $('<div>')
            .addClass('link-' + id)
            .addClass('list-group-item')
            .attr('data-id', id);

        $el.text(name).append('&nbsp;');

        $el.prepend(
            $('<a>')
                .addClass('pull-right')
                .attr('role', 'button')
                .attr('tabindex', '0')
                .attr('data-id', id)
                .attr('data-action', 'clearLink')
                .append(
                    $('<span>').addClass('fas fa-times')
                )
        );

        $container.append($el);

        return $el;
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * @param {string} id An ID.
     * @return {string}
     */
    getIconHtml(id) {
        return this.iconHtml;
    }

    /**
     * Get an item HTML for detail mode.
     *
     * @param {string} id An ID.
     * @param {string} [name] A name.
     * @return {string}
     */
    getDetailLinkHtml(id, name) {
        // Do not use the `html` method to avoid XSS.

        name = name || this.nameHash[id] || id;

        if (!name && id) {
            name = this.translate(this.foreignScope, 'scopeNames');
        }

        const iconHtml = this.isDetailMode() ?
            this.getIconHtml(id) : '';

        const $a = $('<a>')
            .attr('href', this.getUrl(id))
            .attr('data-id', id)
            .text(name);

        if (iconHtml) {
            $a.prepend(iconHtml)
        }

        return $a.get(0).outerHTML;
    }

    /**
     * @protected
     * @param {string} id An ID.
     * @return {string}
     */
    getUrl(id) {
        return '#' + this.foreignScope + '/view/' + id;
    }

    /** @inheritDoc */
    getValueForDisplay() {
        if (!this.isDetailMode() && !this.isListMode()) {
            return null;
        }

        const itemList = [];

        this.ids.forEach(id => {
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

    /** @inheritDoc */
    fetch() {
        const data = {};

        data[this.idsName] = Espo.Utils.clone(this.ids);
        data[this.nameHashName] = Espo.Utils.clone(this.nameHash);

        return data;
    }

    /** @inheritDoc */
    fetchFromDom() {
        this.ids = [];

        this.$el.find('.link-container').children().each((i, li) => {
            const id = $(li).attr('data-id');

            if (!id) {
                return;
            }

            this.ids.push(id);
        });
    }

    /** @inheritDoc */
    fetchSearch() {
        const type = this.$el.find('select.search-type').val();
        const idList = this.ids || [];

        if (~['anyOf', 'allOf', 'noneOf'].indexOf(type) && !idList.length) {
            return {
                type: 'isNotNull',
                attribute: 'id',
                data: {
                    type: type,
                },
            };
        }

        let data;

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
    }

    /** @inheritDoc */
    getSearchType() {
        return this.getSearchParamsData().type ||
            this.searchParams.typeFront ||
            this.searchParams.type || 'anyOf';
    }

    /**
     * @protected
     * @param {string} id
     */
    quickView(id) {
        const entityType = this.foreignScope;

        const helper = new RecordModal(this.getMetadata(), this.getAcl());

        helper.showDetail(this, {
            id: id,
            scope: entityType,
        });
    }

    /**
     * @protected
     */
    actionSelect() {
        Espo.Ui.notify(' ... ');

        const panelDefs = this.panelDefs;

        const viewName = panelDefs.selectModalView ||
            this.getMetadata().get(`clientDefs.${this.foreignScope}.modalViews.select`) ||
            this.selectRecordsView;

        const mandatorySelectAttributeList = this.mandatorySelectAttributeList ||
            panelDefs.selectMandatoryAttributeList;

        const createButton = this.isEditMode() &&
            (!this.createDisabled && !panelDefs.createDisabled || this.forceCreateButton);

        const createAttributesProvider = createButton ?
            this.getCreateAttributesProvider() :
            null;

        this._getSelectFilters().then(filters => {
            this.createView('dialog', viewName, {
                scope: this.foreignScope,
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
            }, dialog => {
                dialog.render();

                Espo.Ui.notify(false);

                this.listenToOnce(dialog, 'select', models => {
                    this.clearView('dialog');

                    if (Object.prototype.toString.call(models) !== '[object Array]') {
                        models = [models];
                    }

                    this.select(models);
                });
            });
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
                    .then(handler => {
                        handler.getAttributes(this.model)
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
     * On records select.
     *
     * @protected
     * @param {module:model[]} models
     * @since 8.0.4
     */
    select(models) {
        models.forEach(model => {
            this.addLink(model.id, model.get('name'));
        });
    }

    /**
     * @private
     * @return {Promise<{bool?: string[], advanced?: Object, primary?: string}>}
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

            return Promise.resolve({
                primary: this.getSelectPrimaryFilterName() || this.panelDefs.selectPrimaryFilterName,
                bool: boolFilterList,
                advanced: this.getSelectFilters() || undefined,
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

                    resolve({
                        bool: boolFilterList,
                        primary: primaryFilter,
                        advanced: advanced,
                    });
                });
        });
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

        return {suggestions: list};
    }

    actionCreateLink() {
        const viewName = this.getMetadata().get(['clientDefs', this.foreignScope, 'modalViews', 'edit']) ||
            'views/modals/edit';

        Espo.Ui.notify(' ... ');

        this.getCreateAttributesProvider()().then(attributes => {
            this.createView('dialog', viewName, {
                scope: this.foreignScope,
                fullFormDisabled: true,
                attributes: attributes,
            }, view => {
                view.render()
                    .then(() => Espo.Ui.notify(false));

                this.listenToOnce(view, 'after:save', model => {
                    view.close();
                    this.clearView('dialog');

                    this.select([model]);
                });
            });
        });
    }
}

export default LinkMultipleFieldView;
