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

/** @module views/fields/text */

import BaseFieldView from 'views/fields/base';
import MailtoHelper from 'helpers/misc/mailto';
import TextPreviewModalView from 'views/modals/text-preview';

/**
 * A text field.
 *
 * @extends BaseFieldView<module:views/fields/text~params>
 */
class TextFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/text~options
     * @property {
     *     module:views/fields/text~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/text~params
     * @property {boolean} [required] Required.
     * @property {number} [maxLength] A max length.
     * @property {number} [rows] A number of rows.
     * @property {number} [rowsMin] A min number of rows.
     * @property {boolean} [noResize] No resize.
     * @property {boolean} [seeMoreDisabled] Disable 'See-more'.
     * @property {boolean} [autoHeightDisabled] Disable auto-height.
     * @property {number} [cutHeight] A height of cut in pixels.
     * @property {boolean} [displayRawText] Display raw text.
     * @property {boolean} [preview] Display the preview button.
     * @property {string} [attachmentField] An attachment-multiple field to connect with.
     */

    /**
     * @param {
     *     module:views/fields/text~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'text'

    listTemplate = 'fields/text/list'
    detailTemplate = 'fields/text/detail'
    editTemplate = 'fields/text/edit'
    searchTemplate = 'fields/text/search'

    /**
     * Show-more is applied.
     * @type {boolean}
     */
    seeMoreText = false

    rowsDefault = 50000
    rowsMin = 2
    seeMoreDisabled = false
    cutHeight = 200
    noResize = false
    changeInterval = 5
    shrinkThreshold = 10;

    searchTypeList = [
        'contains',
        'startsWith',
        'equals',
        'endsWith',
        'like',
        'notContains',
        'notLike',
        'isEmpty',
        'isNotEmpty',
    ]

    /** @private */
    _lastLength

    /** @private */
    maxRows

    /**
     * @private
     * @type {HTMLElement}
     */
    previewButtonElement

    /**
     * @protected
     * @type {HTMLTextAreaElement}
     */
    textAreaElement

    setup() {
        super.setup();

        this.addActionHandler('seeMoreText', () => this.seeMore());
        this.addActionHandler('mailTo', (e, target) => this.mailTo(target.dataset.emailAddress));

        this.maxRows = this.params.rows || this.rowsDefault;
        this.noResize = this.options.noResize || this.params.noResize || this.noResize;
        this.seeMoreDisabled = this.seeMoreDisabled || this.params.seeMoreDisabled;
        this.autoHeightDisabled = this.options.autoHeightDisabled || this.params.autoHeightDisabled ||
            this.autoHeightDisabled;

        if (this.params.cutHeight) {
            this.cutHeight = this.params.cutHeight;
        }

        this.rowsMin = this.options.rowsMin || this.params.rowsMin || this.rowsMin;

        if (this.maxRows < this.rowsMin) {
            this.rowsMin = this.maxRows;
        }

        this.on('remove', () => {
            $(window).off('resize.see-more-' + this.cid);

            if (this.textAreaElement) {
                this.textAreaElement.removeEventListener('keydown', this.onKeyDownMarkdownBind);

                this.textAreaElement = undefined;
            }
        });

        if (this.params.preview) {
            this.addHandler('input', 'textarea', (e, /** HTMLTextAreaElement */target) => {
                const text = target.value;

                if (!this.previewButtonElement) {
                    return;
                }

                if (text) {
                    this.previewButtonElement.classList.remove('hidden');
                } else {
                    this.previewButtonElement.classList.add('hidden');
                }
            });

            this.addActionHandler('previewText', () => this.preview());
        }

        this.listenTo(this.model, `change:${this.name}`, (m, v, /** Record*/o) => {
            if (o.ui === true && this.mode === this.MODE_EDIT) {
                // After changing the field content it's reasonable to show all text
                // when returning to the detail mode.
                this.seeMoreText = true;
            }
        })

        /** @private */
        this.controlSeeMoreBind = this.controlSeeMore.bind(this);
        /** @private */
        this.onPasteBind = this.onPaste.bind(this);
        /** @private */
        this.onKeyDownMarkdownBind = this.onKeyDownMarkdown.bind(this);
    }

    setupSearch() {
        this.events['change select.search-type'] = e => {
            const type = $(e.currentTarget).val();

            this.handleSearchType(type);
        };
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        if (
            this.model.get(this.name) !== null &&
            this.model.get(this.name) !== '' &&
            this.model.has(this.name)
        ) {
            data.isNotEmpty = true;
        }

        if (this.mode === this.MODE_SEARCH) {
            if (typeof this.searchParams.value === 'string') {
                this.searchData.value = this.searchParams.value;
            }
        }

        if (this.mode === this.MODE_EDIT) {
            data.rows = this.autoHeightDisabled ?
                this.maxRows :
                this.rowsMin;
        }

        data.valueIsSet = this.model.has(this.name);

        if (this.isReadMode()) {
            data.isCut = this.isCut();

            if (data.isCut) {
                data.cutHeight = this.cutHeight;
            }

            data.displayRawText = this.params.displayRawText;
        }

        data.htmlValue = undefined;
        data.noResize = this.noResize || (!this.autoHeightDisabled && !this.params.rows);
        data.preview = this.params.preview && !this.params.displayRawText;

        // noinspection JSValidateTypes
        return data;
    }

    handleSearchType(type) {
        if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
            this.$el.find('input.main-element').addClass('hidden');
        } else {
            this.$el.find('input.main-element').removeClass('hidden');
        }
    }

    getValueForDisplay() {
        const text = this.model.get(this.name);

        return text || '';
    }

    /**
     * @public
     * @param {Number} [lastHeight]
     */
    controlTextareaHeight(lastHeight) {
        const scrollHeight = this.$element.prop('scrollHeight');
        const clientHeight = this.$element.prop('clientHeight');

        if (typeof lastHeight === 'undefined' && clientHeight === 0) {
            setTimeout(this.controlTextareaHeight.bind(this), 10);

            return;
        }

        /** @type {HTMLTextAreaElement} */
        const element = this.$element.get(0);

        if (!element || element.value === undefined) {
            return;
        }

        const length = element.value.length;

        if (this._lastLength === undefined) {
            this._lastLength = length;
        }

        if (length > this._lastLength) {
            this._lastLength = length;
        }

        if (clientHeight === lastHeight) {
            // @todo Revise.
            return;
        }

        if (scrollHeight > clientHeight + 1) {
            const rows = element.rows;

            if (this.maxRows && rows >= this.maxRows) {
                return;
            }

            element.rows ++;

            this.controlTextareaHeight(clientHeight);

            return;
        }

        if (this.$element.val().length === 0) {
            element.rows = this.rowsMin;

            return;
        }

        const tryShrink = () => {
            const rows = element.rows;

            if (this.rowsMin && rows - 1 <= this.rowsMin) {
                return;
            }

            element.rows --;

            if (element.scrollHeight > element.clientHeight + 1) {
                this.controlTextareaHeight();

                return;
            }

            tryShrink();
        };

        if (length < this._lastLength - this.shrinkThreshold) {
            this._lastLength = length;

            tryShrink();
        }
    }

    isCut() {
        return !this.seeMoreText && !this.seeMoreDisabled;
    }

    controlSeeMore() {
        if (!this.isCut()) {
            return;
        }

        if (this.$text.height() > this.cutHeight) {
            this.$seeMoreContainer.removeClass('hidden');
            this.$textContainer.addClass('cut');
        } else {
            this.$seeMoreContainer.addClass('hidden');
            this.$textContainer.removeClass('cut');
        }
    }

    afterRender() {
        this.textAreaElement = undefined;

        if (this.mode === this.MODE_EDIT) {
            this.textAreaElement = this.element ? this.element.querySelector('textarea') : undefined;
        }

        super.afterRender();

        if (this.isReadMode()) {
            $(window).off('resize.see-more-' + this.cid);

            this.$textContainer = this.$el.find('> .complex-text-container');
            this.$text = this.$textContainer.find('> .complex-text');
            this.$seeMoreContainer = this.$el.find('> .see-more-container');

            if (this.isCut()) {
                this.controlSeeMore();

                if (this.model.get(this.name) && this.$text.height() === 0) {
                    this.$textContainer.addClass('cut');

                    setTimeout(this.controlSeeMore.bind(this), 50);
                }

                this.listenTo(this.recordHelper, 'panel-show', () => this.controlSeeMore());
                this.on('panel-show-propagated', () => this.controlSeeMore());

                $(window).on('resize.see-more-' + this.cid, () => {
                    this.controlSeeMore();
                });

                // @todo Revise stream post with empty text.

                this.element.querySelectorAll('img').forEach(image => {
                    image.addEventListener('load', this.controlSeeMoreBind);
                });
            }
        }

        if (this.mode === this.MODE_EDIT) {
            const text = this.getValueForDisplay();

            if (text) {
                this.$element.val(text);
            }

            this.previewButtonElement = this.element ?
                this.element.querySelector('a[data-action="previewText"]') : undefined;

            const textAreaElement = this.textAreaElement

            if (this.params.attachmentField && textAreaElement) {
                textAreaElement.removeEventListener('paste', this.onPasteBind);
                textAreaElement.addEventListener('paste', this.onPasteBind);
            }

            if (!this.params.displayRawText) {
                this.initTextareaMarkdownHelper();
            }
        }

        if (this.mode === this.MODE_SEARCH) {
            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);

            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });

            this.$element.on('input', () => {
                this.trigger('change');
            });
        }

        if (this.mode === this.MODE_EDIT && !this.autoHeightDisabled) {
            if (!this.autoHeightDisabled) {
                this.controlTextareaHeight();

                this.$element.on('input', () => this.controlTextareaHeight());
            }

            let lastChangeKeydown = new Date();
            const changeKeydownInterval = this.changeInterval * 1000;

            this.$element.on('keydown', () => {
                if (Date.now() - lastChangeKeydown > changeKeydownInterval) {
                    this.trigger('change');
                    lastChangeKeydown = Date.now();
                }
            });
        }
    }

    fetch() {
        const data = {};

        let value = this.$element.val() || null;

        if (value && value.trim() === '') {
            value = '';
        }

        data[this.name] = value

        return data;
    }

    fetchSearch() {
        const type = this.fetchSearchType() || 'startsWith';

        if (type === 'isEmpty') {
            return  {
                type: 'or',
                value: [
                    {
                        type: 'isNull',
                        field: this.name,
                    },
                    {
                        type: 'equals',
                        field: this.name,
                        value: ''
                    }
                ],
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isNotEmpty') {
            return  {
                type: 'and',
                value: [
                    {
                        type: 'notEquals',
                        field: this.name,
                        value: '',
                    },
                    {
                        type: 'isNotNull',
                        field: this.name,
                        value: null,
                    }
                ],
                data: {
                    type: type,
                },
            };
        }

        const value = this.$element.val().toString().trim();

        if (value) {
            return {
                value: value,
                type: type,
            };
        }

        return false;
    }

    getSearchType() {
        return this.getSearchParamsData().type || this.searchParams.typeFront ||
            this.searchParams.type;
    }

    mailTo(emailAddress) {
        const attributes = {
            status: 'Draft',
            to: emailAddress,
        };

        const helper = new MailtoHelper(this.getConfig(), this.getPreferences(), this.getAcl());

        if (helper.toUse()) {
            document.location.href = helper.composeLink(attributes);

            return;
        }

        const viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.compose') ||
            'views/modals/compose-email';

        Espo.Ui.notifyWait();

        this.createView('quickCreate', viewName, {
            attributes: attributes,
        }, view => {
            view.render();

            Espo.Ui.notify(false);
        });
    }

    /**
     * Show the preview modal.
     *
     * @since 9.0.0
     * @return {Promise<void>}
     */
    async preview() {
        const text = this.model.attributes[this.name] || '';

        const view = new TextPreviewModalView({text: text});

        await this.assignView('modal', view);
        await view.render();
    }

    /**
     * @protected
     * @param {ClipboardEvent} event
     */
    onPaste(event) {
        const items = event.clipboardData.items;

        if (!items) {
            return;
        }

        for (let i = 0; i < items.length; i++) {
            if (!items[i].type.startsWith('image')) {
                continue;
            }

            const blob = items[i].getAsFile();

            this.recordHelper.trigger('upload-files:' + this.params.attachmentField, [blob]);
        }
    }

    /**
     * @return {Promise}
     * @since 9.0.0
     */
    async seeMore() {
        this.seeMoreText = true;

        await this.reRender();
    }

    /**
     * @private
     */
    initTextareaMarkdownHelper() {
        if (!this.textAreaElement) {
            return;
        }

        this.textAreaElement.addEventListener('keydown', this.onKeyDownMarkdownBind);
    }

    /**
     * @type {boolean}
     * @private
     */
    _lastEnteredKeyIsEnter = false

    /**
     * @private
     * @param {KeyboardEvent} event
     */
    onKeyDownMarkdown(event) {
        const key = Espo.Utils.getKeyFromKeyEvent(event);

        if (key !== 'Enter') {
            this._lastEnteredKeyIsEnter = false;

            this.handleKeyDownMarkdown(event, key);

            return;
        }

        const lastEnteredKeyIsEnter = this._lastEnteredKeyIsEnter;

        this._lastEnteredKeyIsEnter = true;

        const target = event.target;

        if (!(target instanceof HTMLTextAreaElement)) {
            return;
        }

        const {selectionStart, selectionEnd, value} = target;

        const before = value.substring(0, selectionStart);
        const after = value.substring(selectionEnd);

        // Last line, a list item syntax.
        const match = before.match(/(^|\n)( *[-*]|\d+\.) ([^*\-\n]*)$/);

        if (!match) {
            // Prevent unwanted scroll applied by the browser on enter.
            const previousWindowScroll = window.scrollY;
            setTimeout(() => window.scrollTo({top: previousWindowScroll}), 0);

            this.controlTextareaHeight();

            return;
        }

        event.preventDefault();

        if (match[3].trim() === '' && lastEnteredKeyIsEnter) {
            target.value = before.substring(0, match.index) + '\n' + after;
            target.selectionStart = target.selectionEnd = target.value.length - after.length;

            this.controlTextareaHeight();

            return;
        }

        let itemPart = match[2];

        const matchPart = itemPart.match(/( *)(\d+)/);

        if (matchPart) {
            const number = parseInt(matchPart[2]);

            if (!isNaN(number)) {
                itemPart = matchPart[1] + (number + 1).toString() + '.';
            }
        }

        const newLine = "\n" + itemPart + " ";

        target.value = before + newLine + after;
        target.selectionStart = target.selectionEnd = target.value.length - after.length;

        this.controlTextareaHeight();
    }

    /**
     * @private
     * @param {KeyboardEvent} event
     * @param {string} key
     */
    handleKeyDownMarkdown(event, key) {
        const target = event.target;

        if (!(target instanceof HTMLTextAreaElement)) {
            return;
        }

        /**
         * @param {string} wrapper
         */
        const toggleWrap = (wrapper) => {
            const {selectionStart, selectionEnd} = target;

            let text = target.value.substring(selectionStart, selectionEnd);

            if (text === '') {
                return;
            }

            let textPrevious = text;

            text = text.trimStart();

            const startPart = textPrevious.substring(0, textPrevious.length - text.length);

            textPrevious = text;

            text = text.trimEnd();

            const endPart = textPrevious.substring(text.length);

            if (text.startsWith(wrapper) && text.endsWith(wrapper)) {
                text = text.slice(wrapper.length, - wrapper.length);
            } else {
                text = wrapper + text + wrapper;
            }

            const newText = startPart + text + endPart;

            // noinspection JSDeprecatedSymbols
            document.execCommand('insertText', false, newText);

            const newStart = selectionStart + startPart.length;

            target.setSelectionRange(newStart, newStart + text.length);

            event.preventDefault();
            event.stopPropagation();
        };

        if (key === 'Control+KeyB') {
            toggleWrap('**');
        }

        if (key === 'Control+KeyI') {
            toggleWrap('_');
        }
    }
}

export default TextFieldView;
