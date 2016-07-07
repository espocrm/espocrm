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

Espo.define('views/email/list', 'views/list', function (Dep) {

    return Dep.extend({

        createButton: false,

        template: 'email/list',

        folderId: null,

        folderScope: 'EmailFolder',

        currentFolderId: null,

        defaultFolderId: 'inbox',

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getUser().isAdmin()) {
                this.menu.dropdown.push({
                    link: '#InboundEmail',
                    label: 'Inbound Emails'
                });
            }

            this.foldersDisabled = this.foldersDisabled ||
                                   this.getMetadata().get('scopes.' + this.folderScope + '.disabled') ||
                                   !this.getAcl().checkScope(this.folderScope);

            var params = this.options.params || {};

            this.selectedFolderId = params.folder || this.defaultFolderId;

            this.applyFolder();
        },

        data: function () {
            var data = {};
            data.foldersDisabled = this.foldersDisabled;
            return data;
        },

        actionComposeEmail: function () {
            this.notify('Loading...');
            var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
            this.createView('quickCreate', viewName, {
                attributes: {
                    status: 'Draft'
                }
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();
                }, this);
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (!this.foldersDisabled && !this.hasView('folders')) {
                this.loadFolders();
            }
        },

        getFolderCollection: function (callback) {
            this.getCollectionFactory().create(this.folderScope, function (collection) {
                collection.url = 'EmailFolder/action/listAll';

                this.collection.folderCollection = collection;

                this.listenToOnce(collection, 'sync', function () {
                    callback.call(this, collection);
                }, this);
                collection.fetch();
            }, this);
        },

        loadFolders: function () {
            this.getFolderCollection(function (collection) {
                this.createView('folders', 'views/email-folder/list-side', {
                    collection: collection,
                    emailCollection: this.collection,
                    el: this.options.el + ' .folders-container',
                    showEditLink: this.getAcl().check(this.folderScope, 'edit'),
                    selectedFolderId: this.selectedFolderId
                }, function (view) {
                    view.render();
                    this.listenTo(view, 'select', function (id) {
                        this.selectedFolderId = id;
                        this.applyFolder();

                        this.notify('Please wait...');
                        this.collection.fetch().then(function () {
                            this.notify(false);
                        }.bind(this));

                        if (id !== this.defaultFolderId) {
                            this.getRouter().navigate('#Email/list/folder=' + id);
                        } else {
                            this.getRouter().navigate('#Email');
                        }
                    }, this);
                }, this);
            }, this);
        },

        applyFolder: function () {
            this.collection.data.folderId = this.selectedFolderId;
        }

    });
});

