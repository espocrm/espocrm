/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/list-with-categories', 'views/list', function (Dep) {

    return Dep.extend({

        template: 'list-with-categories',

        quickCreate: true,

        storeViewAfterCreate: true,

        storeViewAfterUpdate: true,

        currentCategoryId: null,

        currentCategoryName: '',

        categoryScope: null,

        categoryField: 'category',

        categoryFilterType: 'inCategory',

        isExpanded: false,

        hasExpandedToggler: true,

        expandedTogglerDisabled: false,

        keepCurrentRootUrl: true,

        hasNavigationPanel: false,

        data: function () {
            var data = {};

            data.hasTree = (this.isExpanded || this.hasNavigationPanel) && !this.categoriesDisabled;

            data.hasNestedCategories = !this.isExpanded;

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (!this.categoryScope) {
                this.categoryScope = this.scope + 'Category';
            }

            var isExpandedByDefault = this.getMetadata()
                .get(['clientDefs', this.categoryScope, 'isExpandedByDefault']) || false;

            if (isExpandedByDefault) {
                this.isExpanded = true;
            }

            var isCollapsedByDefault = this.getMetadata()
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
            }

            var params = this.options.params || {};

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
        },

        prepareCreateReturnDispatchParams: function (params) {
            if (this.currentCategoryId) {
                params.options.categoryId = this.currentCategoryId;
                params.options.categoryName = this.currentCategoryName;
            }
        },

        applyRoutingParams: function (params) {
            if (!this.isExpanded) {
                if ('categoryId' in params) {
                    if (params.categoryId !== this.currentCategoryId) {
                        this.openCategory(params.categoryId, params.categoryName);
                    }
                }

                this.selectCurrentCategory();
            }
        },

        hasTextFilter: function () {
            if (this.collection.where) {
                for (var i = 0; i < this.collection.where.length; i++) {
                    if (this.collection.where[i].type === 'textFilter') {
                        return true;
                    }
                }
            }

            if (this.collection.data && this.collection.data.textFilter) {
                return true;
            }

            return false;
        },


        hasNavigationPanelStoredValue: function () {
            return this.getStorage().has('state', 'categories-navigation-panel-' + this.scope);
        },

        getNavigationPanelStoredValue: function () {
            var value = this.getStorage().get('state', 'categories-navigation-panel-' + this.scope);

            return value === 'true' || value === true;
        },

        setNavigationPanelStoredValue: function (value) {
            return this.getStorage().set('state', 'categories-navigation-panel-' + this.scope, value);
        },

        hasIsExpandedStoredValue: function () {
            return this.getStorage().has('state', 'categories-expanded-' + this.scope);
        },

        getIsExpandedStoredValue: function () {
            var value = this.getStorage().get('state', 'categories-expanded-' + this.scope);
            return value === 'true' || value === true ;
        },

        setIsExpandedStoredValue: function (value) {
            return this.getStorage().set('state', 'categories-expanded-' + this.scope, value);
        },

        afterRender: function () {
            this.$nestedCategoriesContainer = this.$el.find('.nested-categories-container');
            this.$listContainer = this.$el.find('.list-container');

            if (!this.hasView('list')) {
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
        },

        actionExpand: function () {
            this.isExpanded = true;

            this.setIsExpandedStoredValue(true);

            this.applyCategoryToCollection();

            this.clearView('nestedCategories');
            this.clearView('categories');

            this.getRouter().navigate('#' + this.scope);
            this.updateLastUrl();

            this.reRender();

            this.$listContainer.empty();

            this.collection.fetch();
        },

        actionCollapse: function () {
            this.isExpanded = false;
            this.setIsExpandedStoredValue(false);

            this.applyCategoryToCollection();
            this.applyCategoryToNestedCategoriesCollection();

            this.clearView('categories');

            this.navigateToCurrentCategory();

            this.reRender();

            this.$listContainer.empty();

            this.collection.fetch();
        },

        actionOpenCategory: function (data) {
            this.hideListViewWhileNestedCategoriesLoaded();

            this.openCategory(data.id || null, data.name);

            this.selectCurrentCategory();

            this.navigateToCurrentCategory();
        },

        navigateToCurrentCategory: function () {
            if (!this.isExpanded) {
                if (this.currentCategoryId) {
                    this.getRouter().navigate('#' + this.scope + '/list/categoryId=' + this.currentCategoryId);
                }
                else {
                    this.getRouter().navigate('#' + this.scope);
                }
            }
            else {
                this.getRouter().navigate('#' + this.scope);
            }

            this.updateLastUrl();
        },

        selectCurrentCategory: function () {
            var categoriesView = this.getView('categories');
            if (categoriesView) {
                categoriesView.setSelected(this.currentCategoryId);
                categoriesView.reRender();
            }
        },

        openCategory: function (id, name) {
            this.getView('nestedCategories').isLoading = true;
            this.getView('nestedCategories').reRender();
            this.getView('nestedCategories').isLoading = false;

            this.nestedCategoriesCollection.reset();
            this.collection.reset();

            this.$listContainer.empty();

            this.currentCategoryId = id;
            this.currentCategoryName = name || id;

            this.applyCategoryToNestedCategoriesCollection();

            this.applyCategoryToCollection();

            this.collection.abortLastFetch();

            if (this.nestedCategoriesCollection) {
                this.nestedCategoriesCollection.abortLastFetch();

                Espo.Ui.notify(this.translate('loading', 'messages'));

                Promise.all([
                    this.nestedCategoriesCollection
                        .fetch()
                        .then(() => {
                            this.controlNestedCategoriesVisibility();
                            this.updateHeader();
                        }),

                    this.collection.fetch({openCategory: true})
                ]).then(() => {
                    Espo.Ui.notify(false);

                    this.controlListVisibility();
                });
            }
            else {
                this.collection.fetch().then(() => {
                    Espo.Ui.notify(false);
                });
            }
        },

        controlListVisibility: function () {
            if (this.isExpanded) {
                this.$listContainer.removeClass('hidden');

                return;
            }

            if (
                !this.collection.models.length &&
                this.nestedCategoriesCollection &&
                this.nestedCategoriesCollection.models.length &&
                !this.hasTextFilter()
            ) {
                this.$listContainer.addClass('hidden');
            }
            else {
                this.$listContainer.removeClass('hidden');
            }
        },

        controlNestedCategoriesVisibility: function () {
            this.$nestedCategoriesContainer.removeClass('hidden');
        },

        getTreeCollection: function (callback) {
            this.getCollectionFactory().create(this.categoryScope, collection => {
                collection.url = collection.name + '/action/listTree';

                collection.setOrder(null, null);

                this.collection.treeCollection = collection;

                this.listenToOnce(collection, 'sync', () => {
                    callback.call(this, collection);
                });

                collection.fetch();
            });
        },

        applyCategoryToNestedCategoriesCollection: function () {
            if (!this.nestedCategoriesCollection) {
                return;
            }

            this.nestedCategoriesCollection.where = null;

            var filter;

            this.nestedCategoriesCollection.parentId = this.currentCategoryId;

            this.nestedCategoriesCollection.currentCategoryId = this.currentCategoryId;
            this.nestedCategoriesCollection.currentCategoryName = this.currentCategoryName || this.currentCategoryId;

            this.nestedCategoriesCollection.where = [filter];
        },

        getNestedCategoriesCollection: function (callback) {
            this.getCollectionFactory().create(this.categoryScope, collection => {
                this.nestedCategoriesCollection = collection;

                collection.setOrder(null, null);

                collection.url = collection.name + '/action/listTree';

                collection.maxDepth = 1;

                collection.data.checkIfEmpty = true;

                if (!this.getAcl().checkScope(this.scope, 'create')) {
                    collection.data.onlyNotEmpty = true;
                }

                this.applyCategoryToNestedCategoriesCollection();

                collection.fetch().then(() => {
                    this.controlListVisibility();
                    this.controlNestedCategoriesVisibility();

                    this.updateHeader();

                    callback.call(this, collection);
                });
            });
        },

        loadNestedCategories: function () {
            this.getNestedCategoriesCollection(collection => {
                this.createView('nestedCategories', 'views/record/list-nested-categories', {
                    collection: collection,
                    el: this.options.el + ' .nested-categories-container',
                    showEditLink: this.getAcl().check(this.categoryScope, 'edit'),
                    isExpanded: this.isExpanded,
                    hasExpandedToggler: this.hasExpandedToggler,
                    hasNavigationPanel: this.hasNavigationPanel,
                }, view => {
                    view.render();
                });
            });
        },

        loadCategories: function () {
            this.getTreeCollection(collection => {
                this.createView('categories', 'views/record/list-tree', {
                    collection: collection,
                    el: this.options.el + ' .categories-container',
                    selectable: true,
                    showRoot: true,
                    rootName: this.translate(this.scope, 'scopeNamesPlural'),
                    buttonsDisabled: true,
                    checkboxes: false,
                    showEditLink: this.getAcl().check(this.categoryScope, 'edit'),
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
                            var id = null;
                            var name = null;

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

                        this.applyCategoryToCollection();

                        this.collection.abortLastFetch();

                        Espo.Ui.notify(this.translate('loading', 'messages'));

                        this.listenToOnce(this.collection, 'sync', () => {
                            this.notify(false);
                        });

                        this.collection.fetch();

                    });
                });

            });
        },

        applyCategoryToCollection: function () {
            this.collection.whereFunction = () => {
                var filter;
                var isExpanded = this.isExpanded;

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
        },

        isCategoryMultiple: function () {
            return this.getMetadata()
                .get(['entityDefs', this.scope, 'fields', this.categoryField, 'type']) === 'linkMultiple';
        },

        getCreateAttributes: function () {
            if (this.isCategoryMultiple()) {
                if (this.currentCategoryId) {
                    var names = {};

                    names[this.currentCategoryId] = this.getCurrentCategoryName();

                    var data = {};

                    var idsAttribute = this.categoryField + 'Ids';
                    var namesAttribute = this.categoryField + 'Names';

                    data[idsAttribute] = [this.currentCategoryId],
                    data[namesAttribute] = names;

                    return data;
                }
            }
            else {
                var idAttribute = this.categoryField + 'Id';
                var nameAttribute = this.categoryField + 'Name';

                var data = {};

                data[idAttribute] = this.currentCategoryId;
                data[nameAttribute] = this.getCurrentCategoryName();

                return data;
            }
        },

        getCurrentCategoryName: function () {
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

            return this.currentCatetgoryId;
        },

        actionManageCategories: function () {
            this.clearView('categories');
            this.clearView('nestedCategories');

            this.getRouter().navigate('#' + this.categoryScope, {trigger: true});
        },

        getHeader: function () {
            if (!this.nestedCategoriesCollection) {
                return Dep.prototype.getHeader.call(this);
            }

            var path = this.nestedCategoriesCollection.path;

            if (!path || path.length === 0) {
                return Dep.prototype.getHeader.call(this);
            }

            var rootUrl = '#' + this.scope;

            var list = [
                '<a href="' + rootUrl + '" class="action">' +
                    this.translate(this.scope, 'scopeNamesPlural') + '</a>',
            ];

            var currentName = this.nestedCategoriesCollection.categoryData.name;

            var upperId = this.nestedCategoriesCollection.categoryData.upperId;
            var upperName = this.nestedCategoriesCollection.categoryData.upperName;

            if (path.length > 2) {
                list.push(
                    '...'
                );
            }

            if (upperId) {
                var url = rootUrl + '/' + 'list/categoryId=' + this.escapeString(upperId);

                list.push(
                    '<a href="' + url +'">' + this.escapeString(upperName) + '</a>'
                );
            }

            list.push(
                this.escapeString(currentName)
            );

            return this.buildHeaderHtml(list);
        },

        updateHeader: function () {
            this.getView('header').reRender();
        },

        hideListViewWhileNestedCategoriesLoaded: function () {
            this.$listContainer.addClass('hidden');

            this.nestedCategoriesCollection.once('sync', () => {
                this.$listContainer.removeClass('hidden');
            });
        },

        actionToggleNavigationPanel: function () {
            let value = !this.hasNavigationPanel;

            this.hasNavigationPanel = value;

            this.setNavigationPanelStoredValue(value);

            this.reRender().then(() => {
                this.loadNestedCategories();
            });
        },
    });
});
