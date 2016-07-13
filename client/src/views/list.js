/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('views/list', ['views/main', 'search-manager'], function (Dep, SearchManager) {

    return Dep.extend({

        template: 'list',

        scope: null,

        name: 'List',

        headerView: 'views/header',

        searchView: 'views/record/search',

        recordView: 'views/record/list',

        searchPanel: true,

        searchManager: null,

        createButton: true,

        quickCreate: false,

        optionsToPass: [],

        setup: function () {
            this.collection.maxSize = this.getConfig().get('recordsPerPage') || this.collection.maxSize;

            if (this.getMetadata().get('clientDefs.' + this.scope + '.searchPanelDisabled')) {
                this.searchPanel = false;
            }

            this.headerView = this.options.headerView || this.headerView;
            this.recordView = this.options.recordView || this.recordView;
            this.searchView = this.options.searchView || this.searchView;

            this.setupHeader();

            if (this.searchPanel) {
                this.setupSearchManager();
            }

            this.setupSorting();

            if (this.searchPanel) {
                this.setupSearchPanel();
            }

            if (this.createButton) {
                this.setupCreateButton();
            }
        },

        setupHeader: function () {
            this.createView('header', this.headerView, {
                collection: this.collection,
                el: '#main > .page-header'
            });
        },

        setupCreateButton: function () {
            if (this.quickCreate) {
                this.menu.buttons.unshift({
                    action: 'quickCreate',
                    label: 'Create ' + this.scope,
                    style: 'primary',
                    acl: 'create'
                });
            } else {
                this.menu.buttons.unshift({
                    link: '#' + this.scope + '/create',
                    action: 'create',
                    label: 'Create ' +  this.scope,
                    style: 'primary',
                    acl: 'create'
                });
            }
        },

        setupSearchPanel: function () {
            this.createView('search', this.searchView, {
                collection: this.collection,
                el: '#main > .search-container',
                searchManager: this.searchManager,
            }, function (view) {
                this.listenTo(view, 'reset', function () {
                    this.collection.sortBy = this.defaultSortBy;
                    this.collection.asc = this.defaultAsc;
                    this.getStorage().clear('listSorting', this.collection.name)
                }, this);
            }.bind(this));
        },

        getSearchDefaultData: function () {
            return this.getMetadata().get('clientDefs.' + this.scope + '.defaultFilterData');
        },

        setupSearchManager: function () {
            var collection = this.collection;

            var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime(), this.getSearchDefaultData());

            searchManager.loadStored();
            collection.where = searchManager.getWhere();
            this.searchManager = searchManager;
        },

        setupSorting: function () {
            if (!this.searchPanel) return;

            var collection = this.collection;

            var sortingParams = this.getStorage().get('listSorting', collection.name) || {};

            this.defaultSortBy = collection.sortBy;
            this.defaultAsc = collection.asc;

            if ('sortBy' in sortingParams) {
                collection.sortBy = sortingParams.sortBy;
            }
            if ('asc' in sortingParams) {
                collection.asc = sortingParams.asc;
            }
        },

        getRecordViewName: function () {
            return this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || this.recordView;
        },

        afterRender: function () {
            if (!this.hasView('list')) {
                this.loadList();
            }
        },

        loadList: function () {
            this.notify('Loading...');
            if (this.collection.isFetched) {
                this.createListRecordView(false);
            } else {
                this.listenToOnce(this.collection, 'sync', function () {
                    this.createListRecordView();
                }, this);
                this.collection.fetch();
            }
        },

        createListRecordView: function (fetch) {
            var o = {
                collection: this.collection,
                el: this.options.el + ' .list-container'
            };
            this.optionsToPass.forEach(function (option) {
                o[option] = this.options[option];
            }, this);
            var listViewName = this.getRecordViewName();

            this.createView('list', listViewName, o, function (view) {
                if (!this.hasParentView()) return;

                view.render();
                view.notify(false);
                if (this.searchPanel) {
                    this.listenTo(view, 'sort', function (obj) {
                        this.getStorage().set('listSorting', this.collection.name, obj);
                    }, this);
                }
                if (fetch) {
                    setTimeout(function () {
                        this.collection.fetch();
                    }.bind(this), 2000);
                }
            });
        },

        getHeader: function () {
            return this.buildHeaderHtml([
                this.getLanguage().translate(this.collection.name, 'scopeNamesPlural')
            ]);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate(this.collection.name, 'scopeNamesPlural'));
        },

        getCreateAttributes: function () {},

        actionQuickCreate: function () {
            var attributes = this.getCreateAttributes() || {};

            this.notify('Loading...');
            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') || 'views/modals/edit';
            this.createView('quickCreate', 'views/modals/edit', {
                scope: this.scope,
                attributes: attributes
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();
                }, this);
            }.bind(this));
        },

        actionCreate: function () {
            var router = this.getRouter();

            var url = '#' + this.scope + '/create';
            var attributes = this.getCreateAttributes() || {};

            router.dispatch(this.scope, 'create', {
                attributes: attributes
            });
            router.navigate(url, {trigger: false});
        }

    });
});

