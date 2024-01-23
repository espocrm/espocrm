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

import $ from 'jquery';

/**
 * @type {{
 *     edit: import('ace-builds').edit,
 *     require: import('ace-builds').require,
 * }}
 */
let ace;

function init(langSets) {
    $.extend($.summernote.options, {
        espoImage: {
            icon: '<i class="note-icon-picture"/>',
            tooltip: langSets.image.image,
        },
        espoLink: {
            icon: '<i class="note-icon-link"/>',
            tooltip: langSets.link.link,
        },
        espoTable: {
            icon: '<i class="note-icon-table"/>',
            tooltip: langSets.table.table,
        },
    });

    $.extend($.summernote.plugins, {
        'aceCodeview': function (/** Record */context) {
            const ui = $.summernote.ui;
            const options = context.options;

            const lang = options.langInfo;
            /** @type {JQuery} */
            const $editor = context.layoutInfo.editor;
            /** @type {JQuery} */
            const $editable = context.layoutInfo.editable;

            const view = /** @type {module:view} */options.espoView;

            if (!view) {
                return;
            }

            // noinspection SpellCheckingInspection
            let $codable;
            /** @type {import('ace-builds').Ace.Editor} */
            let aceEditor;
            let isActivated = false;
            let isReplaced = false;
            let isBeingActivated = false;
            const EMPTY = '<p>&nbsp;</p>';

            context.destroyAceCodeview = function () {
                if (isActivated) {
                    deactivate();
                }
            };

            context.code = function (html) {
                if (html === undefined) {
                    let value;

                    if (isActivated) {
                        value = prepareValue(aceEditor.getValue()) || EMPTY

                        $editable.html(value);
                    }

                    return isActivated ? value : $editable.html();
                }

                if (isActivated) {
                    aceEditor.setValue(html);
                }

                $editable.html(html);

                this.$note.val(html);
                context.triggerEvent('change', html, $editable);
            };

            const id = 'editor-' + Math.random().toString(36).substring(2, 17);

            const prepareValue = (input) => {
                return input;
            };

            const prepareHtml = (/** string */input) => {
                return prepareValue(input);
            };

            const toggle = () => {
                isActivated ?
                    deactivate() :
                    activate();

                context.triggerEvent('codeview.toggled');
            };

            const deactivate = () => {
                if (!isActivated) {
                    return;
                }

                $codable.addClass('hidden');

                let value = prepareValue(aceEditor.getValue()) || EMPTY;
                value = context.invoke('codeview.purify', value);

                const isChange = $editable.html() !== value;

                $editable.html(value);

                $editor.removeClass('codeview');

                if (isChange) {
                    context.triggerEvent('change', $editable.html(), $editable);
                }

                $editable.focus();
                context.invoke('toolbar.updateCodeview', false);
                context.invoke('airPopover.updateCodeview', false);

                if (aceEditor) {
                    aceEditor.destroy();
                    aceEditor = null;
                }

                context.aceEditor = undefined;

                isActivated = false;
            };

            const activate = () => {
                if (isBeingActivated) {
                    return;
                }

                isBeingActivated = true;

                context.invoke('toolbar.updateCodeview', true);
                context.invoke('airPopover.updateCodeview', true);
                $editor.addClass('codeview');

                if (!isReplaced) {
                    $codable = $('<div>')
                        .attr('id', id)
                        .css('minHeight', 40 + 'px');
                    $(context.layoutInfo.codable).replaceWith($codable);

                    context.$aceCodable = $codable;

                    isReplaced = true;
                }

                $codable.removeClass('hidden');

                requireAce().then(() => {
                    const html = prepareHtml($editable.html());

                    aceEditor = ace.edit(id);

                    aceEditor.setValue(html);

                    aceEditor.setOptions({
                        maxLines: !$editor.hasClass('fullscreen') ? 34: null,
                        enableLiveAutocompletion: true,
                        tabSize: 2,
                        useSoftTabs: true,
                    });

                    if (options.isDark) {
                        aceEditor.setOptions({theme: 'ace/theme/tomorrow_night'});
                    }

                    aceEditor.getSession().setUseWrapMode(true);
                    aceEditor.setShowPrintMargin(false);
                    aceEditor.getSession().setUseWorker(false);
                    aceEditor.commands.removeCommand('find');
                    aceEditor.setHighlightActiveLine(false);
                    aceEditor.focus();

                    try {
                        aceEditor.gotoLine(0, 0, false);
                    }
                    catch (e) {}

                    aceEditor.on('blur', e => {
                        context.triggerEvent('blur.codeview', aceEditor.getValue(), e);
                    });
                    aceEditor.on('change', () => {
                        context.triggerEvent('change.codeview', aceEditor.getValue());
                    });

                    // noinspection JSValidateTypes
                    context.aceEditor = aceEditor;

                    const modeToRequired = options.handlebars ?
                        'ace/mode/handlebars' :
                        'ace/mode/html';

                    const Mode = ace.require(modeToRequired).Mode;
                    aceEditor.session.setMode(new Mode());

                    isActivated = true;
                    isBeingActivated = false;
                });
            };

            context.memo('button.aceCodeview', () => {
                return ui.button({
                    className: 'btn-codeview note-codeview-keep',
                    contents: '<i class="note-icon-code"/>',
                    tooltip: lang.options.codeview,
                    click: () => toggle(),
                }).render();
            });

            /**
             * @return Promise
             */
            const requireAce = function () {
                return Espo.loader.requirePromise('lib!ace')
                    .then(lib => {
                        ace = /** window.ace */lib;

                        const list = [
                            Espo.loader.requirePromise('lib!ace-ext-language_tools'),
                        ];

                        list.push(
                            options.handlebars ?
                                Espo.loader.requirePromise('lib!ace-mode-handlebars') :
                                Espo.loader.requirePromise('lib!ace-mode-html')
                        );

                        if (options.isDark) {
                            list.push(Espo.loader.requirePromise('lib!ace-theme-tomorrow_night'));
                        }

                        return Promise.all(list);
                    });
            }
        },

        'espoTable': function (context) {
            const ui = $.summernote.ui;
            const options = context.options;
            const self = options.espoView;
            const lang = options.langInfo;

            if (!self) {
                return;
            }

            context.memo('button.espoTable', () => {
                return ui.buttonGroup([
                    ui.button({
                        className: 'dropdown-toggle',
                        contents: ui.dropdownButtonContents(ui.icon(options.icons.table), options),
                        tooltip: options.espoTable.tooltip,
                        data: {
                            toggle: 'dropdown',
                        },
                    }),
                    ui.dropdown({
                        title: lang.table.table,
                        className: 'note-table',
                        items: [
                            '<div class="note-dimension-picker">',
                            '<div class="note-dimension-picker-mousecatcher" data-event="insertTable" data-value="1x1"></div>',
                            '<div class="note-dimension-picker-highlighted"></div>',
                            '<div class="note-dimension-picker-unhighlighted"></div>',
                            '</div>',
                            '<div class="note-dimension-display">1 x 1</div>',
                        ].join(''),
                    }),
                ], {
                    callback: ($node) => {
                        const $catcher = $node.find('.note-dimension-picker-mousecatcher');

                        const createTable = (colCount, rowCount, options) => {
                            const tds = [];
                            let tdHTML;

                            for (let idxCol = 0; idxCol < colCount; idxCol++) {
                                tds.push('<td>&nbsp;</td>');
                            }

                            tdHTML = tds.join('\n');
                            const trs = [];
                            let trHTML;

                            for (let idxRow = 0; idxRow < rowCount; idxRow++) {
                                trs.push('<tr>' + tdHTML + '</tr>');
                            }

                            trHTML = trs.join('\n');
                            const $table = $('<table>\n' + trHTML + '</table>');

                            /*if (options.tableBorderWidth !== undefined) {
                                $table.attr('border', options.tableBorderWidth);
                            }

                            if (options.tableCellPadding !== undefined) {
                                $table.attr('cellpadding', options.tableCellPadding);
                            }*/

                            $table.css({
                                width: '100%',
                                borderCollapse: 'collapse',
                                //borderSpacing: 0,
                            });

                            if (options && options.tableClassName) {
                                $table.addClass(options.tableClassName);
                            }

                            return $table[0];
                        };

                        $catcher
                            .css({
                                width: options.insertTableMaxSize.col + 'em',
                                height: options.insertTableMaxSize.row + 'em',
                            })
                            .mousedown(() => {
                                const $note = context.$note;
                                const dims = $catcher.data('value').split('x');

                                const range = $note.summernote('editor.getLastRange').deleteContents();

                                createTable(dims[0], dims[1], options)

                                range.insertNode(
                                    createTable(dims[0], dims[1], options)
                                );
                            })
                            .on('mousemove', event => {
                                const PX_PER_EM = 18;
                                const $picker = $(event.target.parentNode);

                                const $dimensionDisplay = $picker.next();
                                const $catcher = $picker.find('.note-dimension-picker-mousecatcher');
                                const $highlighted = $picker.find('.note-dimension-picker-highlighted');
                                const $unhighlighted = $picker.find('.note-dimension-picker-unhighlighted');
                                let posOffset;

                                if (event.offsetX === undefined) {
                                    const posCatcher = $(event.target).offset();
                                    posOffset = {
                                        x: event.pageX - posCatcher.left,
                                        y: event.pageY - posCatcher.top
                                    };
                                } else {
                                    posOffset = {
                                        x: event.offsetX,
                                        y: event.offsetY
                                    };
                                }

                                const dim = {
                                    c: Math.ceil(posOffset.x / PX_PER_EM) || 1,
                                    r: Math.ceil(posOffset.y / PX_PER_EM) || 1
                                };
                                $highlighted.css({
                                    width: dim.c + 'em',
                                    height: dim.r + 'em'
                                });

                                $catcher.data('value', dim.c + 'x' + dim.r);

                                if (dim.c > 3 && dim.c < options.insertTableMaxSize.col) {
                                    $unhighlighted.css({
                                        width: dim.c + 1 + 'em'
                                    });
                                }

                                if (dim.r > 3 && dim.r < options.insertTableMaxSize.row) {
                                    $unhighlighted.css({
                                        height: dim.r + 1 + 'em'
                                    });
                                }

                                $dimensionDisplay.html(dim.c + ' x ' + dim.r);
                            });
                    },
                }).render();
            });
        },
        'espoImage': function (context) {
            const ui = $.summernote.ui;
            const options = context.options;
            const self = options.espoView;
            const lang = options.langInfo;

            if (!self) {
                return;
            }

            context.memo('button.espoImage', () => {
                const button = ui.button({
                    contents: options.espoImage.icon,
                    tooltip: options.espoImage.tooltip,
                    click() {
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
                }, view => {
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
            const options = context.options;
            const self = options.espoView;
            const lang = options.langInfo;

            if (!self) {
                return;
            }

            this.show = function () {
                const linkInfo = context.invoke('editor.getLinkInfo');

                self.createView('dialogInsertLink', 'views/wysiwyg/modals/insert-link', {
                    labels: {
                        insert: lang.link.insert,
                        openInNewWindow: lang.link.openInNewWindow,
                        url: lang.link.url,
                        textToDisplay: lang.link.textToDisplay,
                    },
                    linkInfo: linkInfo,
                }, view => {
                    view.render();

                    self.listenToOnce(view, 'insert', (data) => {
                        self.$summernote.summernote('createLink', data);
                    });

                    self.listenToOnce(view, 'close', () => {
                        self.clearView('dialogInsertLink');
                        self.fixPopovers();
                    });
                });
            };
        },

        'espoLink': function (context) {
            const ui = $.summernote.ui;
            const options = context.options;
            const self = options.espoView;
            const lang = options.langInfo;

            if (!self) {
                return;
            }

            const isMacLike = /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform);

            context.memo('button.espoLink', function () {
                const button = ui.button({
                    contents: options.espoLink.icon,
                    tooltip: options.espoLink.tooltip + ' (' + (isMacLike ? 'CMD+K': 'CTRL+K') +')',
                    click() {
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
                const linkInfo = context.invoke('editor.getLinkInfo');

                const container = this.isInModal ? this.$modalBody.get(0) : window;

                self.createView('dialogInsertLink', 'views/wysiwyg/modals/insert-link', {
                    labels: {
                        insert: lang.link.insert,
                        openInNewWindow: lang.link.openInNewWindow,
                        url: lang.link.url,
                        textToDisplay: lang.link.textToDisplay,
                    },
                    linkInfo: linkInfo,
                }, (view) => {
                    view.render();

                    self.listenToOnce(view, 'insert', (data) => {
                        const scrollY = ('scrollY' in container) ?
                            container.scrollY :
                            container.scrollTop;

                        self.$summernote.summernote('createLink', data);

                        setTimeout(() => container.scroll(0, scrollY), 20);
                    });

                    self.listenToOnce(view, 'close', () => {
                        self.clearView('dialogInsertLink');
                        self.fixPopovers();
                    });
                });
            };
        },

        'fullscreen': function (context) {
            const options = context.options;
            const self = options.espoView;

            if (!self) {
                return;
            }

            this.$window = $(window);
            this.$scrollbar = $('html, body');

            this.initialize = function () {
                this.$editor = context.layoutInfo.editor;
                this.$toolbar = context.layoutInfo.toolbar;
                this.$editable = context.layoutInfo.editable;

                this.$modal = self.$el.closest('.modal');
                this.isInModal = this.$modal.length > 0;
            };

            this.resizeTo = function (size) {
                this.$editable.css('height', size.h);

                if (context.$aceCodable) {
                    context.$aceCodable.css('height', size.h);
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

            let maxLines;

            this.toggle = function () {
                this.$editor.toggleClass('fullscreen');

                const aceEditor = /** @type {import('ace-builds').Ace.Editor} */context.aceEditor;

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

                    // noinspection JSUnusedGlobalSymbols
                    this._isFullscreen = true;

                    if (aceEditor) {
                        maxLines = aceEditor.getOption('maxLines');

                        aceEditor.setOptions({maxLines: null});
                        aceEditor.resize();
                    }
                }
                else {
                    this.$window.off('resize.summernote'  + self.cid);
                    this.resizeTo({h: this.$editable.data('orgHeight')});

                    if (context.$aceCodable) {
                        context.$aceCodable.css('height', '');
                    }

                    this.$editable.css('maxHeight', this.$editable.css('orgMaxHeight'));

                    if (this.isInModal) {
                        this.$modal.css('overflow-y', '');
                    } else {
                        this.$scrollbar.css('overflow', '');
                    }

                    // noinspection JSUnusedGlobalSymbols
                    this._isFullscreen = false;

                    if (aceEditor) {
                        aceEditor.setOptions({maxLines: maxLines});
                        aceEditor.resize();
                    }
                }

                context.invoke('toolbar.updateFullscreen', this.isFullscreen());
            };
        },
    });
}

export {init};
