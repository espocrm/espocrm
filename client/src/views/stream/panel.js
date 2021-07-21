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

define('views/stream/panel', ['views/record/panels/relationship', 'lib!Textcomplete'], function (Dep, Textcomplete) {

    return Dep.extend({

        template: 'stream/panel',

        postingMode: false,

        postDisabled: false,

        relatedListFiltersDisabled: true,

        layoutName: null,

        events: _.extend({
            'focus textarea[data-name="post"]': function (e) {
                this.enablePostingMode(true);
            },
            'click button.post': function () {
                this.post();
            },
            'click .action[data-action="switchInternalMode"]': function (e) {
                this.isInternalNoteMode = !this.isInternalNoteMode;

                var $a = $(e.currentTarget);

                if (this.isInternalNoteMode) {
                    $a.addClass('enabled');
                } else {
                    $a.removeClass('enabled');
                }

            },
            'keypress textarea[data-name="post"]': function (e) {
                if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
                    this.post();
                } else if (e.keyCode == 9) {
                    var $text = $(e.currentTarget)
                    if ($text.val() == '') {
                        this.disablePostingMode();
                    }
                }
            },
            'keyup textarea[data-name="post"]': function (e) {
                this.controlPreviewButton();
            },
            'click .action[data-action="preview"]': function () {
                this.preview();
            },
        }, Dep.prototype.events),

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.postDisabled = this.postDisabled;
            data.placeholderText = this.placeholderText;
            data.allowInternalNotes = this.allowInternalNotes;
            return data;
        },

        controlPreviewButton: function () {
            this.$previewButton = this.$previewButton || this.$el.find('.stream-post-preview');
            if (this.$textarea.val() == '') {
                this.$previewButton.addClass('hidden');
            } else {
                this.$previewButton.removeClass('hidden');
            }
        },

        enablePostingMode: function (byFocus) {
            this.$el.find('.buttons-panel').removeClass('hide');

            if (!this.postingMode) {
                if (this.$textarea.val() && this.$textarea.val().length) {
                    this.getView('postField').controlTextareaHeight();
                }
                var isClicked = false;
                $('body').on('click.stream-panel', function (e) {
                    if (byFocus && !isClicked) {
                        isClicked = true;
                        return;
                    }
                    var $target = $(e.target);
                    if ($target.parent().hasClass('remove-attachment')) return;
                    if ($.contains(this.$postContainer.get(0), e.target)) return;
                    if (this.$textarea.val() !== '') return;
                    if ($(e.target).closest('.popover-content').get(0)) return;

                    var attachmentsIds = this.seed.get('attachmentsIds') || [];
                    if (!attachmentsIds.length && (!this.getView('attachments') || !this.getView('attachments').isUploading)) {
                        this.disablePostingMode();
                    }
                }.bind(this));
            }

            this.postingMode = true;

            this.controlPreviewButton();
        },

        disablePostingMode: function () {
            this.postingMode = false;

            this.$textarea.val('');
            if (this.hasView('attachments')) {
                this.getView('attachments').empty();
            }
            this.$el.find('.buttons-panel').addClass('hide');

            $('body').off('click.stream-panel');

            this.$textarea.prop('rows', 1);
        },

        setup: function () {
            this.scope = this.model.name;

            this.filter = this.getStoredFilter();

            this.setupTitle();

            this.placeholderText = this.translate('writeYourCommentHere', 'messages');

            this.allowInternalNotes = false;
            if (!this.getUser().isPortal()) {
                this.allowInternalNotes = this.getMetadata().get(['clientDefs', this.scope, 'allowInternalNotes']);
            }

            this.isInternalNoteMode = false;

            this.storageTextKey = 'stream-post-' + this.model.name + '-' + this.model.id;
            this.storageAttachmentsKey = 'stream-post-attachments-' + this.model.name + '-' + this.model.id;
            this.storageIsInernalKey = 'stream-post-is-internal-' + this.model.name + '-' + this.model.id;

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
                    el: this.getSelector() + ' .textarea-container',
                    name: 'post',
                    mode: 'edit',
                    params: {
                        required: true,
                        rowsMin: 1,
                        rows: 25,
                    },
                    model: this.seed,
                    placeholderText: this.placeholderText
                }, (view) => {
                    this.initPostEvents(view);
                });

                this.createCollection(function () {
                    this.wait(false);
                }, this);
            });

            if (!this.defs.hidden) {
                this.subscribeToWebSocket();
            }

            this.once('show', function () {
                if (!this.isSubscribedToWebSocked) {
                    this.subscribeToWebSocket();
                }
            }, this);

            this.once('remove', function () {
                if (this.isSubscribedToWebSocked) {
                    this.unsubscribeFromWebSocket();
                }
            }.bind(this));
        },

        subscribeToWebSocket: function () {
            if (!this.getConfig().get('useWebSocket')) return;
            if (this.model.entityType === 'User') return;

            var topic = 'streamUpdate.' + this.model.entityType + '.' + this.model.id;
            this.streamUpdateWebSocketTopic = topic;

            this.isSubscribedToWebSocked = true;

            this.getHelper().webSocketManager.subscribe(topic, function (t, data) {
                if (data.createdById === this.getUser().id) return;
                this.collection.fetchNew();
            }.bind(this))
        },

        unsubscribeFromWebSocket: function () {
            this.getHelper().webSocketManager.unsubscribe(this.streamUpdateWebSocketTopic);
        },

        setupTitle: function () {
            this.title = this.translate('Stream');

            this.titleHtml = this.title;

            if (this.filter && this.filter !== 'all') {
                this.titleHtml += ' &middot; ' + this.translate(this.filter, 'filters', 'Note');
            }
        },

        storeControl: function () {
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

            var attachmetIdList = this.seed.get('attachmentsIds') || [];

            if (attachmetIdList.length) {
                this.getSessionStorage().set(this.storageAttachmentsKey, {
                    idList: attachmetIdList,
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
        },

        createCollection: function (callback, context) {
            this.getCollectionFactory().create('Note', function (collection) {
                this.collection = collection;
                collection.url = this.model.name + '/' + this.model.id + '/stream';
                collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;
                this.setFilter(this.filter);

                callback.call(context);
            }, this);
        },

        initPostEvents: function (view) {
            this.listenTo(view, 'add-files', function (files) {
                this.getView('attachments').uploadFiles(files);
            }, this);
        },

        afterRender: function () {
            this.$textarea = this.$el.find('textarea[data-name="post"]');
            this.$attachments = this.$el.find('div.attachments');
            this.$postContainer = this.$el.find('.post-container');

            var $textarea = this.$textarea;

            var storedText = this.getSessionStorage().get(this.storageTextKey);

            if (storedText && storedText.length) {
                this.hasStoredText = true;
                this.$textarea.val(storedText);
            }

            if (this.isInternalNoteMode) {
                this.$el.find('.action[data-action="switchInternalMode"]').addClass('enabled');
            }

            var collection = this.collection;

            this.listenToOnce(collection, 'sync', function () {
                this.createView('list', 'views/stream/record/list', {
                    el: this.options.el + ' > .list-container',
                    collection: collection,
                    model: this.model
                }, function (view) {
                    view.render();
                });

                this.stopListening(this.model, 'all');
                this.stopListening(this.model, 'destroy');
                setTimeout(function () {
                    this.listenTo(this.model, 'all', function (event) {
                        if (!~['sync', 'after:relate'].indexOf(event)) return;
                        collection.fetchNew();
                    }, this);

                    this.listenTo(this.model, 'destroy', function () {
                        this.stopListening(this.model, 'all');
                    }, this);
                }.bind(this), 500);

            }, this);

            if (!this.defs.hidden) {
                collection.fetch();
            } else {
                this.once('show', function () {
                    collection.fetch();
                }, this);
            }

            var assignmentPermission = this.getAcl().get('assignmentPermission');

            var buildUserListUrl = function (term) {
                var url = 'User?orderBy=name&limit=7&q=' + term + '&' + $.param({'primaryFilter': 'active'});
                if (assignmentPermission == 'team') {
                    url += '&' + $.param({'boolFilterList': ['onlyMyTeam']})
                }
                return url;
            }.bind(this);

            if (assignmentPermission !== 'no') {
                this.$textarea.textcomplete([{
                    match: /(^|\s)@(\w*)$/,
                    index: 2,
                    search: function (term, callback) {
                        if (term.length == 0) {
                            callback([]);
                            return;
                        }
                        $.ajax({
                            url: buildUserListUrl(term),
                        }).done(function (data) {
                            callback(data.list)
                        });
                    },
                    template: function (mention) {
                        return this.getHelper().escapeString(mention.name) + ' <span class="text-muted">@' + this.getHelper().escapeString(mention.userName) + '</span>';
                    }.bind(this),
                    replace: function (o) {
                        return '$1@' + o.userName + '';
                    }
                }]);

                this.once('remove', function () {
                    if (this.$textarea.length) {
                        this.$textarea.textcomplete('destroy');
                    }
                }, this);
            }

            var $a = this.$el.find('.buttons-panel a.stream-post-info');

            var message = this.getHelper().transfromMarkdownInlineText(
                this.translate('infoMention', 'messages', 'Stream')
            ) + '<br><br>' +
            this.getHelper().transfromMarkdownInlineText(
                this.translate('infoSyntax', 'messages', 'Stream') + ':'
            ) + '<br>';

            var syntaxItemList = [
                ['code', '`{text}`'],
                ['multilineCode', '```{text}```'],
                ['strongText', '**{text}**'],
                ['emphasizedText', '*{text}*'],
                ['deletedText', '~~{text}~~'],
                ['blockquote', '> {text}'],
                ['link', '[{text}](url)'],
            ];

            var messageItemList = [];

            syntaxItemList.forEach(function (item) {
                var text = this.translate(item[0], 'syntaxItems', 'Stream');
                var result = item[1].replace('{text}', text);
                messageItemList.push(result);
            }, this);

            message += '<ul>' + messageItemList.map(function (item) {
                return '<li>'+ item + '</li>';
            }).join('') + '</ul>';


            $a.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                html: true
            }).on('shown.bs.popover', function () {
                $('body').off('click.popover-' + this.id);
                $('body').on('click.popover-' + this.id , function (e) {
                    if (e.target.classList.contains('popover-content')) return;
                    if ($(e.target).closest('.popover-content').get(0)) return;
                    if ($.contains($a.get(0), e.target)) return;
                    $('body').off('click.popover-' + this.id);
                    $a.popover('hide');
                    e.stopPropagation();
                }.bind(this));
            });

            $a.on('click', function () {
                $(this).popover('toggle');
            });

            this.on('remove', function () {
                if ($a) $a.popover('destroy')
                $('body').off('click.popover-' + this.id);
            }, this);

            this.createView('attachments', 'views/stream/fields/attachment-multiple', {
                model: this.seed,
                mode: 'edit',
                el: this.options.el + ' div.attachments-container',
                defs: {
                    name: 'attachments',
                },
            }, function (view) {
                view.render();
            });
        },

        afterPost: function () {
            this.$el.find('textarea.note').prop('rows', 1);
        },

        post: function () {
            var message = this.$textarea.val();

            this.$textarea.prop('disabled', true);

            this.getModelFactory().create('Note', function (model) {
                if (this.getView('attachments').validateReady()) {
                    this.$textarea.prop('disabled', false)

                    return;
                }

                if (message === '' && (this.seed.get('attachmentsIds') || []).length === 0) {
                    this.notify('Post cannot be empty', 'error');
                    this.$textarea.prop('disabled', false);

                    return;
                }

                this.listenToOnce(model, 'sync', function () {
                    this.notify('Posted', 'success');
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
                }, this);

                model.set('post', message);
                model.set('attachmentsIds', Espo.Utils.clone(this.seed.get('attachmentsIds') || []));
                model.set('type', 'Post');
                model.set('isInternal', this.isInternalNoteMode);

                this.prepareNoteForPost(model);

                this.notify('Posting...');
                model.save(null, {
                    error: function () {
                        this.$textarea.prop('disabled', false);
                    }.bind(this)
                });
            }.bind(this));
        },

        prepareNoteForPost: function (model) {
            model.set('parentId', this.model.id);
            model.set('parentType', this.model.name);
        },

        getButtonList: function () {
            return [];
        },

        filterList: ['all', 'posts', 'updates'],

        setupActions: function () {
            this.actionList = [];

            this.actionList.push({
                action: 'viewPostList',
                html: this.translate('View List') + ' &middot; ' + this.translate('posts', 'filters', 'Note')
            });

            this.actionList.push(false);

            this.filterList.forEach(function (item) {
                var selected = false;
                if (item == 'all') {
                    selected = !this.filter;
                } else {
                    selected = item === this.filter;
                }
                this.actionList.push({
                    action: 'selectFilter',
                    html: '<span class="check-icon fas fa-check pull-right' + (!selected ? ' hidden' : '') + '"></span><div>' + this.translate(item, 'filters', 'Note') + '</div>',
                    data: {
                        name: item
                    }
                });
            }, this);
        },

        actionViewPostList: function () {
            var url = this.model.name + '/' + this.model.id + '/posts';

            var data = {
                scope: 'Note',
                viewOptions: {
                    url: url,
                    title: this.translate('Stream') +
                        ' @right ' + this.translate('posts', 'filters', 'Note'),
                    forceSelectAllAttributes: true
                }
            };
            this.actionViewRelatedList(data);
        },

        getStoredFilter: function () {
            return this.getStorage().get('state', 'streamPanelFilter' + this.scope) || null;
        },

        storeFilter: function (filter) {
            if (filter) {
                this.getStorage().set('state', 'streamPanelFilter' + this.scope, filter);
            } else {
                this.getStorage().clear('state', 'streamPanelFilter' + this.scope);
            }
        },

        setFilter: function (filter) {
            this.filter = filter;
            this.collection.data.filter = null;
            if (filter) {
                this.collection.data.filter = filter;
            }
        },

        actionRefresh: function () {
            if (this.hasView('list')) {
                this.getView('list').showNewRecords();
            }
        },

        preview: function () {
            this.createView('dialog', 'views/modal', {
                templateContent: '<div class="complex-text">{{complexText viewObject.options.text linksInNewTab=true}}</div>',
                text: this.$textarea.val(),
                headerText: this.translate('Preview'),
                backdrop: true,
            }, function (view) {
                view.render();
            });
        },

    });
});
