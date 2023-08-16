/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/** @module views/fields/link */

import BaseFieldView from 'views/fields/base';
import RecordModal from 'helpers/record-modal';

/**
 * A link field (belongs-to relation).
 */
class LinkFieldView extends BaseFieldView {

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

    getEmptyAutocompleteResult = null

    /** @inheritDoc */
    events = {
        /** @this LinkFieldView */
        'auxclick a[href]:not([role="button"])': function (e) {
            if (!this.isReadMode()) {
                return;
            }

            let isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

            if (!isCombination) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.quickView();
        },
    }

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

        if (this.isDetailMode()) {
            iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
        }

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
        };
    }

    /**
     * @protected
     * @return {?string}
     */
    getUrl() {
        let id = this.model.get(this.idName);

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
        let attributeMap = this.getMetadata()
            .get(['clientDefs', this.entityType, 'relationshipPanels', this.name, 'createAttributeMap']) || {};

        let attributes = {};

        Object.keys(attributeMap).forEach(attr => attributes[attributeMap[attr]] = this.model.get(attr));

        return attributes;
    }

    /** @inheritDoc */
    setup() {
        this.nameName = this.name + 'Name';
        this.idName = this.name + 'Id';

        this.foreignScope = this.options.foreignScope || this.foreignScope;

        this.foreignScope = this.foreignScope ||
            this.model.getFieldParam(this.name, 'entity') || this.model.getLinkParam(this.name, 'entity');

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
                let id = $(e.currentTarget).data('id').toString();

                this.deleteLinkOneOf(id);
            };
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
    }

    /**
     * Clear.
     */
    clearLink() {
        this.$elementName.val('');
        this.$elementId.val('');

        this.trigger('change');
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
            let type = $(e.currentTarget).val();

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
     * Compose an autocomplete URL. Can be extended.
     *
     * @protected
     * @return {string}
     */
    getAutocompleteUrl() {
        let url = this.foreignScope + '?maxSize=' + this.getAutocompleteMaxCount();

        if (!this.forceSelectAllAttributes) {
            /** @var {Object.<string, *>} */
            const panelDefs = this.getMetadata()
                .get(['clientDefs', this.entityType, 'relationshipPanels', this.name]) || {};

            const mandatorySelectAttributeList = this.mandatorySelectAttributeList ||
                panelDefs.selectMandatoryAttributeList;

            let select = ['id', 'name'];

            if (mandatorySelectAttributeList) {
                select = select.concat(this.mandatorySelectAttributeList);
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

        return url;
    }

    /** @inheritDoc */
    afterRender() {
        if (this.isEditMode() || this.isSearchMode()) {
            this.$elementId = this.$el.find('input[data-name="' + this.idName + '"]');
            this.$elementName = this.$el.find('input[data-name="' + this.nameName + '"]');

            this.$elementName.on('change', () => {
                if (this.$elementName.val() === '') {
                    this.$elementName.val('');
                    this.$elementId.val('');

                    this.trigger('change');
                }
            });

            this.$elementName.on('blur', e => {
                setTimeout(() => {
                    if (this.mode === this.MODE_EDIT && this.model.has(this.nameName)) {
                        e.currentTarget.value = this.model.get(this.nameName);
                    }
                }, 100);

                if (!this.autocompleteDisabled) {
                    setTimeout(() => this.$elementName.autocomplete('clear'), 300);
                }
            });

            let $elementName = this.$elementName;

            if (!this.autocompleteDisabled) {
                let isEmptyQueryResult = false;

                if (this.getEmptyAutocompleteResult) {
                    this.$elementName.on('keydown', e => {
                        if (e.code === 'Tab' && isEmptyQueryResult) {
                            e.stopImmediatePropagation();
                        }
                    });
                }

                this.$elementName.autocomplete({
                    beforeRender: $c => {
                        if (this.$elementName.hasClass('input-sm')) {
                            $c.addClass('small');
                        }
                    },
                    serviceUrl: q => {
                        return this.getAutocompleteUrl(q);
                    },
                    lookup: (q, callback) => {
                        if (q.length === 0) {
                            isEmptyQueryResult = true;

                            if (this.getEmptyAutocompleteResult) {
                                callback(
                                    this._transformAutocompleteResult(this.getEmptyAutocompleteResult())
                                );
                            }

                            return;
                        }

                        isEmptyQueryResult = false;

                        Espo.Ajax
                            .getRequest(this.getAutocompleteUrl(q), {q: q})
                            .then(response => {
                                callback(this._transformAutocompleteResult(response));
                            });
                    },
                    minChars: 0,
                    triggerSelectOnValidInput: false,
                    autoSelectFirst: true,
                    noCache: true,
                    formatResult: suggestion => {
                        return this.getHelper().escapeString(suggestion.name);
                    },
                    onSelect: (s) => {
                        this.getModelFactory().create(this.foreignScope, (model) => {
                            model.set(s.attributes);

                            this.select(model);

                            this.$elementName.focus();
                        });
                    },
                });

                this.$elementName.off('focus.autocomplete');

                this.$elementName.on('focus', () => {
                    if (this.$elementName.val()) {
                        this.$elementName.get(0).select();

                        return;
                    }

                    this.$elementName.autocomplete('onFocus');
                });

                this.$elementName.attr('autocomplete', 'espo-' + this.name);

                this.once('render', () => {
                    $elementName.autocomplete('dispose');
                });

                this.once('remove', () => {
                    $elementName.autocomplete('dispose');
                });

                if (this.isSearchMode()) {
                    let $elementOneOf = this.$el.find('input.element-one-of');

                    $elementOneOf.autocomplete({
                        serviceUrl: () => {
                            return this.getAutocompleteUrl();
                        },
                        minChars: 1,
                        paramName: 'q',
                        noCache: true,
                        formatResult: suggestion => {
                            // noinspection JSUnresolvedReference
                            return this.getHelper().escapeString(suggestion.name);
                        },
                        transformResult: response => {
                            return this._transformAutocompleteResult(JSON.parse(response));
                        },
                        onSelect: s => {
                            // noinspection JSUnresolvedReference
                            this.addLinkOneOf(s.id, s.name);

                            $elementOneOf.val('');
                            setTimeout(() => $elementOneOf.focus(), 50);
                        },
                    });

                    $elementOneOf.attr('autocomplete', 'espo-' + this.name);

                    this.once('render', () => {
                        $elementOneOf.autocomplete('dispose');
                    });

                    this.once('remove', () => {
                        $elementOneOf.autocomplete('dispose');
                    });

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
            var type = this.$el.find('select.search-type').val();

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
        let list = [];

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

    /** @inheritDoc */
    getValueForDisplay() {
        return this.model.get(this.nameName);
    }

    /** @inheritDoc */
    validateRequired() {
        if (this.isRequired()) {
            if (this.model.get(this.idName) == null) {
                var msg = this.translate('fieldIsRequired', 'messages')
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

        var index = this.searchData.oneOfIdList.indexOf(id);

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
        let $container = this.$el.find('.link-one-of-container');

        let $el = $('<div>')
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
        var data = {};

        data[this.nameName] = this.$el.find('[data-name="'+this.nameName+'"]').val() || null;
        data[this.idName] = this.$el.find('[data-name="'+this.idName+'"]').val() || null;

        return data;
    }

    /** @inheritDoc */
    fetchSearch() {
        var type = this.$el.find('select.search-type').val();
        var value = this.$el.find('[data-name="' + this.idName + '"]').val();

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

            let nameValue = this.$el.find('[data-name="' + this.nameName + '"]').val();

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

            let nameValue = this.$el.find('[data-name="' + this.nameName + '"]').val();

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

        let nameValue = this.$el.find('[data-name="' + this.nameName + '"]').val();

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
        let id = this.model.get(this.idName);

        if (!id) {
            return;
        }

        let entityType = this.foreignScope;

        let helper = new RecordModal(this.getMetadata(), this.getAcl());

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

        /** @var {Object.<string, *>} */
        const panelDefs = this.getMetadata()
            .get(['clientDefs', this.entityType, 'relationshipPanels', this.name]) || {};

        const viewName = panelDefs.selectModalView ||
            this.getMetadata().get(['clientDefs', this.foreignScope, 'modalViews', 'select']) ||
            this.selectRecordsView;

        const mandatorySelectAttributeList = this.mandatorySelectAttributeList ||
            panelDefs.selectMandatoryAttributeList;

        const handler = panelDefs.selectHandler || null;

        const createButton = this.isEditMode() &&
            (!this.createDisabled && !panelDefs.createDisabled || this.forceCreateButton);

        let createAttributesProvider = null;

        if (createButton) {
            createAttributesProvider = () => {
                let attributes = this.getCreateAttributes() || {};

                if (!panelDefs.createHandler) {
                    return Promise.resolve(attributes);
                }

                return new Promise(resolve => {
                    Espo.loader.requirePromise(panelDefs.createHandler)
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

        new Promise(resolve => {
            if (!handler || this.isSearchMode()) {
                resolve({});

                return;
            }

            Espo.loader.requirePromise(handler)
                .then(Handler => new Handler(this.getHelper()))
                .then(/** module:handlers/select-related */handler => {
                    handler.getFilters(this.model)
                        .then(filters => resolve(filters));
                });
        }).then(filters => {
            const advanced = {...(this.getSelectFilters() || {}), ...(filters.advanced || {})};
            const boolFilterList = [
                ...(this.getSelectBoolFilterList() || []),
                ...(filters.bool || []),
                ...(panelDefs.selectBoolFilterList || []),
            ];
            const primaryFilter = this.getSelectPrimaryFilterName() ||
                filters.primary || panelDefs.selectPrimaryFilter;

            this.createView('dialog', viewName, {
                scope: this.foreignScope,
                createButton: createButton,
                filters: advanced,
                boolFilterList: boolFilterList,
                primaryFilterName: primaryFilter,
                mandatorySelectAttributeList: mandatorySelectAttributeList,
                forceSelectAllAttributes: this.forceSelectAllAttributes,
                filterList: this.getSelectFilterList(),
                createAttributesProvider: createAttributesProvider,
            }, view => {
                view.render();

                Espo.Ui.notify(false);

                this.listenToOnce(view, 'select', model => {
                    this.clearView('dialog');

                    this.select(model);
                });
            });
        });
    }

    actionSelectOneOf() {
        Espo.Ui.notify(' ... ');

        let viewName = this.getMetadata()
                .get(['clientDefs', this.foreignScope, 'modalViews', 'select']) ||
            this.selectRecordsView;

        this.createView('dialog', viewName, {
            scope: this.foreignScope,
            createButton: false,
            filters: this.getSelectFilters(),
            boolFilterList: this.getSelectBoolFilterList(),
            primaryFilterName: this.getSelectPrimaryFilterName(),
            multiple: true,
        }, view => {
            view.render();

            Espo.Ui.notify(false);

            this.listenToOnce(view, 'select', models => {
                this.clearView('dialog');

                if (Object.prototype.toString.call(models) !== '[object Array]') {
                    models = [models];
                }

                models.forEach(model => {
                    this.addLinkOneOf(model.id, model.get('name'));
                });
            });
        });
    }
}

export default LinkFieldView;
