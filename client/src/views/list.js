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

define('views/list', ['views/main', 'search-manager'], function (Dep, SearchManager) {

    return Dep.extend({

        template: 'list',

        scope: null,

        name: 'List',

        headerView: 'views/header',

        searchView: 'views/record/search',

        recordView: 'views/record/list',

        recordKanbanView: 'views/record/kanban',

        searchPanel: true,

        searchManager: null,

        createButton: true,

        quickCreate: false,

        optionsToPass: [],

        storeViewAfterCreate: false,

        storeViewAfterUpdate: true,

        keepCurrentRootUrl: false,

        viewMode: null,

        viewModeList: null,

        defaultViewMode: 'list',

        setup: function () {
            this.collection.maxSize = this.getConfig().get('recordsPerPage') || this.collection.maxSize;

            this.collectionUrl = this.collection.url;
            this.collectionMaxSize = this.collection.maxSize;

            this.setupModes();

            this.setViewMode(this.viewMode);

            if (this.getMetadata().get(['clientDefs', this.scope, 'searchPanelDisabled'])) {
                this.searchPanel = false;
            }

            if (this.getUser().isPortal()) {
                if (this.getMetadata().get(['clientDefs', this.scope, 'searchPanelInPortalDisabled'])) {
                    this.searchPanel = false;
                }
            }

            if (this.getMetadata().get(['clientDefs', this.scope, 'createDisabled'])) {
                this.createButton = false;
            }

            this.entityType = this.collection.name;

            this.headerView = this.options.headerView || this.headerView;
            this.recordView = this.options.recordView || this.recordView;
            this.searchView = this.options.searchView || this.searchView;

            this.setupHeader();

            this.collection.orderBy = this.defaultOrderBy || this.collection.orderBy;
            this.collection.order = this.defaultOrder || this.collection.order;

            if (this.searchPanel) {
                this.setupSearchManager();
            }

            this.defaultOrderBy = this.collection.orderBy;
            this.defaultOrder = this.collection.order;

            this.setupSorting();

            if (this.searchPanel) {
                this.setupSearchPanel();
            }

            if (this.createButton) {
                this.setupCreateButton();
            }
        },

        setupModes: function () {
            this.defaultViewMode = this.options.defaultViewMode ||
                this.getMetadata().get(['clientDefs', this.scope, 'listDefaultViewMode']) ||
                this.defaultViewMode;

            this.viewMode = this.viewMode || this.defaultViewMode;

            var viewModeList = this.options.viewModeList ||
                this.viewModeList ||
                this.getMetadata().get(['clientDefs', this.scope, 'listViewModeList']);

            if (viewModeList) {
                this.viewModeList = viewModeList;
            } else {
                this.viewModeList = ['list'];
                if (this.getMetadata().get(['clientDefs', this.scope, 'kanbanViewMode'])) {
                    if (!~this.viewModeList.indexOf('kanban')) {
                        this.viewModeList.push('kanban');
                    }
                }
            }

            if (this.viewModeList.length > 1) {
                this.viewMode = null;
                var modeKey = 'listViewMode' + this.scope;
                if (this.getStorage().has('state', modeKey)) {
                    var storedViewMode = this.getStorage().get('state', modeKey);
                    if (storedViewMode) {
                        if (~this.viewModeList.indexOf(storedViewMode)) {
                            this.viewMode = storedViewMode;
                        }
                    }
                }
                if (!this.viewMode) {
                    this.viewMode = this.defaultViewMode;
                }
            }
        },

        setupHeader: function () {
            this.createView('header', this.headerView, {
                collection: this.collection,
                el: '#main > .page-header',
                scope: this.scope,
                isXsSingleRow: true
            });
        },

        setupCreateButton: function () {
            if (this.quickCreate) {
                this.menu.buttons.unshift({
                    action: 'quickCreate',
                    html: '<span class="fas fa-plus fa-sm"></span> ' + this.translate('Create ' +  this.scope, 'labels', this.scope),
                    style: 'default',
                    acl: 'create',
                    aclScope: this.entityType || this.scope
                });
            } else {
                this.menu.buttons.unshift({
                    link: '#' + this.scope + '/create',
                    action: 'create',
                    html: '<span class="fas fa-plus fa-sm"></span> ' + this.translate('Create ' +  this.scope,  'labels', this.scope),
                    style: 'default',
                    acl: 'create',
                    aclScope: this.entityType || this.scope
                });
            }
        },

        setupSearchPanel: function () {
            this.createView('search', this.searchView, {
                collection: this.collection,
                el: '#main > .search-container',
                searchManager: this.searchManager,
                scope: this.scope,
                viewMode: this.viewMode,
                viewModeList: this.viewModeList,
                isWide: true,
            }, function (view) {
                this.listenTo(view, 'reset', function () {
                    this.resetSorting();
                }, this);

                if (this.viewModeList.length > 1) {
                    this.listenTo(view, 'change-view-mode', this.switchViewMode, this);
                }
            });
        },

        switchViewMode: function (mode) {
            this.clearView('list');
            this.collection.isFetched = false;
            this.collection.reset();
            this.applyStoredSorting();
            this.setViewMode(mode, true);
            this.loadList();
        },

        setViewMode: function (mode, toStore) {
            this.viewMode = mode;

            this.collection.url = this.collectionUrl;
            this.collection.maxSize = this.collectionMaxSize;

            if (toStore) {
                var modeKey = 'listViewMode' + this.scope;
                this.getStorage().set('state', modeKey, mode);
            }

            if (this.searchView && this.getView('search')) {
                this.getView('search').setViewMode(mode);
            }

            var methodName = 'setViewMode' + Espo.Utils.upperCaseFirst(this.viewMode);
            if (this[methodName]) {
                this[methodName]();
                return;
            }
        },

        setViewModeKanban: function () {
            this.collection.url = this.scope + '/action/listKanban';
            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall');

            this.collection.orderBy = this.collection.defaultOrderBy;
            this.collection.order = this.collection.defaultOrder;
        },

        resetSorting: function () {
            this.collection.orderBy = this.defaultOrderBy;
            this.collection.order = this.defaultOrder;
            this.getStorage().clear('listSorting', this.collection.name);
        },

        getSearchDefaultData: function () {
            return this.getMetadata().get('clientDefs.' + this.scope + '.defaultFilterData');
        },

        setupSearchManager: function () {
            var collection = this.collection;

            var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime(), this.getSearchDefaultData());
            searchManager.scope = this.scope;

            searchManager.loadStored();
            collection.where = searchManager.getWhere();
            this.searchManager = searchManager;
        },

        setupSorting: function () {
            if (!this.searchPanel) return;

            this.applyStoredSorting();
        },

        applyStoredSorting: function () {
            var sortingParams = this.getStorage().get('listSorting', this.collection.entityType) || {};

           if ('orderBy' in sortingParams) {
                this.collection.orderBy = sortingParams.orderBy;
            }
            if ('order' in sortingParams) {
                this.collection.order = sortingParams.order;
            }
        },

        getRecordViewName: function () {
            if (this.viewMode === 'list') {
                return this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'list']) || this.recordView;
            }

            var propertyName = 'record' + Espo.Utils.upperCaseFirst(this.viewMode) + 'View';
            return this.getMetadata().get(['clientDefs', this.scope, 'recordViews', this.viewMode]) || this[propertyName];
        },

        afterRender: function () {
            if (!this.hasView('list')) {
                this.loadList();
            }
        },

        loadList: function () {
            var methodName = 'loadList' + Espo.Utils.upperCaseFirst(this.viewMode);
            if (this[methodName]) {
                this[methodName]();
                return;
            }

            if (this.collection.isFetched) {
                this.createListRecordView(false);
            } else {
                Espo.Ui.notify(this.translate('loading', 'messages'));
                this.createListRecordView(true);
            }
        },

        prepareRecordViewOptions: function (options) {},

        createListRecordView: function (fetch) {
            var o = {
                collection: this.collection,
                el: this.options.el + ' .list-container',
                scope: this.scope,
                skipBuildRows: true
            };
            this.optionsToPass.forEach(function (option) {
                o[option] = this.options[option];
            }, this);
            if (this.keepCurrentRootUrl) {
                o.keepCurrentRootUrl = true;
            }
            this.prepareRecordViewOptions(o);
            var listViewName = this.getRecordViewName();
            this.createView('list', listViewName, o, function (view) {
                if (!this.hasParentView()) {
                    view.undelegateEvents();
                    return;
                }

                this.listenToOnce(view, 'after:render', function () {
                    if (!this.hasParentView()) {
                        view.undelegateEvents();
                        this.clearView('list');
                    }
                }, this);

                view.notify(false);
                if (this.searchPanel) {
                    this.listenTo(view, 'sort', function (obj) {
                        this.getStorage().set('listSorting', this.collection.name, obj);
                    }, this);
                }

                if (fetch) {
                    view.getSelectAttributeList(function (selectAttributeList) {
                        if (selectAttributeList) {
                            this.collection.data.select = selectAttributeList.join(',');
                        }
                        this.collection.fetch();
                    }.bind(this));
                } else {
                    view.render();
                }
            });
        },

        getHeader: function () {
            var headerIconHtml = this.getHeaderIconHtml();

            return this.buildHeaderHtml([
                headerIconHtml + this.getLanguage().translate(this.scope, 'scopeNamesPlural')
            ]);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));
        },

        getCreateAttributes: function () {},

        prepareCreateReturnDispatchParams: function (params) {},

        actionQuickCreate: function () {
            var attributes = this.getCreateAttributes() || {};

            this.notify('Loading...');
            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') || 'views/modals/edit';
            var options = {
                scope: this.scope,
                attributes: attributes
            };
            if (this.keepCurrentRootUrl) {
                options.rootUrl = this.getRouter().getCurrentUrl();
            }

            var returnDispatchParams = {
                controller: this.scope,
                action: null,
                options: {
                    isReturn: true
                }
            };
            this.prepareCreateReturnDispatchParams(returnDispatchParams);
            _.extend(options, {
                returnUrl: this.getRouter().getCurrentUrl(),
                returnDispatchParams: returnDispatchParams
            });

            this.createView('quickCreate', 'views/modals/edit', options, function (view) {
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

            var options = {
                attributes: attributes
            };
            if (this.keepCurrentRootUrl) {
                options.rootUrl = this.getRouter().getCurrentUrl();
            }

            var returnDispatchParams = {
                controller: this.scope,
                action: null,
                options: {
                    isReturn: true
                }
            };
            this.prepareCreateReturnDispatchParams(returnDispatchParams);
            _.extend(options, {
                returnUrl: this.getRouter().getCurrentUrl(),
                returnDispatchParams: returnDispatchParams
            });

            router.navigate(url, {trigger: false});
            router.dispatch(this.scope, 'create', options);
        },

        isActualForReuse: function () {
            return this.collection.isFetched;
        }

    });
});
