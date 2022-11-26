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
            'sent',
            'drafts',
        ],

        /**
         * @inheritDoc
         */
        createListRecordView: function (fetch) {
            return Dep.prototype.createListRecordView.call(this, fetch)
                .then(view => {
                    this.listenTo(view, 'after:render', () => this.initDraggable(null));
                    this.listenTo(view, 'after:show-more', fromIndex => this.initDraggable(fromIndex));
                });
        },

        /**
         * @private
         */
        initDroppable: function () {
            this.$el.find('.folders-container .folder-list > .droppable')
                .droppable({
                    accept: '.list-row',
                    tolerance: 'pointer',
                    over: (e) => {
                        if (!this.isDroppable(e)) {
                            return;
                        }

                        let $target = $(e.target);

                        $target.removeClass('success');
                        $target.addClass('active');
                        $target.find('a').css('pointer-events', 'none');
                    },
                    out: (e) => {
                        if (!this.isDroppable(e)) {
                            return;
                        }

                        let $target = $(e.target);

                        $target.removeClass('active')
                        $target.find('a').css('pointer-events', '');
                    },
                    drop: (e, ui) => {
                        if (!this.isDroppable(e)) {
                            return;
                        }

                        let $target = $(e.target);
                        let $helper = $(ui.helper);

                        $target.find('a').css('pointer-events', '');

                        let folderId = $target.attr('data-id');

                        let id = $helper.attr('data-id');
                        id = id === '' ? true : id;

                        this.onDrop(folderId, id);

                        $target.addClass('success');

                        setTimeout(() => {
                            $target.removeClass('success');
                            $target.removeClass('active');
                        }, 1000);
                    },
                });
        },

        /**
         * @private
         * @param {?Number} fromIndex
         */
        initDraggable: function (fromIndex) {
            fromIndex = fromIndex || 0;

            let $container = this.$el.find('.list-container > .list');

            const recordView = this.getRecordView();

            this.collection.models.slice(fromIndex).forEach(m => {
                let $row = $container.find(`.list-row[data-id="${m.id}"]`).first();

                $row.draggable({
                    helper: () => {
                        let text = this.translate('Moving to Folder', 'labels', 'Email');

                        if (
                            recordView.isIdChecked(m.id) &&
                            !recordView.allResultIsChecked &&
                            recordView.checkedList.length > 1
                        ) {
                            text += ' · ' + recordView.checkedList.length;
                        }

                        let draggedId = m.id;

                        if (
                            recordView.isIdChecked(m.id) &&
                            !recordView.allResultIsChecked
                        ) {
                            draggedId = '';
                        }

                        return $('<div>')
                            .attr('data-id', draggedId)
                            .css('cursor', 'grabbing')
                            .addClass('draggable-helper')
                            .text(text);
                    },
                    distance: 8,
                    containment: this.$el,
                    appendTo: 'body',
                    cursor: 'grabbing',
                    cursorAt: {
                        top: 0,
                        left: 0,
                    },
                    start: (e) => {
                        let $target = $(e.target);

                        $target.closest('tr').addClass('active');
                    },
                    stop: () => {
                        if (!recordView.isIdChecked(m.id)) {
                            $container.find(`.list-row[data-id="${m.id}"]`).first().removeClass('active');
                        }
                    },
                });
            });
        },

        isDroppable: function (e) {
            let $target = $(e.target);
            let folderId = $target.attr('data-id');

            if (this.selectedFolderId === this.FOLDER_DRAFTS) {
                return false;
            }

            if (this.selectedFolderId === this.FOLDER_SENT && folderId === this.FOLDER_INBOX) {
                return false;
            }

            if (this.selectedFolderId === this.FOLDER_ALL) {
                if (folderId.indexOf('group:') === 0) {
                    return true;
                }

                return false;
            }

            if (folderId === this.FOLDER_ALL) {
                if (this.selectedFolderId.indexOf('group:') === 0) {
                    return true;
                }

                return false;
            }

            if (this.selectedFolderId === this.FOLDER_DRAFTS) {
                if (folderId.indexOf('group:') === 0) {
                    return true;
                }

                if (folderId === this.FOLDER_TRASH) {
                    return false;
                }

                return true;
            }

            return true;
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

            this.initEmailShortcuts();

            this.on('remove', () => {
                $(window).off('resize.email-folders');
                $(window).off('scroll.email-folders');
            });
        },

        data: function () {
            var data = {};
            data.foldersDisabled = this.foldersDisabled;

            return data;
        },

        initEmailShortcuts: function () {
            this.shortcutKeys['Control+Delete'] = e => {
                if (!this.hasSelectedRecords()) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                this.getRecordView().massActionMoveToTrash();
            };

            this.shortcutKeys['Control+KeyI'] = e => {
                if (!this.hasSelectedRecords()) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                this.getRecordView().toggleMassMarkAsImportant();
            };

            this.shortcutKeys['Control+KeyM'] = e => {
                if (!this.hasSelectedRecords()) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                this.getRecordView().massActionMoveToFolder();
            };
        },

        hasSelectedRecords: function () {
            let recordView = this.getRecordView();

            return recordView.checkedList &&
                recordView.checkedList.length &&
                !recordView.allResultIsChecked;
        },

        /**
         * @inheritDoc
         */
        setupReuse: function (params) {
            this.applyRoutingParams(params);
            this.initDroppable();
            this.initStickableFolders();
        },

        /**
         * @param {Object.<string,*>} [data]
         */
        actionComposeEmail: function (data) {
            data = data || {};

            Espo.Ui.notify(' ... ');

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

            let auxFolderList = [
                this.FOLDER_TRASH,
                this.FOLDER_DRAFTS,
                this.FOLDER_ALL,
                this.FOLDER_INBOX,
                this.FOLDER_IMPORTANT,
                this.FOLDER_SENT,
            ];

            this.getFolderCollection(collection => {
                collection.forEach(model => {
                    if (this.noDropFolderIdList.indexOf(model.id) === -1) {
                        model.droppable = true;
                    }

                    if (model.id.indexOf('group:') === 0) {
                        model.title = this.translate('groupFolder', 'fields', 'Email');
                    }
                    else if (auxFolderList.indexOf(model.id) === -1) {
                        model.title = this.translate('folder', 'fields', 'Email');
                    }
                });

                this.createView('folders', 'views/email-folder/list-side', {
                    collection: collection,
                    emailCollection: this.collection,
                    el: this.options.el + ' .folders-container',
                    showEditLink: this.getAcl().check(this.folderScope, 'edit'),
                    selectedFolderId: this.selectedFolderId,
                }, view => {
                    view.render()
                        .then(() => this.initDroppable())
                        .then(() => this.initStickableFolders());

                    this.listenTo(view, 'select', (id) => {
                        this.selectedFolderId = id;
                        this.applyFolder();

                        if (xhr && xhr.readyState < 4) {
                            xhr.abort();
                        }

                        Espo.Ui.notify(' ... ');

                        xhr = this.collection
                            .fetch()
                            .then(() => Espo.Ui.notify(false));

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

        onDrop: function (folderId, id) {
            let recordView = this.getRecordView();

            if (folderId === this.FOLDER_IMPORTANT) {
                setTimeout(() => {
                    id === true ?
                        recordView.massActionMarkAsImportant() :
                        recordView.actionMarkAsImportant({id: id});
                }, 10);

                return;
            }

            if (this.selectedFolderId === this.FOLDER_TRASH) {
                if (folderId === this.FOLDER_TRASH) {
                    return;
                }

                id === true ?
                    recordView.massRetrieveFromTrashMoveToFolder(folderId) :
                    recordView.actionRetrieveFromTrashMoveToFolder({id: id, folderId: folderId});

                return;
            }

            if (folderId === this.FOLDER_TRASH) {
                id === true ?
                    recordView.massActionMoveToTrash() :
                    recordView.actionMoveToTrash({id: id});

                return;
            }

            if (this.selectedFolderId.indexOf('group:') === 0 && folderId === this.FOLDER_ALL) {
                folderId = this.FOLDER_INBOX;
            }

            id === true ?
                recordView.massMoveToFolder(folderId) :
                recordView.actionMoveToFolder({id: id, folderId: folderId});
        },

        /**
         * @protected
         * @return {module:views/email/record/list.Class}
         */
        getRecordView: function () {
            return this.getView('list');
        },

        /**
         * @private
         */
        initStickableFolders: function () {
            let $window = $(window);
            let $list = this.$el.find('.list-container');
            let $container = this.$el.find('.folders-container');
            let $left = this.$el.find('.left-container').first();

            let screenWidthXs = this.getThemeManager().getParam('screenWidthXs');
            let isSmallScreen = $(window.document).width() < screenWidthXs;
            let offset = this.getThemeManager().getParam('navbarHeight') +
                (this.getThemeManager().getParam('buttonsContainerHeight') || 47);

            let bottomSpaceHeight = parseInt(window.getComputedStyle($('#content').get(0)).paddingBottom, 10);

            let getOffsetTop = (/** JQuery */$element) => {
                let element = $element.get(0);

                let value = 0;

                while (element) {
                    value += !isNaN(element.offsetTop) ? element.offsetTop : 0;

                    element = element.offsetParent;
                }

                if (isSmallScreen) {
                    return value;
                }

                return value - offset;
            };

            let start = getOffsetTop($list);

            let control = () => {
                let scrollTop = $window.scrollTop();

                if (scrollTop <= start || isSmallScreen) {
                    $container
                        .removeClass('sticked')
                        .width('')
                        .scrollTop(0);

                    $container.css({
                        maxHeight: '',
                    });

                    return;
                }

                if (scrollTop > start) {
                    let maxHeight = $window.height() - start - bottomSpaceHeight;

                    let scroll = $window.scrollTop() - start;

                    $container
                        .addClass('sticked')
                        .width($left.outerWidth(true))
                        .scrollTop(scroll)
                        .css({
                            maxHeight: maxHeight,
                        });
                }
            };

            $window.on('resize.email-folders', () => control());
            $window.on('scroll.email-folders', () => control());
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
