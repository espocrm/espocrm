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

import RelationshipPanelView from 'views/record/panels/relationship';
import _ from 'underscore';
import NotePostFieldView from 'views/note/fields/post';
import ViewRecordHelper from 'view-record-helper';
import TextFieldView from 'views/fields/text';

class PanelStreamView extends RelationshipPanelView {

    template = 'stream/panel'

    rowActionsView = 'views/stream/record/row-actions/default'

    postingMode = false
    postDisabled = false
    relatedListFiltersDisabled = true
    layoutName = null
    filterList = ['all', 'posts', 'updates']
    /** @type {import('collections/note').default} */
    collection

    /** @private */
    _justPosted = false

    /** @type {import('collections/note').default} */
    pinnedCollection

    /**
     * @protected
     * @type {string}
     */
    placeholderText

    /**
     * @private
     * @type {import('model').default}
     */
    seed

    additionalEvents = {
        /** @this PanelStreamView */
        'focus textarea[data-name="post"]': function () {
            this.enablePostingMode(true);
        },
        /** @this PanelStreamView */
        'keydown textarea[data-name="post"]': function (e) {
            if (Espo.Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
                e.stopPropagation();
                e.preventDefault();

                this.post();
            }

            // Don't hide to be able to focus on the upload button.
            /*if (e.code === 'Tab') {
                let $text = $(e.currentTarget);

                if ($text.val() === '') {
                    this.disablePostingMode();
                }
            }*/
        },
        /** @this PanelStreamView */
        'input textarea[data-name="post"]': function () {
            this.controlPreviewButton();
            this.controlPostButtonAvailability(this.postFieldView.getTextAreaElement().value);
        },
    }

    data() {
        const data = super.data();

        data.postDisabled = this.postDisabled;
        data.placeholderText = this.placeholderText;
        data.allowInternalNotes = this.allowInternalNotes;
        data.hasPinned = this.hasPinned;

        return data;
    }

    controlPreviewButton() {
        this.$previewButton = this.$previewButton || this.$el.find('.stream-post-preview');

        if (this.postFieldView.getTextAreaElement().value === '') {
            this.$previewButton.addClass('hidden');
        } else {
            this.$previewButton.removeClass('hidden');
        }
    }

    /**
     * @private
     * @param {boolean} [byFocus]
     */
    enablePostingMode(byFocus) {
        this.$el.find('.buttons-panel').removeClass('hide');

        if (!this.postingMode) {
            if (this.postFieldView.getTextAreaElement().value) {
                this.postFieldView.controlTextareaHeight();
            }

            let isClicked = false;

            $('body').on('click.stream-panel', (e) => {
                if (byFocus && !isClicked) {
                    isClicked = true;

                    return;
                }

                const $target = $(e.target);

                if ($target.parent().hasClass('remove-attachment')) {
                    return;
                }

                if ($.contains(this.$postContainer.get(0), e.target)) {
                    return;
                }

                if (this.postFieldView.getTextAreaElement().value !== '') {
                    return;
                }

                if ($(e.target).closest('.popover-content').get(0)) {
                    return;
                }

                const attachmentsIds = this.seed.get('attachmentsIds') || [];

                if (
                    !attachmentsIds.length &&
                    (
                        !this.getAttachmentsFieldView() ||
                        !this.getAttachmentsFieldView().isUploading
                    )
                ) {
                    this.disablePostingMode();
                }
            });
        }

        this.postingMode = true;

        this.controlPreviewButton();
    }

    disablePostingMode() {
        this.postingMode = false;

        this.setPostText(null);

        if (this.getAttachmentsFieldView()) {
            this.getAttachmentsFieldView().empty();
        }

        this.$el.find('.buttons-panel').addClass('hide');

        $('body').off('click.stream-panel');

        this.postFieldView.getTextAreaElement().rows = 1;
    }

    setup() {
        this.events = {
            ...this.additionalEvents,
            ...this.events,
        };

        this.addHandler('click', 'button.post', () => this.post());
        this.addActionHandler('switchInternalMode', () => this.switchInternalMode());
        this.addActionHandler('preview', () => this.preview());

        this.entityType = this.model.entityType;
        this.filter = this.getStoredFilter();

        this.setupTitle();

        this.placeholderText = this.placeholderText || this.translate('writeYourCommentHere', 'messages');
        this.allowInternalNotes = false;

        if (!this.getUser().isPortal()) {
            this.allowInternalNotes = this.getMetadata().get(['clientDefs', this.entityType, 'allowInternalNotes']);
        }

        this.hasPinned = this.model.entityType !== 'User';

        this.isInternalNoteMode = false;

        this.storageTextKey = `stream-post-${this.model.entityType}-${this.model.id}`;
        this.storageAttachmentsKey = `stream-post-attachments-${this.model.entityType}-${this.model.id}`;
        this.storageIsInernalKey = `stream-post-is-internal-${this.model.entityType}-${this.model.id}`;

        this.on('remove', () => {
            this.storeControl();

            $(window).off('beforeunload.stream-'+ this.cid);
        });

        $(window).off(`beforeunload.stream-${this.cid}`);

        $(window).on(`beforeunload.stream-${this.cid}`, () => {
            this.storeControl();
        });

        /** @private */
        this.formRecordHelper = new ViewRecordHelper();

        const storedAttachments = this.getSessionStorage().get(this.storageAttachmentsKey);

        this.setupActions();

        const promise = this.getModelFactory().create('Note', model => {
            this.seed = model;

            if (storedAttachments) {
                this.hasStoredAttachments = true;
                this.seed.set({
                    attachmentsIds: storedAttachments.idList,
                    attachmentsNames: storedAttachments.names,
                    attachmentsTypes: storedAttachments.types,
                });
            }

            if (this.allowInternalNotes) {
                if (this.getMetadata().get(['entityDefs', 'Note', 'fields', 'isInternal', 'default'])) {
                    this.isInternalNoteMode = true;
                }

                if (this.getSessionStorage().has(this.storageIsInernalKey)) {
                    this.isInternalNoteMode = this.getSessionStorage().get(this.storageIsInernalKey);
                }
            }

            if (this.isInternalNoteMode) {
                this.seed.set('isInternal', true);
            }

            this.postFieldView = new NotePostFieldView({
                name: 'post',
                mode: 'edit',
                params: {
                    required: true,
                    rowsMin: 1,
                    preview: false,
                    attachmentField: 'attachments',
                },
                model: this.seed,
                placeholderText: this.placeholderText,
                noResize: true,
                recordHelper: this.formRecordHelper,
            });

            this.assignView('postField', this.postFieldView, '.textarea-container').then(view => {
                this.initPostEvents(view);
            });

            this.wait(
                this.createCollection()
                    .then(() => this.setupPinned())
            );

            this.listenTo(this.seed, 'change:attachmentsIds', () => {
                this.controlPostButtonAvailability();
            });
        });

        this.wait(promise);

        if (!this.defs.hidden) {
            this.subscribeToWebSocket();
        }

        this.once('show', () => {
            if (!this.isSubscribedToWebSocket) {
                this.subscribeToWebSocket();
            }
        });

        this.on('remove', () => {
            if (this.isSubscribedToWebSocket) {
                this.unsubscribeFromWebSocket();
            }
        });
    }

    switchInternalMode() {
        this.isInternalNoteMode = !this.isInternalNoteMode;

        if (this.internalModeButtonElement) {
            this.isInternalNoteMode ?
                this.internalModeButtonElement.classList.add('enabled') :
                this.internalModeButtonElement.classList.remove('enabled');
        }
    }

    subscribeToWebSocket() {
        if (!this.webSocketManager.isEnabled()) {
            return;
        }

        if (this.model.entityType === 'User') {
            return;
        }

        const topic = `streamUpdate.${this.model.entityType}.${this.model.id}`;
        this.streamUpdateWebSocketTopic = topic;

        this.isSubscribedToWebSocket = true;

        this.webSocketManager.subscribe(topic, (t, /** Record */data) => {
            if (data.createdById === this.getUser().id && this._justPosted) {
                return;
            }

            if (data.noteId) {
                const model = this.collection.get(data.noteId);

                if (model) {
                    model.fetch()
                        .then(() => this.syncPinnedModel(model, true));
                }

                if (!data.pin) {
                    return;
                }
            }

            this.collection.fetchNew();
        });
    }

    unsubscribeFromWebSocket() {
        this.webSocketManager.unsubscribe(this.streamUpdateWebSocketTopic);
    }

    setupTitle() {
        this.title = this.translate('Stream');

        this.titleHtml = this.title;

        if (this.filter && this.filter !== 'all') {
            this.titleHtml += ' &middot; ' + this.translate(this.filter, 'filters', 'Note');
        }
    }

    /**
     * @private
     */
    storeControl() {
        let isNotEmpty = false;

        if (this.isRendered()) {
            const text = /** @type {string} */this.seed.attributes.post;

            if (text && text.length) {
                this.getSessionStorage().set(this.storageTextKey, text);

                isNotEmpty = true;
            } else if (this.hasStoredText) {
                this.getSessionStorage().clear(this.storageTextKey);
            }
        }

        const attachmentIdList = this.seed.get('attachmentsIds') || [];

        if (attachmentIdList.length) {
            this.getSessionStorage().set(this.storageAttachmentsKey, {
                idList: attachmentIdList,
                names: this.seed.get('attachmentsNames') || {},
                types: this.seed.get('attachmentsTypes') || {},
            });

            isNotEmpty = true;
        }
        else {
            if (this.hasStoredAttachments) {
                this.getSessionStorage().clear(this.storageAttachmentsKey);
            }
        }

        if (isNotEmpty) {
            this.getSessionStorage().set(this.storageIsInernalKey, this.isInternalNoteMode);
        } else {
            this.getSessionStorage().clear(this.storageIsInernalKey);
        }
    }

    /**
     * @private
     * @return {Promise}
     */
    createCollection() {
        return this.getCollectionFactory().create('Note', collection => {
            this.collection = collection;

            collection.url = `${this.model.entityType}/${this.model.id}/stream`;
            collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.setFilter(this.filter);
        });
    }

    /** @private */
    initPostEvents(view) {
        this.listenTo(this.formRecordHelper, 'upload-files:attachments', () => {
            if (!this.postingMode) {
                this.enablePostingMode();
            }
        });

        this.listenTo(view, 'add-files', (files) => {
            this.getAttachmentsFieldView().uploadFiles(files);

            if (!this.postingMode) {
                this.enablePostingMode();
            }
        });
    }

    /**
     * @private
     * @param {string|null} text
     */
    setPostText(text) {
        this.seed.set('post', text, {silent: true});
        this.postFieldView.getTextAreaElement().value = text || '';
    }

    /**
     * @private
     */
    onSync() {
        if (this.hasPinned) {
            this.pinnedCollection.add(this.collection.pinnedList);

            this.createView('pinnedList', 'views/stream/record/list', {
                selector: '> .list-container[data-role="pinned"]',
                collection: this.pinnedCollection,
                model: this.model,
                noDataDisabled: true,
            }, view => {
                view.render();

                this.listenTo(this.pinnedCollection, 'change',
                    (/** import('model').default */model, /** Record */o) => {
                        if (o.userReaction) {
                            this.syncPinnedModel(model, false);
                        }
                    });

                this.listenTo(view, 'after:save', /** import('model').default */model => {
                    this.syncPinnedModel(model, false);
                });

                this.listenTo(view, 'after:delete', /** import('model').default */model => {
                    this.collection.remove(model.id);
                    this.collection.trigger('update-sync');
                });

                this.listenTo(view, 'quote-reply', /** string */quoted => this.quoteReply(quoted));
            });
        }

        this.createView('list', 'views/stream/record/list', {
            selector: '> .list-container[data-role="stream"]',
            collection: this.collection,
            model: this.model,
        }, view => {
            view.render();

            if (this.pinnedCollection) {
                this.listenTo(view, 'after:delete', /** import('model').default */model => {
                    this.pinnedCollection.remove(model.id);
                    this.pinnedCollection.trigger('update-sync');
                });

                this.listenTo(view, 'after:save', /** import('model').default */model => {
                    this.syncPinnedModel(model, true);
                });

                this.listenTo(this.collection, 'change',
                    (/** import('model').default */model, /** Record */o) => {
                        if (o.userReaction) {
                            this.syncPinnedModel(model, true);
                        }
                    });

                this.listenTo(view, 'quote-reply', /** string */quoted => this.quoteReply(quoted));
            }
        });

        this.stopListening(this.model, 'all');
        this.stopListening(this.model, 'destroy');

        setTimeout(() => {
            this.listenTo(this.model, 'all', event => {
                if (!['sync', 'after:relate'].includes(event)) {
                    return;
                }

                this.collection.fetchNew();
            });

            this.listenTo(this.model, 'destroy', () => {
                this.stopListening(this.model, 'all');
            });
        }, 500);
    }

    afterRender() {
        this.$attachments = this.$el.find('div.attachments');
        this.$postContainer = this.$el.find('.post-container');
        this.$postButton = this.$el.find('button.post');

        const storedText = this.getSessionStorage().get(this.storageTextKey);

        if (storedText && storedText.length) {
            this.hasStoredText = true;

            this.setPostText(storedText);
        }

        this.controlPostButtonAvailability(storedText);

        if (this.allowInternalNotes) {
            this.internalModeButtonElement =
                /** @type {HTMLAnchorElement} */
                this.element.querySelector('.action[data-action="switchInternalMode"]');
        }

        if (this.isInternalNoteMode) {
            this.internalModeButtonElement.classList.add('enabled');
        }

        if (!this.defs.hidden) {
            this.collection.fetch().then(() => this.onSync());
        } else {
            this.once('show', () => {
                this.collection.fetch().then(() => this.onSync());
            });
        }

        const $a = this.$el.find('.buttons-panel a.stream-post-info');

        const text1 = this.translate('infoMention', 'messages', 'Stream');
        const text2 = this.translate('infoSyntax', 'messages', 'Stream');

        const syntaxItemList = [
            ['code', '`{text}`'],
            ['multilineCode', '```{text}```'],
            ['strongText', '**{text}**'],
            ['emphasizedText', '*{text}*'],
            ['deletedText', '~~{text}~~'],
            ['blockquote', '> {text}'],
            ['link', '[{text}](url)'],
        ];

        const messageItemList = [];

        syntaxItemList.forEach(item => {
            const text = this.translate(item[0], 'syntaxItems', 'Stream');
            const result = item[1].replace('{text}', text);

            messageItemList.push(result);
        });

        const $ul = $('<ul>')
            .append(
                messageItemList.map(text => $('<li>').text(text))
            );

        const messageHtml =
            this.getHelper().transformMarkdownInlineText(text1) + '<br><br>' +
            this.getHelper().transformMarkdownInlineText(text2) + ':<br>' +
            $ul.get(0).outerHTML;

        Espo.Ui.popover($a, {content: messageHtml}, this);

        this.createView('attachments', 'views/stream/fields/attachment-multiple', {
            model: this.seed,
            mode: 'edit',
            selector: 'div.attachments-container',
            name: 'attachments',
            recordHelper: this.formRecordHelper,
        }, view => {
            view.render();
        });
    }

    /**
     * @private
     * @param {import('model').default} model
     * @param {boolean} toPinned
     */
    syncPinnedModel(model, toPinned) {
        if (toPinned && !this.pinnedCollection) {
            return;
        }

        const cModel = toPinned ?
            this.pinnedCollection.get(model.id) :
            this.collection.get(model.id);

        if (!cModel) {
            return;
        }

        cModel.setMultiple({
            post: model.attributes.post,
            attachmentsIds: model.attributes.attachmentsIds,
            attachmentsNames: model.attributes.attachmentsNames,
            attachmentsTypes: model.attributes.attachmentsTypes,
            data: model.attributes.data,
            reactionCounts: model.attributes.reactionCounts,
            myReactions: model.attributes.myReactions,
        });
    }

    /**
     * @private
     */
    afterPost() {
        this.postFieldView.getTextAreaElement().rows = 1;
    }

    /**
     * @private
     * @return {import('views/fields/attachment-multiple').default}
     */
    getAttachmentsFieldView() {
        return this.getView('attachments');
    }

    /**
     * @private
     */
    async post() {
        this.postFieldView.fetchToModel();

        /** @type {string} */
        const message = this.seed.attributes.post || '';

        const textAreaElement = this.postFieldView.getTextAreaElement();

        this.disablePostButton();
        textAreaElement.disabled = true;

        const model = await this.getModelFactory().create('Note');

        if (this.getAttachmentsFieldView().validateReady()) {
            textAreaElement.disabled = false;
            this.enablePostButton();

            return;
        }

        /** @type {string[]} */
        const attachmentIds = this.seed.get('attachmentsIds') || [];

        if (message.trim() === '' && attachmentIds.length === 0) {
            Espo.Ui.error(this.translate('Post cannot be empty'))

            textAreaElement.disabled = false;
            this.controlPostButtonAvailability();

            textAreaElement.focus();

            return;
        }

        model.set('post', message || null);
        model.set('attachmentsIds', [...attachmentIds]);
        model.set('type', 'Post');
        model.set('isInternal', this.isInternalNoteMode);

        this.prepareNoteForPost(model);

        this._justPosted = true;
        setTimeout(() => this._justPosted = false, 1000);

        Espo.Ui.notifyWait();

        try {
            await model.save(null);
        } catch (e) {
            this.postFieldView.getTextAreaElement().disabled = false;
            this.controlPostButtonAvailability();

            return;
        }

        Espo.Ui.success(this.translate('Posted'));

        this.collection.fetchNew()
            .then(() => this.afterPostAndFetch(model));

        textAreaElement.disabled = false;

        this.disablePostingMode();
        this.afterPost();

        if (this.getPreferences().get('followEntityOnStreamPost')) {
            this.model.set('isFollowed', true);
        }

        this.getSessionStorage().clear(this.storageTextKey);
        this.getSessionStorage().clear(this.storageAttachmentsKey);
        this.getSessionStorage().clear(this.storageIsInernalKey);
    }

    /**
     * @private
     * @param {import('model').default} model
     */
    afterPostAndFetch(model) {
        const rowView = this.getListView().getView(model.id);

        if (!rowView) {
            return;
        }

        const postView = rowView.getView('post');

        if (!(postView instanceof TextFieldView)) {
            console.error('Post field not obtained.')

            return;
        }

        postView.seeMore();
    }

    /**
     * @protected
     * @param {import('model').default} model
     */
    prepareNoteForPost(model) {
        model.set('parentId', this.model.id);
        model.set('parentType', this.model.entityType);
    }

    getButtonList() {
        return [];
    }

    setupActions() {
        this.actionList = [];

        this.actionList.push({
            action: 'viewPostList',
            text: this.translate('View Posts', 'labels', 'Note'),
            onClick: () => this.actionViewPostList(),
        });

        if (this.model.entityType !== 'User') {
            this.actionList.push({
                action: 'viewAttachmentList',
                text: this.translate('View Attachments', 'labels', 'Note'),
                onClick: () => this.actionViewAttachmentList(),
            });
        }

        if (this.model.entityType === 'User') {
            this.actionList.push({
                action: 'viewUserActivity',
                text: this.translate('View Activity', 'labels', 'Note'),
                onClick: () => this.actionViewUserActivity(),
            });
        }

        this.actionList.push(false);

        this.filterList.forEach(item => {
            let selected ;

            selected = item === 'all' ?
                !this.filter :
                item === this.filter;

            this.actionList.push({
                action: 'selectFilter',
                html:
                    $('<span>')
                        .append(
                            $('<span>')
                                .addClass('check-icon fas fa-check pull-right')
                                .addClass(!selected ? ' hidden' : ''),
                            $('<div>')
                                .text(this.translate(item, 'filters', 'Note')),
                        )
                        .get(0).innerHTML,
                data: {
                    name: item,
                },
            });
        });
    }

    actionViewPostList() {
        const url = this.model.entityType + '/' + this.model.id + '/posts';

        const data = {
            entityType: 'Note',
            viewOptions: {
                url: url,
                title: `${this.translate('Stream')} @right ${this.translate('posts', 'filters', 'Note')}`,
                forceSelectAllAttributes: true,
                forcePagination: true,
                rowActionsView: 'views/stream/record/row-actions/detached',
            },
        };

        this.actionViewRelatedList(data);
    }

    /**
     * @private
     */
    actionViewAttachmentList() {
        const url = `${this.model.entityType}/${this.model.id}/streamAttachments`;

        const title = `${this.translate('Stream')} @right ${this.translate('Attachment', 'scopeNamesPlural')}`;

        const options = {
            model: this.model,
            link: 'streamAttachments',
            entityType: 'Attachment',
            title: title,
            layoutName: 'listForStream',
            defaultOrder: 'desc',
            defaultOrderBy: 'createdAt',
            url: url,
            listViewName: this.listViewName,
            createDisabled: true,
            selectDisabled: true,
            unlinkDisabled: true,
            removeDisabled: true,
            rowActionsView: null,
            filtersDisabled: true,
            searchPanelDisabled: true,
            massActionRemoveDisabled: true,
        };

        Espo.Ui.notifyWait();

        this.createView('modal', 'views/modals/related-list', options, view => {
            Espo.Ui.notify();

            view.render();

            this.listenTo(view, 'action', (event, element) => {
                Espo.Utils.handleAction(this, event, element);
            });
        });
    }

    actionViewUserActivity() {
        const url = `User/${this.model.id}/stream/own`;

        const data = {
            entityType: 'Note',
            viewOptions: {
                url: url,
                title: this.translate('Stream') + ' @right ' + this.translate('activity', 'filters', 'Note'),
                forceSelectAllAttributes: true,
                filtersLayoutName: 'filtersGlobal',
                forcePagination: true,
                rowActionsView: 'views/stream/record/row-actions/detached',
            },
        };

        this.actionViewRelatedList(data);
    }

    getStoredFilter() {
        return this.getStorage().get('state', 'streamPanelFilter' + this.entityType) || null;
    }

    storeFilter(filter) {
        if (filter) {
            this.getStorage().set('state', 'streamPanelFilter' + this.entityType, filter);
        } else {
            this.getStorage().clear('state', 'streamPanelFilter' + this.entityType);
        }
    }

    setFilter(filter) {
        this.filter = filter;
        this.collection.data.filter = null;

        if (filter) {
            this.collection.data.filter = filter;
        }
    }

    /**
     * @return {import('views/stream/record/list').default}
     */
    getListView() {
        return this.getView('list')
    }

    async actionRefresh() {
        if (!this.getListView()) {
            return;
        }

        Espo.Ui.notifyWait();

        await this.getListView().showNewRecords();

        Espo.Ui.notify();
    }

    preview() {
        this.postFieldView.preview();
    }

    /**
     * @private
     * @param {string} [postEntered]
     */
    controlPostButtonAvailability(postEntered) {
        const attachmentsIdList = this.seed.get('attachmentsIds') || [];
        let post = this.seed.get('post');

        if (typeof postEntered !== 'undefined') {
            post = postEntered;
        }

        const isEmpty = !post && !attachmentsIdList.length;

        if (isEmpty) {
            if (this.$postButton.hasClass('disabled')) {
                return;
            }

            this.disablePostButton();

            return;
        }

        if (!this.$postButton.hasClass('disabled')) {
            return;
        }

        this.enablePostButton();
    }

    /**
     * @private
     */
    disablePostButton() {
        this.$postButton.addClass('disabled').attr('disabled', 'disabled');
    }

    /**
     * @private
     */
    enablePostButton() {
        this.$postButton.removeClass('disabled').removeAttr('disabled');
    }

    /**
     * @private
     */
    setupPinned() {
        if (!this.hasPinned) {
            return;
        }

        const promise = this.getCollectionFactory().create('Note')
            .then(/** import('collections/note').default */collection => {
                this.pinnedCollection = collection;

                this.listenTo(this.collection, 'sync', () => {
                    if (!this.collection.pinnedList) {
                        return;
                    }

                    if (
                        _.isEqual(
                            this.collection.pinnedList,
                            this.pinnedCollection.models.map(m => m.attributes)
                        )
                    ) {
                        return;
                    }

                    this.pinnedCollection.reset();
                    this.pinnedCollection.add(this.collection.pinnedList);
                    this.pinnedCollection.trigger('sync', this.pinnedCollection, {}, {});
                });

                this.listenTo(this.pinnedCollection, 'pin unpin', () => {
                    this.collection.fetchNew();
                });

                this.listenTo(this.pinnedCollection, 'pin', id => {
                    const model = this.collection.get(id);

                    if (!model) {
                        return;
                    }

                    model.set('isPinned', true);
                });

                this.listenTo(this.pinnedCollection, 'unpin', id => {
                    const model = this.collection.get(id);

                    if (!model) {
                        return;
                    }

                    model.set('isPinned', false);
                });
            });

        this.wait(promise);
    }

    /**
     * @private
     * @param {string} quoted
     */
    quoteReply(quoted) {
        const quote = '> ' + quoted.split(/\r?\n|\r|\n/g).join('\n> ');

        let post = this.seed.attributes.post || '';

        if (post !== '') {
            post += '\n';
        }

        post += quote + '\n\n';

        this.setPostText(post);

        this.controlPreviewButton();
        this.controlPostButtonAvailability();
        this.postFieldView.controlTextareaHeight();
        this.enablePostingMode();

        setTimeout(() => {
            const textArea = this.postFieldView.getTextAreaElement();

            textArea.focus();
            textArea.setSelectionRange(textArea.value.length, textArea.value.length);
        }, 1);
    }
}

export default PanelStreamView;
