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

Espo.define('views/list-with-categories', 'views/list', function (Dep) {

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

        data: function () {
            var data = {};
            data.categoriesDisabled = this.categoriesDisabled;
            data.isExpanded = this.isExpanded;
            data.hasExpandedToggler = this.hasExpandedToggler;
            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (!this.categoryScope) {
                this.categoryScope = this.scope + 'Category';
            }

            this.categoriesDisabled =
                this.categoriesDisabled ||
                this.getMetadata().get(['scopes', this.categoryScope, 'disabled']) ||
                !this.getAcl().checkScope(this.categoryScope);

            if (this.categoriesDisabled) {
                this.isExpanded = true;
                this.hasExpandedToggler = false;
            } else {
                if (!this.expandedTogglerDisabled) {
                    if (!this.getUser().isPortal()) {
                        if (this.hasIsExpandedStoredValue()) {
                            this.isExpanded = this.getIsExpandedStoredValue();
                        }
                    } else {
                        this.hasExpandedToggler = false;
                        this.isExpanded = false;
                    }
                }
            }

            var params = this.options.params || {};

            if ('categoryId' in params) {
                this.currentCategoryId = params.categoryId;
            }

            this.applyCategoryToCollection();

            this.listenTo(this.collection, 'sync', function (c, d, o) {
                if (o && o.openCategory) return;
                this.controlListVisibility();
            }, this);
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
            } else {
                this.controlListVisibility();
            }
            if (!this.categoriesDisabled && !this.hasView('categories')) {
                this.loadCategories();
            }
            if (!this.isExpanded  && !this.hasView('nestedCategories')) {
                this.loadNestedCategories();
            }
        },

        actionExpand: function () {
            this.isExpanded = true;
            this.setIsExpandedStoredValue(true);

            this.applyCategoryToCollection();

            this.clearView('nestedCategories');
            if (this.hasView('categories')) {
                this.getView('categories').isExpanded = true;
            }

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

            if (this.hasView('categories')) {
                this.getView('categories').isExpanded = false;
            }

            this.navigateToCurrentCategory();

            this.reRender();
            this.$listContainer.empty();

            this.collection.fetch();
        },

        actionOpenCategory: function (data) {
            this.openCategory(data.id, data.name);
            this.selectCurrentCategory();
            this.navigateToCurrentCategory();
        },

        navigateToCurrentCategory: function () {
            if (!this.isExpanded) {
                if (this.currentCategoryId) {
                    this.getRouter().navigate('#' + this.scope + '/list/categoryId=' + this.currentCategoryId);
                } else {
                    this.getRouter().navigate('#' + this.scope);
                }
            } else {
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
                    this.nestedCategoriesCollection.fetch().then(function () {
                        this.controlNestedCategoriesVisibility();
                    }.bind(this)),
                    this.collection.fetch({openCategory: true})
                ]).then(function () {
                    Espo.Ui.notify(false);
                    this.controlListVisibility();
                }.bind(this));
            } else {
                this.collection.fetch().then(function () {
                    Espo.Ui.notify(false);
                }.bind(this));
            }
        },

        controlListVisibility: function () {
            if (this.isExpanded) {
                this.$listContainer.removeClass('hidden');
                return;
            }
            if (!this.collection.models.length && this.nestedCategoriesCollection && this.nestedCategoriesCollection.models.length) {
                this.$listContainer.addClass('hidden');
            } else {
                this.$listContainer.removeClass('hidden');
            }
        },

        controlNestedCategoriesVisibility: function () {
            if (this.nestedCategoriesCollection.models.length) {
                this.$nestedCategoriesContainer.removeClass('hidden');
            } else {
                this.$nestedCategoriesContainer.addClass('hidden');
            }
        },

        getTreeCollection: function (callback) {
            this.getCollectionFactory().create(this.categoryScope, function (collection) {
                collection.url = collection.name + '/action/listTree';

                this.collection.treeCollection = collection;

                this.listenToOnce(collection, 'sync', function () {
                    callback.call(this, collection);
                }, this);
                collection.fetch();
            }, this);
        },

        applyCategoryToNestedCategoriesCollection: function () {
            if (!this.nestedCategoriesCollection) return;

            this.nestedCategoriesCollection.where = null;

            var filter;

            this.nestedCategoriesCollection.parentId = this.currentCategoryId;

            this.nestedCategoriesCollection.currentCategoryId = this.currentCategoryId;
            this.nestedCategoriesCollection.currentCategoryName = this.currentCategoryName || this.currentCategoryId;

            this.nestedCategoriesCollection.where = [filter];
        },

        getNestedCategoriesCollection: function (callback) {
            this.getCollectionFactory().create(this.categoryScope, function (collection) {
                this.nestedCategoriesCollection = collection;

                collection.url = collection.name + '/action/listTree';

                collection.maxDepth = 1;

                collection.data.checkIfEmpty = true;
                if (!this.getAcl().checkScope(this.scope, 'create')) {
                    collection.data.onlyNotEmpty = true;
                }

                this.applyCategoryToNestedCategoriesCollection();

                collection.fetch().then(function () {
                    this.controlListVisibility();
                    this.controlNestedCategoriesVisibility();
                    callback.call(this, collection);
                }.bind(this));
            }, this);
        },

        loadNestedCategories: function () {
            this.getNestedCategoriesCollection(function (collection) {
                this.createView('nestedCategories', 'views/record/list-nested-categories', {
                    collection: collection,
                    el: this.options.el + ' .nested-categories-container'
                }, function (view) {
                    view.render();
                });
            });
        },

        loadCategories: function () {
            this.getTreeCollection(function (collection) {
                this.createView('categories', 'views/record/list-tree', {
                    collection: collection,
                    el: this.options.el + ' .categories-container',
                    selectable: true,
                    createDisabled: true,
                    showRoot: true,
                    rootName: this.translate(this.scope, 'scopeNamesPlural'),
                    buttonsDisabled: true,
                    checkboxes: false,
                    showEditLink: this.getAcl().check(this.categoryScope, 'edit'),
                    isExpanded: this.isExpanded,
                    hasExpandedToggler: this.hasExpandedToggler
                }, function (view) {
                    if (this.currentCategoryId) {
                        view.setSelected(this.currentCategoryId);
                    }
                    view.render();

                    this.listenTo(view, 'select', function (model) {
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

                        this.notify('Please wait...');
                        this.listenToOnce(this.collection, 'sync', function () {
                            this.notify(false);
                        }, this);
                        this.collection.fetch();

                    }, this);
                }, this);

            }, this);
        },

        applyCategoryToCollection: function () {

            this.collection.whereFunction = function () {
                var filter;
                var isExpanded = this.isExpanded;

                var hasTextFilter = false;
                if (this.collection.where) {
                    for (var i = 0; i < this.collection.where.length; i++) {
                        if (this.collection.where[i].type === 'textFilter') {
                            hasTextFilter = true;
                            break;
                        }
                    }
                }

                if (this.collection.data && this.collection.data.textFilter) {
                    hasTextFilter = true;
                }

                if (!isExpanded && !hasTextFilter) {
                    if (this.isCategoryMultiple()) {
                        if (this.currentCategoryId) {
                            filter = {
                                attribute: this.categoryField,
                                type: 'linkedWith',
                                value: [this.currentCategoryId]
                            };
                        } else {
                            filter = {
                                attribute: this.categoryField,
                                type: 'isNotLinked'
                            };
                        }
                    } else {
                        if (this.currentCategoryId) {
                            filter = {
                                attribute: this.categoryField + 'Id',
                                type: 'equals',
                                value: this.currentCategoryId
                            };
                        } else {
                            filter = {
                                attribute: this.categoryField + 'Id',
                                type: 'isNull'
                            };
                        }
                    }
                } else {
                    if (this.currentCategoryId) {
                        filter = {
                            field: this.categoryField,
                            type: this.categoryFilterType,
                            value: this.currentCategoryId
                        };
                    }
                }
                if (filter) {
                    return [filter];
                }

            }.bind(this);
        },

        isCategoryMultiple: function () {
            return this.getMetadata().get(['entityDefs', this.scope, 'fields', this.categoryField, 'type']) === 'linkMultiple';
        },

        getCreateAttributes: function () {
            var fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', this.categoryField, 'type']);

            if (this.isCategoryMultiple()) {
                if (this.currentCategoryId) {
                    var names = {};
                    names[this.currentCategoryId] = this.currentCategoryName;
                    var data = {};
                    var idsAttribute = this.categoryField + 'Ids';
                    var namesAttribute = this.categoryField + 'Names';
                    data[idsAttribute] = [this.currentCategoryId],
                    data[namesAttribute] = names;
                    return data;
                }
            } else {
                var idAttribute = this.categoryField + 'Id';
                var nameAttribute = this.categoryField + 'Name';
                var data = {};
                data[idAttribute] = this.currentCategoryId;
                data[nameAttribute] = this.currentCategoryName;
                return data;
            }
        },

        actionManageCategories: function () {
            this.clearView('categories');
            this.getRouter().navigate('#' + this.categoryScope, {trigger: true});
        }

    });
});
