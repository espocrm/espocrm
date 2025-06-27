/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/list-with-categories */

import ListView from 'views/list';

class ListWithCategories extends ListView {

    template = 'list-with-categories'

    quickCreate = true
    storeViewAfterCreate = true
    storeViewAfterUpdate = true
    /** @type {string|null} */
    currentCategoryId = null
    currentCategoryName = ''
    /** @type {string|null} */
    categoryScope = null
    categoryField = 'category'
    categoryFilterType = 'inCategory'
    isExpanded = false
    hasExpandedToggler = true
    expandedTogglerDisabled = false
    keepCurrentRootUrl = true
    hasNavigationPanel = false
    /** @private */
    nestedCollectionIsBeingFetched = false
    /**
     * @type {module:collections/tree}
     * @private
     */
    nestedCategoriesCollection

    /**
     * @protected
     * @type {boolean}
     */
    isCategoryMultiple

    data() {
        const data = {};

        data.hasTree = (this.isExpanded || this.hasNavigationPanel) && !this.categoriesDisabled;
        data.hasNestedCategories = !this.categoriesDisabled;
        data.fallback = !data.hasTree && !data.hasNestedCategories;

        return data;
    }

    setup() {
        super.setup();

        this.addActionHandler('toggleExpandedFromNavigation', () => this.actionToggleExpandedFromNavigation());
        this.addActionHandler('manageCategories', () => this.actionManageCategories());

        this.defaultMaxSize = this.collection.maxSize;

        if (!this.categoryScope) {
            this.categoryScope = `${this.scope}Category`;
        }

        this.categoryField = this.getMetadata().get(`scopes.${this.categoryScope}.categoryField`) || this.categoryField;

        this.isCategoryMultiple = this.getMetadata()
            .get(`entityDefs.${this.scope}.fields.${this.categoryField}.type`) === 'linkMultiple';

        this.showEditLink =
            this.getAcl().check(this.categoryScope, 'edit') ||
            this.getAcl().check(this.categoryScope, 'create');

        const isExpandedByDefault = this.getMetadata()
            .get(['clientDefs', this.categoryScope, 'isExpandedByDefault']) || false;

        if (isExpandedByDefault) {
            this.isExpanded = true;
        }

        const isCollapsedByDefault = this.getMetadata()
            .get(['clientDefs', this.categoryScope, 'isCollapsedByDefault']) || false;

        if (isCollapsedByDefault) {
            this.isExpanded = false;
        }

        this.categoriesDisabled =
            this.categoriesDisabled ||
            this.getMetadata().get(['scopes', this.categoryScope, 'disabled']) ||
            !this.getAcl().checkScope(this.categoryScope);

        if (this.categoriesDisabled) {
            this.isExpanded = true;
            this.hasExpandedToggler = false;
            this.hasNavigationPanel = false;
        } else if (!this.expandedTogglerDisabled) {
            if (!this.getUser().isPortal() && this.hasIsExpandedStoredValue()) {
                this.isExpanded = this.getIsExpandedStoredValue();
            }

            if (this.getUser().isPortal()) {
                this.hasExpandedToggler = false;
                this.isExpanded = false;
            }
        }

        if (this.hasNavigationPanelStoredValue()) {
            this.hasNavigationPanel = this.getNavigationPanelStoredValue();
        } else {
            this.hasNavigationPanel =
                this.getMetadata().get(`scopes.${this.categoryScope}.showNavigationPanel`) ||
                this.hasNavigationPanel;
        }

        const params = this.options.params || {};

        if ('categoryId' in params) {
            this.currentCategoryId = params.categoryId;
        }

        this.applyCategoryToCollection();

        this.listenTo(this.collection, 'sync', (c, d, o) => {
            if (o && o.openCategory) {
                return;
            }

            this.controlListVisibility();
        });
    }

    /**
     * @inheritDoc
     */
    prepareCreateReturnDispatchParams(params) {
        if (this.currentCategoryId) {
            params.options.categoryId = this.currentCategoryId;
            params.options.categoryName = this.currentCategoryName;
        }
    }

    /**
     * @inheritDoc
     */
    setupReuse(params) {
        super.setupReuse(params);

        this.applyRoutingParams(params);
    }

    /**
     * @private
     * @param {Record} params
     */
    applyRoutingParams(params) {
        if ('categoryId' in params) {
            if (params.categoryId !== this.currentCategoryId) {
                this.openCategory(params.categoryId, params.categoryName);
            }
        }

        this.selectCurrentCategory();
    }

    /**
     * @private
     * @return {boolean}
     */
    hasTextFilter() {
        return !!this.collection.data.textFilter ||
            (
                this.collection.where &&
                this.collection.where.find(it => it.type === 'textFilter')
            );
    }

    hasNavigationPanelStoredValue() {
        return this.getStorage().has('state', `categories-navigation-panel-${this.scope}`);
    }

    getNavigationPanelStoredValue() {
        const value = this.getStorage().get('state', `categories-navigation-panel-${this.scope}`);

        return value === 'true' || value === true;
    }

    setNavigationPanelStoredValue(value) {
        return this.getStorage().set('state', `categories-navigation-panel-${this.scope}`, value);
    }

    hasIsExpandedStoredValue() {
        return this.getStorage().has('state', `categories-expanded-${this.scope}`);
    }

    getIsExpandedStoredValue() {
        const value = this.getStorage().get('state', `categories-expanded-${this.scope}`);

        return value === 'true' || value === true ;
    }

    setIsExpandedStoredValue(value) {
        return this.getStorage().set('state', `categories-expanded-${this.scope}`, value);
    }

    afterRender() {
        this.$nestedCategoriesContainer = this.$el.find('.nested-categories-container');
        this.$listContainer = this.$el.find('.list-container');

        if (!this.hasView('list')) {
            if (!this.isExpanded) {
                this.hideListContainer();
            }

            this.loadList();
        } else {
            this.controlListVisibility();
        }

        if (
            !this.categoriesDisabled &&
            (this.isExpanded || this.hasNavigationPanel) &&
            !this.hasView('categories')
        ) {
            this.loadCategories();
        }

        if (!this.hasView('nestedCategories') && !this.categoriesDisabled) {
            this.loadNestedCategories();
        }

        this.$el.focus();
    }

    /**
     * @private
     */
    clearCategoryViews() {
        this.clearNestedCategoriesView();
        this.clearCategoriesView();
    }

    /**
     * @private
     */
    clearCategoriesView() {
        this.clearView('categories');
    }

    /**
     * @private
     */
    clearNestedCategoriesView() {
        this.clearView('nestedCategories');
    }

    /**
     * @private
     */
    emptyListContainer() {
        this.$listContainer.empty();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     */
    async actionExpand() {
        this.isExpanded = true;
        this.setIsExpandedStoredValue(true);
        this.applyCategoryToCollection();
        this.clearNestedCategoriesView();

        if (this.getCategoriesView()) {
            this.getCategoriesView().isExpanded = true;
            this.getCategoriesView().expandToggleInactive = true;
        }

        this.reRender().then(() => {});

        this.emptyListContainer();

        await this.collection.fetch();

        if (this.getCategoriesView()) {
            this.getCategoriesView().expandToggleInactive = false;
            await this.getCategoriesView().reRender();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     */
    async actionCollapse() {
        this.isExpanded = false;
        this.setIsExpandedStoredValue(false);
        this.applyCategoryToCollection();
        this.applyCategoryToNestedCategoriesCollection();
        this.clearNestedCategoriesView();

        if (this.getCategoriesView()) {
            this.getCategoriesView().isExpanded = false;
            this.getCategoriesView().expandToggleInactive = true;
        }

        this.reRender().then(() => {});

        this.emptyListContainer();

        await this.collection.fetch();

        if (this.getCategoriesView()) {
            this.getCategoriesView().expandToggleInactive = false;
            await this.getCategoriesView().reRender();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    actionOpenCategory(data) {
        this.openCategory(data.id || null, data.name);

        this.selectCurrentCategory();
        this.navigateToCurrentCategory();
    }

    navigateToCurrentCategory() {
        let url = `#${this.scope}`;

        if (this.currentCategoryId) {
            url += `/list/categoryId=${this.currentCategoryId}`;

            if (this._primaryFilter) {
                url += `&primaryFilter=${this.getHelper().escapeString(this._primaryFilter)}`;
            }
        } else {
            if (this._primaryFilter) {
                url += `/list/primaryFilter=${this.getHelper().escapeString(this._primaryFilter)}`;
            }
        }

        this.getRouter().navigate(url);
        this.updateLastUrl();
    }

    selectCurrentCategory() {
        const categoriesView = this.getCategoriesView();

        if (categoriesView) {
            categoriesView.setSelected(this.currentCategoryId);
            categoriesView.reRender();
        }
    }

    /**
     * @param {string|null} id
     * @param {string|null} [name]
     */
    openCategory(id, name) {
        this.getNestedCategoriesView().isLoading = true;
        this.getNestedCategoriesView().reRender();
        this.getNestedCategoriesView().isLoading = false;

        this.nestedCategoriesCollection.reset();
        this.collection.reset();
        this.collection.offset = 0;
        this.collection.maxSize = this.defaultMaxSize;

        this.emptyListContainer();

        this.currentCategoryId = id;
        this.currentCategoryName = name || id;

        this.applyCategoryToNestedCategoriesCollection();
        this.applyCategoryToCollection();

        this.collection.abortLastFetch();

        if (this.nestedCategoriesCollection) {
            this.nestedCategoriesCollection.abortLastFetch();

            this.hideListContainer();
            this.$nestedCategoriesContainer.addClass('hidden');

            Espo.Ui.notifyWait();

            const promises = [
                this.nestedCategoriesCollection.fetch().then(() => this.updateHeader()),
                this.collection.fetch({openCategory: true})
            ];

            Promise.all(promises)
                .then(() => {
                    Espo.Ui.notify(false);

                    this.controlNestedCategoriesVisibility();
                    this.controlListVisibility();
                });

            return;
        }

        this.collection.fetch()
            .then(() => {
                Espo.Ui.notify(false);
            });
    }

    /**
     * @private
     */
    controlListVisibility() {
        if (this.isExpanded) {
            this.showListContainer();

            return;
        }

        if (this.nestedCollectionIsBeingFetched) {
            return;
        }

        if (
            !this.collection.models.length &&
            this.nestedCategoriesCollection &&
            this.nestedCategoriesCollection.models.length &&
            !this.hasTextFilter()
        ) {
            this.hideListContainer();

            return;
        }

        this.showListContainer();
    }

    /**
     * @private
     */
    controlNestedCategoriesVisibility() {
        this.$nestedCategoriesContainer.removeClass('hidden');
    }

    /**
     * @private
     * @param {function(import('collection').default)} callback
     */
    getTreeCollection(callback) {
        this.getCollectionFactory().create(this.categoryScope)
            .then(collection => {
                collection.url = `${collection.entityType}/action/listTree`;
                collection.setOrder(null, null);

                // @todo Revise. To remove?
                this.collection.treeCollection = collection;

                collection.fetch()
                    .then(() => callback.call(this, collection));
            });
    }

    applyCategoryToNestedCategoriesCollection() {
        if (!this.nestedCategoriesCollection) {
            return;
        }

        this.nestedCategoriesCollection.parentId = this.currentCategoryId;
        this.nestedCategoriesCollection.currentCategoryId = this.currentCategoryId;
        this.nestedCategoriesCollection.currentCategoryName = this.currentCategoryName || this.currentCategoryId;
        this.nestedCategoriesCollection.where = [];
    }

    /**
     * @private
     * @param {function(import('collection').default)} callback
     */
    getNestedCategoriesCollection(callback) {
        this.getCollectionFactory().create(this.categoryScope, async collection => {
            this.nestedCategoriesCollection = collection;

            collection.setOrder(null, null);
            collection.url = `${collection.entityType}/action/listTree`;
            collection.data.checkIfEmpty = true;

            if (!this.getAcl().checkScope(this.scope, 'create')) {
                collection.data.onlyNotEmpty = true;
            }

            this.applyCategoryToNestedCategoriesCollection();

            this.nestedCollectionIsBeingFetched = true;

            // Needed even in expanded mode to display the header path.
            await collection.fetch();

            this.nestedCollectionIsBeingFetched = false;

            this.controlNestedCategoriesVisibility();
            this.controlListVisibility();

            this.updateHeader();

            callback.call(this, collection);
        });
    }

    /**
     * @return {module:views/record/list-nested-categories}
     */
    getNestedCategoriesView() {
        return /** @type module:views/record/list-nested-categories */this.getView('nestedCategories');
    }

    /**
     * @return {module:views/record/list-tree}
     */
    getCategoriesView() {
        return /** @type module:views/record/list-tree */this.getView('categories');
    }

    /**
     * @private
     */
    loadNestedCategories() {
        this.getNestedCategoriesCollection(collection => {
            this.createView('nestedCategories', 'views/record/list-nested-categories', {
                collection: collection,
                itemCollection: this.collection,
                selector: '.nested-categories-container',
                showEditLink: this.showEditLink,
                isExpanded: this.isExpanded,
                hasExpandedToggler: this.hasExpandedToggler,
                hasNavigationPanel: this.hasNavigationPanel,
                subjectEntityType: this.collection.entityType,
                primaryFilter: this._primaryFilter,
            }, view => {
                view.render();
            });
        });
    }

    /**
     * @private
     */
    loadCategories() {
        this.getTreeCollection(collection => {
            this.createView('categories', 'views/record/list-tree', {
                collection: collection,
                selector: '.categories-container',
                selectable: true,
                showRoot: true,
                buttonsDisabled: true,
                checkboxes: false,
                showEditLink: this.showEditLink,
                isExpanded: this.isExpanded,
                hasExpandedToggler: this.hasExpandedToggler,
                readOnly: true,
            }, view => {
                if (this.currentCategoryId) {
                    view.setSelected(this.currentCategoryId);
                }

                view.render();

                this.listenTo(view, 'select', /** import('model').default */model => {
                    if (!this.isExpanded) {
                        let id = null;
                        let name = null;

                        if (model && model.id) {
                            id = model.id;
                            name = model.attributes.name;
                        }

                        this.openCategory(id, name);
                        this.navigateToCurrentCategory();

                        return;
                    }

                    this.currentCategoryId = null;
                    this.currentCategoryName = '';

                    if (model && model.id) {
                        this.currentCategoryId = model.id;
                        this.currentCategoryName = model.attributes.name;
                    }

                    this.collection.offset = 0;
                    this.collection.maxSize = this.defaultMaxSize;
                    this.collection.reset();

                    this.applyCategoryToCollection();
                    this.collection.abortLastFetch();

                    this.openCategory(this.currentCategoryId, this.currentCategoryName);
                    this.navigateToCurrentCategory();
                });
            });
        });
    }

    /**
     * @private
     * @todo Move to helper. Together with select-records view.
     */
    applyCategoryToCollection() {
        this.collection.whereFunction = () => {
            let filter;
            const isExpanded = this.isExpanded;

            if (!isExpanded && !this.hasTextFilter()) {
                if (this.isCategoryMultiple) {
                    if (this.currentCategoryId) {
                        filter = {
                            attribute: this.categoryField,
                            type: 'linkedWith',
                            value: [this.currentCategoryId]
                        };
                    }
                    else {
                        filter = {
                            attribute: this.categoryField,
                            type: 'isNotLinked'
                        };
                    }
                }
                else {
                    if (this.currentCategoryId) {
                        filter = {
                            attribute: this.categoryField + 'Id',
                            type: 'equals',
                            value: this.currentCategoryId
                        };
                    }
                    else {
                        filter = {
                            attribute: this.categoryField + 'Id',
                            type: 'isNull'
                        };
                    }
                }
            }
            else {
                if (this.currentCategoryId) {
                    filter = {
                        attribute: this.categoryField,
                        type: this.categoryFilterType,
                        value: this.currentCategoryId,
                    };
                }
            }

            if (filter) {
                return [filter];
            }
        };
    }

    /**
     * @inheritDoc
     */
    getCreateAttributes() {
        let data;

        if (this.isCategoryMultiple) {
            if (this.currentCategoryId) {
                const names = {};

                names[this.currentCategoryId] = this.getCurrentCategoryName();

                data = {};

                const idsAttribute = this.categoryField + 'Ids';
                const namesAttribute = this.categoryField + 'Names';

                data[idsAttribute] = [this.currentCategoryId];
                data[namesAttribute] = names;

                return data;
            }

            return null;
        }

        const idAttribute = this.categoryField + 'Id';
        const nameAttribute = this.categoryField + 'Name';

        data = {};

        data[idAttribute] = this.currentCategoryId;
        data[nameAttribute] = this.getCurrentCategoryName();

        return data;
    }

    /**
     * @private
     * @return {string|null}
     */
    getCurrentCategoryName() {
        if (this.currentCategoryName) {
            return this.currentCategoryName;
        }

        if (
            this.nestedCategoriesCollection &&
            this.nestedCategoriesCollection.categoryData &&
            this.nestedCategoriesCollection.categoryData.name
        ) {
            return this.nestedCategoriesCollection.categoryData.name;
        }

        return this.currentCategoryId;
    }

    /**
     * @private
     */
    actionManageCategories() {
        this.clearCategoryViews();

        const url = `#${this.categoryScope}`;

        const options = {};

        if (this.currentCategoryId) {
            options.currentId = this.currentCategoryId;
        }

        this.getRouter().navigate(url, {trigger: false});
        this.getRouter().dispatch(this.categoryScope, 'listTree', options);
    }

    /**
     * @inheritDoc
     */
    getHeader() {
        if (!this.nestedCategoriesCollection) {
            return super.getHeader();
        }

        const path = this.nestedCategoriesCollection.path;

        if (!path || path.length === 0) {
            return super.getHeader();
        }

        let rootUrl = `#${this.scope}`;

        if (this._primaryFilter) {
            const filterPart = this.getHelper().escapeString(this._primaryFilter);

            rootUrl += `/list/primaryFilter=${filterPart}`;
        }

        const root = document.createElement('a');
        root.href = rootUrl;
        root.textContent = this.translate(this.scope, 'scopeNamesPlural');
        root.dataset.action = 'openCategory';
        root.classList.add('action');
        root.style.userSelect = 'none';

        /** @type {*[]} */
        const list = [root];

        const currentName = this.nestedCategoriesCollection.categoryData.name;
        const upperId = this.nestedCategoriesCollection.categoryData.upperId;
        const upperName = this.nestedCategoriesCollection.categoryData.upperName;

        if (path.length > 2) {
            list.push('...');
        }

        if (upperId) {
            const upperIdPart = this.getHelper().escapeString(upperId);

            let url = `${rootUrl}/list/categoryId=${upperIdPart}`;

            if (this._primaryFilter) {
                const filterPart = this.getHelper().escapeString(this._primaryFilter);

                url += `&primaryFilter=${filterPart}`;
            }

            const folder = document.createElement('a');
            folder.href = url;
            folder.textContent = upperName;
            folder.classList.add('action');
            folder.dataset.action = 'openCategory';
            folder.dataset.id = upperId;
            folder.dataset.name = upperName;
            folder.style.userSelect = 'none';

            list.push(folder);
        }

        const last = document.createElement('span');
        last.textContent = currentName;
        last.dataset.action = 'fullRefresh';
        last.style.cursor = 'pointer';
        last.style.userSelect = 'none';

        list.push(last);

        return this.buildHeaderHtml(list);
    }

    /**
     * @protected
     */
    updateHeader() {
        if (this.getView('header')) {
            this.getView('header').reRender();
        }
    }

    /**
     * @protected
     */
    hideListContainer() {
        this.$listContainer.addClass('hidden');
    }

    /**
     * @protected
     */
    showListContainer() {
        this.$listContainer.removeClass('hidden');
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     * @return {Promise}
     */
    async actionToggleNavigationPanel() {
        this.hasNavigationPanel = !this.hasNavigationPanel;

        this.setNavigationPanelStoredValue(this.hasNavigationPanel);

        await this.reRender();

        this.loadNestedCategories();
    }

    /**
     * @inheritDoc
     */
    prepareRecordViewOptions(options) {
        super.prepareRecordViewOptions(options);

        options.forceDisplayTopBar = false;
    }

    /**
     * @private
     */
    async actionToggleExpandedFromNavigation() {
        this.isExpanded = !this.isExpanded;

        this.hasNavigationPanel = true;
        this.setNavigationPanelStoredValue(this.hasNavigationPanel);

        /** @type {HTMLAnchorElement} */
        const a = this.element.querySelector('a[data-role="expandButtonContainer"]');

        if (a) {
            a.classList.add('disabled');
        }

        Espo.Ui.notifyWait();

        if (this.isExpanded) {
            await this.actionExpand();
        } else {
            await this.actionCollapse();
        }

        Espo.Ui.notify();
    }
}

export default ListWithCategories;
