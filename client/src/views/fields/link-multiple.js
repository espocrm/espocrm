/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/link-multiple', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'linkMultiple',

        listTemplate: 'fields/link-multiple/list',

        detailTemplate: 'fields/link-multiple/detail',

        editTemplate: 'fields/link-multiple/edit',

        searchTemplate: 'fields/link-multiple/search',

        nameHashName: null,

        idsName: null,

        nameHash: null,

        foreignScope: null,

        autocompleteDisabled: false,

        selectRecordsView: 'views/modals/select-records',

        createDisabled: false,

        sortable: false,

        searchTypeList: ['anyOf', 'isEmpty', 'isNotEmpty', 'noneOf', 'allOf'],

        selectFilterList: null,

        data: function () {
            var ids = this.model.get(this.idsName);

            return _.extend({
                idValues: this.model.get(this.idsName),
                idValuesString: ids ? ids.join(',') : '',
                nameHash: this.model.get(this.nameHashName),
                foreignScope: this.foreignScope,
                valueIsSet: this.model.has(this.idsName),
            }, Dep.prototype.data.call(this));
        },

        getSelectFilters: function () {},

        getSelectBoolFilterList: function () {
            return this.selectBoolFilterList;
        },

        getSelectPrimaryFilterName: function () {
            return this.selectPrimaryFilterName;
        },

        getSelectFilterList: function () {
            return this.selectFilterList;
        },

        getCreateAttributes: function () {},

        setup: function () {
            this.nameHashName = this.name + 'Names';
            this.idsName = this.name + 'Ids';

            this.foreignScope = this.options.foreignScope ||
                this.foreignScope ||
                this.model.getFieldParam(this.name, 'entity') ||
                this.model.getLinkParam(this.name, 'entity');

            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }

            var self = this;

            if (this.mode === 'search') {
                var nameHash = this.getSearchParamsData().nameHash || this.searchParams.nameHash || {};
                var idList = this.getSearchParamsData().idList || this.searchParams.value || [];
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

            if (this.mode !== 'list') {
                this.addActionHandler('selectLink', () => {
                    self.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select') ||
                        this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode !== 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        filterList: this.getSelectFilterList(),
                        multiple: true,
                        createAttributes: (this.mode === 'edit') ? this.getCreateAttributes() : null,
                        mandatorySelectAttributeList: this.mandatorySelectAttributeList,
                        forceSelectAllAttributes: this.forceSelectAllAttributes,
                    }, (dialog) => {
                        dialog.render();

                        self.notify(false);

                        this.listenToOnce(dialog, 'select', (models) => {
                            this.clearView('dialog');

                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }

                            models.forEach((model) => {
                                self.addLink(model.id, model.get('name'));
                            });
                        });
                    });
                });

                this.events['click a[data-action="clearLink"]'] = (e) => {
                    var id = $(e.currentTarget).attr('data-id');

                    this.deleteLink(id);
                };
            }
        },

        copyValuesFromModel: function () {
            this.ids = Espo.Utils.clone(this.model.get(this.idsName) || []);
            this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});
        },

        handleSearchType: function (type) {
            if (~['anyOf', 'noneOf', 'allOf'].indexOf(type)) {
                this.$el.find('div.link-group-container').removeClass('hidden');
            }
            else {
                this.$el.find('div.link-group-container').addClass('hidden');
            }
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': (e) => {
                    var type = $(e.currentTarget).val();

                    this.handleSearchType(type);
                },
            }, this.events || {});
        },

        getAutocompleteMaxCount: function () {
            if (this.autocompleteMaxCount) {
                return this.autocompleteMaxCount;
            }

            return this.getConfig().get('recordsPerPage');
        },

        getAutocompleteUrl: function () {
            var url = this.foreignScope + '?orderBy=name&maxSize=' + this.getAutocompleteMaxCount();

            if (!this.forceSelectAllAttributes) {
                var select = ['id', 'name'];

                if (this.mandatorySelectAttributeList) {

                    select = select.concat(this.mandatorySelectAttributeList);
                }

                url += '&select=' + select.join(',')
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

        afterRender: function () {
            if (this.mode === 'edit' || this.mode === 'search') {
                this.$element = this.$el.find('input.main-element');

                var $element = this.$element;

                if (!this.autocompleteDisabled) {
                    this.$element.autocomplete({
                        serviceUrl: (q) => {
                            return this.getAutocompleteUrl(q);
                        },
                        minChars: 1,
                        paramName: 'q',
                        noCache: true,
                        triggerSelectOnValidInput: false,
                        beforeRender: ($c) => {
                            if (this.$element.hasClass('input-sm')) {
                                $c.addClass('small');
                            }
                        },
                        formatResult: (suggestion) => {
                            return this.getHelper().escapeString(suggestion.name);
                        },
                        transformResult: (response) => {
                            var response = JSON.parse(response);

                            var list = [];

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
                        onSelect: (s) => {
                            this.addLink(s.id, s.name);

                            this.$element.val('');
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

                if (this.mode === 'edit') {
                    if (this.sortable) {
                        this.$el.find('.link-container').sortable({
                            stop: () => {
                                this.fetchFromDom();
                                this.trigger('change');
                            },
                        });
                    }
                }

                if (this.mode === 'search') {
                    var type = this.$el.find('select.search-type').val();

                    this.handleSearchType(type);

                    this.$el.find('select.search-type').on('change', () => {
                        this.trigger('change');
                    });
                }
            }
        },

        renderLinks: function () {
            this.ids.forEach((id) => {
                this.addLinkHtml(id, this.nameHash[id]);
            });
        },

        deleteLink: function (id) {
            this.trigger('delete-link', id);
            this.trigger('delete-link:' + id);

            this.deleteLinkHtml(id);

            var index = this.ids.indexOf(id);

            if (index > -1) {
                this.ids.splice(index, 1);
            }

            delete this.nameHash[id];

            this.afterDeleteLink(id);

            this.trigger('change');
        },

        addLink: function (id, name) {
            if (!~this.ids.indexOf(id)) {
                this.ids.push(id);

                this.nameHash[id] = name;

                this.addLinkHtml(id, name);
                this.afterAddLink(id);

                this.trigger('add-link', id);
                this.trigger('add-link:' + id);
            }

            this.trigger('change');
        },

        afterDeleteLink: function (id) {},

        afterAddLink: function (id) {},

        deleteLinkHtml: function (id) {
            this.$el.find('.link-' + id).remove();
        },

        addLinkHtml: function (id, name) {
            name = name || id;

            id = Handlebars.Utils.escapeExpression(id);
            name = Handlebars.Utils.escapeExpression(name);

            var $container = this.$el.find('.link-container');

            var $el = $('<div />')
                .addClass('link-' + id)
                .addClass('list-group-item')
                .attr('data-id', id);

            $el.html(name + '&nbsp');

            $el.prepend(
                $('<a />')
                    .addClass('pull-right')
                    .attr('href', 'javascript:')
                    .attr('data-id', id)
                    .attr('data-action', 'clearLink')
                    .append(
                        $('<span />').addClass('fas fa-times')
                    )
            );

            $container.append($el);

            return $el;
        },

        getIconHtml: function (id) {
            return this.iconHtml;
        },

        getDetailLinkHtml: function (id) {
            var name = this.nameHash[id] || id;

            id = Handlebars.Utils.escapeExpression(id);
            name = Handlebars.Utils.escapeExpression(name);

            if (!name && id) {
                name = this.translate(this.foreignScope, 'scopeNames');
            }

            var iconHtml = '';

            if (this.mode === 'detail') {
                iconHtml = this.getIconHtml(id);
            }

            return '<a href="#' + this.foreignScope + '/view/' + id + '">' +
                iconHtml + name + '</a>';
        },

        getValueForDisplay: function () {
            if (this.mode !== 'detail' && this.mode !== 'list') {
                return null;
            }

            var names = [];

            this.ids.forEach((id) => {
                names.push(this.getDetailLinkHtml(id));
            });

            if (!names.length) {
                return null;
            }

            return names
                .map(
                    name => $('<div />')
                        .addClass('link-multiple-item')
                        .html(name)
                        .wrap('<div />').parent().html()
                )
                .join('');
        },

        validateRequired: function () {
            if (this.isRequired()) {
                var idList = this.model.get(this.idsName) || [];

                if (idList.length === 0) {
                    var msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg);

                    return true;
                }
            }
        },

        fetch: function () {
            var data = {};

            data[this.idsName] = this.ids;
            data[this.nameHashName] = this.nameHash;

            return data;
        },

        fetchFromDom: function () {
            this.ids = [];

            this.$el.find('.link-container').children().each((i, li) => {
                var id = $(li).attr('data-id');

                if (!id) {
                    return;
                }

                this.ids.push(id);
            });
        },

        fetchSearch: function () {
            var type = this.$el.find('select.search-type').val();
            var idList = this.ids || [];

            if (~['anyOf', 'allOf', 'noneOf'].indexOf(type) && !idList.length) {
                return {
                    type: 'isNotNull',
                    attribute: 'id',
                    data: {
                        type: type,
                    },
                };
            }

            if (type === 'anyOf') {
                var data = {
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
                var data = {
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
                var data = {
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
                var data = {
                    type: 'isNotLinked',
                    data: {
                        type: type,
                    },
                };

                return data;
            }

            if (type === 'isNotEmpty') {
                var data = {
                    type: 'isLinked',
                    data: {
                        type: type,
                    },
                };

                return data;
            }
        },

        getSearchType: function () {
            return this.getSearchParamsData().type ||
                this.searchParams.typeFront ||
                this.searchParams.type || 'anyOf';
        },

    });
});
