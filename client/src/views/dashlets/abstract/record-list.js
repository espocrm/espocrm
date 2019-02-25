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

Espo.define('views/dashlets/abstract/record-list', ['views/dashlets/abstract/base', 'search-manager'], function (Dep, SearchManager) {

    return Dep.extend({

        scope: null,

        listViewColumn: 'views/record/list',

        listViewExpanded: 'views/record/list-expanded',

        listView: null,

        _template: '<div class="list-container">{{{list}}}</div>',

        layoutType: 'expanded',

        optionsFields: _.extend(_.clone(Dep.prototype.optionsFields), {
            'displayRecords': {
                type: 'enumInt',
                options: [3,4,5,10,15],
            }
        }),

        rowActionsView: 'views/record/row-actions/view-and-edit',

        init: function () {
            this.scope = this.getMetadata().get(['dashlets', this.name, 'entityType']) || this.scope;
            Dep.prototype.init.call(this);
        },

        checkAccess: function () {
            return this.getAcl().check(this.scope, 'read');
        },

        getSearchData: function () {
            return this.getOption('searchData');
        },

        afterRender: function () {
            this.getCollectionFactory().create(this.scope, function (collection) {
                var searchData = this.getSearchData();

                var searchManager = this.searchManager = new SearchManager(collection, 'list', null, this.getDateTime(), searchData);

                if (!this.scope) {
                    this.$el.find('.list-container').html(this.translate('selectEntityType', 'messages', 'DashletOptions'));
                    return;
                }

                if (!this.checkAccess()) {
                    this.$el.find('.list-container').html(this.translate('No Access'));
                    return;
                }

                if (this.collectionUrl) {
                    collection.url = this.collectionUrl;
                }

                this.collection = collection;

                collection.orderBy = this.getOption('orderBy') || this.getOption('sortBy') || this.collection.orderBy;

                if (this.getOption('orderBy')) {
                    collection.order = 'asc';
                }

                if (this.hasOption('asc')) {
                    collection.order = this.getOption('asc') ? 'asc' : false;
                }

                if (this.getOption('sortDirection') === 'asc') {
                    collection.order = 'asc';
                } else if (this.getOption('sortDirection') === 'desc') {
                    collection.order = 'desc';
                }

                if (this.getOption('order') === 'asc') {
                    collection.order = 'asc';
                } else if (this.getOption('order') === 'desc') {
                    collection.order = 'desc';
                }

                collection.maxSize = this.getOption('displayRecords');
                collection.where = searchManager.getWhere();

                var viewName = this.listView || ((this.layoutType == 'expanded') ? this.listViewExpanded : this.listViewColumn);

                this.createView('list', viewName, {
                    collection: collection,
                    el: this.getSelector() + ' .list-container',
                    pagination: this.getOption('pagination') ? 'bottom' : false,
                    type: 'listDashlet',
                    rowActionsView: this.rowActionsView,
                    checkboxes: false,
                    showMore: true,
                    listLayout: this.getOption(this.layoutType + 'Layout'),
                    skipBuildRows: true
                }, function (view) {
                    view.getSelectAttributeList(function (selectAttributeList) {
                        if (selectAttributeList) {
                            collection.data.select = selectAttributeList.join(',');
                        }
                        collection.fetch();
                    }.bind(this));
                });

            }, this);
        },

        setupActionList: function () {
            if (this.scope && this.getAcl().checkScope(this.scope, 'create')) {
                this.actionList.unshift({
                    name: 'create',
                    html: this.translate('Create ' + this.scope, 'labels', this.scope),
                    iconHtml: '<span class="fas fa-plus"></span>',
                    url: '#'+this.scope+'/create'
                });
            }
        },

        actionRefresh: function () {
            if (!this.collection) return;

            this.collection.where = this.searchManager.getWhere();
            this.collection.fetch();
        },

        actionCreate: function () {
            var attributes = this.getCreateAttributes() || {};

            if (this.getOption('populateAssignedUser')) {
                if (this.getMetadata().get(['entityDefs', this.scope, 'fields', 'assignedUsers'])) {
                    attributes['assignedUsersIds'] = [this.getUser().id];
                    attributes['assignedUsersNames'] = {};
                    attributes['assignedUsersNames'][this.getUser().id] = this.getUser().get('name');
                } else {
                    attributes['assignedUserId'] = this.getUser().id;
                    attributes['assignedUserName'] = this.getUser().get('name');
                }
            }

            this.notify('Loading...');
            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') || 'views/modals/edit';
            this.createView('modal', viewName, {
                scope: this.scope,
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.actionRefresh();
                }, this);
            }.bind(this));
        },

        getCreateAttributes: function () {

        }
    });
});

