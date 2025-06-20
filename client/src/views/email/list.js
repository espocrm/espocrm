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

import ListView from 'views/list';

class EmailListView extends ListView {

    createButton = false
    template = 'email/list'
    folderId = null
    folderScope = 'EmailFolder'
    selectedFolderId = null
    defaultFolderId = 'inbox'
    keepCurrentRootUrl = true
    stickableTop = null

    /** @const */
    FOLDER_ALL = 'all'
    /** @const */
    FOLDER_INBOX = 'inbox'
    /** @const */
    FOLDER_IMPORTANT = 'important'
    /** @const */
    FOLDER_SENT = 'sent'
    /** @const */
    FOLDER_DRAFTS = 'drafts'
    /** @const */
    FOLDER_TRASH = 'trash'
    /** @const */
    FOLDER_ARCHIVE = 'archive'

    noDropFolderIdList = [
        'sent',
        'drafts',
    ]

    /** @inheritDoc */
    createListRecordView(fetch) {
        return super.createListRecordView(fetch)
            .then(view => {
                this.listenTo(view, 'after:render', () => this.initDraggable(null));
                this.listenTo(view, 'after:show-more', fromIndex => this.initDraggable(fromIndex));
            });
    }

    /**
     * @private
     */
    initDroppable() {
        // noinspection JSUnresolvedReference
        this.$el.find('.folders-container .folder-list > .droppable')
            .droppable({
                accept: '.list-row',
                tolerance: 'pointer',
                over: (e) => {
                    if (!this.isDroppable(e)) {
                        return;
                    }

                    const $target = $(e.target);

                    $target.removeClass('success');
                    $target.addClass('active');
                    $target.find('a').css('pointer-events', 'none');
                },
                out: (e) => {
                    if (!this.isDroppable(e)) {
                        return;
                    }

                    const $target = $(e.target);

                    $target.removeClass('active');
                    $target.find('a').css('pointer-events', '');
                },
                drop: (e, ui) => {
                    if (!this.isDroppable(e)) {
                        return;
                    }

                    const $target = $(e.target);
                    const $helper = $(ui.helper);

                    $target.find('a').css('pointer-events', '');

                    const folderId = $target.attr('data-id');

                    let id = $helper.attr('data-id');
                    id = id === '' ? true : id;

                    this.onDrop(folderId, id);

                    $target.removeClass('active');
                    $target.addClass('success');

                    setTimeout(() => {
                        $target.removeClass('success');
                    }, 1000);
                },
            });
    }

    /**
     * @private
     * @param {?Number} fromIndex
     */
    initDraggable(fromIndex) {
        fromIndex = fromIndex || 0;

        const isTouchDevice = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;

        if (isTouchDevice) {
            return;
        }

        const $container = this.$el.find('.list-container > .list');

        const recordView = this.getEmailRecordView();

        this.collection.models.slice(fromIndex).forEach(m => {
            const $row = $container.find(`.list-row[data-id="${m.id}"]`).first();

            // noinspection JSUnresolvedReference,JSUnusedGlobalSymbols
            $row.draggable({
                cancel: 'input,textarea,button,select,option,.dropdown-menu',
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
                drag: () => {
                    if (recordView.allResultIsChecked) {
                        return false;
                    }
                },
                start: (e) => {
                    if (recordView.allResultIsChecked) {
                        return;
                    }

                    const $target = $(e.target);

                    $target.closest('tr').addClass('active');
                },
                stop: () => {
                    if (!recordView.isIdChecked(m.id)) {
                        $container.find(`.list-row[data-id="${m.id}"]`).first().removeClass('active');
                    }
                },
            });
        });
    }

    isDroppable(e) {
        const $target = $(e.target);
        const folderId = $target.attr('data-id');

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

            if (folderId === this.FOLDER_TRASH || folderId === this.FOLDER_ARCHIVE) {
                return false;
            }

            return true;
        }

        if (this.selectedFolderId.indexOf('group:') === 0) {
            if (
                [this.FOLDER_ALL, this.FOLDER_ARCHIVE, this.FOLDER_TRASH].includes(folderId) ||
                folderId.startsWith('group:')
            ) {
                return true;
            }

            return false;
        }

        return true;
    }

    setup() {
        super.setup();

        this.addMenuItem('dropdown', false);

        if (this.getAcl().checkScope('EmailAccountScope')) {
            this.addMenuItem('dropdown', {
                name: 'reply',
                label: 'Email Accounts',
                link: '#EmailAccount/list/userId=' + this.getUser().id + '&userName=' +
                    encodeURIComponent(this.getUser().get('name'))
            });
        }

        if (!this.getAcl().checkScope('Import')) {
            this.hideHeaderActionItem('archiveEmail');
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

        const params = this.options.params || {};

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
    }

    data() {
        const data = {};
        data.foldersDisabled = this.foldersDisabled;

        return data;
    }

    /** @inheritDoc */
    createSearchView() {
        /** @type {Promise<module:view>} */
        const promise = super.createSearchView();

        promise.then(view => {
            this.listenTo(view, 'update-ui', () => {
                this.stickableTop = null;

                setTimeout(() => {
                    $(window).trigger('scroll')

                    // If search fields are not yet rendered, the value may be wrong.
                    this.stickableTop = null;
                }, 100);
            });
        });

        return promise;
    }

    initEmailShortcuts() {
        this.shortcutKeys['Control+Delete'] = e => {
            if (!this.hasSelectedRecords()) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.getEmailRecordView().massActionMoveToTrash();
        };

        this.shortcutKeys['Control+Backspace'] = e => {
            if (!this.hasSelectedRecords()) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.getEmailRecordView().massActionMoveToArchive();
        };

        this.shortcutKeys['Control+KeyI'] = e => {
            if (!this.hasSelectedRecords()) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.getEmailRecordView().toggleMassMarkAsImportant();
        };

        this.shortcutKeys['Control+KeyM'] = e => {
            if (!this.hasSelectedRecords()) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.getEmailRecordView().massActionMoveToFolder();
        };

        this.shortcutKeys['Control+KeyQ'] = e => {
            e.preventDefault();
            e.stopPropagation();

            if (this.hasSelectedRecords()) {
                this.getEmailRecordView().massActionMarkAsRead();

                return;
            }

            this.getEmailRecordView().actionMarkAllAsRead();
        };
    }

    hasSelectedRecords() {
        const recordView = this.getEmailRecordView();

        return recordView.checkedList &&
            recordView.checkedList.length &&
            !recordView.allResultIsChecked;
    }

    /** @inheritDoc */
    setupReuse(params) {
        super.setupReuse(params);

        this.applyRoutingParams(params);
        this.initDroppable();
        this.initStickableFolders();

        const recordView = /** @type {import('views/email/record/list').default} */this.getRecordView();

        recordView.removeQueuedRecord();
    }

    /**
     * @param {Object.<string,*>} [data]
     */
    actionComposeEmail(data) {
        data = data || {};

        Espo.Ui.notifyWait();

        const viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
            'views/modals/compose-email';

        const options = {
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
    }

    afterRender() {
        super.afterRender();

        if (!this.foldersDisabled && !this.hasView('folders')) {
            this.loadFolders();
        }
    }

    /**
     * @private
     * @param {function(import('collection').default)} callback
     */
    getFolderCollection(callback) {
        this.getCollectionFactory().create(this.folderScope, (collection) => {
            collection.url = 'EmailFolder/action/listAll';
            collection.maxSize = 200;

            this.listenToOnce(collection, 'sync', () =>{
                callback.call(this, collection);
            });

            collection.fetch();
        });
    }

    loadFolders() {
        let xhr = null;

        const auxFolderList = [
            this.FOLDER_TRASH,
            this.FOLDER_DRAFTS,
            this.FOLDER_ALL,
            this.FOLDER_INBOX,
            this.FOLDER_IMPORTANT,
            this.FOLDER_SENT,
            this.FOLDER_ARCHIVE,
        ];

        const iconMap = {
            [this.FOLDER_TRASH]: 'far fa-trash-alt',
            [this.FOLDER_SENT]: 'far fa-paper-plane',
            [this.FOLDER_INBOX]: 'fas fa-inbox',
            [this.FOLDER_ARCHIVE]: 'far fa-caret-square-down',
            [this.FOLDER_DRAFTS]: 'far fa-file',
            [this.FOLDER_IMPORTANT]: 'far fa-star',
        }

        this.getFolderCollection(collection => {
            collection.forEach((model, i) => {
                if (this.noDropFolderIdList.indexOf(model.id) === -1) {
                    model.droppable = true;
                }

                if (model.id === this.FOLDER_INBOX) {
                    model.groupStart = true;
                } else if (
                    model.id === this.FOLDER_ARCHIVE ||
                    model.id === this.FOLDER_TRASH && !collection.models.find(m => m.id === this.FOLDER_ARCHIVE)
                ) {
                    model.groupStart = true;
                } else if (
                    model.id.indexOf('group:') === 0 &&
                    collection.models.findIndex(m => m.id.indexOf('group:') === 0) === i
                ) {
                    model.groupStart = true;
                }

                model.iconClass = iconMap[model.id];

                if (model.id.indexOf('group:') === 0) {
                    model.title = this.translate('groupFolder', 'fields', 'Email');
                    model.iconClass = 'far fa-circle';
                } else if (auxFolderList.indexOf(model.id) === -1) {
                    model.title = this.translate('folder', 'fields', 'Email');
                    model.iconClass = 'far fa-folder';
                }
            });

            this.createView('folders', 'views/email-folder/list-side', {
                collection: collection,
                emailCollection: this.collection,
                selector: '.folders-container',
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

                    Espo.Ui.notifyWait();

                    this.collection.offset = 0;

                    xhr = this.collection
                        .fetch()
                        .then(() => Espo.Ui.notify(false));

                    if (id !== this.defaultFolderId) {
                        this.getRouter().navigate(`#Email/list/folder=${id}`);
                    } else {
                        this.getRouter().navigate('#Email');
                    }

                    this.updateLastUrl();
                });
            });
        });
    }

    applyFolder() {
        this.rootData.selectedFolderId = this.selectedFolderId;
        this.collection.trigger('select-folder');

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
    }

    /**
     * @protected
     * @return {import('views/email-folder/list-side').default}
     */
    getFoldersView() {
        // noinspection JSValidateTypes
        return this.getView('folders')
    }

    applyRoutingParams(params) {
        let id;

        if ('folder' in params) {
            id = params.folder || 'inbox';
        } else {
            return;
        }

        if (!params.isReturnThroughLink && id !== this.selectedFolderId) {
            const foldersView = this.getFoldersView();

            if (foldersView) {
                foldersView.actionSelectFolder(id);
                foldersView.reRender();
                $(window).scrollTop(0);
            }
        }
    }

    onDrop(folderId, id) {
        const recordView = this.getEmailRecordView();

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
    }

    /**
     * @protected
     * @return {module:views/email/record/list}
     */
    getEmailRecordView() {
        return /** @type {module:views/email/record/list} */this.getRecordView();
    }

    /**
     * @private
     */
    initStickableFolders() {
        const $window = $(window);
        const $list = this.$el.find('.list-container');
        const $container = this.$el.find('.folders-container');
        const $left = this.$el.find('.left-container').first();

        const screenWidthXs = this.getThemeManager().getParam('screenWidthXs');
        const isSmallScreen = $(window.document).width() < screenWidthXs;

        const factor = this.getThemeManager().getFontSizeFactor();

        const offset = this.getThemeManager().getParam('navbarHeight') * factor +
            (this.getThemeManager().getParam('buttonsContainerHeight') || 47) * factor;

        const bottomSpaceHeight = parseInt(window.getComputedStyle($('#content').get(0)).paddingBottom, 10);

        const getOffsetTop = (/** JQuery */$element) => {
            let element = /** @type {HTMLElement} */$element.get(0);

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

        this.stickableTop = getOffsetTop($list);

        const control = () => {
            let start = this.stickableTop;

            if (start === null) {
                start = this.stickableTop = getOffsetTop($list);
            }

            const scrollTop = $window.scrollTop();

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
                const scroll = $window.scrollTop() - start;

                $container
                    .addClass('sticked')
                    .width($left.outerWidth(true))
                    .scrollTop(scroll);

                const topStickPosition = parseInt(window.getComputedStyle($container.get(0)).top);

                const maxHeight = $window.height() - topStickPosition - bottomSpaceHeight;

                $container.css({maxHeight: maxHeight});
            }
        };

        $window.on('resize.email-folders', () => control());
        $window.on('scroll.email-folders', () => control());
    }

    /**
     * @protected
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyCtrlSpace(e) {
        if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
            return;
        }

        if (!this.getAcl().checkScope(this.scope, 'create')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.actionComposeEmail({focusForCreate: true});
    }
}

export default EmailListView;
