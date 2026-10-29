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
import CreateRelatedHelper from 'helpers/record/create-related';
import Collection from 'collection';
import type Model from 'model';
import SearchView from 'views/record/search';
import ListRecordView from 'views/record/list';
import Ui from 'ui';
import Ajax from 'ajax';
import EditModalView from 'views/modals/edit';
import Utils from 'utils';

export interface ListRelatedViewSchema {
    collection: Collection;
    model: Model;
    options: ListRelatedViewOptions;
}

export interface ListRelatedViewOptions extends MainViewOptions {
    link: string;
    defaultViewMode?: string;
    params?: Record<string, any> & {
        rootUrl?: string;
        primaryFilter?: string | null;
        fromAdmin?: boolean;
    };
}

/**
 * A list-related view.
 */
class ListRelatedView<S extends ListRelatedViewSchema = ListRelatedViewSchema> extends MainView<S> {

    protected template = 'list'

    name: string = 'ListRelated'

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
     * Has a search panel.
     */
    protected searchPanel: boolean = true

    /**
     * A search manager.
     */
    protected searchManager: SearchManager | null = null

    optionsToPass: string[] = []

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

    private rowActionsView: string = 'views/record/row-actions/relationship'

    /**
     * A create button.
     *
     */
    protected createButton = true

    protected unlinkDisabled = false

    protected filtersDisabled = false

    private nameAttribute: string

    /**
     * Disable select-all-result.
     *
     */
    protected allResultDisabled = false

    protected entityType: string

    protected link: string

    private panelDefs: Record<string, any> & {
        filterList?: any[];
    }

    private collectionUrl: string | null

    private collectionMaxSize: number

    private foreignScope: string

    protected defaultOrderBy: string | null

    protected defaultOrder: 'asc' | 'desc' | boolean | null

    protected shortcutKeys: (Record<string, (event: KeyboardEvent) => void>) | null = {
        'Control+Space': function (this: ListRelatedView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlSpace(e);
        },
        'Control+Slash': function (this: ListRelatedView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlSlash(e);
        },
        'Control+Comma': function (this: ListRelatedView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlComma(e);
        },
        'Control+Period': function (this: ListRelatedView, e: KeyboardEvent) {
            this.handleShortcutKeyCtrlPeriod(e);
        },
    }

    protected readonly rootLinkDisabled: boolean

    protected setup() {
        if (!this.options.link) {
            console.error(`Link not passed.`);
            throw new Error();
        }

        this.link = this.options.link;

        if (!this.model) {
            console.error(`Model not passed.`);
            throw new Error();
        }

        if (!this.collection) {
            console.error(`Collection not passed.`);
            throw new Error();
        }

        this.rootUrl = this.options.rootUrl ?? this.options.params?.rootUrl ?? `#${this.scope}`;

        this.nameAttribute = this.getMetadata().get(`clientDefs.${this.scope}.nameAttribute`) ?? 'name';
        this.panelDefs = this.getMetadata().get(['clientDefs', this.scope, 'relationshipPanels', this.link]) ?? {};

        if (this.panelDefs.fullFormDisabled) {
            console.error(`Full-form disabled.`);

            throw new Error();
        }

        this.collection.maxSize = this.getConfig().get('recordsPerPage') || this.collection.maxSize;
        this.collectionUrl = this.collection.url;
        this.collectionMaxSize = this.collection.maxSize;

        if (this.panelDefs.primaryFilter) {
            this.collection.data.primaryFilter = this.panelDefs.primaryFilter;
        }

        if (!this.collection.entityType) {
            console.error(`Not foreign entity type.`);

            throw new Error();
        }

        this.foreignScope = this.collection.entityType;

        this.setupModes();
        this.setViewMode(this.viewMode);

        if (this.getMetadata().get(['clientDefs', this.foreignScope, 'searchPanelDisabled'])) {
            this.searchPanel = false;
        }

        if (
            this.getUser().isPortal() &&
            this.getMetadata().get(['clientDefs', this.foreignScope, 'searchPanelInPortalDisabled'])
        ) {
            this.searchPanel = false;
        }

        if (this.getMetadata().get(['clientDefs', this.foreignScope, 'createDisabled'])) {
            this.createButton = false;
        }

        // noinspection JSUnresolvedReference
        if (
            this.panelDefs.create === false ||
            this.panelDefs.createDisabled ||
            this.panelDefs.createAction
        ) {
            this.createButton = false;
        }

        this.entityType = this.collection.entityType;

        this.headerView = this.options.headerView || this.headerView;
        this.recordView = this.options.recordView || this.recordView;
        this.searchView = this.options.searchView || this.searchView;

        this.setupHeader();

        this.defaultOrderBy = this.panelDefs.orderBy || this.collection.orderBy;
        this.defaultOrder = this.panelDefs.orderDirection || this.collection.order;

        if (this.panelDefs.orderBy && !this.panelDefs.orderDirection) {
            this.defaultOrder = 'asc';
        }

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

        this.wait(
            this.getHelper().processSetupHandlers(this, 'list')
        );

        this.addActionHandler('fullRefresh', () => this.actionFullRefresh());
        this.addActionHandler('removeRelated',
            (_e, target) => this.actionRemoveRelated({id: target.dataset.id as string}));
    }

    /**
     * Set up modes.
     */
    protected setupModes() {
        this.defaultViewMode = this.options.defaultViewMode ??
            this.getMetadata().get(['clientDefs', this.foreignScope, 'listRelatedDefaultViewMode']) ??
            this.defaultViewMode;

        this.viewMode = this.viewMode || this.defaultViewMode;

        const viewModeList = this.options.viewModeList ||
            this.viewModeList ||
            this.getMetadata().get(['clientDefs', this.foreignScope, 'listRelatedViewModeList']);

        this.viewModeList = viewModeList ? viewModeList : [this.MODE_LIST];

        if (this.viewModeList.length > 1) {
            let viewMode = null;

            const modeKey = 'listRelatedViewMode' + this.scope + this.link;

            if (this.getStorage().has('state', modeKey)) {
                const storedViewMode = this.getStorage().get('state', modeKey);

                if (storedViewMode && this.viewModeList.includes(storedViewMode)) {
                    viewMode = storedViewMode;
                }
            }

            if (!viewMode) {
                viewMode = this.defaultViewMode;
            }

            this.viewMode = viewMode;
        }
    }

    /**
     * Set up a header.
     */
    protected setupHeader() {
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
        this.addMenuItem('buttons', {
            action: 'quickCreate',
            iconHtml: '<span class="fas fa-plus fa-sm"></span>',
            text: this.translate('Create ' + this.foreignScope, 'labels', this.foreignScope),
            style: 'default',
            acl: 'create',
            aclScope: this.foreignScope,
            title: 'Ctrl+Space',
        });
    }

    /**
     * Set up a search panel.
     */
    protected setupSearchPanel() {
        this.createSearchView();
    }

    /**
     * Create a search view.
     *
     */
    protected async createSearchView(): Promise<SearchView> {
        let filterList = Utils
            .clone(this.getMetadata().get(['clientDefs', this.foreignScope, 'filterList']) || []) as any[];

        if (this.panelDefs.filterList) {
            this.panelDefs.filterList.forEach(item1 => {
                let isFound = false;
                const name1 = item1.name || item1;

                if (!name1 || name1 === 'all') {
                    return;
                }

                filterList.forEach(item2 => {
                    const name2 = item2.name || item2;

                    if (name1 === name2) {
                        isFound = true;
                    }
                });

                if (!isFound) {
                    filterList.push(item1);
                }
            });
        }

        if (this.filtersDisabled) {
            filterList = [];
        }

        const storageKey = this.panelDefs.recordSpecificSearchStorage ?
            this.model.entityType + '-' + this.model.id + '-' + this.link : null;

        const view = await this.createView<SearchView>('search', this.searchView, {
            collection: this.collection,
            fullSelector: '#main > .search-container',
            searchManager: this.searchManager,
            scope: this.foreignScope,
            viewMode: this.viewMode,
            viewModeList: this.viewModeList,
            isWide: true,
            filterList: filterList,
            storageKey: storageKey,
        });

        if (this.viewModeList.length > 1) {
            this.listenTo(view, 'change-view-mode', mode => this.switchViewMode(mode));
        }

        return view;
    }

    /**
     * Switch a view mode.
     *
     * @param {string} mode
     */
    private switchViewMode(mode: string) {
        this.clearView('list');
        this.collection.isFetched = false;
        this.collection.reset();
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
            const modeKey = 'listViewMode' + this.scope + this.link;

            this.getStorage().set('state', modeKey, mode);
        }

        if (this.searchView && this.getView('search')) {
            this.getSearchView().setViewMode(mode);
        }

        const methodName = 'setViewMode' + Utils.upperCaseFirst(this.viewMode);

        // @ts-ignore
        if (this[methodName]) {
            // @ts-ignore
            this[methodName]();
        }
    }

    /**
     * Set up a search manager.
     */
    setupSearchManager() {
        const collection = this.collection;

        const searchManager = new SearchManager(collection, {
            scope: this.foreignScope,
        });

        if (this.panelDefs.primaryFilter) {
            searchManager.setPrimary(this.panelDefs.primaryFilter);
        }

        collection.where = searchManager.getWhere();

        this.searchManager = searchManager;
    }

    /**
     * Set up sorting.
     */
    setupSorting() {}

    protected getSearchView(): SearchView {
        return this.getView<SearchView>('search') as SearchView;
    }

    protected getRecordView(): ListRecordView {
        return this.getView<ListRecordView>('list') as ListRecordView;
    }

    /**
     * Get a record view name.
     */
    protected getRecordViewName(): string {
        if (this.viewMode === this.MODE_LIST) {
            return this.panelDefs.recordListView ??
                this.getMetadata().get(['clientDefs', this.foreignScope, 'recordViews', this.MODE_LIST]) ??
                this.recordView;
        }

        const propertyName = 'record' + Utils.upperCaseFirst(this.viewMode) + 'View';

        return this.getMetadata().get(['clientDefs', this.foreignScope, 'recordViews', this.viewMode]) ||
            // @ts-ignore
            this[propertyName];
    }

    protected afterRender() {
        Ui.notify();

        if (!this.hasView('list')) {
            this.loadList();
        }

        // noinspection JSUnresolvedReference
        this.$el.get(0).focus({preventScroll: true});
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
        let o = {
            collection: this.collection,
            selector: '.list-container',
            scope: this.foreignScope,
            skipBuildRows: true,
            shortcutKeysEnabled: true,
        } as Record<string, unknown>;

        this.optionsToPass.forEach(option => {
            o[option] = this.options[option];
        });

        if (this.keepCurrentRootUrl) {
            o.keepCurrentRootUrl = true;
        }

        if (this.panelDefs.layout && typeof this.panelDefs.layout === 'string') {
            o.layoutName = this.panelDefs.layout;
        }

        o.rowActionsView = this.panelDefs.readOnly ? false :
            (this.panelDefs.rowActionsView || this.rowActionsView);

        if (
            this.getConfig().get('listPagination') ||
            this.getMetadata().get(['clientDefs', this.foreignScope, 'listPagination'])
        ) {
            o.pagination = true;
        }

        const unlinkDisabled = this.panelDefs.unlinkDisabled ||
            this.unlinkDisabled ||
            (this.link && this.model.getLinkParam(this.link, 'readOnly'));

        const massUnlinkDisabled = this.panelDefs.massUnlinkDisabled || unlinkDisabled;

        o = {
            unlinkMassAction: !massUnlinkDisabled,
            skipBuildRows: true,
            buttonsDisabled: true,
            forceDisplayTopBar: true,
            rowActionsOptions:  {
                unlinkDisabled: unlinkDisabled,
                editDisabled: this.panelDefs.editDisabled,
                removeDisabled: this.panelDefs.removeDisabled,
            },
            additionalRowActionList: this.panelDefs.rowActionList,
            ...o,
            settingsEnabled: true,
            removeDisabled: this.panelDefs.removeDisabled,
        } as Record<string, unknown>;

        if (this.getHelper().isXsScreen()) {
            o.type = 'listSmall';
        }

        const foreignLink = this.model.getLinkParam(this.link, 'foreign');

        if (!this.allResultDisabled && !this.panelDefs.allResultDisabled && foreignLink) {
            o.forceAllResultSelectable = true;

            o.allResultWhereItem = {
                type: 'linkedWith',
                attribute: foreignLink,
                value: [this.model.id],
            };
        }

        this.prepareRecordViewOptions(o);

        const listViewName = this.getRecordViewName();

        const promise = this.createView<ListRecordView>('list', listViewName, o).then(async view => {
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

    /**
     * A quick-create action.
     */
    protected async actionQuickCreate(
        data?: {focusForCreate?: boolean},
    ): Promise<EditModalView> {

        data = data ?? {};

        const link = this.link;

        const helper = new CreateRelatedHelper(this);

        return helper.process(this.model, link, {
            focusForCreate: data.focusForCreate,
            afterSave: () => {
                this.collection.fetch()
            },
        });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * An `unlink-related` action.
     */
    protected async actionUnlinkRelated(data: {id: string}) {
        const id = data.id;

        await this.confirm({
            message: this.translate('unlinkRecordConfirmation', 'messages'),
            confirmText: this.translate('Unlink'),
        })

        Ui.notifyWait();

        await Ajax.deleteRequest(this.collection.url as string, {id: id})

        Ui.success(this.translate('Unlinked'));

        this.collection.fetch();

        this.model.trigger('after:unrelate');
        this.model.trigger(`after:unrelate:${this.link}`);
    }

    getHeader() {
        const name = this.model.attributes[this.nameAttribute] || this.model.id;

        const recordUrl = `#${this.scope}/view/${this.model.id}`;

        const title = document.createElement('a');
        title.href = recordUrl;
        title.classList.add('font-size-flexible', 'title');
        title.textContent = name;
        title.style.userSelect = 'none';

        if (this.model.attributes.deleted) {
            title.style.textDecoration = 'line-through';
        }

        const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

        let root = document.createElement('span');
        root.textContent = scopeLabel;
        root.style.userSelect = 'none';

        if (!this.rootLinkDisabled) {
            const a = document.createElement('a');

            if (this.rootUrl) {
                a.href = this.rootUrl;
            }

            a.classList.add('action');
            a.dataset.action = 'navigateToRoot';
            a.text = scopeLabel;

            root = document.createElement('span');
            root.style.userSelect = 'none';
            root.append(a);
        }

        const iconHtml = this.getHeaderIconHtml();

        if (iconHtml) {
            root.insertAdjacentHTML('afterbegin', iconHtml);
        }

        const link = document.createElement('span');
        link.textContent = this.translate(this.link, 'links', this.scope);

        link.title = this.translate('clickToRefresh', 'messages');
        link.dataset.action = 'fullRefresh';
        link.style.cursor = 'pointer';
        link.style.userSelect = 'none';

        return this.buildHeaderHtml([
            root,
            title,
            link,
        ]);
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate(this.link, 'links', this.scope));
    }

    /**
     * Create attributes for an entity being created.
     */
    getCreateAttributes(): Record<string, unknown> | null {
        return null;
    }

    protected handleShortcutKeyCtrlSpace(e: KeyboardEvent) {
        if (!this.createButton) {
            return;
        }

        if (!this.getAcl().checkScope(this.foreignScope, 'create')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();


        this.actionQuickCreate({focusForCreate: true});
    }

    protected handleShortcutKeyCtrlSlash(e: KeyboardEvent) {
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

    protected async actionFullRefresh() {
        Ui.notifyWait();

        await this.collection.fetch();

        Ui.notify();
    }

    protected async actionRemoveRelated(data: {id: string}): Promise<void> {
        const id = data.id;

        await this.confirm({
            message: this.translate('removeRecordConfirmation', 'messages'),
            confirmText: this.translate('Remove'),
        });

        const model = this.collection.get(id);

        if (!model) {
            return;
        }

        Ui.notifyWait();

        await model.destroy();

        Ui.success(this.translate('Removed'));

        this.collection.fetch().then(() => {});

        this.model.trigger('after:unrelate');
        this.model.trigger(`after:unrelate:${this.link}`);
    }
}

export default ListRelatedView;
