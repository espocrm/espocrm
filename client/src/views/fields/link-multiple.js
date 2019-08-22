/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/fields/link-multiple', 'views/fields/base', function (Dep) {

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

        searchTypeList: ['anyOf', 'isEmpty', 'isNotEmpty', 'noneOf'],

        data: function () {
            var ids = this.model.get(this.idsName);

            return _.extend({
                idValues: this.model.get(this.idsName),
                idValuesString: ids ? ids.join(',') : '',
                nameHash: this.model.get(this.nameHashName),
                foreignScope: this.foreignScope,
                valueIsSet: this.model.has(this.idsName)
            }, Dep.prototype.data.call(this));
        },

        getSelectFilters: function () {},

        getSelectBoolFilterList: function () {
            return this.selectBoolFilterList;
        },

        getSelectPrimaryFilterName: function () {
            return this.selectPrimaryFilterName;
        },

        getCreateAttributes: function () {},

        setup: function () {
            this.nameHashName = this.name + 'Names';
            this.idsName = this.name + 'Ids';

            this.foreignScope = this.options.foreignScope || this.foreignScope || this.model.getFieldParam(this.name, 'entity') || this.model.getLinkParam(this.name, 'entity');

            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }

            var self = this;

            if (this.mode == 'search') {
                var nameHash = this.getSearchParamsData().nameHash || this.searchParams.nameHash || {};
                var idList = this.getSearchParamsData().idList || this.searchParams.value || [];
                this.nameHash = Espo.Utils.clone(nameHash);
                this.ids = Espo.Utils.clone(idList);
            } else {
                this.ids = Espo.Utils.clone(this.model.get(this.idsName) || []);
                this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});
            }

            this.listenTo(this.model, 'change:' + this.idsName, function () {
                this.ids = Espo.Utils.clone(this.model.get(this.idsName) || []);
                this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});
            }, this);

            this.sortable = this.sortable || this.params.sortable;

            this.iconHtml = this.getHelper().getScopeColorIconHtml(this.foreignScope);

            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    self.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select')  || this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode != 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        multiple: true,
                        createAttributes: (this.mode === 'edit') ? this.getCreateAttributes() : null,
                        mandatorySelectAttributeList: this.mandatorySelectAttributeList,
                        forceSelectAllAttributes: this.forceSelectAllAttributes
                    }, function (dialog) {
                        dialog.render();
                        self.notify(false);
                        this.listenToOnce(dialog, 'select', function (models) {
                            this.clearView('dialog');
                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }
                            models.forEach(function (model) {
                                self.addLink(model.id, model.get('name'));
                            });
                        });
                    }, this);
                });

                this.events['click a[data-action="clearLink"]'] = function (e) {
                    var id = $(e.currentTarget).attr('data-id');
                    this.deleteLink(id);
                };
            }
        },

        handleSearchType: function (type) {
            if (~['anyOf', 'noneOf'].indexOf(type)) {
                this.$el.find('div.link-group-container').removeClass('hidden');
            } else {
                this.$el.find('div.link-group-container').addClass('hidden');
            }
        },

        setupSearch: function () {
            this.events = _.extend({
                'change select.search-type': function (e) {
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
            var where = [];
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
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$element = this.$el.find('input.main-element');

                var $element = this.$element;

                if (!this.autocompleteDisabled) {
                    this.$element.autocomplete({
                        serviceUrl: function (q) {
                            return this.getAutocompleteUrl(q);
                        }.bind(this),
                        minChars: 1,
                        paramName: 'q',
                        noCache: true,
                        triggerSelectOnValidInput: false,
                        formatResult: function (suggestion) {
                            return this.getHelper().escapeString(suggestion.name);
                        }.bind(this),
                        transformResult: function (response) {
                            var response = JSON.parse(response);
                            var list = [];
                            response.list.forEach(function(item) {
                                list.push({
                                    id: item.id,
                                    name: item.name || item.id,
                                    data: item.id,
                                    value: item.name || item.id,
                                });
                            }, this);
                            return {
                                suggestions: list
                            };
                        }.bind(this),
                        onSelect: function (s) {
                            this.addLink(s.id, s.name);
                            this.$element.val('');
                        }.bind(this)
                    });
                    this.$element.attr('autocomplete', 'espo-' + this.name);

                    this.once('render', function () {
                        $element.autocomplete('dispose');
                    }, this);

                    this.once('remove', function () {
                        $element.autocomplete('dispose');
                    }, this);
                }

                $element.on('change', function () {
                    $element.val('');
                });

                this.renderLinks();

                if (this.mode == 'edit') {
                    if (this.sortable) {
                        this.$el.find('.link-container').sortable({
                            stop: function () {
                                this.fetchFromDom();
                                this.trigger('change');
                            }.bind(this)
                        });
                    }
                }

                if (this.mode == 'search') {
                    var type = this.$el.find('select.search-type').val();
                    this.handleSearchType(type);
                }
            }
        },

        renderLinks: function () {
            this.ids.forEach(function (id) {
                this.addLinkHtml(id, this.nameHash[id]);
            }, this);
        },

        deleteLink: function (id) {
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

            var $container = this.$el.find('.link-container');
            var $el = $('<div />').addClass('link-' + id).addClass('list-group-item').attr('data-id', id);
            $el.html(this.getHelper().escapeString(name || id) + '&nbsp');
            $el.prepend('<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink"><span class="fas fa-times"></a>');
            $container.append($el);

            return $el;
        },

        getIconHtml: function (id) {
            return this.iconHtml;
        },

        getDetailLinkHtml: function (id) {
            var name = this.nameHash[id] || id;
            if (!name && id) {
                name = this.translate(this.foreignScope, 'scopeNames');
            }
            var iconHtml = '';
            if (this.mode == 'detail') {
                iconHtml = this.getIconHtml(id);
            }
            return '<a href="#' + this.foreignScope + '/view/' + id + '">' + iconHtml + this.getHelper().escapeString(name) + '</a>';
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                this.ids.forEach(function (id) {
                    names.push(this.getDetailLinkHtml(id));
                }, this);
                if (names.length) {
                    return '<div>' + names.join('</div><div>') + '</div>';
                }
                return;
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                var idList = this.model.get(this.idsName) || [];
                if (idList.length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
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
            this.$el.find('.link-container').children().each(function(i, li) {
                var id = $(li).attr('data-id');
                if (!id) return;
                this.ids.push(id);
            }.bind(this));
        },

        fetchSearch: function () {
            var type = this.$el.find('select.search-type').val();

            if (type === 'anyOf') {
                var idList = this.ids || [];

                var data = {
                    type: 'linkedWith',
                    value: idList,
                    nameHash: this.nameHash,
                    data: {
                        type: type
                    }
                };
                if (!idList.length) {
                    data.value = null;
                }
                return data;
            } else if (type === 'noneOf') {
                var values = this.ids || [];

                var data = {
                    type: 'notLinkedWith',
                    value: this.ids || [],
                    nameHash: this.nameHash,
                    data: {
                        type: type
                    }
                };
                return data;
            } else if (type === 'isEmpty') {
                var data = {
                    type: 'isNotLinked',
                    data: {
                        type: type
                    }
                };
                return data;
            } else if (type === 'isNotEmpty') {
                var data = {
                    type: 'isLinked',
                    data: {
                        type: type
                    }
                };
                return data;
            }
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.typeFront || this.searchParams.type || 'anyOf';
        }

    });
});


