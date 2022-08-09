/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/link', ['views/fields/base'], function (Dep) {

    /**
     * A link field (belongs-to relation).
     *
     * @class
     * @name Class
     * @extends module:views/fields/base.Class
     * @memberOf module:views/fields/link
     */
    return Dep.extend(/** @lends module:views/fields/link.Class# */{

        /**
         * @inheritDoc
         */
        type: 'link',

        /**
         * @inheritDoc
         */
        listTemplate: 'fields/link/list',

        /**
         * @inheritDoc
         */
        detailTemplate: 'fields/link/detail',

        /**
         * @inheritDoc
         */
        editTemplate: 'fields/link/edit',

        /**
         * @inheritDoc
         */
        searchTemplate: 'fields/link/search',

        /**
         * A name attribute name.
         *
         * @type {string}
         */
        nameName: null,

        /**
         * An ID attribute name.
         *
         * @type {string}
         */
        idName: null,

        /**
         * A foreign entity type.
         *
         * @type {string}
         */
        foreignScope: null,

        /**
         * A select-record view.
         *
         * @protected
         * @type {string}
         */
        selectRecordsView: 'views/modals/select-records',

        /**
         * Autocomplete disabled.
         *
         * @protected
         * @type {boolean}
         */
        autocompleteDisabled: false,

        /**
         * Create disabled.
         *
         * @protected
         * @type {boolean}
         */
        createDisabled: false,

        /**
         * A search type list.
         *
         * @protected
         * @type {string[]}
         */
        searchTypeList: [
            'is',
            'isEmpty',
            'isNotEmpty',
            'isNot',
            'isOneOf',
            'isNotOneOf',
        ],

        /**
         * A primary filter list that will be available when selecting a record.
         *
         * @protected
         * @type {string[]|null}
         */
        selectFilterList: null,

        /**
         * A select primary filter.
         *
         * @protected
         * @type {string|null}
         */
        selectPrimaryFilterName: null,

        /**
         * A select bool filter list.
         *
         * @protected
         * @type {string[]|null}
         */
        selectBoolFilterList: null,

        /**
         * An autocomplete max record number.
         *
         * @protected
         * @type {number|null}
         */
        autocompleteMaxCount: null,

        /**
         * Select all attributes.
         *
         * @protected
         * @type {boolean}
         */
        forceSelectAllAttributes: false,

        /**
         * @protected
         * @type {string[]|null}
         */
        mandatorySelectAttributeList: null,

        /**
         * @inheritDoc
         */
        data: function () {
            var nameValue = this.model.has(this.nameName) ?
                this.model.get(this.nameName) :
                this.model.get(this.idName);

            if (nameValue === null) {
                nameValue = this.model.get(this.idName);
            }

            if (this.isReadMode() && !nameValue && this.model.get(this.idName)) {
                nameValue = this.translate(this.foreignScope, 'scopeNames');
            }

            var iconHtml = null;

            if (this.isDetailMode()) {
                iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);
            }

            return _.extend({
                idName: this.idName,
                nameName: this.nameName,
                idValue: this.model.get(this.idName),
                nameValue: nameValue,
                foreignScope: this.foreignScope,
                valueIsSet: this.model.has(this.idName),
                iconHtml: iconHtml,
                url: this.getUrl(),
            }, Dep.prototype.data.call(this));
        },

        getEmptyAutocompleteResult: null,

        /**
         * @protected
         * @return {?string}
         */
        getUrl: function () {
            let id = this.model.get(this.idName);

            if (!id) {
                return null;
            }

            return '#' + this.foreignScope + '/view/' + id;
        },

        /**
         * Get advanced filters (field filters) to be applied when select a record.
         * Can be extended.
         *
         * @protected
         * @return {Object.<string,module:search-manager~advancedFilter>|null}
         */
        getSelectFilters: function () {
            return null;
        },

        /**
         * Get a select bool filter list. Applied when select a record.
         * Can be extended.
         *
         * @protected
         * @return {string[]|null}
         */
        getSelectBoolFilterList: function () {
            return this.selectBoolFilterList;
        },

        /**
         * Get a select primary filter. Applied when select a record.
         * Can be extended.
         *
         * @protected
         * @return {string|null}
         */
        getSelectPrimaryFilterName: function () {
            return this.selectPrimaryFilterName;
        },

        /**
         * Get a primary filter list that will be available when selecting a record.
         * Can be extended.
         *
         * @return {string[]|null}
         */
        getSelectFilterList: function () {
            return this.selectFilterList;
        },

        /**
         * Attributes to pass to a model when creating a new record.
         * Can be extended.
         *
         * @return {Object.<string,*>|null}
         */
        getCreateAttributes: function () {
            return null;
        },

        /**
         * @inheritDoc
         */
        setup: function () {
            this.nameName = this.name + 'Name';
            this.idName = this.name + 'Id';

            this.foreignScope = this.options.foreignScope || this.foreignScope;

            this.foreignScope = this.foreignScope ||
                this.model.getFieldParam(this.name, 'entity') || this.model.getLinkParam(this.name, 'entity');

            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }

            if (this.mode !== 'list') {
                this.addActionHandler('selectLink', () => {
                    this.notify('Loading...');

                    var viewName = this.getMetadata()
                            .get('clientDefs.' + this.foreignScope + '.modalViews.select') ||
                        this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && !this.isSearchMode(),
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        createAttributes: this.isEditMode() ? this.getCreateAttributes() : null,
                        mandatorySelectAttributeList: this.mandatorySelectAttributeList,
                        forceSelectAllAttributes: this.forceSelectAllAttributes,
                        filterList: this.getSelectFilterList(),
                    }, view => {
                        view.render();

                        Espo.Ui.notify(false);

                        this.listenToOnce(view, 'select', model => {
                            this.clearView('dialog');

                            this.select(model);
                        });
                    });
                });

                this.addActionHandler('clearLink', () => {
                    this.clearLink();
                });
            }

            if (this.mode === 'search') {
                this.addActionHandler('selectLinkOneOf', () => {
                    this.notify('Loading...');

                    var viewName = this.getMetadata()
                            .get('clientDefs.' + this.foreignScope + '.modalViews.select') ||
                        this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode !== 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        multiple: true,
                    }, (view) => {
                        view.render();
                        this.notify(false);

                        this.listenToOnce(view, 'select', (models) => {
                            this.clearView('dialog');

                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }

                            models.forEach((model) => {
                                this.addLinkOneOf(model.id, model.get('name'));
                            });
                        });
                    });
                });

                this.events['click a[data-action="clearLinkOneOf"]'] = function (e) {
                    var id = $(e.currentTarget).data('id').toString();

                    this.deleteLinkOneOf(id);
                };
            }
        },

        /**
         * Select.
         *
         * @param {module:model.Class} model A model.
         * @protected
         */
        select: function (model) {
            this.$elementName.val(model.get('name') || model.id);
            this.$elementId.val(model.get('id'));

            if (this.mode === 'search') {
                this.searchData.idValue = model.get('id');
                this.searchData.nameValue = model.get('name') || model.id;
            }

            this.trigger('change');
        },

        /**
         * Clear.
         */
        clearLink: function () {
            this.$elementName.val('');
            this.$elementId.val('');
            this.trigger('change');
        },

        /**
         * @inheritDoc
         */
        setupSearch: function () {
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

            this.events = _.extend({
                'change select.search-type': function (e) {
                    var type = $(e.currentTarget).val();
                    this.handleSearchType(type);
                },
            }, this.events || {});
        },

        /**
         * Handle a search type.
         *
         * @protected
         * @param {string} type A type.
         */
        handleSearchType: function (type) {
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
        },

        /**
         * Get an autocomplete max record number. Can be extended.
         *
         * @protected
         * @return {number}
         */
        getAutocompleteMaxCount: function () {
            if (this.autocompleteMaxCount) {
                return this.autocompleteMaxCount;
            }

            return this.getConfig().get('recordsPerPage');
        },

        /**
         * Compose an autocomplete URL. Can be extended.
         *
         * @protected
         * @return {string}
         */
        getAutocompleteUrl: function () {
            var url = this.foreignScope + '?maxSize=' + this.getAutocompleteMaxCount();

            if (!this.forceSelectAllAttributes) {
                var select = ['id', 'name'];

                if (this.mandatorySelectAttributeList) {
                    select = select.concat(this.mandatorySelectAttributeList);
                }

                url += '&select=' + select.join(',');
            }

            var boolList = this.getSelectBoolFilterList();

            if (boolList) {
                url += '&' + $.param({'boolFilterList': boolList});
            }

            var primary = this.getSelectPrimaryFilterName();

            if (primary) {
                url += '&' + $.param({'primaryFilter': primary});
            }

            return url;
        },

        /**
         * @inheritDoc
         */
        afterRender: function () {
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
                        if (this.mode === 'edit' && this.model.has(this.nameName)) {
                            e.currentTarget.value = this.model.get(this.nameName);
                        }
                    }, 100);

                    if (!this.autocompleteDisabled) {
                        setTimeout(() => this.$elementName.autocomplete('clear'), 300);
                    }
                });

                var $elementName = this.$elementName;

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
                            serviceUrl: q => {
                                return this.getAutocompleteUrl(q);
                            },
                            minChars: 1,
                            paramName: 'q',
                            noCache: true,
                            formatResult: suggestion => {
                                return this.getHelper().escapeString(suggestion.name);
                            },
                            transformResult: response => {
                                return this._transformAutocompleteResult(JSON.parse(response));
                            },
                            onSelect: s => {
                                this.addLinkOneOf(s.id, s.name);

                                $elementOneOf.val('');
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
        },

        /**
         * @private
         */
        _transformAutocompleteResult: function (response) {
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
        },

        /**
         * @inheritDoc
         */
        getValueForDisplay: function () {
            return this.model.get(this.nameName);
        },

        /**
         * @inheritDoc
         */
        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.idName) == null) {
                    var msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg);

                    return true;
                }
            }
        },

        /**
         * Delete a one-of item. For search mode.
         *
         * @param {string} id An ID.
         */
        deleteLinkOneOf: function (id) {
            this.deleteLinkOneOfHtml(id);

            var index = this.searchData.oneOfIdList.indexOf(id);

            if (index > -1) {
                this.searchData.oneOfIdList.splice(index, 1);
            }

            delete this.searchData.oneOfNameHash[id];

            this.trigger('change');
        },

        /**
         * Add a one-of item. For search mode.
         *
         * @param {string} id An ID.
         * @param {string} name A name.
         */
        addLinkOneOf: function (id, name) {
            if (!~this.searchData.oneOfIdList.indexOf(id)) {
                this.searchData.oneOfIdList.push(id);
                this.searchData.oneOfNameHash[id] = name;
                this.addLinkOneOfHtml(id, name);

                this.trigger('change');
            }
        },

        /**
         * @protected
         * @param {string} id An ID.
         */
        deleteLinkOneOfHtml: function (id) {
            this.$el.find('.link-one-of-container .link-' + id).remove();
        },

        /**
         * @protected
         * @param {string} id An ID.
         * @param {string} name A name.
         * @return {JQuery}
         */
        addLinkOneOfHtml: function (id, name) {
            id = Handlebars.Utils.escapeExpression(id);

            name = this.getHelper().escapeString(name);

            var $container = this.$el.find('.link-one-of-container');

            var $el = $('<div />').addClass('link-' + id).addClass('list-group-item');

            $el.html(name + '&nbsp');

            $el.prepend(
                '<a role="button" class="pull-right" data-id="' + id + '" ' +
                'data-action="clearLinkOneOf"><span class="fas fa-times"></a>'
            );

            $container.append($el);

            return $el;
        },

        /**
         * @inheritDoc
         */
        fetch: function () {
            var data = {};

            data[this.nameName] = this.$el.find('[data-name="'+this.nameName+'"]').val() || null;
            data[this.idName] = this.$el.find('[data-name="'+this.idName+'"]').val() || null;

            return data;
        },

        /**
         * @inheritDoc
         */
        fetchSearch: function () {
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
        },

        /**
         * @inheritDoc
         */
        getSearchType: function () {
            return this.getSearchParamsData().type ||
                this.searchParams.typeFront ||
                this.searchParams.type;
        },
    });
});
