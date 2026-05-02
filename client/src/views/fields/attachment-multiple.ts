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

import BaseFieldView, {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';
import FileUpload from 'helpers/file-upload';
import AttachmentInsertSourceFromHelper from 'helpers/misc/attachment-insert-from-source';
import Utils from 'utils';
import View from 'view';
import Model from 'model';

export interface AttachmentMultipleParams extends BaseParams {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Show previews.
     */
    showPreviews?: boolean;
    /**
     * A preview size.
     */
    previewSize?: PreviewSize;
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
    /**
     * A max number of items.
     */
    maxCount?: number;
}

export interface AttachmentMultipleOptions extends BaseOptions {
    /**
     * @internal
     */
    previewSize?: PreviewSize
}

type PreviewSize = 'x-small' | 'small' | 'medium' | 'large';

/**
 * An attachment-multiple field.
 */
class AttachmentMultipleFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends AttachmentMultipleOptions = AttachmentMultipleOptions,
    P extends AttachmentMultipleParams = AttachmentMultipleParams,
> extends BaseFieldView<S, O, P> {

    readonly type: string = 'attachmentMultiple'

    protected listTemplate = 'fields/attachments-multiple/list'
    protected detailTemplate = 'fields/attachments-multiple/detail'
    protected editTemplate = 'fields/attachments-multiple/edit'
    protected searchTemplate = 'fields/link-multiple/search'

    protected previewSize: PreviewSize = 'medium'

    protected nameHashName: string

    protected typeHashName: string

    protected idsName: string

    protected nameHash: Record<string, string>

    protected foreignScope: string

    protected accept: string[] | null = null

    protected showPreviews: boolean = true

    protected showPreviewsInListMode: boolean = false

    /**
     * @internal
     */
    initialSearchIsNotIdle: boolean = true;

    protected validations: (FieldValidator | string)[] = [
        'ready',
        'required',
        'maxCount',
    ]

    protected searchTypeList: string[] = [
        'isNotEmpty',
        'isEmpty',
    ]

    private acceptAttribute: string

    private uploadedIdMap: Record<string, true>

    private sourceList: string[]

    private previewTypeList: string[]

    private imageSizes: Record<string, [number, number]>

    private resizeIsBeingListened: boolean

    private isUploading: boolean = false

    private $attachments: JQuery

    protected data(): Record<string, any> {
        const ids = this.model.get(this.idsName);

        const data = {
            ...super.data(),
            idValues: this.model.get(this.idsName),
            idValuesString: ids ? ids.join(',') : '',
            nameHash: this.model.get(this.nameHashName),
            foreignScope: this.foreignScope,
            valueIsSet: this.model.has(this.idsName),
            acceptAttribute: this.acceptAttribute,
        } as any;

        if (this.mode === this.MODE_EDIT) {
            data.fileSystem = this.sourceList.includes('FileSystem');
            data.sourceList = this.sourceList;
        }

        // noinspection JSValidateTypes
        return data;
    }

    protected setup() {
        this.addHandler('click', 'a.remove-attachment', (_e, target) => this.removeAttachmentHandler(target));
        this.addHandler('change', 'input.file', (_e, target) => this.changeFileHandler(target as HTMLInputElement));
        this.addActionHandler('insertFromSource', (_e, target) => this.insertFromSource(target.dataset.name as string))
        this.addActionHandler('showImagePreview', (e, target) => this.showImagePreviewHandler(e, target));
        this.addHandler('keydown', 'label.attach-file-label', (e) => this.keydownAttachFileLabelHandler(e))

        this.nameHashName = this.name + 'Names';
        this.typeHashName = this.name + 'Types';
        this.idsName = this.name + 'Ids';
        this.foreignScope = 'Attachment';

        this.previewSize = this.options.previewSize || this.params.previewSize || this.previewSize;

        this.previewTypeList = this.getMetadata().get(['app', 'image', 'previewFileTypeList']) || [];
        this.imageSizes = this.getMetadata().get(['app', 'image', 'sizes']) || {};

        this.nameHash = Utils.clone(this.model.get(this.nameHashName) ?? {});

        if ('showPreviews' in this.params) {
            this.showPreviews = this.params.showPreviews as boolean;
        }

        if ('accept' in this.params) {
            this.accept = this.params.accept as string[];
        }

        if (this.accept && this.accept.length) {
            this.acceptAttribute = this.accept.join(', ');
        }

        const sourceDefs = this.getMetadata().get(['clientDefs', 'Attachment', 'sourceDefs']) || {};

        this.sourceList = Espo.Utils.clone(this.params.sourceList || []);

        this.sourceList = this.sourceList
            .concat(
                this.getMetadata().get(['clientDefs', 'Attachment', 'generalSourceList']) || []
            )
            .filter((item, i, self) => {
                return self.indexOf(item) === i;
            })
            .filter((item) => {
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

        this.listenTo(this.model, 'change:' + this.nameHashName, () => {
            this.nameHash = Utils.clone(this.model.get(this.nameHashName) ?? {});
        });

        this.on('remove', () => {
            if (this.resizeIsBeingListened) {
                $(window).off('resize.' + this.cid);
            }

            this.uploadedIdMap = {};
        });

        this.on('inline-edit-off', () => {
            this.isUploading = false;
        });

        if (this.recordHelper) {
            this.listenTo(this.recordHelper, `upload-files:${this.name}`, (files: File[]) => {
                if (!this.isEditMode()) {
                    return;
                }

                this.uploadFiles(files);
            });
        }

        this.uploadedIdMap = {};
    }

    private keydownAttachFileLabelHandler(e: Event) {
        const key = Espo.Utils.getKeyFromKeyEvent(e as KeyboardEvent);

        if (key === 'Enter') {
            const element = this.$el.find('input.file').get(0) as HTMLInputElement;

            element.click();
        }
    }

    private showImagePreviewHandler(e: MouseEvent, target: HTMLElement) {
        e.preventDefault();

        const id = target.dataset.id as string;

        const attachmentIdList: string[] = this.model.get(this.idsName) || [];
        const typeHash = this.model.get(this.typeHashName) || {};

        const imageIdList: string[] = [];

        attachmentIdList.forEach(cId => {
            if (!this.isTypeIsImage(typeHash[cId])) {
                return;
            }

            imageIdList.push(cId);
        });

        const imageList: {id: string, name: string}[] = [];

        imageIdList.forEach(cId => {
            imageList.push({
                id: cId,
                name: this.nameHash[cId],
            });
        });

        this.createView('preview', 'views/modals/image-preview', {
            id: id,
            model: this.model,
            name: this.nameHash[id],
            imageList: imageList,
        }).then(view => {
            view.render();
        });
    }

    private changeFileHandler(input: HTMLInputElement) {
        const $file = $(input);
        const files = input.files as FileList;

        this.uploadFiles(files);

        input.value = '';

        $file.replaceWith($file.clone(true));
    }

    private removeAttachmentHandler(target: HTMLElement) {
        const $div = $(target).parent();

        const id = $div.attr('data-id');

        if (id) {
            this.deleteAttachment(id);
        }

        $div.parent().remove();

        this.$el.find('input.file').val(null);

        setTimeout(() => this.focusOnUploadButton(), 10);
    }

    protected setupSearch() {
        this.addHandler('change', 'select.search-type', (_e, target) => {
            this.handleSearchType((target as HTMLSelectElement).value);

            this.trigger('change');
        });
    }

    protected focusOnInlineEdit() {
        this.focusOnUploadButton();
    }

    protected focusOnUploadButton() {
        this.$el.find('.attach-file-label').focus();
    }

    protected empty() {
        this.clearIds();

        this.$attachments.empty();
    }

    private handleResize() {
        const width = this.$el.width();

        this.$el.find('img.image-preview').css('maxWidth', width + 'px');
    }

    protected deleteAttachment(id: string) {
        this.removeId(id);

        if (this.model.isNew()) {
            this.getModelFactory().create('Attachment').then((attachment) => {
                attachment.id = id;
                attachment.destroy();
            });
        }
    }

    protected getImageUrl(id: string, size?: PreviewSize): string {
        let url = `${this.getBasePath()}?entryPoint=image&id=${id}`;

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

    protected removeId(id: string) {
        const arr = Utils.clone(this.model.get(this.idsName) ?? []);
        const i = arr.indexOf(id);

        arr.splice(i, 1);

        this.model.set(this.idsName, arr);

        const nameHash = Utils.clone(this.model.get(this.nameHashName) ?? {});
        delete nameHash[id];

        this.model.set(this.nameHashName, nameHash);

        const typeHash = Utils.clone(this.model.get(this.typeHashName) ?? {});
        delete typeHash[id];

        this.model.set(this.typeHashName, typeHash);
    }

    protected clearIds(silent?: boolean) {
        silent = silent || false;

        this.model.set(this.idsName, [], {silent: silent});
        this.model.set(this.nameHashName, {}, {silent: silent});
        this.model.set(this.typeHashName, {}, {silent: silent})
    }

    protected pushAttachment(attachment: Model, ui?: boolean) {
        const arr = Utils.clone(this.model.get(this.idsName) ?? []);

        arr.push(attachment.id as string);

        this.model.set(this.idsName, arr, {ui: ui});

        const typeHash = Utils.clone(this.model.get(this.typeHashName) ?? {});

        typeHash[attachment.id as string] = attachment.get('type');

        this.model.set(this.typeHashName, typeHash, {ui: ui});

        const nameHash = Utils.clone(this.model.get(this.nameHashName) ?? {});

        nameHash[attachment.id as string] = attachment.get('name');

        this.model.set(this.nameHashName, nameHash, {ui: ui});

        this.uploadedIdMap[attachment.id as string] = true;
    }

    protected getEditPreview(name: string, type: string, id: string): string | null {
        if (!this.previewTypeList.includes(type)) {
            return null;
        }

        const size = (id in this.uploadedIdMap) ? undefined : 'small';

        // noinspection HtmlRequiredAltAttribute,RequiredAttributes
        return $('<img>')
            .attr('src', this.getImageUrl(id, size))
            .attr('title', name)
            .attr('alt', name)
            .attr('draggable', 'false')
            .css({
                maxWidth: (this.imageSizes['small'] || {})[0],
                maxHeight: (this.imageSizes['small'] || {})[1],
            })
            .get(0)
            ?.outerHTML as string;
    }

    private getBoxPreviewHtml(name: string, type: string, id?: string | undefined): string {
        const $text = $('<span>').text(name);

        if (!id) {
            return $text.get(0)?.outerHTML as string;
        }

        if (this.showPreviews) {
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

    private addAttachmentBox(name: string, type: string, id?: string) {
        const $attachments = this.$attachments;

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

        $attachments.append($container);

        if (id) {
            $att.attr('data-id', id);

            return $att;
        }

        const $loading = $('<span>')
            .addClass('small uploading-message')
            .text(this.translate('Uploading...'));

        $container.append($loading);

        $att.on('ready', () => {
            $loading.html(this.translate('Ready'));

            const id = $att.attr('data-id') as string;

            const previewHtml = this.getBoxPreviewHtml(name, type, id);

            $att.find('.preview').html(previewHtml);

            if ($att.find('.preview').find('img').length) {
                $loading.remove();
            }
        });

        return $att;
    }

    showValidationMessage(msg: string, selector: string, view?: View) {
        const $label = this.$el.find('label');
        const title = $label.attr('title');

        $label.attr('title', '');

        super.showValidationMessage(msg, selector, view);

        $label.attr('title', title);
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

    /**
     * Upload files.
     */
    uploadFiles(files: File[] | FileList) {
        let uploadedCount = 0;
        let totalCount = 0;

        let exceedsMaxFileSize = false;

        const maxFileSize = this.getMaxFileSize();

        if (maxFileSize) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                if (file.size > maxFileSize * 1024 * 1024) {
                    exceedsMaxFileSize = true;
                }
            }
        }

        if (exceedsMaxFileSize) {
            const msg = this.translate('fieldMaxFileSizeError', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{max}', maxFileSize.toString());

            this.showValidationMessage(msg, 'label');

            return;
        }

        const fileList: File[] = [];

        for (let i = 0; i < files.length; i++) {
            fileList.push(files[i]);

            totalCount ++;
        }

        this.isUploading = true;

        this.getModelFactory().create('Attachment').then(model => {
            const canceledList: string[] = [];
            const uploadedList: Model[] = [];

            const uploadHelper = new FileUpload();

            fileList.forEach(file => {
                const $attachmentBox = this.addAttachmentBox(file.name, file.type);

                const $uploadingMsg = $attachmentBox.parent().find('.uploading-message');

                const mediator: {isCanceled?: boolean} = {};

                $attachmentBox.find('.remove-attachment').on('click.uploading', () => {
                    canceledList.push(attachment.cid);

                    totalCount--;

                    if (uploadedCount === totalCount) {
                        this.isUploading = false;

                        if (totalCount) {
                            this.afterAttachmentsUploaded.call(this);
                        }
                    }

                    mediator.isCanceled = true;
                });

                const attachment = model.clone();

                attachment.set('role', 'Attachment');
                attachment.set('parentType', this.model.entityType);
                attachment.set('field', this.name);

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
                        if (canceledList.indexOf(attachment.cid) !== -1) {
                            return;
                        }

                        this.pushAttachment(attachment, true);

                        $attachmentBox.attr('data-id', attachment.id);
                        $attachmentBox.trigger('ready');

                        uploadedCount++;
                        uploadedList.push(attachment);

                        if (uploadedCount === totalCount && this.isUploading) {
                            this.model.trigger(`attachment-uploaded:${this.name}`, uploadedList);

                            this.afterAttachmentsUploaded();

                            this.isUploading = false;

                            setTimeout(() => {
                                if (
                                    document.activeElement &&
                                    document.activeElement.tagName !== 'BODY'
                                ) {
                                    return;
                                }

                                this.focusOnUploadButton();
                            }, 50);
                        }
                    })
                    .catch(() => {
                        if (mediator.isCanceled) {
                            return;
                        }

                        $attachmentBox.remove();
                        $uploadingMsg.remove();

                        totalCount--;

                        if (!totalCount) {
                            this.isUploading = false;
                        }

                        if (uploadedCount === totalCount && this.isUploading) {
                            this.isUploading = false;
                            this.afterAttachmentsUploaded.call(this);
                        }
                    });
            });
        });
    }

    protected afterAttachmentsUploaded() {}

    protected afterRender() {
        if (this.mode === this.MODE_EDIT) {
            this.$attachments = this.$el.find('div.attachments');

            const ids: string[] = this.model.get(this.idsName) || [];

            const nameHash = this.model.get(this.nameHashName);
            const typeHash = this.model.get(this.typeHashName) || {};

            ids.forEach(id => {
                if (nameHash) {
                    const name = nameHash[id];
                    const type = typeHash[id] || null;

                    this.addAttachmentBox(name, type, id);
                }
            });

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
                    this.uploadFiles(event.dataTransfer.files);
                }
            });

            this.$el.get(0).addEventListener('dragover', (e: any) => e.preventDefault());
            this.$el.get(0).addEventListener('dragleave', (e: any) => e.preventDefault());
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

    protected isTypeIsImage(type: string): boolean {
        if (this.previewTypeList.includes(type)) {
            return true;
        }

        return false;
    }

    protected getDetailPreview(name: string, type: string, id: string): string {
        if (!this.isTypeIsImage(type)) {
            return $('<span>')
                .text(name)
                .get(0)
                ?.outerHTML as string;
        }

        // noinspection HtmlRequiredAltAttribute,RequiredAttributes
        return $('<a>')
            .attr('data-action', 'showImagePreview')
            .attr('data-id', id)
            .attr('title', name)
            .attr('href', this.getImageUrl(id))
            .append(
                $('<img>')
                    .attr('src', this.getImageUrl(id, this.previewSize))
                    .addClass('image-preview')
                    .attr('alt', name)
                    .css({
                        maxWidth: (this.imageSizes[this.previewSize] ?? [])[0],
                        maxHeight: (this.imageSizes[this.previewSize] ?? [])[1],
                    })
            )
            .get(0)
            ?.outerHTML as string;
    }

    protected getValueForDisplay(): any {
        if (this.isDetailMode() || this.isListMode()) {
            const nameHash = this.nameHash;
            const typeHash = this.model.get(this.typeHashName) || {};
            const ids: string[] = this.model.get(this.idsName) || [];

            const previews = [];
            const names = [];

            for (const id of ids) {
                const type = typeHash[id] || false;
                const name = nameHash[id];

                if (
                    this.showPreviews &&
                    this.previewTypeList.includes(type) &&
                    (
                        this.isDetailMode() ||
                        this.isListMode() && this.showPreviewsInListMode
                    )
                ) {
                    previews.push(
                        $('<div>')
                            .addClass('attachment-preview')
                            .append(this.getDetailPreview(name, type, id))
                    );

                    continue;
                }

                names.push(
                    $('<div>')
                        .addClass('attachment-block')
                        .append(
                            $('<span>').addClass('fas fa-paperclip text-soft small'),
                            ' ',
                            $('<a>')
                                .attr('href', this.getDownloadUrl(id))
                                .attr('target', '_blank')
                                .text(name)
                        )
                );
            }

            let containerClassName = '';

            if (this.previewSize === 'large') {
                containerClassName = 'attachment-block-container-large';
            }

            if (this.previewSize === 'small') {
                containerClassName = 'attachment-block-container-small';
            }

            if (names.length === 0 && previews.length === 0) {
                return '';
            }

            const $container = $('<div>')
                .append(
                    $('<div>')
                        .addClass('attachment-block-container')
                        .addClass(containerClassName)
                        .append(previews)
                )
                .append(names);

            return $container.get(0)?.innerHTML as string;
        }
    }

    private insertFromSource(source: string) {
        const helper = new AttachmentInsertSourceFromHelper(this);

        helper.insert({
            source: source,
            onInsert: models => {
                models.forEach(model => this.pushAttachment(model));
            },
        });
    }

    validateRequired() {
        if (this.isRequired()) {
            if ((this.model.get(this.idsName) || []).length === 0) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg, 'label');

                return true;
            }
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    protected validateReady() {
        if (this.isUploading) {
            const msg = this.translate('fieldIsUploading', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, 'label');

            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    protected validateMaxCount() {
        const maxCount = this.params.maxCount;

        if (!maxCount) {
            return false;
        }

        const idList = this.model.get(this.idsName) || [];

        if (idList.length === 0) {
            return false;
        }

        if (idList.length <= maxCount) {
            return false;
        }

        const msg = this.translate('fieldExceedsMaxCount', 'messages')
            .replace('{field}', this.getLabelText())
            .replace('{maxCount}', maxCount.toString());

        this.showValidationMessage(msg, 'label');

        return true;
    }

    fetch(): Record<string, unknown> {
        const data = {} as any;

        data[this.idsName] = this.model.get(this.idsName) || [];

        return data;
    }

    // noinspection JSUnusedLocalSymbols
    protected handleSearchType(type: string) {
        // noinspection BadExpressionStatementJS
        type;

        this.$el.find('div.link-group-container').addClass('hidden');
    }

    fetchSearch(): Record<any, unknown> | null {
        const type = this.$el.find('select.search-type').val();

        if (type === 'isEmpty') {
            return {
                type: 'isNotLinked',
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isNotEmpty') {
            return {
                type: 'isLinked',
                data: {
                    type: type,
                },
            };
        }

        return null;
    }
}

export default AttachmentMultipleFieldView;
