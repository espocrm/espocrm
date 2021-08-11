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

define('views/fields/wysiwyg', ['views/fields/text', 'lib!Summernote'], function (Dep, Summernote) {

    return Dep.extend({

        type: 'wysiwyg',

        listTemplate: 'fields/wysiwyg/detail',

        detailTemplate: 'fields/wysiwyg/detail',

        editTemplate: 'fields/wysiwyg/edit',

        height: 250,

        rowsDefault: 15,

        seeMoreDisabled: true,

        fetchEmptyValueAsNull: false,

        setup: function () {
            Dep.prototype.setup.call(this);

            if ($.summernote.options && !('espoImage' in $.summernote.options)) {
                this.initEspoPlugin();
            }

            this.hasBodyPlainField = !!~this.getFieldManager()
                .getEntityTypeFieldList(this.model.entityType)
                .indexOf(this.name + 'Plain');

            if ('height' in this.params) {
                this.height = this.params.height;
            }

            if ('minHeight' in this.params) {
                this.minHeight = this.params.minHeight;
            }

            this.useIframe = this.params.useIframe || this.useIframe;

            this.setupToolbar();

            this.listenTo(this.model, 'change:isHtml', (model, value, o) => {
                if (o.ui && this.mode === 'edit') {
                    if (this.isRendered()) {
                        if (!model.has('isHtml') || model.get('isHtml')) {
                            var value = this.plainToHtml(this.model.get(this.name));

                            if (
                                this.lastHtmlValue &&
                                this.model.get(this.name) === this.htmlToPlain(this.lastHtmlValue)
                            ) {
                                value = this.lastHtmlValue;
                            }

                            this.model.set(this.name, value, {skipReRender: true});
                            this.enableWysiwygMode();
                        }
                        else {
                            this.lastHtmlValue = this.model.get(this.name);

                            var value = this.htmlToPlain(this.model.get(this.name));

                            this.disableWysiwygMode();

                            this.model.set(this.name, value);
                        }
                    }
                }
                if (this.mode === 'detail') {
                    if (this.isRendered()) {
                        this.reRender();
                    }
                }
            });

            this.once('remove', () => {
                this.destroySummernote();
            });

            this.on('inline-edit-off', () => {
                this.destroySummernote();
            });

            this.on('render', () => {
                this.destroySummernote();
            });

            this.once('remove', () => {
                $(window).off('resize.' + this.cid);

                if (this.$scrollable) {
                    this.$scrollable.off('scroll.' + this.cid + '-edit');
                }
            });
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.useIframe = this.useIframe;
            data.isPlain = this.isPlain();

            return data;
        },

        events: {
            'click .note-editable': function () {
                this.fixPopovers();
            },
        },

        setupToolbar: function () {
            this.buttons = {};

            this.toolbar = this.params.toolbar || this.toolbar || [
                ['style', ['style']],
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['table', ['table', 'espoLink', 'espoImage', 'hr']],
                ['misc', ['codeview', 'fullscreen']],
            ];

            if (!this.params.toolbar) {
                if (this.params.attachmentField) {
                    this.toolbar.push([
                        'attachment',
                        ['attachment']
                    ]);

                    var AttachmentButton = () => {
                        var ui = $.summernote.ui;

                        var button = ui.button({
                            contents: '<i class="fas fa-paperclip"></i>',
                            tooltip: this.translate('Attach File'),
                            click: () => {
                                this.attachFile();

                                this.listenToOnce(this.model, 'attachment-uploaded:attachments', () => {
                                    if (this.mode === 'edit') {
                                        Espo.Ui.success(this.translate('Attached'));
                                    }
                                });
                            }
                        });

                        return button.render();
                    };

                    this.buttons['attachment'] = AttachmentButton;
                }
            }
        },

        isPlain: function () {
            return this.model.has('isHtml') && !this.model.get('isHtml');
        },

        fixPopovers: function () {
            $('body > .note-popover').removeClass('hidden');
        },

         getValueForDisplay: function () {
            var value = Dep.prototype.getValueForDisplay.call(this);

            if (this.isPlain()) {
                return value;
            }

            return this.sanitizeHtml(value);
        },

        sanitizeHtml: function (value) {
            if (value) {
                if (!this.htmlPurificationDisabled) {
                    value = this.getHelper().sanitizeHtml(value);
                } else {
                    value = this.sanitizeHtmlLight(value);
                }
            }
            return value || '';
        },


        sanitizeHtmlLight: function (value) {
           return this.getHelper().moderateSanitizeHtml(value);
        },

        getValueForEdit: function () {
            var value = this.model.get(this.name) || '';

            if (this.htmlPurificationForEditDisabled) {
                return this.sanitizeHtmlLight(value);
            }

            return this.sanitizeHtml(value);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                this.$summernote = this.$el.find('.summernote');
                this.$noteEditor = this.$el.find('> .note-editor');
            }

            var language = this.getConfig().get('language');

            if (!(language in $.summernote.lang)) {
                $.summernote.lang[language] = this.getLanguage().translate('summernote', 'sets');
            }

            if (this.mode === 'edit') {
                if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                    this.enableWysiwygMode();
                }
                else {
                    this.$element.removeClass('hidden');
                }
            }

            if (this.mode === 'detail' || this.mode === 'list') {
                this.renderDetail();
            }
        },

        renderDetail: function () {
            if (this.model.has('isHtml') && !this.model.get('isHtml')) {
                this.$el.find('.plain').removeClass('hidden');

                return;

            }

            if (!this.useIframe) {
                this.$element = this.$el.find('.html-container');

                return;
            }

            this.$el.find('iframe').removeClass('hidden');

            var $iframe = this.$el.find('iframe');

            var iframeElement = this.iframe = $iframe.get(0);

            if (!iframeElement) {
                return;
            }

            $iframe.on('load', () => {
                $iframe.contents().find('a').attr('target', '_blank');
            });

            var documentElement = iframeElement.contentWindow.document;

            var body = this.sanitizeHtml(this.model.get(this.name) || '');

            var linkElement = iframeElement.contentWindow.document.createElement('link');

            linkElement.type = 'text/css';
            linkElement.rel = 'stylesheet';
            linkElement.href = this.getBasePath() + this.getThemeManager().getIframeStylesheet();

            body = linkElement.outerHTML + body;

            documentElement.write(body);
            documentElement.close();

            var $body = $iframe.contents().find('html body');

            var $document = $(documentElement);

            var processWidth = function () {
                var bodyElement = $body.get(0);
                if (bodyElement) {
                    if (bodyElement.clientWidth !== iframeElement.scrollWidth) {
                        iframeElement.style.height = (iframeElement.scrollHeight + 20) + 'px';
                    }
                }
            };

            var increaseHeightStep = 10;

            var processIncreaseHeight = function (iteration, previousDiff) {
                $body.css('height', '');

                iteration = iteration || 0;

                if (iteration > 200) {
                    return;
                }

                iteration ++;

                var diff = $document.height() - iframeElement.scrollHeight;

                if (typeof previousDiff !== 'undefined') {
                    if (diff === previousDiff) {
                        $body.css('height', (iframeElement.clientHeight - increaseHeightStep) + 'px');
                        processWidth();

                        return;
                    }
                }

                if (diff) {
                    var height = iframeElement.scrollHeight + increaseHeightStep;

                    iframeElement.style.height = height + 'px';
                    processIncreaseHeight(iteration, diff);
                }
                else {
                    processWidth();
                }
            };

            var processHeight = function (isOnLoad) {
                if (!isOnLoad) {
                    $iframe.css({
                        overflowY: 'hidden',
                        overflowX: 'hidden'
                    });

                    iframeElement.style.height = '0px';
                }
                else {
                    if (iframeElement.scrollHeight >= $document.height()) {
                        return;
                    }
                }

                var $body = $iframe.contents().find('html body');
                var height = $body.height();

                if (height === 0) {
                    height = $body.children(0).height() + 100;
                }

                iframeElement.style.height = height + 'px';

                processIncreaseHeight();

                if (!isOnLoad) {
                    $iframe.css({
                        overflowY: 'hidden',
                        overflowX: 'scroll'
                    });
                }
            };

            $iframe.css({
                visibility: 'hidden'
            });

            setTimeout(() => {
                processHeight();

                $iframe.css({
                    visibility: 'visible'
                });

                $iframe.on('load', () => {
                    processHeight(true);
                });
            }, 40);

            if (!this.model.get(this.name)) {
                $iframe.addClass('hidden');
            }

            var windowWidth = $(window).width();

            $(window).off('resize.' + this.cid);
            $(window).on('resize.' + this.cid, () => {
                if ($(window).width() !== windowWidth) {
                    processHeight();
                    windowWidth = $(window).width();
                }
            });
        },

        enableWysiwygMode: function () {
            if (!this.$element) {
                return;
            }

            this.$element.addClass('hidden');
            this.$summernote.removeClass('hidden');

            var contents = this.getValueForEdit();

            this.$summernote.html(contents);

            this.$summernote.find('style').remove();
            this.$summernote.find('link[ref="stylesheet"]').remove();

            var keyMap = Espo.Utils.cloneDeep($.summernote.options.keyMap);
            keyMap.pc['CTRL+K'] = 'espoLink.show';
            keyMap.mac['CMD+K'] = 'espoLink.show';

            var toolbar = this.toolbar;

            var options = {
                espoView: this,
                lang: this.getConfig().get('language'),
                keyMap: keyMap,
                callbacks: {
                    onImageUpload: (files) =>  {
                        var file = files[0];

                        this.notify('Uploading...');

                        this.getModelFactory().create('Attachment', (attachment) => {
                            var fileReader = new FileReader();

                            fileReader.onload = (e) => {
                                attachment.set('name', file.name);
                                attachment.set('type', file.type);
                                attachment.set('role', 'Inline Attachment');
                                attachment.set('global', true);
                                attachment.set('size', file.size);

                                if (this.model.id) {
                                    attachment.set('relatedId', this.model.id);
                                }

                                attachment.set('relatedType', this.model.name);
                                attachment.set('file', e.target.result);
                                attachment.set('field', this.name);

                                attachment.once('sync', () => {
                                    var url = '?entryPoint=attachment&id=' + attachment.id;
                                    this.$summernote.summernote('insertImage', url);

                                    this.notify(false);
                                });

                                attachment.save();
                            };

                            fileReader.readAsDataURL(file);
                        });
                    },
                    onBlur: () => {
                        this.trigger('change')
                    },
                },
                onCreateLink: function (link) {
                    return link;
                },
                toolbar: toolbar,
                buttons: this.buttons,
                dialogsInBody: this.$el,
                codeviewFilter: true,
            };

            if (this.height) {
                options.height = this.height;
            }
            else {
                var $scrollable = this.$el.closest('.modal-body');

                if (!$scrollable.length) {
                    $scrollable = $(window);
                }

                this.$scrollable = $scrollable;

                $scrollable.off('scroll.' + this.cid + '-edit');
                $scrollable.on('scroll.' + this.cid + '-edit', (e) => {
                    this.onScrollEdit(e);
                });
            }

            if (this.minHeight) {
                options.minHeight = this.minHeight;
            }

            this.destroySummernote();

            this.$summernote.summernote(options);
            this.summernoteIsInitialized = true;

            this.$toolbar = this.$el.find('.note-toolbar');
            this.$area = this.$el.find('.note-editing-area');

            this.$area.on('paste', (e) => {
                var items = e.originalEvent.clipboardData.items;

                if (items) {
                    for (var i = 0; i < items.length; i++) {
                        if (!~items[i].type.indexOf('image')) {
                            continue;
                        }

                        var blob = items[i].getAsFile();

                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            });
        },

        destroySummernote: function () {
            if (this.summernoteIsInitialized && this.$summernote) {
                this.$summernote.summernote('destroy');
                this.summernoteIsInitialized = false;
            }
        },

        plainToHtml: function (html) {
            html = html || '';
            var value = html.replace(/\n/g, '<br>');

            return value;
        },

        htmlToPlain: function (text) {
            text = text || '';

            var value = text.replace(/<br\s*\/?>/mg, '\n');

            value = value.replace(/<\/p\s*\/?>/mg, '\n\n');

            var $div = $('<div>').html(value);

            $div.find('style').remove();
            $div.find('link[ref="stylesheet"]').remove();

            value =  $div.text();

            return value;
        },

        disableWysiwygMode: function () {
            this.destroySummernote();

            if (this.$summernote) {
                this.$summernote.addClass('hidden');
            }
            this.$element.removeClass('hidden');

            if (this.$scrollable) {
                this.$scrollable.off('scroll.' + this.cid + '-edit');
            }
        },

        fetch: function () {
            var data = {};

            if (!this.model.has('isHtml') || this.model.get('isHtml')) {
                var code = this.$summernote.summernote('code');

                if (code === '<p><br></p>') {
                    code = '';
                }

                var imageTagString = '<img src="' + window.location.origin + window.location.pathname +
                    '?entryPoint=attachment';

                code = code.replace(
                    new RegExp(imageTagString.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), 'g'),
                    '<img src="?entryPoint=attachment'
                );
                data[this.name] = code;
            }
            else {
                data[this.name] = this.$element.val();

                if (this.fetchEmptyValueAsNull) {
                    if (!data[this.name]) {
                        data[this.name] = null;
                    }
                }
            }

            if (this.model.has('isHtml') && this.hasBodyPlainField) {
                if (this.model.get('isHtml')) {
                    data[this.name + 'Plain'] = this.htmlToPlain(data[this.name]);
                }
                else {
                    data[this.name + 'Plain'] = data[this.name];
                }
            }

            return data;
        },

        onScrollEdit: function (e) {
            var $target = $(e.target);
            var toolbarHeight = this.$toolbar.height();
            var toolbarWidth = this.$toolbar.parent().width();
            var edgeTop, edgeTopAbsolute;

            if ($target.get(0) === window.document) {
                var $buttonContainer = $target.find('.detail-button-container:not(.hidden)');
                var offset = $buttonContainer.offset();

                if (offset) {
                    edgeTop = offset.top + $buttonContainer.height();
                    edgeTopAbsolute = edgeTop - $(window).scrollTop();
                }
            }
            else {
                var offset = $target.offset();

                if (offset) {
                    edgeTop = offset.top;
                    edgeTopAbsolute = edgeTop - $(window).scrollTop();
                }
            }

            var top = this.$el.offset().top;
            var bottom = top + this.$el.height() - toolbarHeight;

            var toStick = false;
            if (edgeTop > top && bottom > edgeTop) {
                toStick = true;
            }

            if (toStick) {
                this.$toolbar.css({
                    top: edgeTopAbsolute + 'px',
                    width: toolbarWidth + 'px'
                });
                this.$toolbar.addClass('sticked');
                this.$area.css({
                    marginTop: toolbarHeight + 'px',
                    backgroundColor: ''
                });
            } else {
                this.$toolbar.css({
                    top: '',
                    width: ''
                });
                this.$toolbar.removeClass('sticked');
                this.$area.css({
                    marginTop: ''
                });
            }
        },

        attachFile: function () {
            var $form = this.$el.closest('.record');
            $form.find('.field[data-name="' + this.params.attachmentField + '"] input.file').click();
        },

       initEspoPlugin: function () {
            var langSets = this.getLanguage().get('Global', 'sets', 'summernote') || {
                image: {},
                link: {},
                video: {},
            };

            $.extend($.summernote.options, {
                espoImage: {
                    icon: '<i class="note-icon-picture"/>',
                    tooltip: langSets.image.image,
                },
                espoLink: {
                    icon: '<i class="note-icon-link"/>',
                    tooltip: langSets.link.link,
                },
            });

            $.extend($.summernote.plugins, {
                'espoImage': function (context) {
                    var ui = $.summernote.ui;
                    var options = context.options;
                    var self = options.espoView;
                    var lang = options.langInfo;

                    if (!self) {
                        return;
                    }

                    context.memo('button.espoImage', () => {
                        var button = ui.button({
                            contents: options.espoImage.icon,
                            tooltip: options.espoImage.tooltip,
                            click: function (e) {
                                context.invoke('espoImage.show');
                            },
                        });

                        return button.render();
                    });

                    this.initialize = function () {};

                    this.destroy = function () {
                        if (!self) {
                            return;
                        }

                        self.clearView('insertImageDialog');
                    };

                    this.show = function () {
                        self.createView('insertImageDialog', 'views/wysiwyg/modals/insert-image', {
                            labels: {
                                insert: lang.image.insert,
                                url: lang.image.url,
                                selectFromFiles: lang.image.selectFromFiles,
                            },
                        }, (view) => {
                            view.render();

                            self.listenToOnce(view, 'upload', (target) => {
                                self.$summernote.summernote('insertImagesOrCallback', target);
                            });

                            self.listenToOnce(view, 'insert', (target) => {
                                self.$summernote.summernote('insertImage', target);
                            });

                            self.listenToOnce(view, 'close', () => {
                                self.clearView('insertImageDialog');
                                self.fixPopovers();
                            });
                        });
                    };
                },

                'linkDialog': function (context) {
                    var options = context.options;
                    var self = options.espoView;
                    var lang = options.langInfo;

                    if (!self) {
                        return;
                    }

                    this.show = function () {
                        var linkInfo = context.invoke('editor.getLinkInfo');

                        self.createView('dialogInsertLink', 'views/wysiwyg/modals/insert-link', {
                            labels: {
                                insert: lang.link.insert,
                                openInNewWindow: lang.link.openInNewWindow,
                                url: lang.link.url,
                                textToDisplay: lang.link.textToDisplay,
                            },
                            linkInfo: linkInfo,
                        }, function (view) {
                            view.render();

                            self.listenToOnce(view, 'insert', function (data) {
                                self.$summernote.summernote('createLink', data);
                            });

                            self.listenToOnce(view, 'close', function () {
                                self.clearView('dialogInsertLink');
                                self.fixPopovers();
                            }, self);
                        });
                    };
                },

                'espoLink': function (context) {
                    var ui = $.summernote.ui;
                    var options = context.options;
                    var self = options.espoView;
                    var lang = options.langInfo;

                    if (!self) {
                        return;
                    }

                    var isMacLike = /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform);

                    context.memo('button.espoLink', function () {
                        var button = ui.button({
                            contents: options.espoLink.icon,
                            tooltip: options.espoLink.tooltip + ' (' + (isMacLike ? 'CMD+K': 'CTRL+K') +')',
                            click: function (e) {
                                context.invoke('espoLink.show');
                            },
                        });

                        return button.render();
                    });

                    this.initialize = function () {
                        this.$modalBody = self.$el.closest('.modal-body');

                        this.isInModal = this.$modalBody.length > 0;
                    };

                    this.destroy = function () {
                        if (!self) {
                            return;
                        }

                        self.clearView('dialogInsertLink');
                    };

                    this.show = function () {
                        var linkInfo = context.invoke('editor.getLinkInfo');

                        let container = this.isInModal ? this.$modalBody.get(0) : window;

                        self.createView('dialogInsertLink', 'views/wysiwyg/modals/insert-link', {
                            labels: {
                                insert: lang.link.insert,
                                openInNewWindow: lang.link.openInNewWindow,
                                url: lang.link.url,
                                textToDisplay: lang.link.textToDisplay,
                            },
                            linkInfo: linkInfo,
                        }, function (view) {
                            view.render();

                            self.listenToOnce(view, 'insert', function (data) {
                                let scrollY = ('scrollY' in container) ?
                                    container.scrollY :
                                    container.scrollTop;

                                self.$summernote.summernote('createLink', data);

                                setTimeout(() => container.scroll(0, scrollY), 20);
                            });

                            self.listenToOnce(view, 'close', function () {
                                self.clearView('dialogInsertLink');
                                self.fixPopovers();
                            }, self);
                        });
                    };
                },

                'fullscreen': function (context) {
                    var ui = $.summernote.ui;
                    var options = context.options;
                    var self = options.espoView;
                    var lang = options.langInfo;

                    if (!self) {
                        return;
                    }

                    this.$window = $(window);
                    this.$scrollbar = $('html, body');

                    this.initialize = function () {
                        this.$editor = context.layoutInfo.editor;
                        this.$toolbar = context.layoutInfo.toolbar;
                        this.$editable = context.layoutInfo.editable;
                        this.$codable = context.layoutInfo.codable;

                        this.$modal = self.$el.closest('.modal');
                        this.isInModal = this.$modal.length > 0;
                    };

                    this.resizeTo = function (size) {
                        this.$editable.css('height', size.h);
                        this.$codable.css('height', size.h);
                        if (this.$codable.data('cmeditor')) {
                            this.$codable.data('cmeditor').setsize(null, size.h);
                        }
                    };

                    this.onResize = function () {
                        this.resizeTo({
                            h: this.$window.height() - this.$toolbar.outerHeight(),
                        });
                    };

                    this.isFullscreen = function () {
                        return this.$editor.hasClass('fullscreen');
                    };

                    this.destroy = function () {
                        this.$window.off('resize.summernote' + self.cid);

                        if (this.isInModal) {
                            this.$modal.css('overflow-y', '');
                        }
                        else {
                            this.$scrollbar.css('overflow', '');
                        }
                    };

                    this.toggle = function () {
                        this.$editor.toggleClass('fullscreen');
                        if (this.isFullscreen()) {
                            this.$editable.data('orgHeight', this.$editable.css('height'));
                            this.$editable.data('orgMaxHeight', this.$editable.css('maxHeight'));
                            this.$editable.css('maxHeight', '');

                            this.$window
                                .on('resize.summernote' + self.cid, this.onResize.bind(this))
                                .trigger('resize');

                            if (this.isInModal) {
                                this.$modal.css('overflow-y', 'hidden');
                            }
                            else {
                                this.$scrollbar.css('overflow', 'hidden');
                            }

                            this._isFullscreen = true;
                        }
                        else {
                            this.$window.off('resize.summernote'  + self.cid);
                            this.resizeTo({ h: this.$editable.data('orgHeight') });
                            this.$editable.css('maxHeight', this.$editable.css('orgMaxHeight'));
                            if (this.isInModal) {
                                this.$modal.css('overflow-y', '');
                            } else {
                                this.$scrollbar.css('overflow', '');
                            }
                            this._isFullscreen = false;
                        }

                        context.invoke('toolbar.updateFullscreen', this.isFullscreen());
                    };
                },

            });
        },
    });
});
