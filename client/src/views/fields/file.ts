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

import LinkFieldView, {LinkOptions, LinkParams} from 'views/fields/link';
import FileUpload from 'helpers/file-upload';
import AttachmentInsertSourceFromHelper from 'helpers/misc/attachment-insert-from-source';
import {BaseViewSchema, FieldValidator} from 'views/fields/base';
import View from 'view';
import Model from 'model';

export interface FileParams extends LinkParams {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Show preview.
     */
    showPreview?: boolean;
    /**
     * A preview size.
     */
    previewSize?: PreviewSize;
    /**
     * A list mode preview size.
     */
    listPreviewSize?: PreviewSize;
    /**
     * A source list.
     */
    sourceList?: string[];
    /**
     * Formats to accept.
     */
    accept?: string[];
    /**
     * A max file size (in Mb).
     */
    maxFileSize?: number;
}

export interface FileOptions extends LinkOptions {
    /**
     * @internal
     */
    previewSize?: PreviewSize
}

export type PreviewSize = 'x-small' | 'small' | 'medium' | 'large';

/**
 * A file field.
 */
class FileFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends FileOptions = FileOptions,
    P extends FileParams = FileParams,
> extends LinkFieldView<S, O, P> {

    readonly type: string = 'file'

    protected listTemplate = 'fields/file/list'
    protected listLinkTemplate = 'fields/file/list'
    protected detailTemplate = 'fields/file/detail'
    protected editTemplate = 'fields/file/edit'

    protected showPreview: boolean = false

    protected accept: string[] | null = null

    protected defaultType: string | null = null

    protected previewSize: PreviewSize = 'small'

    private previewTypeList: string[]

    private imageSizes: Record<string, [number, number]>

    validations: (FieldValidator | string)[] = [
        'ready',
        'required',
    ]

    searchTypeList: string[] = [
        'isNotEmpty',
        'isEmpty',
    ]

    private acceptAttribute: string

    private resizeIsBeingListened: boolean

    private isUploading: boolean = false

    private sourceList: string[]

    protected typeName: string

    private $attachment: JQuery

    protected data(): Record<string, any> {
        const data = {
            ...super.data(),
            id: this.model.get(this.idName),
            acceptAttribute: this.acceptAttribute,
        } as any;

        if (this.mode === this.MODE_EDIT) {
            data.sourceList = this.sourceList;
        }

        data.valueIsSet = this.model.has(this.idName);

        return data;
    }

    showValidationMessage(msg: string, selector: string, view?: View) {
        const $label = this.$el.find('label');

        const title = $label.attr('title');

        $label.attr('title', '');

        super.showValidationMessage(msg, selector, view);

        $label.attr('title', title);
    }

    validateRequired(): boolean {
        if (!this.isRequired()) {
            return false;
        }

        if (this.model.get(this.idName) == null) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            let $target: any;

            if (this.isUploading) {
                $target = this.$el.find('.gray-box');
            } else {
                $target = this.$el.find('.attachment-button label');
            }

            this.showValidationMessage(msg, $target);

            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    validateReady(): boolean {
        if (this.isUploading) {
            const $target = this.$el.find('.gray-box');

            const msg = this.translate('fieldIsUploading', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, $target);

            return true;
        }

        return false;
    }

    protected setup() {
        this.addHandler('click', 'a.remove-attachment', (_e, target) => this.removeAttachmentHandler(target));
        this.addHandler('change', 'input.file', (_e, target) => this.handeInputChange(target as HTMLInputElement));
        this.addActionHandler('showImagePreview', (e) => this.showPreviewHandler(e))
        this.addActionHandler('insertFromSource', (_e, target) => this.insertFromSource(target.dataset.name as string));
        this.addHandler('keydown', 'label.attach-file-label', (e) => this.keydownAttachFileLabelHandler(e));

        this.nameName = this.name + 'Name';
        this.idName = this.name + 'Id';
        this.typeName = this.name + 'Type';
        this.foreignScope = 'Attachment';

        this.previewSize = this.options.previewSize || this.params.previewSize || this.previewSize;

        this.previewTypeList = this.getMetadata().get(['app', 'image', 'previewFileTypeList']) || [];
        this.imageSizes = this.getMetadata().get(['app', 'image', 'sizes']) || {};

        const sourceDefs = this.getMetadata().get(['clientDefs', 'Attachment', 'sourceDefs']) || {};

        this.sourceList = Espo.Utils.clone(this.params.sourceList || []);

        this.sourceList = this.sourceList
            .concat(
                this.getMetadata().get(['clientDefs', 'Attachment', 'generalSourceList']) || []
            )
            .filter((item, i, self) => {
                return self.indexOf(item) === i;
            })
            .filter(item => {
                const defs = sourceDefs[item] || {};

                if (defs.accessDataList) {
                    if (
                        !Espo.Utils.checkAccessDataList(
                            defs.accessDataList, this.getAcl(), this.getUser()
                        )
                    ) {
                        return false;
                    }
                }

                if (defs.configCheck) {
                    const arr = defs.configCheck.split('.');

                    if (!this.getConfig().getByPath(arr)) {
                        return false;
                    }
                }

                return true;
            });

        if ('showPreview' in this.params) {
            this.showPreview = this.params.showPreview as boolean;
        }

        if ('accept' in this.params) {
            this.accept = this.params.accept as string[];
        }

        if (this.accept && this.accept.length) {
            this.acceptAttribute = this.accept.join(', ');
        }

        this.on('remove', () => {
            if (this.resizeIsBeingListened) {
                $(window).off('resize.' + this.cid);
            }
        });

        this.on('inline-edit-off', () => {
            this.isUploading = false;
        });
    }

    private keydownAttachFileLabelHandler(e: Event) {
        const key = Espo.Utils.getKeyFromKeyEvent(e as KeyboardEvent);

        if (key === 'Enter') {
            const input = this.element.querySelector<HTMLInputElement>('input.file');

            input?.click();
        }
    }

    private showPreviewHandler(e: MouseEvent) {
        e.preventDefault();

        const id = this.model.get(this.idName);

        this.createView('preview', 'views/modals/image-preview', {
            id: id,
            model: this.model,
            name: this.model.get(this.nameName),
        }).then(view => {
            view.render();
        });
    }

    private handeInputChange(input: HTMLInputElement) {
        const files = input.files;

        if (!files?.length) {
            return;
        }

        this.uploadFile(files[0]);

        input.value = '';

        // @todo Test.
        // @todo The same in multiple.

        // Note: Event listeners are not cloned.
        const newInput = input.cloneNode(true);
        input.replaceWith(newInput);
    }

    private removeAttachmentHandler(target: HTMLElement) {
        const div = target.parentElement;

        this.deleteAttachment();

        div?.parentElement?.remove();

        const input = this.element.querySelector<HTMLInputElement>('input.file');

        if (input) {
            input.value = '';
        }

        setTimeout(() => this.focusOnUploadButton(), 10);
    }

    protected afterRender() {
        if (this.mode === this.MODE_EDIT) {
            this.$attachment = this.$el.find('div.attachment');

            const name = this.model.get(this.nameName);
            const type = this.model.get(this.typeName) || this.defaultType;
            const id = this.model.get(this.idName);

            if (id) {
                this.addAttachmentBox(name, type, id);
            }

            this.$el.off('drop');
            this.$el.off('dragover');
            this.$el.off('dragleave');

            this.$el.on('drop', (e: any) => {
                e.preventDefault();
                e.stopPropagation();

                const event = e.originalEvent as DragEvent;

                if (
                    event.dataTransfer &&
                    event.dataTransfer.files &&
                    event.dataTransfer.files.length
                ) {
                    this.uploadFile(event.dataTransfer.files[0]);
                }
            });

            this.$el.on('dragover', (e: any) => {
                e.preventDefault();
            });

            this.$el.on('dragleave', (e: any) =>{
                e.preventDefault();
            });
        }

        if (this.mode === this.MODE_SEARCH) {
            const type = this.$el.find('select.search-type').val();

            this.handleSearchType(type);
        }

        if (this.mode === this.MODE_DETAIL) {
            if (this.previewSize === 'large') {
                this.handleResize();
                this.resizeIsBeingListened = true;

                $(window).on('resize.' + this.cid, () => {
                    this.handleResize();
                });
            }
        }
    }

    focusOnInlineEdit() {
        this.focusOnUploadButton();
    }

    focusOnUploadButton() {
        const $element = this.$el.find('.attach-file-label');

        if ($element.length) {
            $element.focus();
        }
    }

    handleResize() {
        const width = this.$el.width();

        this.$el.find('img.image-preview').css('maxWidth', width + 'px');
    }

    protected getDetailPreview(name: string, type: string, id: string): string {
        if (!this.previewTypeList.includes(type)) {
            return name;
        }

        let previewSize = this.previewSize;

        if (this.isListMode()) {
            previewSize = this.params.listPreviewSize || 'small';
        }

        const src = this.getBasePath() + '?entryPoint=image&size=' + previewSize + '&id=' + id;

        let maxHeight: number | string = (this.imageSizes[previewSize] || {})[1];

        if (this.isListMode() && !this.params.listPreviewSize) {
            maxHeight =  '';
        }

        // noinspection HtmlRequiredAltAttribute,RequiredAttributes
        const $img = $('<img>')
            .attr('src', src)
            .attr('alt', name)
            .addClass('image-preview')
            .css({
                maxWidth: (this.imageSizes[previewSize] || {})[0],
                maxHeight: maxHeight,
            });

        if (this.mode === this.MODE_LIST_LINK) {
            const link = `#${this.model.entityType}/view/${this.model.id}`;

            return $('<a>')
                .attr('href', link)
                .append($img)
                .get(0)
                ?.outerHTML as string;
        }

        return $('<a>')
            .attr('data-action', 'showImagePreview')
            .attr('data-id', id)
            .attr('title', name)
            .attr('href', this.getImageUrl(id))
            .append($img)
            .get(0)
            ?.outerHTML as string;
    }

    protected getEditPreview(name: string, type: string, id: string): string | null {
        if (!this.previewTypeList.includes(type)) {
            return null;
        }

        // noinspection HtmlRequiredAltAttribute,RequiredAttributes
        return $('<img>')
            .attr('src', this.getImageUrl(id, 'small'))
            .attr('title', name)
            .attr('alt', name)
            .attr('draggable', 'false')
            .css({
                maxWidth: (this.imageSizes[this.previewSize] || {})[0],
                maxHeight: (this.imageSizes[this.previewSize] || {})[1],
            })
            .get(0)
            ?.outerHTML as string;
    }

    protected getValueForDisplay(): any {
        if (! (this.isDetailMode() || this.isListMode())) {
            return '';
        }

        const name = this.model.get(this.nameName);
        const type = this.model.get(this.typeName) || this.defaultType;
        const id = this.model.get(this.idName);

        if (!id) {
            return false;
        }

        if (this.showPreview && ~this.previewTypeList.indexOf(type)) {
            let className = '';

            if (this.isListMode() && this.params.listPreviewSize) {
                className += 'no-shrink';
            }

            const $item = $('<div>')
                .addClass('attachment-preview')
                .addClass(className)
                .append(
                    this.getDetailPreview(name, type, id)
                );

            let containerClassName = 'attachment-block-container';

            if (this.previewSize === 'large') {
                containerClassName += ' attachment-block-container-large';
            }

            if (this.previewSize === 'small') {
                containerClassName += ' attachment-block-container-small';
            }

            return $('<div>')
                .addClass(containerClassName)
                .append(
                    $('<div>')
                        .addClass('attachment-block attachment-block-preview')
                        .append($item)
                )
                .get(0)?.outerHTML as string;
        }

        const container = document.createElement('div');
        container.classList.add('attachment-block');

        container.append(
            (() => {
                const span = document.createElement('span');
                span.classList.add('fas', 'fa-paperclip', 'text-soft', 'small');

                return span;
            })(),
            (() => {
                const a = document.createElement('a');
                a.target = '_blank';
                a.textContent = name;
                a.href = this.getDownloadUrl(id);

                return a;
            })(),
        );

        return container.outerHTML;
    }

    protected getImageUrl(id: string, size?: string): string {
        let url = this.getBasePath() + '?entryPoint=image&id=' + id;

        if (size) {
            url += '&size=' + size;
        }

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    protected getDownloadUrl(id: string): string {
        let url = `${this.getBasePath()}?entryPoint=download&id=${id}`;

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    private deleteAttachment() {
        const id = this.model.get(this.idName);

        const o = {} as any;

        o[this.idName] = null;
        o[this.nameName] = null;

        this.model.setMultiple(o);

        this.$attachment.empty();

        if (!id || !this.model.isNew()) {
            return;
        }

        this.getModelFactory().create('Attachment').then(attachment => {
            attachment.id = id;
            attachment.destroy();
        });
    }

    protected setAttachment(attachment: Model, ui?: boolean) {
        const attributes = {} as Record<string, any>;

        attributes[this.idName] = attachment.id;
        attributes[this.nameName] = attachment.get('name');

        this.model.setMultiple(attributes, {ui: ui});
    }

    protected getMaxFileSize(): number {
        let maxFileSize = this.params.maxFileSize || 0;

        const noChunk = !this.getConfig().get('attachmentUploadChunkSize');
        const attachmentUploadMaxSize = this.getConfig().get('attachmentUploadMaxSize') || 0;
        const appMaxUploadSize = this.getHelper().getAppParam('maxUploadSize') || 0;

        if (!maxFileSize || maxFileSize > attachmentUploadMaxSize) {
            maxFileSize = attachmentUploadMaxSize;
        }

        if (noChunk && maxFileSize > appMaxUploadSize) {
            maxFileSize = appMaxUploadSize;
        }

        return maxFileSize;
    }

    protected uploadFile(file: File) {
        let isCanceled = false;

        let exceedsMaxFileSize = false;

        const maxFileSize = this.getMaxFileSize();

        if (maxFileSize && file.size > maxFileSize * 1024 * 1024) {
            exceedsMaxFileSize = true;
        }

        if (exceedsMaxFileSize) {
            const msg = this.translate('fieldMaxFileSizeError', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{max}', maxFileSize.toString());

            this.showValidationMessage(msg, '.attachment-button label');

            return;
        }

        this.isUploading = true;

        const uploadHelper = new FileUpload();

        this.getModelFactory().create('Attachment').then(attachment => {
            const $attachmentBox = this.addAttachmentBox(file.name, file.type);

            const $uploadingMsg = $attachmentBox.parent().find('.uploading-message');

            this.$el.find('.attachment-button').addClass('hidden');

            const mediator: {isCanceled?: boolean} = {};

            $attachmentBox.find('.remove-attachment').on('click.uploading', () => {
                isCanceled = true;
                this.isUploading = false;

                this.$el.find('.attachment-button').removeClass('hidden');
                this.$el.find('input.file').val(null);

                mediator.isCanceled = true;
            });

            attachment.set('role', 'Attachment');
            attachment.set('relatedType', this.model.entityType);
            attachment.set('field', this.name);

            this.handleUploadingFile(file).then(file => {
                uploadHelper
                    .upload(file, attachment, {
                        afterChunkUpload: (size: number) => {
                            const msg = Math.floor((size / file.size) * 100) + '%';

                            $uploadingMsg.html(msg);
                        },
                        afterAttachmentSave: (attachment: Model) => {
                            $attachmentBox.attr('data-id', attachment.id);
                        },
                        mediator: mediator,
                    })
                    .then(() => {
                        if (isCanceled) {
                            return;
                        }

                        if (!this.isUploading) {
                            return;
                        }

                        this.setAttachment(attachment, true);

                        $attachmentBox.trigger('ready');

                        this.isUploading = false;

                        setTimeout(() => {
                            if (
                                document.activeElement &&
                                document.activeElement.tagName !== 'BODY'
                            ) {
                                return;
                            }

                            const $a = this.$el.find('.preview a');
                            $a.focus();
                        }, 50);
                    })
                    .catch(() => {
                        if (mediator.isCanceled) {
                            return;
                        }

                        $attachmentBox.remove();

                        this.$el.find('.uploading-message').remove();
                        this.$el.find('.attachment-button').removeClass('hidden');

                        this.isUploading = false;
                    });
            });
        });
    }

    protected handleUploadingFile(file: File): Promise<File> {
        return new Promise(resolve => resolve(file));
    }

    private getBoxPreviewHtml(name: string, type: string, id?: string) {
        const $text = $('<span>').text(name);

        if (!id) {
            return $text.get(0)?.outerHTML as string;
        }

        if (this.showPreview) {
            const html = this.getEditPreview(name, type, id);

            if (html) {
                return html;
            }
        }

        const url = this.getBasePath() + '?entryPoint=download&id=' + id;

        return $('<a>')
            .attr('href', url)
            .attr('target', '_BLANK')
            .text(name)
            .get(0)?.outerHTML as string;
    }

    private addAttachmentBox(name: string, type: string, id?: string): JQuery {
        this.$attachment.empty();

        const $remove = $('<a>')
            .attr('role', 'button')
            .attr('tabindex', '0')
            .addClass('remove-attachment pull-right')
            .append(
                $('<span>').addClass('fas fa-times')
            );

        const previewHtml = this.getBoxPreviewHtml(name, type, id);

        const $att = $('<div>')
            .addClass('gray-box')
            .append($remove)
            .append(
                $('<span>')
                    .addClass('preview')
                    .append($(previewHtml))
            );

        const $container = $('<div>').append($att);

        this.$attachment.append($container);

        if (id) {
            return $att;
        }

        const $loading = $('<span>')
            .addClass('small uploading-message')
            .text(this.translate('Uploading...'));

        $container.append($loading);

        $att.on('ready', () => {
            const id = this.model.get(this.idName);

            const previewHtml = this.getBoxPreviewHtml(name, type, id);

            $att.find('.preview').html(previewHtml);

            $loading.html(this.translate('Ready'));

            if ($att.find('.preview').find('img').length) {
                $loading.remove();
            }
        });

        return $att;
    }

    private insertFromSource(source: string) {
        const helper = new AttachmentInsertSourceFromHelper(this);

        helper.insert({
            source: source,
            onInsert: models => {
                models.forEach(model => this.setAttachment(model));
            },
        });
    }

    fetch(): Record<string, unknown> {
        const data = {} as any;

        data[this.idName] = this.model.get(this.idName);

        return data;
    }
}

export default FileFieldView;
