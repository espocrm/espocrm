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

/** @module views/fields/wysiwyg */

import TextFieldView from 'views/fields/text';
import {init as initSummernoteCustom} from 'helpers/misc/summernote-custom';

/**
 * A wysiwyg field.
 *
 * @extends TextFieldView<module:views/fields/wysiwyg~params>
 */
class WysiwygFieldView extends TextFieldView {

    /**
     * @typedef {Object} module:views/fields/wysiwyg~options
     * @property {
     *     module:views/fields/wysiwyg~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/wysiwyg~params
     * @property {boolean} [required] Required.
     * @property {number} [maxLength] A max length.
     * @property {number} [height] A height in pixels.
     * @property {number} [minHeight] A min height in pixels.
     * @property {boolean} [useIframe] Use iframe.
     * @property {Array} [toolbar] A custom toolbar.
     * @property {string} [attachmentField] An attachment field name.
     */

    /**
     * @param {
     *     module:views/fields/wysiwyg~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'wysiwyg'

    listTemplate = 'fields/wysiwyg/detail'
    detailTemplate = 'fields/wysiwyg/detail'
    editTemplate = 'fields/wysiwyg/edit'

    height = 250
    rowsDefault = 10000
    fallbackBodySideMargin = 5
    fallbackBodyTopMargin = 4
    seeMoreDisabled = true
    fetchEmptyValueAsNull = true
    validationElementSelector = '.note-editor'
    htmlPurificationDisabled = false
    htmlPurificationForEditDisabled = false
    tableClassName = 'table table-bordered'
    noStylesheet = false
    useIframe = false
    handlebars = false

    /** @protected */
    toolbar
    /** @protected */
    hasBodyPlainField = false

    events = {
        /** @this WysiwygFieldView */
        'click .note-editable': function () {
            this.fixPopovers();
        },
        /** @this WysiwygFieldView */
        'focus .note-editable': function () {
            this.$noteEditor.addClass('in-focus');
        },
        /** @this WysiwygFieldView */
        'blur .note-editable': function () {
            this.$noteEditor.removeClass('in-focus');
        },
    }

    setup() {
        super.setup();
        this.loadSummernote();

        if ('height' in this.params) {
            this.height = this.params.height;
        }

        if ('minHeight' in this.params) {
            this.minHeight = this.params.minHeight;
        }

        this.useIframe = this.params.useIframe || this.useIframe;

        this.setupToolbar();
        this.setupIsHtml();

        this.once('remove', () => this.destroySummernote());
        this.on('inline-edit-off', () => this.destroySummernote());
        this.on('render', () => this.destroySummernote());

        this.once('remove', () => {
            $(window).off(`resize.${this.cid}`);

            if (this.$scrollable) {
                this.$scrollable.off(`scroll.${this.cid}-edit`);
            }
        });
    }

    /** @private */
    loadSummernote() {
        this.wait(
            Espo.loader.requirePromise('lib!summernote')
                .then(() => {
                    if (!$.summernote.options || 'espoImage' in $.summernote.options) {
                        return;
                    }

                    this.initEspoPlugin();
                })
        );
    }

    /** @protected */
    setupIsHtml() {
        if (!this.hasBodyPlainField) {
            return;
        }

        this.listenTo(this.model, 'change:isHtml', (model, value, o) => {
            if (o.ui && this.isEditMode()) {
                if (!this.isRendered()) {
                    return;
                }

                if (this.isHtml()) {
                    let value = this.plainToHtml(this.model.get(this.name));

                    if (
                        this.lastHtmlValue &&
                        this.model.get(this.name) === this.htmlToPlain(this.lastHtmlValue)
                    ) {
                        value = this.lastHtmlValue;
                    }

                    this.model.set(this.name, value, {skipReRender: true});
                    this.enableWysiwygMode();

                    return;
                }

                this.lastHtmlValue = this.model.get(this.name);
                const value = this.htmlToPlain(this.model.get(this.name));

                this.disableWysiwygMode();
                this.model.set(this.name, value);

                return;
            }

            if (this.isDetailMode() && this.isRendered()) {
                this.reRender();
            }
        });
    }

    data() {
        const data = super.data();

        data.useIframe = this.useIframe;
        data.isPlain = !this.isHtml();

        data.isNone = !data.isNotEmpty && data.valueIsSet && this.isDetailMode();

        // noinspection JSValidateTypes
        return data;
    }

    setupToolbar() {
        this.buttons = {};

        const codeviewName = this.getConfig().get('wysiwygCodeEditorDisabled') ?
            'codeview' : 'aceCodeview';

        this.toolbar = this.params.toolbar || this.toolbar || [
            ['style', ['style']],
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['table', ['espoTable', 'espoLink', 'espoImage', 'hr']],
            ['misc', [codeviewName, 'fullscreen']],
        ];

        if (this.params.toolbar) {
            return;
        }

        if (!this.params.attachmentField) {
            return;
        }

        this.toolbar.push(['attachment', ['attachment']]);

        this.buttons['attachment'] = () => {
            const ui = $.summernote.ui;

            const button = ui.button({
                contents: '<i class="fas fa-paperclip"></i>',
                tooltip: this.translate('Attach File'),
                click: () => {
                    this.attachFile();
                }
            });

            return button.render();
        };
    }

    /**
     * @protected
     * @return {boolean}
     */
    isHtml() {
        if (!this.hasBodyPlainField) {
            return true;
        }

        return !this.model.has('isHtml') || this.model.get('isHtml');
    }

    fixPopovers() {
        $('body > .note-popover').removeClass('hidden');
    }

    getValueForDisplay() {
        if (!this.isReadMode() && this.isHtml()) {
            return undefined;
        }

        const value = super.getValueForDisplay();

        if (!this.isHtml()) {
            return value;
        }

        return this.sanitizeHtml(value);
    }

    /**
     * @protected
     * @param {string} value
     * @return {string}
     */
    sanitizeHtml(value) {
        if (!value) {
            return '';
        }

        if (this.htmlPurificationDisabled) {
            return this.sanitizeHtmlLight(value);
        }

        value = this.getHelper().sanitizeHtml(value);

        if (this.isEditMode()) {
            // Trick to handle the issue that attributes are re-ordered.
            value = this.getHelper().sanitizeHtml(value);
        }

        return value;
    }

    sanitizeHtmlLight(value) {
       return this.getHelper().moderateSanitizeHtml(value);
    }

    getValueForEdit() {
        const value = this.model.get(this.name) || '';

        if (this.htmlPurificationForEditDisabled) {
            return this.sanitizeHtmlLight(value);
        }

        return this.sanitizeHtml(value);
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            this.$summernote = this.$el.find('.summernote');
        }

        const language = this.getConfig().get('language');

        if (!(language in $.summernote.lang)) {
            $.summernote.lang[language] = this.getLanguage().translate('summernote', 'sets');
        }

        if (this.isEditMode()) {
            if (this.isHtml()) {
                this.enableWysiwygMode();
            } else {
                this.$element.removeClass('hidden');
            }

            if (this.params.attachmentField && this.isInlineEditMode()) {
                this.$el.find('.note-attachment').addClass('hidden');
            }
        }

        if (this.isReadMode()) {
            this.renderDetail();
        }
    }

    renderDetail() {
        if (!this.isHtml()) {
            this.$el.find('.plain').removeClass('hidden');

            return;
        }

        if (!this.useIframe) {
            this.$element = this.$el.find('.html-container');

            return;
        }

        this.$el.find('iframe').removeClass('hidden');

        const $iframe = this.$el.find('iframe');

        /** @type {HTMLIFrameElement} */
        const iframeElement = this.iframe = $iframe.get(0);

        iframeElement.setAttribute('sandbox', '');

        if (!iframeElement || !iframeElement.contentWindow) {
            return;
        }

        $iframe.on('load', () => {
            $iframe.contents().find('a').attr('target', '_blank');
        });

        const documentElement = iframeElement.contentWindow.document;

        let bodyHtml = this.getValueForIframe();

        const useFallbackStylesheet = this.getThemeManager().getParam('isDark') && this.htmlHasColors(bodyHtml);
        const addFallbackClass = this.getThemeManager().getParam('isDark') &&
            (this.htmlHasColors(bodyHtml) || this.noStylesheet);

        const $iframeContainer = $iframe.parent();

        addFallbackClass ?
            $iframeContainer.addClass('fallback') :
            $iframeContainer.removeClass('fallback');

        if (!this.noStylesheet) {
            const linkElement = iframeElement.contentWindow.document.createElement('link');

            linkElement.type = 'text/css';
            linkElement.rel = 'stylesheet';
            linkElement.href = this.getBasePath() + (
                useFallbackStylesheet ?
                    this.getThemeManager().getIframeFallbackStylesheet() :
                    this.getThemeManager().getIframeStylesheet()
            );

            bodyHtml = linkElement.outerHTML + bodyHtml;
        }

        let headHtml = '';

        if (this.noStylesheet) {
            const styleElement = documentElement.createElement('style');

            styleElement.textContent = `\ntable.bordered, table.bordered td, table.bordered th {border: 1px solid;}\n`;

            headHtml = styleElement.outerHTML;
        }

        // noinspection HtmlRequiredTitleElement
        const documentHtml = `<head>${headHtml}</head><body>${bodyHtml}</body>`

        documentElement.write(documentHtml);
        documentElement.close();

        const $body = $iframe.contents().find('html body');

        $body.find('img').each((i, img) => {
            const $img = $(img);

            if ($img.css('max-width') !== 'none') {
                return;
            }

            $img.css('max-width', '100%');
        });

        const $document = $(documentElement);

        // Make dropdowns closed.
        $document.on('click', () => {
            const event = new MouseEvent('click', {
                bubbles: true,
            });

            $iframe[0].dispatchEvent(event);
        });

        // Make notifications & global-search popup closed.
        $document.on('mouseup', () => {
            const event = new MouseEvent('mouseup', {
                bubbles: true,
            });

            $iframe[0].dispatchEvent(event);
        });

        // Make shortcuts working.
        $document.on('keydown', e => {
            const originalEvent = /** @type {KeyboardEvent} */ e.originalEvent;

            const event = new KeyboardEvent('keydown', {
                bubbles: true,
                code: originalEvent.code,
                ctrlKey: originalEvent.ctrlKey,
                metaKey: originalEvent.metaKey,
                altKey: originalEvent.altKey,
            });

            $iframe[0].dispatchEvent(event);
        });

        const processWidth = function () {
            const bodyElement = $body.get(0);

            if (bodyElement) {
                if (bodyElement.clientWidth !== iframeElement.scrollWidth) {
                    iframeElement.style.height = (iframeElement.scrollHeight + 20) + 'px';
                }
            }
        };

        if (useFallbackStylesheet) {
            $iframeContainer.css({
                paddingLeft: this.fallbackBodySideMargin + 'px',
                paddingRight: this.fallbackBodySideMargin + 'px',
                paddingTop: this.fallbackBodyTopMargin + 'px',
            });
        }

        const increaseHeightStep = 10;

        const processIncreaseHeight = function (iteration, previousDiff) {
            $body.css('height', '');

            iteration = iteration || 0;

            if (iteration > 200) {
                return;
            }

            iteration ++;

            const diff = $document.height() - iframeElement.scrollHeight;

            if (typeof previousDiff !== 'undefined') {
                if (diff === previousDiff) {
                    $body.css('height', (iframeElement.clientHeight - increaseHeightStep) + 'px');
                    processWidth();

                    return;
                }
            }

            if (diff) {
                const height = iframeElement.scrollHeight + increaseHeightStep;

                iframeElement.style.height = height + 'px';
                processIncreaseHeight(iteration, diff);
            } else {
                processWidth();
            }
        };

        const processBg = () => {
            const color = iframeElement.contentWindow.getComputedStyle($body.get(0)).backgroundColor;

            $iframeContainer.css({
                backgroundColor: color,
            });
        };

        const processHeight = function (isOnLoad) {
            if (!isOnLoad) {
                $iframe.css({
                    overflowY: 'hidden',
                    overflowX: 'hidden',
                });

                iframeElement.style.height = '0px';
            }
            else {
                if (iframeElement.scrollHeight >= $document.height()) {
                    return;
                }
            }

            const $body = $iframe.contents().find('html body');
            let height = $body.height();

            if (height === 0) {
                height = $body.children().height() + 100;
            }

            iframeElement.style.height = height + 'px';

            processIncreaseHeight();

            if (!isOnLoad) {
                $iframe.css({
                    overflowY: 'hidden',
                    overflowX: 'scroll',
                });
            }
        };

        $iframe.css({
            visibility: 'hidden'
        });

        setTimeout(() => {
            processHeight();

            $iframe.css({
                visibility: 'visible',
            });

            $iframe.on('load', () => {
                processHeight(true);

                if (useFallbackStylesheet && !this.noStylesheet) {
                    processBg();
                }
            });
        }, 40);

        if (!this.model.get(this.name)) {
            $iframe.addClass('hidden');
        }

        let windowWidth = $(window).width();

        $(window).off('resize.' + this.cid);
        $(window).on('resize.' + this.cid, () => {
            if ($(window).width() !== windowWidth) {
                processHeight();
                windowWidth = $(window).width();
            }
        });
    }

    /**
     * @protected
     * @return {string}
     */
    getValueForIframe() {
        return this.sanitizeHtml(this.model.get(this.name) || '');
    }

    enableWysiwygMode() {
        if (!this.$element) {
            return;
        }

        this.$element.addClass('hidden');
        this.$summernote.removeClass('hidden');

        const contents = this.getValueForEdit();

        this.$summernote.html(contents);

        // The same sanitizing in the email body field.
        this.$summernote.find('style').remove();
        this.$summernote.find('link[ref="stylesheet"]').remove();

        const keyMap = Espo.Utils.cloneDeep($.summernote.options.keyMap);

        keyMap.pc['CTRL+K'] = 'espoLink.show';
        keyMap.mac['CMD+K'] = 'espoLink.show';
        keyMap.pc['CTRL+DELETE'] = 'removeFormat';
        keyMap.mac['CMD+DELETE']  = 'removeFormat';

        delete keyMap.pc['CTRL+ENTER'];
        delete keyMap.mac['CMD+ENTER'];
        delete keyMap.pc['CTRL+BACKSLASH'];
        delete keyMap.mac['CMD+BACKSLASH'];

        const toolbar = this.toolbar;

        let lastChangeKeydown = new Date();
        const changeKeydownInterval = this.changeInterval * 1000;

        // noinspection JSUnusedGlobalSymbols
        const options = {
            handlebars: this.handlebars,
            prettifyHtml: false, // should not be true
            disableResizeEditor: true,
            isDark: this.getThemeManager().getParam('isDark'),
            espoView: this,
            lang: this.getConfig().get('language'),
            keyMap: keyMap,
            callbacks: {
                onImageUpload: (files) => {
                    const file = files[0];

                    Espo.Ui.notify(this.translate('Uploading...'));

                    this.uploadInlineAttachment(file)
                        .then(attachment => {
                            const url = '?entryPoint=attachment&id=' + attachment.id;
                            this.$summernote.summernote('insertImage', url);

                            Espo.Ui.notify(false);
                        });
                },
                onBlur: () => {
                    this.trigger('change');
                },
                onKeydown: () => {
                    if (Date.now() - lastChangeKeydown > changeKeydownInterval) {
                        this.trigger('change');
                        lastChangeKeydown = Date.now();
                    }
                },
            },
            onCreateLink(link) {
                return link;
            },
            toolbar: toolbar,
            buttons: this.buttons,
            dialogsInBody: this.$el,
            codeviewFilter: true,
            tableClassName: this.tableClassName,
            // Dnd has issues.
            disableDragAndDrop: true,
            colorButton: {
                foreColor: '#000000',
                backColor: '#FFFFFF'
            },
        };

        if (this.height) {
            options.height = this.height;
        } else {
            let $scrollable = this.$el.closest('.modal-body');

            if (!$scrollable.length) {
                $scrollable = $(window);
            }

            this.$scrollable = $scrollable;

            $scrollable.off(`scroll.${this.cid}-edit`);
            $scrollable.on(`scroll.${this.cid}-edit`, e => this.onScrollEdit(e));
        }

        if (this.minHeight) {
            options.minHeight = this.minHeight;
        }

        this.destroySummernote();

        this.$summernote.summernote(options);
        this.summernoteIsInitialized = true;

        this.$toolbar = this.$el.find('.note-toolbar');
        this.$area = this.$el.find('.note-editing-area');

        this.$noteEditor = this.$el.find('> .note-editor');
    }

    focusOnInlineEdit() {
        if (this.$noteEditor)  {
            this.$summernote.summernote('focus');

            return;
        }

        super.focusOnInlineEdit();
    }

    uploadInlineAttachment(file) {
        return new Promise((resolve, reject) => {
            this.getModelFactory().create('Attachment', attachment => {
                const fileReader = new FileReader();

                fileReader.onload = (e) => {
                    attachment.set('name', file.name);
                    attachment.set('type', file.type);
                    attachment.set('role', 'Inline Attachment');
                    attachment.set('global', true);
                    attachment.set('size', file.size);

                    if (this.model.id) {
                        attachment.set('relatedId', this.model.id);
                    }

                    attachment.set('relatedType', this.model.entityType);
                    attachment.set('file', e.target.result);
                    attachment.set('field', this.name);

                    attachment.save()
                        .then(() => resolve(attachment))
                        .catch(() => reject());
                };

                fileReader.readAsDataURL(file);
            });
        });
    }

    destroySummernote() {
        if (this.summernoteIsInitialized && this.$summernote) {
            this.$summernote.summernote('destroyAceCodeview');
            this.$summernote.summernote('destroy');
            this.summernoteIsInitialized = false;
        }
    }

    plainToHtml(html) {
        html = html || '';

        return html.replace(/\n/g, '<br>');
    }

    /**
     * @protected
     * @param {string} html
     * @return {string}
     */
    htmlToPlain(html) {
        const div = document.createElement('div');
        div.innerHTML = html;

        /**
         * @param {Node|HTMLElement} node
         * @return {string}
         */
        function processNode(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                return node.nodeValue;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                if (node instanceof HTMLAnchorElement) {
                    if (node.textContent === node.href) {
                        return node.href;
                    }

                    return `${node.textContent} (${node.href})`;
                }

                if (node instanceof HTMLQuoteElement) {
                    return `> ${node.textContent.trim()}`;
                }

                switch (node.tagName.toLowerCase()) {
                    case 'br':
                    case 'p':
                    case 'div':
                        return `\n${Array.from(node.childNodes).map(processNode).join('')}\n`;
                }

                return Array.from(node.childNodes).map(processNode).join('');
            }

            return '';
        }

        return processNode(div).replace(/\n{2,}/g, '\n\n').trim();
    }

    disableWysiwygMode() {
        this.destroySummernote();

        this.$noteEditor = null;

        if (this.$summernote) {
            this.$summernote.addClass('hidden');
        }

        this.$element.removeClass('hidden');

        if (this.$scrollable) {
            this.$scrollable.off('scroll.' + this.cid + '-edit');
        }
    }

    fetch() {
        const data = {};

        if (this.isHtml()) {
            let code = this.$summernote.summernote('code');

            if (code === '<p><br></p>') {
                code = '';
            }

            const imageTagString =
                `<img src="${window.location.origin}${window.location.pathname}?entryPoint=attachment`;

            code = code.replace(
                new RegExp(imageTagString.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), 'g'),
                '<img src="?entryPoint=attachment'
            );
            data[this.name] = code;
        } else {
            data[this.name] = this.$element.val();
        }

        if (this.fetchEmptyValueAsNull && !data[this.name]) {
            data[this.name] = null;
        }

        return data;
    }

    onScrollEdit(e) {
        const $target = $(e.target);
        const toolbarHeight = this.$toolbar.height();
        const toolbarWidth = this.$toolbar.parent().width();
        let edgeTop, edgeTopAbsolute;

        // noinspection JSIncompatibleTypesComparison
        if ($target.get(0) === window.document) {
            const $buttonContainer = $target.find('.detail-button-container:not(.hidden)');
            const offset = $buttonContainer.offset();

            if (offset) {
                edgeTop = offset.top + $buttonContainer.outerHeight();
                edgeTopAbsolute = edgeTop - $(window).scrollTop();
            }
        }
        else {
            const offset = $target.offset();

            if (offset) {
                edgeTop = offset.top;
                edgeTopAbsolute = edgeTop - $(window).scrollTop();
            }
        }

        const top = this.$el.offset().top;
        const bottom = top + this.$el.height() - toolbarHeight;

        let toStick = false;

        if (edgeTop > top && bottom > edgeTop) {
            toStick = true;
        }

        if (toStick) {
            this.$toolbar.css({
                top: edgeTopAbsolute + 'px',
                width: toolbarWidth + 'px',
            });

            this.$toolbar.addClass('sticked');

            this.$area.css({
                marginTop: toolbarHeight + 'px',
                backgroundColor: ''
            });

            return;
        }

        this.$toolbar.css({
            top: '',
            width: '',
        });

        this.$toolbar.removeClass('sticked');

        this.$area.css({
            marginTop: '',
        });
    }

    attachFile() {
        const $form = this.$el.closest('.record');

        $form.find(`.field[data-name="${this.params.attachmentField}"] input.file`).click();

        this.stopListening(this.model, 'attachment-uploaded:attachments');

        this.listenToOnce(this.model, 'attachment-uploaded:attachments', /** module:model[] */attachments => {
            if (this.isEditMode()) {
                const msg = this.translate('Attached') + '\n' +
                    attachments.map(m => m.attributes.name).join('\n');

                Espo.Ui.notify(msg, 'success', 3000);
            }
        });
    }

    initEspoPlugin() {
        const langSets = this.getLanguage().get('Global', 'sets', 'summernote') || {
            image: {},
            link: {},
            video: {},
        };

        initSummernoteCustom(langSets);
    }

    htmlHasColors(string) {
        if (~string.indexOf('background-color:')) {
            return true;
        }

        if (~string.indexOf('color:')) {
            return true;
        }

        if (~string.indexOf('<font color="')) {
            return true;
        }

        return false;
    }

    /**
     * @param {string} text
     * @since 8.4.0
     */
    insertText(text) {
        if (this.isHtml()) {
            this.$summernote.summernote('insertText', text);
        }
    }

    toSkipReRenderOnChange() {
        if (!this.element || !this.element.contains(document.activeElement)) {
            return false;
        }

        if (!this.model.hasChanged(this.name)) {
            return true;
        }

        return false;
    }
}

export default WysiwygFieldView;
