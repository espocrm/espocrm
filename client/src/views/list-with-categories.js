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

    data() {
        const data = {};

        data.hasTree = (this.isExpanded || this.hasNavigationPanel) && !this.categoriesDisabled;
        data.hasNestedCategories = !this.isExpanded;
        data.fallback = !data.hasTree && !data.hasNestedCategories;

        return data;
    }

    setup() {
        super.setup();

        this.defaultMaxSize = this.collection.maxSize;

        if (!this.categoryScope) {
            this.categoryScope = this.scope + 'Category';
        }

        this.categoryField = this.getMetadata().get(`scopes.${this.categoryScope}.categoryField`) || this.categoryField;

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
        }
        else if (!this.expandedTogglerDisabled) {
            if (!this.getUser().isPortal()) {
                if (this.hasIsExpandedStoredValue()) {
                    this.isExpanded = this.getIsExpandedStoredValue();
                }
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
        this.applyRoutingParams(params);
    }

    applyRoutingParams(params) {
        if (!this.isExpanded) {
            if ('categoryId' in params) {
                if (params.categoryId !== this.currentCategoryId) {
                    this.openCategory(params.categoryId, params.categoryName);
                }
            }

            this.selectCurrentCategory();
        }
    }

    hasTextFilter() {
        if (this.collection.where) {
            for (let i = 0; i < this.collection.where.length; i++) {
                if (this.collection.where[i].type === 'textFilter') {
                    return true;
                }
            }
        }

        if (this.collection.data && this.collection.data.textFilter) {
            return true;
        }

        return false;
    }

    hasNavigationPanelStoredValue() {
        return this.getStorage().has('state', 'categories-navigation-panel-' + this.scope);
    }

    getNavigationPanelStoredValue() {
        const value = this.getStorage().get('state', 'categories-navigation-panel-' + this.scope);

        return value === 'true' || value === true;
    }

    setNavigationPanelStoredValue(value) {
        return this.getStorage().set('state', 'categories-navigation-panel-' + this.scope, value);
    }

    hasIsExpandedStoredValue() {
        return this.getStorage().has('state', 'categories-expanded-' + this.scope);
    }

    getIsExpandedStoredValue() {
        const value = this.getStorage().get('state', 'categories-expanded-' + this.scope);

        return value === 'true' || value === true ;
    }

    setIsExpandedStoredValue(value) {
        return this.getStorage().set('state', 'categories-expanded-' + this.scope, value);
    }

    afterRender() {
        this.$nestedCategoriesContainer = this.$el.find('.nested-categories-container');
        this.$listContainer = this.$el.find('.list-container');

        if (!this.hasView('list')) {
            if (!this.isExpanded) {
                this.hideListContainer();
            }

            this.loadList();
        }
        else {
            this.controlListVisibility();
        }

        if (
            !this.categoriesDisabled &&
            (this.isExpanded || this.hasNavigationPanel) &&
            !this.hasView('categories')
        ) {
            this.loadCategories();
        }

        if (!this.isExpanded && !this.hasView('nestedCategories')) {
            this.loadNestedCategories();
        }

        this.$el.focus();
    }

    // noinspection JSUnusedGlobalSymbols
    actionExpand() {
        this.isExpanded = true;

        this.setIsExpandedStoredValue(true);

        this.applyCategoryToCollection();

        this.clearView('nestedCategories');
        this.clearView('categories');

        this.getRouter().navigate('#' + this.scope);
        this.updateLastUrl();

        this.nestedCategoriesCollection = null;

        this.reRender();

        this.$listContainer.empty();

        this.collection.fetch();
    }

    // noinspection JSUnusedGlobalSymbols
    actionCollapse() {
        this.isExpanded = false;
        this.setIsExpandedStoredValue(false);

        this.applyCategoryToCollection();
        this.applyCategoryToNestedCategoriesCollection();

        this.clearView('categories');

        this.navigateToCurrentCategory();

        this.reRender();

        this.$listContainer.empty();

        this.collection.fetch();
    }

    // noinspection JSUnusedGlobalSymbols
    actionOpenCategory(data) {
        this.openCategory(data.id || null, data.name);

        this.selectCurrentCategory();
        this.navigateToCurrentCategory();
    }

    navigateToCurrentCategory() {
        let url = '#' + this.scope;

        if (!this.isExpanded && this.currentCategoryId) {
            url += '/list/categoryId=' + this.currentCategoryId;

            if (this._primaryFilter) {
                url += '&primaryFilter=' + this.getHelper().escapeString(this._primaryFilter);
            }
        } else {
            if (this._primaryFilter) {
                url += '/list/primaryFilter=' + this.getHelper().escapeString(this._primaryFilter);
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

    openCategory(id, name) {
        this.getNestedCategoriesView().isLoading = true;
        this.getNestedCategoriesView().reRender();
        this.getNestedCategoriesView().isLoading = false;

        this.nestedCategoriesCollection.reset();
        this.collection.reset();
        this.collection.offset = 0;
        this.collection.maxSize = this.defaultMaxSize;

        this.$listContainer.empty();

        this.currentCategoryId = id;
        this.currentCategoryName = name || id;

        this.applyCategoryToNestedCategoriesCollection();
        this.applyCategoryToCollection();

        this.collection.abortLastFetch();

        if (this.nestedCategoriesCollection) {
            this.nestedCategoriesCollection.abortLastFetch();

            this.hideListContainer();
            this.$nestedCategoriesContainer.addClass('hidden');

            Espo.Ui.notify(' ... ');

            Promise
                .all([
                    this.nestedCategoriesCollection.fetch().then(() => this.updateHeader()),
                    this.collection.fetch({openCategory: true})
                ])
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

    controlNestedCategoriesVisibility() {
        this.$nestedCategoriesContainer.removeClass('hidden');
    }

    getTreeCollection(callback) {
        this.getCollectionFactory().create(this.categoryScope)
            .then(collection => {
                collection.url = collection.entityType + '/action/listTree';
                collection.setOrder(null, null);

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

    getNestedCategoriesCollection(callback) {
        this.getCollectionFactory().create(this.categoryScope, collection => {
            this.nestedCategoriesCollection = collection;

            collection.setOrder(null, null);

            collection.url = collection.entityType + '/action/listTree';
            collection.maxDepth = null;
            collection.data.checkIfEmpty = true;

            if (!this.getAcl().checkScope(this.scope, 'create')) {
                collection.data.onlyNotEmpty = true;
            }

            this.applyCategoryToNestedCategoriesCollection();

            this.nestedCollectionIsBeingFetched = true;

            collection
                .fetch()
                .then(() => {
                    this.nestedCollectionIsBeingFetched = false;

                    this.controlNestedCategoriesVisibility();
                    this.controlListVisibility();

                    this.updateHeader();

                    callback.call(this, collection);
                });
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

    loadNestedCategories() {
        this.getNestedCategoriesCollection(collection => {
            this.createView('nestedCategories', 'views/record/list-nested-categories', {
                collection: collection,
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

    loadCategories() {
        this.getTreeCollection(collection => {
            this.createView('categories', 'views/record/list-tree', {
                collection: collection,
                selector: '.categories-container',
                selectable: true,
                showRoot: true,
                rootName: this.translate(this.scope, 'scopeNamesPlural'),
                buttonsDisabled: true,
                checkboxes: false,
                showEditLink: this.showEditLink,
                isExpanded: this.isExpanded,
                hasExpandedToggler: this.hasExpandedToggler,
                menuDisabled: !this.isExpanded && this.hasNavigationPanel,
                readOnly: true,
            }, view => {
                if (this.currentCategoryId) {
                    view.setSelected(this.currentCategoryId);
                }

                view.render();

                this.listenTo(view, 'select', model => {
                    if (!this.isExpanded) {
                        let id = null;
                        let name = null;

                        if (model && model.id) {
                            id = model.id;
                            name = model.get('name');
                        }

                        this.openCategory(id, name);
                        this.navigateToCurrentCategory();

                        return;
                    }

                    this.currentCategoryId = null;
                    this.currentCategoryName = '';

                    if (model && model.id) {
                        this.currentCategoryId = model.id;
                        this.currentCategoryName = model.get('name');
                    }

                    this.collection.offset = 0;
                    this.collection.maxSize = this.defaultMaxSize;
                    this.collection.reset();

                    this.applyCategoryToCollection();
                    this.collection.abortLastFetch();

                    Espo.Ui.notify(' ... ');

                    this.collection
                        .fetch()
                        .then(() => Espo.Ui.notify(false));
                });
            });

        });
    }

    applyCategoryToCollection() {
        this.collection.whereFunction = () => {
            let filter;
            const isExpanded = this.isExpanded;

            if (!isExpanded && !this.hasTextFilter()) {
                if (this.isCategoryMultiple()) {
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

    isCategoryMultiple() {
        return this.getMetadata()
            .get(['entityDefs', this.scope, 'fields', this.categoryField, 'type']) === 'linkMultiple';
    }

    getCreateAttributes() {
        let data;

        if (this.isCategoryMultiple()) {
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

    // noinspection JSUnusedGlobalSymbols
    actionManageCategories() {
        this.clearView('categories');
        this.clearView('nestedCategories');

        this.getRouter().navigate('#' + this.categoryScope, {trigger: true});
    }

    getHeader() {
        if (!this.nestedCategoriesCollection) {
            return super.getHeader();
        }

        const path = this.nestedCategoriesCollection.path;

        if (!path || path.length === 0) {
            return super.getHeader();
        }

        let rootUrl = '#' + this.scope;

        if (this._primaryFilter) {
            rootUrl += '/list/primaryFilter=' + this.getHelper().escapeString(this._primaryFilter);
        }

        const $root = $('<a>')
            .attr('href', rootUrl)
            .addClass('action')
            .text(this.translate(this.scope, 'scopeNamesPlural'))
            .addClass('action')
            .attr('data-action', 'openCategory');

        const list = [$root];

        const currentName = this.nestedCategoriesCollection.categoryData.name;
        const upperId = this.nestedCategoriesCollection.categoryData.upperId;
        const upperName = this.nestedCategoriesCollection.categoryData.upperName;

        if (path.length > 2) {
            list.push('...');
        }

        if (upperId) {
            let url = rootUrl + '/' + 'list/categoryId=' + this.getHelper().escapeString(upperId);

            if (this._primaryFilter) {
                url += '&primaryFilter=' + this.getHelper().escapeString(this._primaryFilter);
            }

            const $folder = $('<a>')
                .attr('href', url)
                .text(upperName)
                .addClass('action')
                .attr('data-action', 'openCategory')
                .attr('data-id', upperId)
                .attr('data-name', upperName);

            list.push($folder);
        }

        const $last = $('<span>').text(currentName);

        list.push($last);

        return this.buildHeaderHtml(list);
    }

    updateHeader() {
        this.getView('header').reRender();
    }

    hideListContainer() {
        this.$listContainer.addClass('hidden');
    }

    showListContainer() {
        this.$listContainer.removeClass('hidden');
    }

    // noinspection JSUnusedGlobalSymbols
    actionToggleNavigationPanel() {
        const value = !this.hasNavigationPanel;

        this.hasNavigationPanel = value;

        this.setNavigationPanelStoredValue(value);

        this.reRender().then(() => {
            this.loadNestedCategories();
        });
    }
}

export default ListWithCategories;
