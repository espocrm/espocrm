/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/email/list', ['views/list'], function (Dep) {

    /**
     * @class
     * @name Class
     * @memberOf module:views/email/list
     * @extends module:views/list.Class
     */
    return Dep.extend(/** @lends module:views/email/list.Class# */{

        createButton: false,

        template: 'email/list',

        folderId: null,

        folderScope: 'EmailFolder',

        selectedFolderId: null,

        defaultFolderId: 'inbox',

        keepCurrentRootUrl: true,

        /** @const */
        FOLDER_ALL: 'all',
        /** @const */
        FOLDER_INBOX: 'inbox',
        /** @const */
        FOLDER_IMPORTANT: 'important',
        /** @const */
        FOLDER_SENT: 'sent',
        /** @const */
        FOLDER_DRAFTS: 'drafts',
        /** @const */
        FOLDER_TRASH: 'trash',

        noDropFolderIdList: [
            'all',
            'sent',
            'drafts',
        ],

        events: {
            'mousedown .folders-container + .list-container a.link': function (e) {
                $(e.target).attr('draggable', 'true');
            },
            'dragstart a.link': function (e) {
                let $target = $(e.target);
                let id = $target.attr('data-id');

                e.originalEvent.dataTransfer.dropEffect = 'move';
                e.originalEvent.dataTransfer.effectAllowed = 'move';

                e.originalEvent.dataTransfer.setData('text/plain', id);
            },
            'dragenter .folder-list > li.droppable': function (e) {
                if (this.selectedFolderId === this.FOLDER_ALL) {
                    return;
                }

                let $target = $(e.target);

                $target.addClass('active');
                $target.find('a').css('pointer-events', 'none');
            },
            'dragleave .folder-list > li.droppable': function (e) {
                if (this.selectedFolderId === this.FOLDER_ALL) {
                    return;
                }

                let $target = $(e.target);

                $target.removeClass('active')
                $target.find('a').css('pointer-events', '');
            },
            'drop .folder-list > li.droppable': function (e) {
                if (this.selectedFolderId === this.FOLDER_ALL) {
                    return;
                }

                let $target = $(e.target);
                let folderId = $target.attr('data-id');
                let id = e.originalEvent.dataTransfer.getData('text/plain');

                $target.removeClass('active');
                $target.find('a').css('pointer-events', '');

                this.onDrop(id, folderId);
            },
            'dragover .folder-list > li.droppable': function (e) {
                e.preventDefault();
            },
            ...Dep.prototype.events,
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.addMenuItem('dropdown', false);

            if (this.getAcl().checkScope('EmailAccountScope')) {
                this.addMenuItem('dropdown', {
                    name: 'reply',
                    label: 'Email Accounts',
                    link: '#EmailAccount/list/userId=' + this.getUser().id + '&userName=' +
                        encodeURIComponent(this.getUser().get('name'))
                });
            }

            if (this.getUser().isAdmin()) {
                this.addMenuItem('dropdown', {
                    link: '#InboundEmail',
                    label: 'Inbound Emails'
                });
            }

            this.foldersDisabled = this.foldersDisabled ||
                this.getConfig().get('emailFoldersDisabled') ||
                this.getMetadata().get(['scopes', this.folderScope, 'disabled']) ||
                !this.getAcl().checkScope(this.folderScope);

            var params = this.options.params || {};

            this.selectedFolderId = params.folder || this.defaultFolderId;

            if (this.foldersDisabled) {
                this.selectedFolderId = null;
            }

            this.applyFolder();
        },

        data: function () {
            var data = {};
            data.foldersDisabled = this.foldersDisabled;

            return data;
        },

        /**
         * @param {Object.<string,*>} [data]
         */
        actionComposeEmail: function (data) {
            data = data || {};

            this.notify('Loading...');

            let viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
                'views/modals/compose-email';

            let options = {
                attributes: {
                    status: 'Draft',
                },
                focusForCreate: data.focusForCreate,
            };

            this.createView('quickCreate', viewName, options, (view) => {
                view.render();
                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    this.collection.fetch();
                });
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (!this.foldersDisabled && !this.hasView('folders')) {
                this.loadFolders();
            }
        },

        getFolderCollection: function (callback) {
            this.getCollectionFactory().create(this.folderScope, (collection) => {
                collection.url = 'EmailFolder/action/listAll';
                collection.maxSize = 200;

                this.listenToOnce(collection, 'sync', () =>{
                    callback.call(this, collection);
                });

                collection.fetch();
            });
        },

        loadFolders: function () {
            var xhr = null;

            this.getFolderCollection(collection => {
                collection.forEach(model => {
                    if (this.noDropFolderIdList.indexOf(model.id) === -1) {
                        model.droppable = true;
                    }
                });

                this.createView('folders', 'views/email-folder/list-side', {
                    collection: collection,
                    emailCollection: this.collection,
                    el: this.options.el + ' .folders-container',
                    showEditLink: this.getAcl().check(this.folderScope, 'edit'),
                    selectedFolderId: this.selectedFolderId,
                }, function (view) {
                    view.render();

                    this.listenTo(view, 'select', (id) => {
                        this.selectedFolderId = id;
                        this.applyFolder();

                        if (xhr && xhr.readyState < 4) {
                            xhr.abort();
                        }

                        this.notify(this.translate('pleaseWait', 'messages'));

                        xhr = this.collection
                            .fetch()
                            .then(() => this.notify(false));

                        if (id !== this.defaultFolderId) {
                            this.getRouter().navigate('#Email/list/folder=' + id);
                        } else {
                            this.getRouter().navigate('#Email');
                        }

                        this.updateLastUrl();
                    });
                });
            });
        },

        applyFolder: function () {
            this.collection.selectedFolderId = this.selectedFolderId;

            if (!this.selectedFolderId) {
                this.collection.whereFunction = null;

                return;
            }

            this.collection.whereFunction = () => {
                return [
                    {
                        type: 'inFolder',
                        attribute: 'folderId',
                        value: this.selectedFolderId,
                    }
                ];
            };
        },

        applyRoutingParams: function (params) {
            var id;

            if ('folder' in params) {
                id = params.folder || 'inbox';
            } else {
                return;
            }

            if (!params.isReturnThroughLink && id !== this.selectedFolderId) {
                var foldersView = this.getView('folders');

                if (foldersView) {
                    foldersView.actionSelectFolder(id);
                    foldersView.reRender();
                    $(window).scrollTop(0);
                }
            }
        },

        onDrop: function (id, folderId) {
            if (folderId === this.FOLDER_IMPORTANT) {
                this.getRecordView().actionMarkAsImportant({id: id});

                return;
            }

            if (this.selectedFolderId === this.FOLDER_TRASH) {
                if (folderId === this.FOLDER_TRASH) {
                    return;
                }

                this.getRecordView().actionRetrieveFromTrashMoveToFolder({id: id, folderId: folderId});

                return;
            }

            if (folderId === this.FOLDER_TRASH) {
                this.getRecordView().actionMoveToTrash({id: id});

                return;
            }

            this.getRecordView().actionMoveToFolder({id: id, folderId: folderId});
        },

        /**
         * @protected
         * @return {module:views/email/record/list.Class}
         */
        getRecordView: function () {
            return this.getView('list');
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyCtrlSpace: function (e) {
            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
                return;
            }

            if (!this.getAcl().checkScope(this.scope, 'create')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionComposeEmail({focusForCreate: true});
        },
    });
});
