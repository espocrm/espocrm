/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import MainView, {MainViewOptions} from 'views/main';
import SearchManager from 'search-manager';
import RecordModal from 'helpers/record-modal';
import Utils from 'utils';
import PipelinesHelper from 'helpers/misc/pipelines';
import Collection from 'collection';
import type SearchView from 'views/record/search';
import type View from 'view';
import Ui from 'ui';
import type ListRecordView from 'views/record/list';
import EditModalView from 'views/modals/edit';

export interface ListViewSchema {
    collection: Collection;
    options: ListViewOptions;
}

export interface ListViewOptions extends MainViewOptions {
    defaultViewMode?: string;
    params?: Record<string, any> & {
        rootUrl?: string;
        primaryFilter?: string | null;
        fromAdmin?: boolean;
    };
    recordView?: string;
}

/**
 * A list view.
 */
class ListView<S extends ListViewSchema = ListViewSchema> extends MainView<S> {

    protected template = 'list'

    readonly name: string = 'List'

    protected optionsToPass: string[] = []

    /**
     * A header view name.
     */
    protected headerView: string = 'views/header'

    /**
     * A search view name.
     */
    protected searchView: string = 'views/record/search'

    /**
     * A record/list view name.
     */
    protected recordView: string = 'views/record/list'

    /**
     * A record/kanban view name.
     */
    protected recordKanbanView: string = 'views/record/kanban'

    /**
     * Has a search panel.
     */
    protected searchPanel: boolean = true

    /**
     * A search manager.
     */
    protected searchManager: SearchManager | null = null

    /**
     * Has a create button.
     */
    protected createButton: boolean = true

    /**
     * To use a modal dialog when creating a record.
     */
    protected quickCreate: boolean = false

    /**
     * After create the view will be stored, so it can be re-used after.
     * Useful to avoid re-rendering when come back to the list view.
     */
    readonly storeViewAfterCreate: boolean = false

    /**
     * After update the view will be stored, so it can be re-used after.
     * Useful to avoid re-rendering when come back to the list view.
     */
    readonly storeViewAfterUpdate: boolean = true

    /**
     * Use a current URL as a root URL when open a record. To be able to return to the same URL.
     */
    protected keepCurrentRootUrl: boolean = false

    /**
     * A view mode. 'list', 'kanban'.
     */
    protected viewMode: string

    /**
     * An available view mode list.
     */
    protected viewModeList: string[]

    /**
     * A default view mode.
     */
    protected defaultViewMode: string = 'list'

    readonly MODE_LIST = 'list'
    readonly MODE_KANBAN = 'kanban'

    protected entityType: string | null

    protected defaultOrderBy: string | null

    protected defaultOrder: 'asc' | 'desc' | boolean | null

    /**
     * Root data. To be passed to the detail record view when following to a record.
     *
     * @since 9.0.0
     */
    protected rootData: Record<string, unknown>

    private collectionUrl: string | null

    private collectionMaxSize: number

    /**
     * @internal
     */
    protected _primaryFilter: string | null;

    private _fromAdmin: boolean;

    protected shortcutKeys: (Record<string, (event: KeyboardEvent) => void>) | null = {
        'Control+Space': function (this: ListView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlSpace(e);
        },
        /** @this ListView */       'Control+Slash': function (this: ListView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlSlash(e);
        },
        'Control+Comma': function (this: ListView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlComma(e);
        },
        'Control+Period': function (this: ListView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlPeriod(e);
        },
        'Control+ArrowLeft': function (this: ListView, e: KeyboardEvent) {
            this.handleShortcutKeyControlArrowLeft(e);
        },
        'Control+ArrowRight': function (this: ListView, e: KeyboardEvent) {
            this.handleShortcutKeyControlArrowRight(e);
        },
    }

    protected setup() {
        this.collection.maxSize = this.getConfig().get('recordsPerPage') || this.collection.maxSize;

        this.collectionUrl = this.collection.url;
        this.collectionMaxSize = this.collection.maxSize;

        this.rootData = {};

        this._primaryFilter = this.options.params?.primaryFilter ?? null;
        this._fromAdmin = this.options.params?.fromAdmin ?? false;

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

        this.defaultOrderBy = this.defaultOrderBy ?? this.collection.orderBy ?? null;
        this.defaultOrder = this.defaultOrder ?? this.collection.order ?? null;

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

        if (this._fromAdmin || this._primaryFilter) {
            this.keepCurrentRootUrl = true;
        }

        this.addActionHandler('fullRefresh', () => this.actionFullRefresh());
    }

    protected setupFinal() {
        super.setupFinal();

        this.wait(
            this.getHelper().processSetupHandlers(this, 'list')
        );
    }

    /**
     * Set up modes.
     */
    protected setupModes() {
        this.defaultViewMode = this.options.defaultViewMode ??
            this.getMetadata().get(['clientDefs', this.scope, 'listDefaultViewMode']) ??
            this.defaultViewMode;

        this.viewMode = this.viewMode || this.defaultViewMode;

        const viewModeList = this.options.viewModeList ||
            this.viewModeList ||
            this.getMetadata().get(['clientDefs', this.scope, 'listViewModeList']);

        if (viewModeList) {
            this.viewModeList = viewModeList;
        } else {
            this.viewModeList = [this.MODE_LIST];

            // @todo Mode defs in metadata. Availability is checked in a handler class.

            if (
                this.getMetadata().get(`clientDefs.${this.scope}.kanbanViewMode`) &&
                !this.viewModeList.includes(this.MODE_KANBAN)
            ) {
                const pipelinesHelper = new PipelinesHelper();

                if (
                    !pipelinesHelper.isEnabled(this.scope) ||
                    pipelinesHelper.get(this.scope).length
                ) {
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
    protected setupCreateButton() {
        if (this.quickCreate) {
            this.addMenuItem('buttons', {
                action: 'quickCreate',
                iconHtml: '<span class="fas fa-plus fa-sm"></span>',
                text: this.translate('Create ' +  this.scope, 'labels', this.scope),
                style: 'default',
                acl: 'create',
                aclScope: this.entityType ?? this.scope,
                title: 'Ctrl+Space',
            }, true);

            return;
        }

        this.addMenuItem('buttons', {
            link: `#${this.scope}/create`,
            action: 'create',
            iconHtml: '<span class="fas fa-plus fa-sm"></span>',
            text: this.translate('Create ' +  this.scope,  'labels', this.scope),
            style: 'default',
            acl: 'create',
            aclScope: this.entityType ?? this.scope,
            title: 'Ctrl+Space',
        }, true);
    }

    /**
     * Set up a search panel.
     *
     * @protected
     */
    protected setupSearchPanel() {
        this.createSearchView();
    }

    /**
     * Create a search view.
     */
    protected async createSearchView(): Promise<View> {
        const view = await this.createView('search', this.searchView, {
            collection: this.collection,
            fullSelector: '#main > .search-container',
            searchManager: this.searchManager,
            scope: this.scope,
            viewMode: this.viewMode,
            viewModeList: this.viewModeList,
            isWide: true,
            disableSavePreset: !!this._primaryFilter,
            primaryFiltersDisabled: !!this._primaryFilter,
        }) as View;

        this.listenTo(view, 'reset', () => this.resetSorting());

        if (this.viewModeList.length > 1) {
            this.listenTo(view, 'change-view-mode', mode => this.switchViewMode(mode));
        }

        return view;
    }

    /**
     * Switch a view mode.
     *
     * @param mode A mode.
     */
    protected switchViewMode(mode: string) {
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
     * @param mode A mode.
     * @param toStore To preserve a mode being set.
     */
    protected setViewMode(mode: string, toStore: boolean = false) {
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

        const methodName = 'setViewMode' + Utils.upperCaseFirst(this.viewMode);

        if ((this as any)[methodName]) {
            (this as any)[methodName]();
        }
    }

    /**
     * Called when the kanban mode is set.
     */
    protected setViewModeKanban() {
        this.collection.url = 'Kanban/' + this.scope;
        this.collection.maxSize = this.getConfig().get('recordsPerPageKanban');
        this.collection.resetOrderToDefault();
    }

    /**
     * Reset sorting in a storage.
     */
    protected resetSorting() {
        this.getStorage().clear('listSorting', this.scope);
    }

    /**
     * Get default search data.
     *
     */
    protected getSearchDefaultData(): Record<string, any>{
        return this.getMetadata().get(`clientDefs.${this.scope}.defaultFilterData`);
    }

    /**
     * Set up a search manager.
     */
    protected setupSearchManager() {
        const collection = this.collection;

        let key = 'list';

        if (this._primaryFilter) {
            key += 'Filter' + Utils.upperCaseFirst(this._primaryFilter);
        }

        const searchManager = new SearchManager(collection, {
            storageKey: key,
            defaultData: this.getSearchDefaultData(),
            scope: this.scope,
        });

        searchManager.loadStored();

        if (this._primaryFilter) {
            searchManager.clearPreset();
            searchManager.setPrimary(this._primaryFilter);
        }

        collection.where = searchManager.getWhere();

        this.searchManager = searchManager;
    }

    /**
     * Set up sorting.
     */
    protected setupSorting() {
        if (!this.searchPanel) {
            return;
        }

        this.applyStoredSorting();
    }

    /**
     * Apply stored sorting.
     */
    protected applyStoredSorting() {
        const sortingParams = this.getStorage().get('listSorting', this.scope) || {};

        if ('orderBy' in sortingParams) {
            this.collection.orderBy = sortingParams.orderBy;
        }

        if ('order' in sortingParams) {
            this.collection.order = sortingParams.order;
        }
    }

    protected getSearchView(): SearchView {
        return this.getView('search') as SearchView;
    }

    protected getRecordView(): View {
        return this.getView('list') as View;
    }

    /**
     * Get a record view name.
     */
    private getRecordViewName(): string {
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

        const propertyName = 'record' + Utils.upperCaseFirst(this.viewMode) + 'View';

        viewName = (this as any)[propertyName];

        if (!viewName) {
            throw new Error("No record view.");
        }

        return viewName;
    }

    cancelRender() {
        if (this.hasView('list')) {
            this.getRecordView();

            if (this.getRecordView().isBeingRendered()) {
                this.getRecordView().cancelRender();
            }
        }

        super.cancelRender();
    }

    protected afterRender() {
        Ui.notify();

        if (!this.hasView('list')) {
            this.loadList();
        }

        // noinspection JSUnresolvedReference
        this.$el.get(0)?.focus({preventScroll: true});
    }

    /**
     * Load a record list view.
     */
    protected loadList() {
        if ('isFetched' in this.collection && this.collection.isFetched) {
            this.createListRecordView(false);

            return;
        }

        Ui.notifyWait();

        this.createListRecordView(true);
    }

    /**
     * Prepare record view options. Options can be modified in an extended method.
     *
     * @param options Options
     */
    protected prepareRecordViewOptions(options: Record<string, any>) {
        // noinspection BadExpressionStatementJS
        options;
    }

    /**
     * Create a record list view.
     *
     * @param fetch To fetch after creation.
     */
    protected async createListRecordView(fetch: boolean = false): Promise<ListRecordView> {
        const o = {
            collection: this.collection,
            selector: '.list-container',
            scope: this.scope,
            skipBuildRows: true,
            shortcutKeysEnabled: true,
            forceDisplayTopBar: true,
            additionalRowActionList: this.getMetadata().get(`clientDefs.${this.scope}.rowActionList`),
            settingsEnabled: true,
            forceSettings: this.getMetadata().get(`clientDefs.${this.scope}.forceListViewSettings`),
        } as any; // @todo Add type.

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
            o.pagination = true;
        }

        o.rootData = this.rootData;

        this.prepareRecordViewOptions(o);

        const listViewName = this.getRecordViewName();

        const promise = this.createView<ListRecordView>('list', listViewName, o).then(async (view) => {
            if (!this.hasParentView()) {
                view.undelegateEvents();

                return view;
            }

            this.listenTo(view, 'after:paginate', () => window.scrollTo({top: 0}));
            this.listenTo(view, 'sort', () => window.scrollTo({top: 0}));

            this.listenToOnce(view, 'after:render', () => {
                if (!this.hasParentView()) {
                    view.undelegateEvents();

                    this.clearView('list');
                }
            });

            if (!fetch) {
                Ui.notify();
            }

            if (this.searchPanel && this.scope) {
                this.listenTo(view, 'sort', o => {
                    this.getStorage().set('listSorting', this.scope, o);
                });
            }

            if (!fetch) {
                await view.render();

                return view;
            }

            const selectAttributes = await view.getSelectAttributeList();

            if (this.options.mediator && this.options.mediator.abort) {
                return view;
            }

            if (selectAttributes) {
                this.collection.data.select = selectAttributes.join(',');
            }

            Ui.notifyWait();

            await this.collection.fetch({main: true});

            Ui.notify();

            return view;
        });

        return promise as Promise<ListRecordView>;
    }

    getHeader(): string {
        if (this._fromAdmin) {
            const root = document.createElement('a');
            root.href = '#Admin';
            root.textContent = this.translate('Administration', 'labels', 'Admin');
            root.style.userSelect = 'none';

            const scope = document.createElement('span');
            scope.textContent = this.getLanguage().translate(this.scope, 'scopeNamesPlural');
            scope.dataset.action = 'fullRefresh';
            scope.style.cursor = 'pointer';
            scope.style.userSelect = 'none';

            return this.buildHeaderHtml([root, scope]);
        }

        const root = document.createElement('span');
        root.textContent = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

        root.title = this.translate('clickToRefresh', 'messages');
        root.dataset.action = 'fullRefresh';
        root.style.cursor = 'pointer';
        root.style.userSelect = 'none';

        const iconHtml = this.getHeaderIconHtml();

        if (iconHtml) {
            root.insertAdjacentHTML('afterbegin', iconHtml);
        }

        if (this._primaryFilter) {
            const label = this.translate(this._primaryFilter, 'presetFilters', this.entityType);

            // noinspection SpellCheckingInspection
            root.insertAdjacentHTML('beforeend', ' · ' + label);
        }

        return this.buildHeaderHtml([root]);
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));
    }

    /**
     * Create attributes for an entity being created.
     */
    getCreateAttributes(): Record<string, unknown> | null {
        return null;
    }

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
     * @param params Parameters to be modified.
     * Unset `controller` if bypass return dispatching (to force to come back to the `returnUrl`).
     */
    protected prepareCreateReturnDispatchParams(
        params: {
            controller?: string;
            action?: string| null;
            options?: Record<string, any> & {isReturn?: boolean};
        }
    ) {
        // noinspection BadExpressionStatementJS
        params;
    }

    /**
     * Action `quickCreate`.
     */
    protected actionQuickCreate(
        data?: Record<string, any> & {focusForCreate?: boolean},
    ): Promise<EditModalView> {
        data = data ?? {};

        const attributes = this.getCreateAttributes() || {};

        const returnDispatchParams = {
            controller: this.scope,
            action: null,
            options: {isReturn: true},
        };

        this.prepareCreateReturnDispatchParams(returnDispatchParams);

        const helper = new RecordModal();

        return helper.showCreate(this, {
            entityType: this.scope,
            attributes: attributes,
            rootUrl: this.keepCurrentRootUrl ? this.getRouter().getCurrentUrl() : undefined,
            focusForCreate: data.focusForCreate,
            returnUrl: this.getRouter().getCurrentUrl(),
            returnDispatchParams: returnDispatchParams,
            afterSave: () => {
                this.collection.fetch()
            },
        });
    }

    /**
     * Action 'create'.
     */
    protected actionCreate(data?: Record<string, any>) {
        data = data || {};

        const router = this.getRouter();

        const url = '#' + this.scope + '/create';
        const attributes = this.getCreateAttributes() || {};

        let options = {attributes: attributes} as any;

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
     */
    isActualForReuse(): boolean {
        return 'isFetched' in this.collection && this.collection.isFetched;
    }

    protected handleShortcutKeyCtrlSpace(event: KeyboardEvent) {
        if (!this.createButton) {
            return;
        }

        if (!this.getAcl().checkScope(this.scope, 'create')) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (this.quickCreate) {
            this.actionQuickCreate({focusForCreate: true});

            return;
        }

        this.actionCreate({focusForCreate: true});
    }

    protected handleShortcutKeyCtrlSlash(event: KeyboardEvent) {
        if (!this.searchPanel) {
            return;
        }

        const $search = this.$el.find('input.text-filter').first();

        if (!$search.length) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        $search.focus();
    }

    protected handleShortcutKeyCtrlComma(event: KeyboardEvent) {
        // noinspection BadExpressionStatementJS
        event;

        if (!this.getSearchView()) {
            return;
        }

        this.getSearchView().selectPreviousPreset();
    }

    protected handleShortcutKeyCtrlPeriod(event: KeyboardEvent) {
        // noinspection BadExpressionStatementJS
        event;

        if (!this.getSearchView()) {
            return;
        }

        this.getSearchView().selectNextPreset();
    }

    protected handleShortcutKeyControlArrowLeft(event: KeyboardEvent) {
        if (Utils.isKeyEventInTextInput(event)) {
            return;
        }

        this.getRecordView().trigger('request-page', 'previous');
    }

    protected handleShortcutKeyControlArrowRight(event: KeyboardEvent) {
        if (Utils.isKeyEventInTextInput(event)) {
            return;
        }

        this.getRecordView().trigger('request-page', 'next');
    }

    protected async actionFullRefresh() {
        Ui.notifyWait();

        await this.collection.fetch();

        Ui.notify();
    }
}

export default ListView;
