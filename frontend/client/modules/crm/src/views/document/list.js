/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

        currentFolderId: null,

        currentFolderName: '',

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (!this.hasView('folders')) {
                this.loadFolders();
            }
        },

        getTreeCollection: function (callback) {
            this.getCollectionFactory().create('DocumentFolder', function (collection) {
                collection.url = collection.name + '/action/listTree';

                this.collection.treeCollection = collection;

                this.listenToOnce(collection, 'sync', function () {
                    callback.call(this, collection);
                }, this);
                collection.fetch();

            }, this);
        },

        loadFolders: function () {
            this.getTreeCollection(function (collection) {
                this.createView('folders', 'views/record/list-tree', {
                    collection: collection,
                    el: this.options.el + ' .folders-container',
                    selectable: true,
                    createDisabled: true,
                    showRoot: true,
                    rootName: this.translate('Document', 'scopeNamesPlural'),
                    buttonsDisabled: true,
                    checkboxes: false,
                    showEditLink: this.getAcl().check('DocumentFolder', 'edit')
                }, function (view) {
                    view.render();

                    this.listenTo(view, 'select', function (model) {
                        this.currentFolderId = null;
                        this.currentFolderName = '';

                        if (model && model.id) {
                            this.currentFolderId = model.id;
                            this.currentFolderName = model.get('name');
                        }
                        this.collection.whereAdditional = null;

                        if (this.currentFolderId) {
                            this.collection.whereAdditional = [
                                {
                                    field: 'folder',
                                    type: 'inCategory',
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
            /*this.getCollectionFactory().create('DocumentFolder', function (collection) {
                collection.url = collection.name + '/action/listTree';

                this.collection.treeCollection = collection;

                this.listenToOnce(collection, 'sync', function () {

                }, this);
                collection.fetch();
            }, this);*/
        },

        getCreateAttributes: function () {
            return {
                folderId: this.currentFolderId,
                folderName: this.currentFolderName
            };
        },

    });

});
