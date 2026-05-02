/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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


import BaseFieldView, {BaseOptions, BaseParams, BaseViewSchema} from 'views/fields/base';
import MailtoHelper from 'helpers/misc/mailto';
import TextPreviewModalView from 'views/modals/text-preview';
import Ui from 'ui';

/**
 * Parameters.
 */
export interface TextParams extends BaseParams {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * A max length.
     */
    maxLength?: number;
    /**
     * A number of rows.
     */
    rows?: number;
    /**
     * A min number of rows.
     */
    rowsMin?: number;
    /**
     * No resize.
     */
    noResize?: boolean;
    /**
     * Disable 'See-more'.
     */
    seeMoreDisabled?: boolean;
    /**
     * Disable auto-height.
     */
    autoHeightDisabled?: boolean;
    /**
     * A height of cut in pixels.
     */
    cutHeight?: number;
    /**
     * Display raw text.
     */
    displayRawText?: boolean;
    /**
     * Display the preview button.
     */
    preview?: boolean;
    /**
     * An attachment-multiple field to connect with.
     */
    attachmentField?: string;
}

/**
 * Options.
 */
export interface TextOptions extends BaseOptions {
    /**
     * @internal
     */
    noResize?: boolean;
    /**
     * @internal
     */
    rowsMin?: number;
    /**
     * @internal
     */
    autoHeightDisabled?: boolean;
}

/**
 * A text field.
 */
class TextFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends TextOptions = TextOptions,
    P extends TextParams = TextParams,
> extends BaseFieldView<S, O, P> {

    readonly type: string = 'text'

    protected listTemplate = 'fields/text/list'
    protected detailTemplate = 'fields/text/detail'
    protected editTemplate = 'fields/text/edit'
    protected searchTemplate = 'fields/text/search'

    /**
     * Show-more is applied.
     *
     * @internal
     */
    seeMoreText: boolean = false

    protected readonly rowsDefault: number = 50000

    protected rowsMin: number = 2

    protected seeMoreDisabled: boolean = false

    protected cutHeight: number = 200

    protected noResize: boolean = false

    protected readonly changeInterval: number = 5

    protected readonly shrinkThreshold: number = 10;

    protected searchTypeList: string[] = [
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

    private _lastLength: number | undefined

    private maxRows: number

    private previewButtonElement: HTMLElement | null

    protected textAreaElement: HTMLTextAreaElement | null

    private autoHeightDisabled: boolean

    private onKeyDownMarkdownBind: any
    private controlSeeMoreBind: any
    private onPasteBind: any

    private $text: JQuery
    private $seeMoreContainer: JQuery
    private $textContainer: JQuery

    protected setup() {
        super.setup();

        this.addActionHandler('seeMoreText', () => this.seeMore());
        this.addActionHandler('mailTo', (_, target) => this.mailTo(target.dataset.emailAddress as string));

        this.maxRows = this.params.rows || this.rowsDefault;
        this.noResize = this.options.noResize || this.params.noResize || this.noResize;
        this.seeMoreDisabled = this.seeMoreDisabled || this.params.seeMoreDisabled || false;
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

                this.textAreaElement = null;
            }
        });

        if (this.params.preview) {
            this.addHandler('input', 'textarea', (_, target) => {
                const text = (target as HTMLInputElement).value;

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

        this.listenTo(this.model, `change:${this.name}`, (_m, _v, o: Record<string, any>) => {
            if (o.ui === true && this.mode === this.MODE_EDIT) {
                // After changing the field content it's reasonable to show all text
                // when returning to the detail mode.
                this.seeMoreText = true;
            }
        })

        this.controlSeeMoreBind = this.controlSeeMore.bind(this);
        this.onPasteBind = this.onPaste.bind(this);
        this.onKeyDownMarkdownBind = this.onKeyDownMarkdown.bind(this);
    }

    protected setupSearch() {
        this.addHandler('change', 'select.search-type', (_, target) => {
            this.handleSearchType((target as HTMLSelectElement).type);
        });
    }

    protected data() {
        const data = super.data();

        if (
            this.model.get(this.name) !== null &&
            this.model.get(this.name) !== '' &&
            this.model.has(this.name)
        ) {
            data.isNotEmpty = true;
        }

        if (this.mode === this.MODE_SEARCH) {
            if (typeof this.searchParams?.value === 'string') {
                this.searchData.value = this.searchParams?.value;
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

        return data;
    }

    protected handleSearchType(type: string) {
        if (['isEmpty', 'isNotEmpty'].includes(type)) {
            this.$el.find('input.main-element').addClass('hidden');
        } else {
            this.$el.find('input.main-element').removeClass('hidden');
        }
    }

    protected getValueForDisplay(): string {
        const text = this.model.get(this.name);

        return text || '';
    }

    /**
     * @internal
     */
    controlTextareaHeight(lastHeight?: number) {
        const scrollHeight = this.$element?.prop('scrollHeight');
        const clientHeight = this.$element?.prop('clientHeight');

        if (typeof lastHeight === 'undefined' && clientHeight === 0) {
            setTimeout(this.controlTextareaHeight.bind(this), 10);

            return;
        }

        const element = this.$element?.get(0) as HTMLTextAreaElement;

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

        if ((this.$element?.val() as string).length === 0) {
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

    protected isCut(): boolean {
        return !this.seeMoreText && !this.seeMoreDisabled;
    }

    private controlSeeMore() {
        if (!this.isCut()) {
            return;
        }

        if (this.$text.height() as number > this.cutHeight) {
            this.$seeMoreContainer.removeClass('hidden');
            this.$textContainer.addClass('cut');
        } else {
            this.$seeMoreContainer.addClass('hidden');
            this.$textContainer.removeClass('cut');
        }
    }

    protected afterRender() {
        this.textAreaElement = null;

        if (this.mode === this.MODE_EDIT) {
            this.textAreaElement = this.element ? this.element.querySelector('textarea') : null;
        }

        super.afterRender();

        if (this.isReadMode()) {
            $(window).off(`resize.see-more-${this.cid}`);

            this.$textContainer = this.$el.find('> .complex-text-container');
            this.$text = this.$textContainer.find('> .complex-text');
            this.$seeMoreContainer = this.$el.find('> .see-more-container');

            if (this.isCut()) {
                this.controlSeeMore();

                if (this.model.get(this.name) && this.$text.height() === 0) {
                    this.$textContainer.addClass('cut');

                    setTimeout(this.controlSeeMore.bind(this), 50);
                }

                if (this.recordHelper) {
                    this.listenTo(this.recordHelper, 'panel-show', () => this.controlSeeMore());
                }

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
                this.$element?.val(text);
            }

            this.previewButtonElement = this.element ?
                this.element.querySelector('a[data-action="previewText"]') : null;

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

            this.$element?.on('input', () => {
                this.trigger('change');
            });
        }

        if (this.mode === this.MODE_EDIT && !this.autoHeightDisabled) {
            if (!this.autoHeightDisabled) {
                this.controlTextareaHeight();

                this.$element?.on('input', () => this.controlTextareaHeight());
            }

            let lastChangeKeydown = Date.now();
            const changeKeydownInterval = this.changeInterval * 1000;

            this.$element?.on('keydown', () => {
                if (Date.now() - lastChangeKeydown > changeKeydownInterval) {
                    this.trigger('change');
                    lastChangeKeydown = Date.now();
                }
            });
        }
    }

    fetch(): Record<string, unknown> {
        const data: Record<string, unknown> = {};

        let value = (this.$element?.val() || null) as string | null;

        if (value && value.trim() === '') {
            value = '';
        }

        data[this.name] = value

        return data;
    }

    fetchSearch(): Record<string, unknown> | null {
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

        const value = this.$element?.val()?.toString()?.trim();

        if (value) {
            return {
                value: value,
                type: type,
            };
        }

        return null;
    }

    protected getSearchType(): string | null {
        return this.getSearchParamsData().type ??
            this.searchParams?.typeFront ??
            this.searchParams?.type ??
            null;
    }

    protected async mailTo(emailAddress: string) {
        const attributes = {
            status: 'Draft',
            to: emailAddress,
        };

        const helper = new MailtoHelper(this.getConfig(), this.getPreferences(), this.getAcl());

        if (helper.toUse()) {
            document.location.href = helper.composeLink(attributes);

            return;
        }

        const viewName =  'views/modals/compose-email';


        Ui.notifyWait();

        const view = await this.createView('quickCreate', viewName, {attributes: attributes});

        await view.render();

        Ui.notify();
    }

    /**
     * Show the preview modal.
     *
     * @since 9.0.0
     */
    async preview(): Promise<void> {
        const text = this.model.attributes[this.name] || '';

        const view = new TextPreviewModalView({text: text});

        await this.assignView('modal', view);
        await view.render();
    }

    protected onPaste(event: ClipboardEvent) {
        const items = event.clipboardData?.items;

        if (!items) {
            return;
        }

        for (let i = 0; i < items.length; i++) {
            if (!items[i].type.startsWith('image')) {
                continue;
            }

            const blob = items[i].getAsFile();

            this.recordHelper?.trigger('upload-files:' + this.params.attachmentField, [blob]);
        }
    }

    /**
     * @since 9.0.0
     */
    async seeMore(): Promise<void> {
        this.seeMoreText = true;

        await this.reRender();
    }

    private initTextareaMarkdownHelper() {
        if (!this.textAreaElement) {
            return;
        }

        this.textAreaElement.addEventListener('keydown', this.onKeyDownMarkdownBind);
    }

    private _lastEnteredKeyIsEnter: boolean = false

    private onKeyDownMarkdown(event: KeyboardEvent) {
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

    private handleKeyDownMarkdown(event: KeyboardEvent, key: string) {
        const target = event.target;

        if (!(target instanceof HTMLTextAreaElement)) {
            return;
        }

        const toggleWrap = (wrapper: string) => {
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
