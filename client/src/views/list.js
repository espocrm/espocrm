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

/** @module module:views/list */

import MainView from 'views/main';
import SearchManager from 'search-manager';

/**
 * A list view.
 */
class ListView extends MainView {

    /** @inheritDoc */
    template = 'list'

    /** @inheritDoc */
    name = 'List'

    /** @inheritDoc */
    optionsToPass = []

    /**
     * A header view name.
     *
     * @type {string}
     * @protected
     */
    headerView = 'views/header'

    /**
     * A search view name.
     *
     * @type {string}
     * @protected
     */
    searchView = 'views/record/search'

    /**
     * A record/list view name.
     *
     * @type {string}
     * @protected
     */
    recordView = 'views/record/list'

    /**
     * A record/kanban view name.
     *
     * @type {string}
     * @protected
     */
    recordKanbanView = 'views/record/kanban'

    /**
     * Has a search panel.
     *
     * @type {boolean}
     * @protected
     */
    searchPanel = true

    /**
     * @type {module:search-manager}
     * @protected
     */
    searchManager = null

    /**
     * Has a create button.
     *
     * @type {boolean}
     * @protected
     */
    createButton = true

    /**
     * To use a modal dialog when creating a record.
     *
     * @type {boolean}
     * @protected
     */
    quickCreate = false

    /**
     * After create a view will be stored, so it can be re-used after.
     * Useful to avoid re-rendering when come back the list view.
     *
     * @type {boolean}
     */
    storeViewAfterCreate = false

    /**
     * After update a view will be stored, so it can be re-used after.
     * Useful to avoid re-rendering when come back the list view.
     *
     * @type {boolean}
     */
    storeViewAfterUpdate = true

    /**
     * Use a current URL as a root URL when open a record. To be able to return to the same URL.
     */
    keepCurrentRootUrl = false

    /**
     * A view mode. 'list', 'kanban'.
     *
     * @type {string}
     */
    viewMode = ''

    /**
     * An available view mode list.
     *
     * @type {string[]|null}
     */
    viewModeList = null

    /**
     * A default view mode.
     *
     * @type {string}
     */
    defaultViewMode = 'list'

    /** @const */
    MODE_LIST = 'list'
    /** @const */
    MODE_KANBAN = 'kanban'

    /** @inheritDoc */
    shortcutKeys = {
        /** @this ListView */
        'Control+Space': function (e) {
            this.handleShortcutKeyCtrlSpace(e);
        },
        /** @this ListView */
        'Control+Slash': function (e) {
            this.handleShortcutKeyCtrlSlash(e);
        },
        /** @this ListView */
        'Control+Comma': function (e) {
            this.handleShortcutKeyCtrlComma(e);
        },
        /** @this ListView */
        'Control+Period': function (e) {
            this.handleShortcutKeyCtrlPeriod(e);
        },
    }

    /** @inheritDoc */
    setup() {
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

        this.entityType = this.collection.entityType;

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
    }

    setupFinal() {
        super.setupFinal();

        this.wait(
            this.getHelper().processSetupHandlers(this, 'list')
        );
    }

    /**
     * Set up modes.
     */
    setupModes() {
        this.defaultViewMode = this.options.defaultViewMode ||
            this.getMetadata().get(['clientDefs', this.scope, 'listDefaultViewMode']) ||
            this.defaultViewMode;

        this.viewMode = this.viewMode || this.defaultViewMode;

        const viewModeList = this.options.viewModeList ||
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
            let viewMode = null;

            const modeKey = 'listViewMode' + this.scope;

            if (this.getStorage().has('state', modeKey)) {
                const storedViewMode = this.getStorage().get('state', modeKey);

                if (storedViewMode && this.viewModeList.includes(storedViewMode)) {
                    viewMode = storedViewMode;
                }
            }

            if (!viewMode) {
                viewMode = this.defaultViewMode;
            }

            this.viewMode = /** @type {string} */viewMode;
        }
    }

    /**
     * Set up a header.
     */
    setupHeader() {
        this.createView('header', this.headerView, {
            collection: this.collection,
            fullSelector: '#main > .page-header',
            scope: this.scope,
            isXsSingleRow: true,
        });
    }

    /**
     * Set up a create button.
     */
    setupCreateButton() {
        if (this.quickCreate) {
            this.menu.buttons.unshift({
                action: 'quickCreate',
                iconHtml: '<span class="fas fa-plus fa-sm"></span>',
                text: this.translate('Create ' +  this.scope, 'labels', this.scope),
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
            iconHtml: '<span class="fas fa-plus fa-sm"></span>',
            text: this.translate('Create ' +  this.scope,  'labels', this.scope),
            style: 'default',
            acl: 'create',
            aclScope: this.entityType || this.scope,
            title: 'Ctrl+Space',
        });
    }

    /**
     * Set up a search panel.
     *
     * @protected
     */
    setupSearchPanel() {
        this.createSearchView();
    }

    /**
     * Create a search view.
     *
     * @return {Promise<module:view>}
     * @protected
     */
    createSearchView() {
        return this.createView('search', this.searchView, {
            collection: this.collection,
            fullSelector: '#main > .search-container',
            searchManager: this.searchManager,
            scope: this.scope,
            viewMode: this.viewMode,
            viewModeList: this.viewModeList,
            isWide: true,
        }, view => {
            this.listenTo(view, 'reset', () => this.resetSorting());

            if (this.viewModeList.length > 1) {
                this.listenTo(view, 'change-view-mode', mode => this.switchViewMode(mode));
            }
        });
    }

    /**
     * Switch a view mode.
     *
     * @param {string} mode
     */
    switchViewMode(mode) {
        this.clearView('list');
        this.collection.isFetched = false;
        this.collection.reset();
        this.applyStoredSorting();
        this.setViewMode(mode, true);
        this.loadList();
    }

    /**
     * Set a view mode.
     *
     * @param {string} mode A mode.
     * @param {boolean} [toStore=false] To preserve a mode being set.
     */
    setViewMode(mode, toStore) {
        this.viewMode = mode;

        this.collection.url = this.collectionUrl;
        this.collection.maxSize = this.collectionMaxSize;

        if (toStore) {
            const modeKey = 'listViewMode' + this.scope;

            this.getStorage().set('state', modeKey, mode);
        }

        if (this.searchView && this.getView('search')) {
            this.getSearchView().setViewMode(mode);
        }

        if (this.viewMode === this.MODE_KANBAN) {
            this.setViewModeKanban();

            return;
        }

        const methodName = 'setViewMode' + Espo.Utils.upperCaseFirst(this.viewMode);

        if (this[methodName]) {
            this[methodName]();
        }
    }

    /**
     * Called when the kanban mode is set.
     */
    setViewModeKanban() {
        this.collection.url = 'Kanban/' + this.scope;
        this.collection.maxSize = this.getConfig().get('recordsPerPageKanban');
        this.collection.resetOrderToDefault();
    }

    /**
     * Reset sorting in a storage.
     */
    resetSorting() {
        this.getStorage().clear('listSorting', this.collection.entityType);
    }

    /**
     * Get default search data.
     *
     * @returns {Object}
     */
    getSearchDefaultData() {
        return this.getMetadata().get('clientDefs.' + this.scope + '.defaultFilterData');
    }

    /**
     * Set up a search manager.
     */
    setupSearchManager() {
        const collection = this.collection;

        const searchManager = new SearchManager(
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
    }

    /**
     * Set up sorting.
     */
    setupSorting() {
        if (!this.searchPanel) {
            return;
        }

        this.applyStoredSorting();
    }

    /**
     * Apply stored sorting.
     */
    applyStoredSorting() {
        const sortingParams = this.getStorage().get('listSorting', this.collection.entityType) || {};

        if ('orderBy' in sortingParams) {
            this.collection.orderBy = sortingParams.orderBy;
        }

        if ('order' in sortingParams) {
            this.collection.order = sortingParams.order;
        }
    }

    /**
     * @protected
     * @return {module:views/record/search}
     */
    getSearchView() {
        return this.getView('search');
    }

    /**
     * @protected
     * @return {module:view}
     */
    getRecordView() {
        return this.getView('list');
    }

    /**
     * Get a record view name.
     *
     * @returns {string}
     */
    getRecordViewName() {
        let viewName = this.getMetadata().get(['clientDefs', this.scope, 'recordViews', this.viewMode]);

        if (viewName) {
            return viewName;
        }

        if (this.viewMode === this.MODE_LIST) {
            return this.recordView;
        }

        if (this.viewMode === this.MODE_KANBAN) {
            return this.recordKanbanView;
        }

        const propertyName = 'record' + Espo.Utils.upperCaseFirst(this.viewMode) + 'View';

        viewName = this[propertyName];

        if (!viewName) {
            throw new Error("No record view.");
        }

        return viewName;
    }

    /** @inheritDoc */
    cancelRender() {
        if (this.hasView('list')) {
            this.getRecordView();

            if (this.getRecordView().isBeingRendered()) {
                this.getRecordView().cancelRender();
            }
        }

        super.cancelRender();
    }

    /**
     * @inheritDoc
     */
    afterRender() {
        Espo.Ui.notify(false);

        if (!this.hasView('list')) {
            this.loadList();
        }

        // noinspection JSUnresolvedReference
        this.$el.get(0).focus({preventScroll: true});
    }

    /**
     * Load a record list view.
     */
    loadList() {
        if ('isFetched' in this.collection && this.collection.isFetched) {
            this.createListRecordView(false);

            return;
        }

        Espo.Ui.notify(' ... ');

        this.createListRecordView(true);
    }

    /**
     * Prepare record view options. Options can be modified in an extended method.
     *
     * @param {Object} options Options
     */
    prepareRecordViewOptions(options) {}

    /**
     * Create a record list view.
     *
     * @param {boolean} [fetch=false] To fetch after creation.
     * @return {Promise<module:views/record/list>}
     */
    createListRecordView(fetch) {
        const o = {
            collection: this.collection,
            selector: '.list-container',
            scope: this.scope,
            skipBuildRows: true,
            shortcutKeysEnabled: true,
            forceDisplayTopBar: true,
            additionalRowActionList: this.getMetadata().get(`clientDefs.${this.scope}.rowActionList`),
            settingsEnabled: true,
        };

        if (this.getHelper().isXsScreen()) {
            o.type = 'listSmall';
        }

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
            // @todo Remove in v8.1.
            console.warn(`'listPagination' parameter is deprecated and will be removed in the future.`);

            o.pagination = true;
        }

        this.prepareRecordViewOptions(o);

        const listViewName = this.getRecordViewName();

        return this.createView('list', listViewName, o, view => {
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
                    this.getStorage().set('listSorting', this.collection.entityType, obj);
                });
            }

            if (!fetch) {
                view.render();

                return;
            }

            view.getSelectAttributeList(selectAttributeList => {
                if (this.options.mediator && this.options.mediator.abort) {
                    return;
                }

                if (selectAttributeList) {
                    this.collection.data.select = selectAttributeList.join(',');
                }

                Espo.Ui.notify(' ... ');

                this.collection.fetch({main: true})
                    .then(() => Espo.Ui.notify(false));
            });
        });
    }

    /**
     * @inheritDoc
     */
    getHeader() {
        const $root = $('<span>')
            .text(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));

        if (this.options.params && this.options.params.fromAdmin) {
            const $root = $('<a>')
                .attr('href', '#Admin')
                .text(this.translate('Administration', 'labels', 'Admin'));

            const $scope = $('<span>')
                .text(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));

            return this.buildHeaderHtml([$root, $scope]);
        }

        const headerIconHtml = this.getHeaderIconHtml();

        if (headerIconHtml) {
            $root.prepend(headerIconHtml);
        }

        return this.buildHeaderHtml([$root]);
    }

    /**
     * @inheritDoc
     */
    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));
    }

    /**
     * Create attributes for an entity being created.
     *
     * @return {Object}
     */
    getCreateAttributes() {}

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
    prepareCreateReturnDispatchParams(params) {}

    /**
     * Action `quickCreate`.
     *
     * @param {Object.<string,*>} [data]
     * @returns {Promise<module:views/modals/edit>}
     */
    actionQuickCreate(data) {
        data = data || {};

        const attributes = this.getCreateAttributes() || {};

        Espo.Ui.notify(' ... ');

        const viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') ||
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

        const returnDispatchParams = {
            controller: this.scope,
            action: null,
            options: {isReturn: true},
        };

        this.prepareCreateReturnDispatchParams(returnDispatchParams);

        options = {
            ...options,
            returnUrl: this.getRouter().getCurrentUrl(),
            returnDispatchParams: returnDispatchParams,
        };

        return this.createView('quickCreate', viewName, options, (view) => {
            view.render();
            view.notify(false);

            this.listenToOnce(view, 'after:save', () => {
                this.collection.fetch();
            });
        });
    }

    /**
     * Action 'create'.
     *
     * @param {Object.<string,*>} [data]
     */
    actionCreate(data) {
        data = data || {};

        const router = this.getRouter();

        const url = '#' + this.scope + '/create';
        const attributes = this.getCreateAttributes() || {};

        let options = {attributes: attributes};

        if (this.keepCurrentRootUrl) {
            options.rootUrl = this.getRouter().getCurrentUrl();
        }

        if (data.focusForCreate) {
            options.focusForCreate = true;
        }

        const returnDispatchParams = {
            controller: this.scope,
            action: null,
            options: {isReturn: true},
        };

        this.prepareCreateReturnDispatchParams(returnDispatchParams);

        options = {
            ...options,
            returnUrl: this.getRouter().getCurrentUrl(),
            returnDispatchParams: returnDispatchParams,
        };

        router.navigate(url, {trigger: false});
        router.dispatch(this.scope, 'create', options);
    }

    /**
     * Whether the view is actual to be reused.
     *
     * @returns {boolean}
     */
    isActualForReuse() {
        return 'isFetched' in this.collection && this.collection.isFetched;
    }

    /**
     * @protected
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyCtrlSpace(e) {
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
    }

    /**
     * @protected
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyCtrlSlash(e) {
        if (!this.searchPanel) {
            return;
        }

        const $search = this.$el.find('input.text-filter').first();

        if (!$search.length) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        $search.focus();
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * @protected
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyCtrlComma(e) {
        if (!this.getSearchView()) {
            return;
        }

        this.getSearchView().selectPreviousPreset();
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * @protected
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyCtrlPeriod(e) {
        if (!this.getSearchView()) {
            return;
        }

        this.getSearchView().selectNextPreset();
    }
}

export default ListView;
