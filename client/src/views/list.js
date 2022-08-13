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

define('views/list', ['views/main', 'search-manager'],
function (Dep, /** typeof module:search-manager.Class */SearchManager) {

    /**
     * A list view page.
     *
     * @class
     * @name Class
     * @extends module:views/main.Class
     * @memberOf module:views/list
     */
    return Dep.extend(/** @lends module:views/list.Class# */{

        /**
         * @inheritDoc
         */
        template: 'list',

        /**
         * @inheritDoc
         */
        scope: null,

        /**
         * @inheritDoc
         */
        name: 'List',

        /**
         * A header view name.
         *
         * @type {string}
         */
        headerView: 'views/header',

        /**
         * A search view name.
         *
         * @type {string}
         */
        searchView: 'views/record/search',

        /**
         * A record/list view name.
         *
         * @type {string}
         */
        recordView: 'views/record/list',

        /**
         * A record/kanban view name.
         *
         * @type {string}
         */
        recordKanbanView: 'views/record/kanban',

        /**
         * Has a search panel.
         *
         * @type {boolean}
         */
        searchPanel: true,

        /**
         * @type {module:search-manager.Class}
         */
        searchManager: null,

        /**
         * Has a create button.
         *
         * @type {boolean}
         */
        createButton: true,

        /**
         * To use a modal dialog when creating a record.
         *
         * @type {boolean}
         */
        quickCreate: false,

        /**
         * @inheritDoc
         */
        optionsToPass: [],

        /**
         * After create a view will be stored, so it can be re-used after.
         * Useful to avoid re-rendering when come back the list view.
         *
         * @type {boolean}
         */
        storeViewAfterCreate: false,

        /**
         * After update a view will be stored, so it can be re-used after.
         * Useful to avoid re-rendering when come back the list view.
         *
         * @type {boolean}
         */
        storeViewAfterUpdate: true,

        /**
         * Use a current URL as a root URL when open a record. To be able to return to the same URL.
         */
        keepCurrentRootUrl: false,

        /**
         * A view mode. 'list', 'kanban`.
         *
         * @type {string}
         */
        viewMode: null,

        /**
         * An available view mode list.
         *
         * @type {string[]}
         */
        viewModeList: null,

        /**
         * A default view mode.
         *
         * @type {string}
         */
        defaultViewMode: 'list',

        /**
         * @const
         */
        MODE_LIST: 'list',

        /**
         * @const
         */
        MODE_KANBAN: 'kanban',

        /**
         * @inheritDoc
         */
        shortcutKeys: {
            'Control+Space': function (e) {
                this.handleShortcutKeyCtrlSpace(e);
            },
            'Control+Slash': function (e) {
                this.handleShortcutKeyCtrlSlash(e);
            },
            'Control+Comma': function (e) {
                this.handleShortcutKeyCtrlComma(e);
            },
            'Control+Period': function (e) {
                this.handleShortcutKeyCtrlPeriod(e);
            },
        },

        /**
         * @inheritDoc
         */
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

            this.defaultOrderBy = this.defaultOrderBy || this.collection.orderBy;
            this.defaultOrder = this.defaultOrder || this.collection.order;

            this.collection.setOrder(this.defaultOrderBy, this.defaultOrder, true);

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

            if (this.options.params && this.options.params.fromAdmin) {
                this.keepCurrentRootUrl = true;
            }

            this.getHelper().processSetupHandlers(this, 'list');
        },

        /**
         * Set up modes.
         */
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
            }
            else {
                this.viewModeList = [this.MODE_LIST];

                if (this.getMetadata().get(['clientDefs', this.scope, 'kanbanViewMode'])) {
                    if (!~this.viewModeList.indexOf(this.MODE_KANBAN)) {
                        this.viewModeList.push(this.MODE_KANBAN);
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

        /**
         * Set up a header.
         */
        setupHeader: function () {
            this.createView('header', this.headerView, {
                collection: this.collection,
                el: '#main > .page-header',
                scope: this.scope,
                isXsSingleRow: true,
            });
        },

        /**
         * Set up a create button.
         */
        setupCreateButton: function () {
            if (this.quickCreate) {
                this.menu.buttons.unshift({
                    action: 'quickCreate',
                    html: '<span class="fas fa-plus fa-sm"></span> ' +
                        this.translate('Create ' +  this.scope, 'labels', this.scope),
                    style: 'default',
                    acl: 'create',
                    aclScope: this.entityType || this.scope,
                    title: 'Ctrl+Space',
                });

                return;
            }

            this.menu.buttons.unshift({
                link: '#' + this.scope + '/create',
                action: 'create',
                html: '<span class="fas fa-plus fa-sm"></span> ' +
                    this.translate('Create ' +  this.scope,  'labels', this.scope),
                style: 'default',
                acl: 'create',
                aclScope: this.entityType || this.scope,
                title: 'Ctrl+Space',
            });

        },

        /**
         * Set up a search panel.
         */
        setupSearchPanel: function () {
            this.createView('search', this.searchView, {
                collection: this.collection,
                el: '#main > .search-container',
                searchManager: this.searchManager,
                scope: this.scope,
                viewMode: this.viewMode,
                viewModeList: this.viewModeList,
                isWide: true,
            }, (view) => {
                this.listenTo(view, 'reset', () => {
                    this.resetSorting();
                });

                if (this.viewModeList.length > 1) {
                    this.listenTo(view, 'change-view-mode', this.switchViewMode, this);
                }
            });
        },

        /**
         * Switch a view mode.
         *
         * @param {string} mode
         */
        switchViewMode: function (mode) {
            this.clearView('list');
            this.collection.isFetched = false;
            this.collection.reset();
            this.applyStoredSorting();
            this.setViewMode(mode, true);
            this.loadList();
        },

        /**
         * Set a view mode.
         *
         * @param {string} mode A mode.
         * @param {boolean} [toStore=false] To preserve a mode being set.
         */
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
            }
        },

        /**
         * Called when the kanban mode is set.
         */
        setViewModeKanban: function () {
            this.collection.url = 'Kanban/' + this.scope;
            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall');
            this.collection.resetOrderToDefault();
        },

        /**
         * Reset sorting in a storage.
         */
        resetSorting: function () {
            this.getStorage().clear('listSorting', this.collection.name);
        },

        /**
         * Get default search data.
         *
         * @returns {Object}
         */
        getSearchDefaultData: function () {
            return this.getMetadata().get('clientDefs.' + this.scope + '.defaultFilterData');
        },

        /**
         * Set up a search manager.
         */
        setupSearchManager: function () {
            var collection = this.collection;

            var searchManager = new SearchManager(
                collection,
                'list',
                this.getStorage(),
                this.getDateTime(),
                this.getSearchDefaultData()
            );

            searchManager.scope = this.scope;
            searchManager.loadStored();

            collection.where = searchManager.getWhere();

            this.searchManager = searchManager;
        },

        /**
         * Set up sorting.
         */
        setupSorting: function () {
            if (!this.searchPanel) {
                return;
            }

            this.applyStoredSorting();
        },

        /**
         * Apply stored sorting.
         */
        applyStoredSorting: function () {
            var sortingParams = this.getStorage().get('listSorting', this.collection.entityType) || {};

            if ('orderBy' in sortingParams) {
                this.collection.orderBy = sortingParams.orderBy;
            }

            if ('order' in sortingParams) {
                this.collection.order = sortingParams.order;
            }
        },

        /**
         * @protected
         * @return {?module:views/record/search.Class}
         */
        getSearchView: function () {
            return this.getView('search');
        },

        /**
         * @protected
         * @return {?module:view}
         */
        getRecordView: function () {
            return this.getView('list');
        },

        /**
         * Get a record view name.
         *
         * @returns {string}
         */
        getRecordViewName: function () {
            if (this.viewMode === this.MODE_LIST) {
                return this.getMetadata().get(['clientDefs', this.scope, 'recordViews', this.MODE_LIST]) ||
                    this.recordView;
            }

            var propertyName = 'record' + Espo.Utils.upperCaseFirst(this.viewMode) + 'View';

            return this.getMetadata().get(['clientDefs', this.scope, 'recordViews', this.viewMode]) ||
                this[propertyName];
        },

        /**
         * @inheritDoc
         */
        afterRender: function () {
            Espo.Ui.notify(false);

            if (!this.hasView('list')) {
                this.loadList();
            }

            this.$el.get(0).focus({preventScroll: true});
        },

        /**
         * Load a record list view.
         */
        loadList: function () {
            var methodName = 'loadList' + Espo.Utils.upperCaseFirst(this.viewMode);

            if (this[methodName]) {
                this[methodName]();

                return;
            }

            if (this.collection.isFetched) {
                this.createListRecordView(false);

                return;
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));

            this.createListRecordView(true);
        },

        /**
         * Prepare record view options. Options can be modified in an extended method.
         *
         * @param {Object} options Options
         */
        prepareRecordViewOptions: function (options) {},

        /**
         * Create a record list view.
         *
         * @param {boolean} [fetch=false] To fetch after creation.
         */
        createListRecordView: function (fetch) {
            let o = {
                collection: this.collection,
                el: this.options.el + ' .list-container',
                scope: this.scope,
                skipBuildRows: true,
                shortcutKeysEnabled: true,
            };

            this.optionsToPass.forEach(option => {
                o[option] = this.options[option];
            });

            if (this.keepCurrentRootUrl) {
                o.keepCurrentRootUrl = true;
            }

            if (
                this.getConfig().get('listPagination') ||
                this.getMetadata().get(['clientDefs', this.scope, 'listPagination'])
            ) {
                o.pagination = true;
            }

            this.prepareRecordViewOptions(o);

            var listViewName = this.getRecordViewName();

            this.createView('list', listViewName, o, view =>{
                if (!this.hasParentView()) {
                    view.undelegateEvents();

                    return;
                }

                this.listenToOnce(view, 'after:render', () => {
                    if (!this.hasParentView()) {
                        view.undelegateEvents();

                        this.clearView('list');
                    }
                });

                if (!fetch) {
                    Espo.Ui.notify(false);
                }

                if (this.searchPanel) {
                    this.listenTo(view, 'sort', obj => {
                        this.getStorage().set('listSorting', this.collection.name, obj);
                    });
                }

                if (fetch) {
                    view.getSelectAttributeList(selectAttributeList => {
                        if (this.options.mediator && this.options.mediator.abort) {
                            return;
                        }

                        if (selectAttributeList) {
                            this.collection.data.select = selectAttributeList.join(',');
                        }

                        Espo.Ui.notify(this.translate('loading', 'messages'));

                        this.collection.fetch()
                            .then(() => Espo.Ui.notify(false));

                    });

                    return;
                }

                view.render();
            });
        },

        /**
         * @inheritDoc
         */
        getHeader: function () {
            if (this.options.params && this.options.params.fromAdmin) {
                let $root = $('<a>')
                    .attr('href', '#Admin')
                    .text(this.translate('Administration', 'labels', 'Admin'));

                let $scope = $('<span>')
                    .text(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));

                return this.buildHeaderHtml([$root, $scope]);
            }

            let $root = $('<span>')
                .text(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));

            let headerIconHtml = this.getHeaderIconHtml();

            if (headerIconHtml) {
                $root.prepend(headerIconHtml);
            }

            return this.buildHeaderHtml([$root]);
        },

        /**
         * @inheritDoc
         */
        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));
        },

        /**
         * Create attributes for an entity being created.
         *
         * @return {Object}
         */
        getCreateAttributes: function () {},

        /**
         * Prepare return dispatch parameters to pass to a view when creating a record.
         * To pass some data to restore when returning to the list view.
         *
         * Example:
         * ```
         * params.options.categoryId = this.currentCategoryId;
         * params.options.categoryName = this.currentCategoryName;
         * ```
         *
         * @param {Object} params Parameters to be modified.
         */
        prepareCreateReturnDispatchParams: function (params) {},

        /**
         * Action `quickCreate`.
         *
         * @param {Object.<string,*>} [data]
         * @returns {Promise<module:views/modals/edit.Class>}
         */
        actionQuickCreate: function (data) {
            data = data || {};

            let attributes = this.getCreateAttributes() || {};

            this.notify('Loading...');

            let viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') ||
                'views/modals/edit';

            let options = {
                scope: this.scope,
                attributes: attributes,
            };

            if (this.keepCurrentRootUrl) {
                options.rootUrl = this.getRouter().getCurrentUrl();
            }

            if (data.focusForCreate) {
                options.focusForCreate = true;
            }

            let returnDispatchParams = {
                controller: this.scope,
                action: null,
                options: {isReturn: true},
            };

            this.prepareCreateReturnDispatchParams(returnDispatchParams);

            _.extend(options, {
                returnUrl: this.getRouter().getCurrentUrl(),
                returnDispatchParams: returnDispatchParams,
            });

            return this.createView('quickCreate', viewName, options, (view) => {
                view.render();
                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    this.collection.fetch();
                });
            });
        },

        /**
         * Action `create'.
         *
         * @param {Object.<string,*>} [data]
         */
        actionCreate: function (data) {
            data = data || {};

            let router = this.getRouter();

            let url = '#' + this.scope + '/create';
            let attributes = this.getCreateAttributes() || {};

            let options = {attributes: attributes};

            if (this.keepCurrentRootUrl) {
                options.rootUrl = this.getRouter().getCurrentUrl();
            }

            if (data.focusForCreate) {
                options.focusForCreate = true;
            }

            let returnDispatchParams = {
                controller: this.scope,
                action: null,
                options: {isReturn: true},
            };

            this.prepareCreateReturnDispatchParams(returnDispatchParams);

            _.extend(options, {
                returnUrl: this.getRouter().getCurrentUrl(),
                returnDispatchParams: returnDispatchParams
            });

            router.navigate(url, {trigger: false});
            router.dispatch(this.scope, 'create', options);
        },

        /**
         * Whether the view is actual to be reused.
         *
         * @returns {boolean}
         */
        isActualForReuse: function () {
            return this.collection.isFetched;
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlSpace: function (e) {
            if (!this.createButton) {
                return;
            }

            /*if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
                return;
            }*/

            if (!this.getAcl().checkScope(this.scope, 'create')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            if (this.quickCreate) {
                this.actionQuickCreate({focusForCreate: true});

                return;
            }

            this.actionCreate({focusForCreate: true});
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlSlash: function (e) {
            if (!this.searchPanel) {
                return;
            }

            let $search = this.$el.find('input.text-filter').first();

            if (!$search.length) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            $search.focus();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlComma: function (e) {
            if (!this.getSearchView()) {
                return;
            }

            this.getSearchView().selectPreviousPreset();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlPeriod: function (e) {
            if (!this.getSearchView()) {
                return;
            }

            this.getSearchView().selectNextPreset();
        },
    });
});
