/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/document/list', 'views/list', function (Dep) {

    return Dep.extend({

        template: 'crm:document/list',

        quickCreate: true,

        currentCategoryId: null,

        currentCategoryName: '',

        categoryScope: 'DocumentFolder',

        categoryField: 'folder',

        categoryFilterType: 'inCategory',

        data: function () {
            var data = {};
            data.categoriesDisabled = this.categoriesDisabled;
            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.categoriesDisabled = this.categoriesDisabled ||
                                   this.getMetadata().get('scopes.' + this.categoryScope + '.disabled') ||
                                   !this.getAcl().checkScope(this.categoryScope);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (!this.categoriesDisabled && !this.hasView('categories')) {
                this.loadCategories();
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
                    showEditLink: this.getAcl().check(this.categoryScope, 'edit')
                }, function (view) {
                    view.render();

                    this.listenTo(view, 'select', function (model) {
                        this.currentCategoryId = null;
                        this.currentCategoryName = '';

                        if (model && model.id) {
                            this.currentCategoryId = model.id;
                            this.currentCategoryName = model.get('name');
                        }
                        this.collection.whereAdditional = null;

                        if (this.currentCategoryId) {
                            this.collection.whereAdditional = [
                                {
                                    field: this.categoryField,
                                    type: this.categoryFilterType,
                                    value: model.id
                                }
                            ];
                        }

                        this.notify('Please wait...');
                        this.listenToOnce(this.collection, 'sync', function () {
                            this.notify(false);
                        }, this);
                        this.collection.fetch();

                    }, this);
                }, this);

            }, this);
        },

        getCreateAttributes: function () {
            return {
                folderId: this.currentCategoryId,
                folderName: this.currentCategoryName
            };
        }

    });

});
