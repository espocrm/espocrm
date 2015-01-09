/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

Espo.define('Views.List', ['Views.Main', 'SearchManager'], function (Dep, SearchManager) {

    return Dep.extend({

        template: 'list',

        el: '#main',

        scope: null,

        name: 'List',

        views: {
            header: {
                selector: '> .page-header',
                view: 'Header'
            }
        },

        searchPanel: true,

        searchManager: true,

        createButton: true,

        setup: function () {
            this.setupSearchManager();
            this.setupSorting();

            if (this.getMetadata().get('clientDefs.' + this.scope + '.disableSearchPanel')) {
                this.searchPanel = false;
            }

            if (this.searchPanel) {
                this.createView('search', 'Record.Search', {
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
            }

            if (this.createButton) {
                this.menu.buttons.unshift({
                    link: '#' + this.scope + '/create',
                    label: 'Create ' +  this.scope,
                    style: 'primary',
                    acl: 'edit'
                });
            }
        },

        getSearchDefaultData: function () {
            return null;
        },

        setupSearchManager: function () {
            var collection = this.collection;

            var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime(), this.getSearchDefaultData());
            searchManager.loadStored();
            collection.where = searchManager.getWhere();
            collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

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

        afterRender: function () {
            this.notify('Loading...');

            var listViewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || 'Record.List';

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', listViewName, {
                    collection: this.collection,
                    el: this.options.el + ' > .list-container',
                }, function (view) {
                    view.render();
                    view.notify(false);

                    if (this.searchPanel) {
                        this.listenTo(this.getView('list'), 'sort', function (obj) {
                            this.getStorage().set('listSorting', this.collection.name, obj);
                        }, this);
                    }

                }.bind(this));
            }, this);
            this.collection.fetch();
        },

        getHeader: function () {
            return this.getLanguage().translate(this.collection.name, 'scopeNamesPlural');
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate(this.collection.name, 'scopeNamesPlural'));
        },
    });
});

