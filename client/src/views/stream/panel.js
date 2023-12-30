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

import RelationshipPanelView from 'views/record/panels/relationship';
// noinspection ES6UnusedImports
import Textcomplete from 'jquery-textcomplete';

class PanelStreamView extends RelationshipPanelView {

    template = 'stream/panel'

    postingMode = false
    postDisabled = false
    relatedListFiltersDisabled = true
    layoutName = null
    filterList = ['all', 'posts', 'updates']

    additionalEvents = {
        /** @this PanelStreamView */
        'focus textarea[data-name="post"]': function () {
            this.enablePostingMode(true);
        },
        /** @this PanelStreamView */
        'click button.post': function () {
            this.post();
        },
        /** @this PanelStreamView */
        'click .action[data-action="switchInternalMode"]': function (e) {
            this.isInternalNoteMode = !this.isInternalNoteMode;

            var $a = $(e.currentTarget);

            if (this.isInternalNoteMode) {
                $a.addClass('enabled');
            } else {
                $a.removeClass('enabled');
            }

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
            this.controlPostButtonAvailability(this.$textarea.val());
        },
        /** @this PanelStreamView */
        'click .action[data-action="preview"]': function () {
            this.preview();
        },
    }

    data() {
        let data = super.data();

        data.postDisabled = this.postDisabled;
        data.placeholderText = this.placeholderText;
        data.allowInternalNotes = this.allowInternalNotes;

        return data;
    }

    controlPreviewButton() {
        this.$previewButton = this.$previewButton || this.$el.find('.stream-post-preview');

        if (this.$textarea.val() === '') {
            this.$previewButton.addClass('hidden');
        } else {
            this.$previewButton.removeClass('hidden');
        }
    }

    enablePostingMode(byFocus) {
        this.$el.find('.buttons-panel').removeClass('hide');

        if (!this.postingMode) {
            if (this.$textarea.val() && this.$textarea.val().length) {
                this.getView('postField').controlTextareaHeight();
            }

            var isClicked = false;

            $('body').on('click.stream-panel', (e) => {
                if (byFocus && !isClicked) {
                    isClicked = true;

                    return;
                }

                var $target = $(e.target);

                if ($target.parent().hasClass('remove-attachment')) {
                    return;
                }

                if ($.contains(this.$postContainer.get(0), e.target)) {
                    return;
                }

                if (this.$textarea.val() !== '') {
                    return;
                }

                if ($(e.target).closest('.popover-content').get(0)) {
                    return;
                }

                var attachmentsIds = this.seed.get('attachmentsIds') || [];

                if (
                    !attachmentsIds.length &&
                    (!this.getView('attachments') || !this.getView('attachments').isUploading)
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

        this.$textarea.val('');

        if (this.hasView('attachments')) {
            this.getView('attachments').empty();
        }

        this.$el.find('.buttons-panel').addClass('hide');

        $('body').off('click.stream-panel');

        this.$textarea.prop('rows', 1);
    }

    setup() {
        this.events = {
            ...this.additionalEvents,
            ...this.events,
        };

        this.entityType = this.model.entityType;
        this.filter = this.getStoredFilter();

        this.setupTitle();

        this.placeholderText = this.translate('writeYourCommentHere', 'messages');
        this.allowInternalNotes = false;

        if (!this.getUser().isPortal()) {
            this.allowInternalNotes = this.getMetadata().get(['clientDefs', this.entityType, 'allowInternalNotes']);
        }

        this.isInternalNoteMode = false;

        this.storageTextKey = 'stream-post-' + this.model.entityType + '-' + this.model.id;
        this.storageAttachmentsKey = 'stream-post-attachments-' + this.model.entityType + '-' + this.model.id;
        this.storageIsInernalKey = 'stream-post-is-internal-' + this.model.entityType + '-' + this.model.id;

        this.on('remove', () => {
            this.storeControl();

            $(window).off('beforeunload.stream-'+ this.cid);
        });

        $(window).off('beforeunload.stream-'+ this.cid);

        $(window).on('beforeunload.stream-'+ this.cid, () => {
            this.storeControl();
        });

        var storedAttachments = this.getSessionStorage().get(this.storageAttachmentsKey);

        this.setupActions();

        this.wait(true);

        this.getModelFactory().create('Note', (model) => {
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

            this.createView('postField', 'views/note/fields/post', {
                selector: '.textarea-container',
                name: 'post',
                mode: 'edit',
                params: {
                    required: true,
                    rowsMin: 1,
                },
                model: this.seed,
                placeholderText: this.placeholderText,
                noResize: true,
            }, view => {
                this.initPostEvents(view);
            });

            this.createCollection(() => {
                this.wait(false);
            });

            this.listenTo(this.seed, 'change:attachmentsIds', () => {
                this.controlPostButtonAvailability();
            });
        });

        if (!this.defs.hidden) {
            this.subscribeToWebSocket();
        }

        this.once('show', () => {
            if (!this.isSubscribedToWebSocket) {
                this.subscribeToWebSocket();
            }
        });

        this.once('remove', () => {
            if (this.isSubscribedToWebSocket) {
                this.unsubscribeFromWebSocket();
            }
        });
    }

    subscribeToWebSocket() {
        if (!this.getHelper().webSocketManager) {
            return;
        }

        if (this.model.entityType === 'User') {
            return;
        }

        var topic = 'streamUpdate.' + this.model.entityType + '.' + this.model.id;
        this.streamUpdateWebSocketTopic = topic;

        this.isSubscribedToWebSocket = true;

        this.getHelper().webSocketManager.subscribe(topic, (t, data) => {
            if (data.createdById === this.getUser().id) {
                return;
            }

            if (data.noteId) {
                let model = this.collection.get(data.noteId);

                if (model) {
                    model.fetch();
                }

                return;
            }

            this.collection.fetchNew();
        });
    }

    unsubscribeFromWebSocket() {
        this.getHelper().webSocketManager.unsubscribe(this.streamUpdateWebSocketTopic);
    }

    setupTitle() {
        this.title = this.translate('Stream');

        this.titleHtml = this.title;

        if (this.filter && this.filter !== 'all') {
            this.titleHtml += ' &middot; ' + this.translate(this.filter, 'filters', 'Note');
        }
    }

    storeControl() {
        var isNotEmpty = false;

        if (this.$textarea && this.$textarea.length) {
            var text = this.$textarea.val();

            if (text.length) {
                this.getSessionStorage().set(this.storageTextKey, text);

                isNotEmpty = true;
            }
            else {
                if (this.hasStoredText) {
                    this.getSessionStorage().clear(this.storageTextKey);
                }
            }
        }

        var attachmentIdList = this.seed.get('attachmentsIds') || [];

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
        }
        else {
            this.getSessionStorage().clear(this.storageIsInernalKey);
        }
    }

    createCollection(callback, context) {
        this.getCollectionFactory().create('Note', (collection) => {
            this.collection = collection;

            collection.url = this.model.entityType + '/' + this.model.id + '/stream';
            collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.setFilter(this.filter);

            callback.call(context);
        });
    }

    initPostEvents(view) {
        this.listenTo(view, 'add-files', (files) => {
            this.getView('attachments').uploadFiles(files);

            if (!this.postingMode) {
                this.enablePostingMode();
            }
        });
    }

    afterRender() {
        this.$textarea = this.$el.find('textarea[data-name="post"]');
        this.$attachments = this.$el.find('div.attachments');
        this.$postContainer = this.$el.find('.post-container');
        this.$postButton = this.$el.find('button.post');

        let storedText = this.getSessionStorage().get(this.storageTextKey);

        if (storedText && storedText.length) {
            this.hasStoredText = true;
            this.$textarea.val(storedText);
        }

        this.controlPostButtonAvailability(storedText);

        if (this.isInternalNoteMode) {
            this.$el.find('.action[data-action="switchInternalMode"]').addClass('enabled');
        }

        let collection = this.collection;

        this.listenToOnce(collection, 'sync', () => {
            this.createView('list', 'views/stream/record/list', {
                selector: '> .list-container',
                collection: collection,
                model: this.model
            }, view => {
                view.render();
            });

            this.stopListening(this.model, 'all');
            this.stopListening(this.model, 'destroy');

            setTimeout(() => {
                this.listenTo(this.model, 'all', event => {
                    if (!~['sync', 'after:relate'].indexOf(event)) {
                        return;
                    }

                    collection.fetchNew();
                });

                this.listenTo(this.model, 'destroy', () => {
                    this.stopListening(this.model, 'all');
                });
            }, 500);
        });

        if (!this.defs.hidden) {
            collection.fetch();
        }
        else {
            this.once('show', () => collection.fetch());
        }

        let assignmentPermission = this.getAcl().getPermissionLevel('assignmentPermission');

        let buildUserListUrl = term => {
            var url = 'User?orderBy=name&limit=7&q=' + term + '&' + $.param({'primaryFilter': 'active'});

            if (assignmentPermission === 'team') {
                url += '&' + $.param({'boolFilterList': ['onlyMyTeam']})
            }

            return url;
        };

        if (assignmentPermission !== 'no') {
            this.$textarea.textcomplete([{
                match: /(^|\s)@(\w*)$/,
                index: 2,
                search: (term, callback) => {
                    if (term.length === 0) {
                        callback([]);

                        return;
                    }

                    Espo.Ajax
                        .getRequest(buildUserListUrl(term))
                        .then(data => callback(data.list));
                },
                template: (mention) => {
                    return this.getHelper()
                        .escapeString(mention.name) +
                        ' <span class="text-muted">@' +
                        this.getHelper().escapeString(mention.userName) + '</span>';
                },
                replace: (o) => {
                    return '$1@' + o.userName + '';
                },
            }]);

            this.once('remove', () => {
                if (this.$textarea.length) {
                    this.$textarea.textcomplete('destroy');
                }
            });
        }

        let $a = this.$el.find('.buttons-panel a.stream-post-info');

        let text1 = this.translate('infoMention', 'messages', 'Stream');
        let text2 = this.translate('infoSyntax', 'messages', 'Stream');

        let syntaxItemList = [
            ['code', '`{text}`'],
            ['multilineCode', '```{text}```'],
            ['strongText', '**{text}**'],
            ['emphasizedText', '*{text}*'],
            ['deletedText', '~~{text}~~'],
            ['blockquote', '> {text}'],
            ['link', '[{text}](url)'],
        ];

        let messageItemList = [];

        syntaxItemList.forEach(item => {
            let text = this.translate(item[0], 'syntaxItems', 'Stream');
            let result = item[1].replace('{text}', text);

            messageItemList.push(result);
        });

        let $ul = $('<ul>')
            .append(
                messageItemList.map(text => $('<li>').text(text))
            );

        let messageHtml =
            this.getHelper().transformMarkdownInlineText(text1) + '<br><br>' +
            this.getHelper().transformMarkdownInlineText(text2) + ':<br>' +
            $ul.get(0).outerHTML;

        Espo.Ui.popover($a, {
            content: messageHtml,
        }, this);

        this.createView('attachments', 'views/stream/fields/attachment-multiple', {
            model: this.seed,
            mode: 'edit',
            selector: 'div.attachments-container',
            defs: {
                name: 'attachments',
            },
        }, view => {
            view.render();
        });
    }

    afterPost() {
        this.$el.find('textarea.note').prop('rows', 1);
    }

    post() {
        let message = this.$textarea.val();

        this.disablePostButton();
        this.$textarea.prop('disabled', true);

        this.getModelFactory().create('Note', model => {
            if (this.getView('attachments').validateReady()) {
                this.$textarea.prop('disabled', false);
                this.enablePostButton();

                return;
            }

            if (message.trim() === '' && (this.seed.get('attachmentsIds') || []).length === 0) {
                this.notify('Post cannot be empty', 'error');
                this.$textarea.prop('disabled', false);
                this.controlPostButtonAvailability();

                this.$textarea.focus();

                return;
            }

            model.set('post', message);
            model.set('attachmentsIds', Espo.Utils.clone(this.seed.get('attachmentsIds') || []));
            model.set('type', 'Post');
            model.set('isInternal', this.isInternalNoteMode);

            this.prepareNoteForPost(model);

            Espo.Ui.notify(' ... ');

            model.save(null)
                .then(() => {
                    Espo.Ui.success(this.translate('Posted'));

                    this.collection.fetchNew();

                    this.$textarea.prop('disabled', false);
                    this.disablePostingMode();
                    this.afterPost();

                    if (this.getPreferences().get('followEntityOnStreamPost')) {
                        this.model.set('isFollowed', true);
                    }

                    this.getSessionStorage().clear(this.storageTextKey);
                    this.getSessionStorage().clear(this.storageAttachmentsKey);
                    this.getSessionStorage().clear(this.storageIsInernalKey);
                })
                .catch(() => {
                    this.$textarea.prop('disabled', false);
                    this.controlPostButtonAvailability();
                });
        });
    }

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
            html:
                $('<span>')
                    .append(
                        $('<span>').text(this.translate('View List')),
                        ' &middot; ',
                        $('<span>').text(this.translate('posts', 'filters', 'Note')),
                    )
                    .get(0).innerHTML,
            onClick: () => this.actionViewPostList(),
        });

        this.actionList.push(false);

        this.filterList.forEach((item) => {
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

    // noinspection JSUnusedGlobalSymbols
    actionViewPostList() {
        var url = this.model.entityType + '/' + this.model.id + '/posts';

        var data = {
            scope: 'Note',
            viewOptions: {
                url: url,
                title: this.translate('Stream') +
                    ' @right ' + this.translate('posts', 'filters', 'Note'),
                forceSelectAllAttributes: true,
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
        }
        else {
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

    actionRefresh() {
        if (this.hasView('list')) {
            this.getView('list').showNewRecords();
        }
    }

    preview() {
        this.createView('dialog', 'views/modal', {
            templateContent: '<div class="complex-text">' +
                   '{{complexText viewObject.options.text linksInNewTab=true}}</div>',
            text: this.$textarea.val(),
            headerText: this.translate('Preview'),
            backdrop: true,
        }, view => {
            view.render();
        });
    }

    controlPostButtonAvailability(postEntered) {
        let attachmentsIdList = this.seed.get('attachmentsIds') || [];
        let post = this.seed.get('post');

        if (typeof postEntered !== 'undefined') {
            post = postEntered;
        }

        let isEmpty = !post && !attachmentsIdList.length;

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

    disablePostButton() {
        this.$postButton.addClass('disabled').attr('disabled', 'disabled');
    }

    enablePostButton() {
        this.$postButton.removeClass('disabled').removeAttr('disabled');
    }
}

export default PanelStreamView;
