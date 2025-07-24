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

/** @module views/fields/attachment-multiple */

import BaseFieldView from 'views/fields/base';
import FileUpload from 'helpers/file-upload';
import AttachmentInsertSourceFromHelper from 'helpers/misc/attachment-insert-from-source';

/**
 * An attachment-multiple field.
 *
 * @extends BaseFieldView<module:views/fields/attachment-multiple~params>
 */
class AttachmentMultipleFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/attachment-multiple~options
     * @property {
     *     module:views/fields/attachment-multiple~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/attachment-multiple~params
     * @property {boolean} [required] Required.
     * @property {boolean} [showPreviews] Show previews.
     * @property {'x-small'|'small'|'medium'|'large'} [previewSize] A preview size.
     * @property {string[]} [sourceList] A source list.
     * @property {string[]} [accept] Formats to accept.
     * @property {number} [maxFileSize] A max file size (in Mb).
     * @property {number} [maxCount] A max number of items.
     */

    /**
     * @param {
     *     module:views/fields/attachment-multiple~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'attachmentMultiple'

    listTemplate = 'fields/attachments-multiple/list'
    detailTemplate = 'fields/attachments-multiple/detail'
    editTemplate = 'fields/attachments-multiple/edit'
    searchTemplate = 'fields/link-multiple/search'

    previewSize = 'medium'
    nameHashName
    idsName
    nameHash
    foreignScope
    accept = null
    /** @protected */
    showPreviews = true
    /** @protected */
    showPreviewsInListMode = false

    initialSearchIsNotIdle = true;

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = [
        'ready',
        'required',
        'maxCount',
    ]

    searchTypeList = ['isNotEmpty', 'isEmpty']

    /**
     * @private
     * @type {Object.<string, true>}
     */
    uploadedIdMap

    events = {
        /** @this AttachmentMultipleFieldView */
        'click a.remove-attachment': function (e) {
            const $div = $(e.currentTarget).parent();

            const id = $div.attr('data-id');

            if (id) {
                this.deleteAttachment(id);
            }

            $div.parent().remove();

            this.$el.find('input.file').val(null);

            setTimeout(() => this.focusOnUploadButton(), 10);
        },
        /** @this AttachmentMultipleFieldView */
        'change input.file': function (e) {
            const $file = $(e.currentTarget);
            const files = e.currentTarget.files;

            this.uploadFiles(files);

            e.target.value = null;

            $file.replaceWith($file.clone(true));
        },
        /** @this AttachmentMultipleFieldView */
        'click a.action[data-action="insertFromSource"]': function (e) {
            const name = $(e.currentTarget).data('name');

            this.insertFromSource(name);
        },
        /** @this AttachmentMultipleFieldView */
        'click a[data-action="showImagePreview"]': function (e) {
            e.preventDefault();

            const id = $(e.currentTarget).data('id');

            const attachmentIdList = this.model.get(this.idsName) || [];
            const typeHash = this.model.get(this.typeHashName) || {};

            const imageIdList = [];

            attachmentIdList.forEach(cId => {
                if (!this.isTypeIsImage(typeHash[cId])) {
                    return;
                }

                imageIdList.push(cId);
            });

            const imageList = [];

            imageIdList.forEach((cId) => {
                imageList.push({
                    id: cId,
                    name: this.nameHash[cId]
                });
            });

            this.createView('preview', 'views/modals/image-preview', {
                id: id,
                model: this.model,
                name: this.nameHash[id],
                imageList: imageList,
            }, view => {
                view.render();
            });
        },
        /** @this AttachmentMultipleFieldView */
        'keydown label.attach-file-label': function (e) {
            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (key === 'Enter') {
                const element = /** @type {HTMLInputElement} */this.$el.find('input.file').get(0);

                element.click();
            }
        },
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const ids = this.model.get(this.idsName);

        const data = {
            ...super.data(),
            idValues: this.model.get(this.idsName),
            idValuesString: ids ? ids.join(',') : '',
            nameHash: this.model.get(this.nameHashName),
            foreignScope: this.foreignScope,
            valueIsSet: this.model.has(this.idsName),
            acceptAttribute: this.acceptAttribute,
        };

        if (this.mode === this.MODE_EDIT) {
            data.fileSystem = ~this.sourceList.indexOf('FileSystem');
            data.sourceList = this.sourceList;
        }

        // noinspection JSValidateTypes
        return data;
    }

    setup() {
        this.nameHashName = this.name + 'Names';
        this.typeHashName = this.name + 'Types';
        this.idsName = this.name + 'Ids';
        this.foreignScope = 'Attachment';

        this.previewSize = this.options.previewSize || this.params.previewSize || this.previewSize;

        this.previewTypeList = this.getMetadata().get(['app', 'image', 'previewFileTypeList']) || [];
        this.imageSizes = this.getMetadata().get(['app', 'image', 'sizes']) || {};

        this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};

        if ('showPreviews' in this.params) {
            this.showPreviews = this.params.showPreviews;
        }

        if ('accept' in this.params) {
            this.accept = this.params.accept;
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
            this.nameHash = _.clone(this.model.get(this.nameHashName)) || {};
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
            this.listenTo(this.recordHelper, `upload-files:${this.name}`, /** File[] */files => {
                if (!this.isEditMode()) {
                    return;
                }

                this.uploadFiles(files);
            });
        }

        this.uploadedIdMap = {};
    }

    setupSearch() {
        this.addHandler('change', 'select.search-type', (e, /** HTMLSelectElement */target) => {
            this.handleSearchType(target.value);

            this.trigger('change');
        });
    }

    focusOnInlineEdit() {
        this.focusOnUploadButton();
    }

    focusOnUploadButton() {
        this.$el.find('.attach-file-label').focus();
    }

    /**
     * @protected
     */
    empty() {
        this.clearIds();

        this.$attachments.empty();
    }

    /**
     * @private
     */
    handleResize() {
        const width = this.$el.width();

        this.$el.find('img.image-preview').css('maxWidth', width + 'px');
    }

    /**
     * @protected
     * @param {string} id
     */
    deleteAttachment(id) {
        this.removeId(id);

        if (this.model.isNew()) {
            this.getModelFactory().create('Attachment', (attachment) => {
                attachment.id = id;
                attachment.destroy();
            });
        }
    }

    /**
     * @protected
     * @param {string} id
     * @param {string} [size]
     * @return {string}
     */
    getImageUrl(id, size) {
        let url = `${this.getBasePath()}?entryPoint=image&id=${id}`;

        if (size) {
            url += '&size=' + size;
        }

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    /**
     * @protected
     * @param {string} id
     * @return {string}
     */
    getDownloadUrl(id) {
        let url = `${this.getBasePath()}?entryPoint=download&id=${id}`;

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    /**
     * @protected
     * @param {string} id
     */
    removeId(id) {
        const arr = _.clone(this.model.get(this.idsName) || []);
        const i = arr.indexOf(id);

        arr.splice(i, 1);

        this.model.set(this.idsName, arr);

        const nameHash = _.clone(this.model.get(this.nameHashName) || {});
        delete nameHash[id];

        this.model.set(this.nameHashName, nameHash);

        const typeHash = _.clone(this.model.get(this.typeHashName) || {});
        delete typeHash[id];

        this.model.set(this.typeHashName, typeHash);
    }

    /**
     * @protected
     * @param {boolean} [silent]
     */
    clearIds(silent) {
        silent = silent || false;

        this.model.set(this.idsName, [], {silent: silent});
        this.model.set(this.nameHashName, {}, {silent: silent});
        this.model.set(this.typeHashName, {}, {silent: silent})
    }

    /**
     * @protected
     * @param {import('model').default} attachment
     * @param {boolean} [ui]
     */
    pushAttachment(attachment, ui) {
        const arr = _.clone(this.model.get(this.idsName) || []);

        arr.push(attachment.id);

        this.model.set(this.idsName, arr, {ui: ui});

        const typeHash = _.clone(this.model.get(this.typeHashName) || {});

        typeHash[attachment.id] = attachment.get('type');

        this.model.set(this.typeHashName, typeHash, {ui: ui});

        const nameHash = _.clone(this.model.get(this.nameHashName) || {});

        nameHash[attachment.id] = attachment.get('name');

        this.model.set(this.nameHashName, nameHash, {ui: ui});

        this.uploadedIdMap[attachment.id] = true;
    }

    /**
     * @protected
     * @param {string} name
     * @param {string} type
     * @param {string} id
     * @return {string|null}
     */
    getEditPreview(name, type, id) {
        if (!~this.previewTypeList.indexOf(type)) {
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
            .outerHTML;
    }

    getBoxPreviewHtml(name, type, id) {
        const $text = $('<span>').text(name);

        if (!id) {
            return $text.get(0).outerHTML;
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
            .get(0).outerHTML;
    }

    addAttachmentBox(name, type, id) {
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

            const id = $att.attr('data-id');

            const previewHtml = this.getBoxPreviewHtml(name, type, id);

            $att.find('.preview').html(previewHtml);

            if ($att.find('.preview').find('img').length) {
                $loading.remove();
            }
        });

        return $att;
    }

    showValidationMessage(msg, selector, view) {
        const $label = this.$el.find('label');
        const title = $label.attr('title');

        $label.attr('title', '');

        super.showValidationMessage(msg, selector, view);

        $label.attr('title', title);
    }

    getMaxFileSize() {
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
     *
     * @param {FileList|File[]} files
     */
    uploadFiles(files) {
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

        this.isUploading = true;

        this.getModelFactory().create('Attachment', model => {
            const canceledList = [];
            const fileList = [];
            const uploadedList = [];

            for (let i = 0; i < files.length; i++) {
                fileList.push(files[i]);

                totalCount++;
            }

            const uploadHelper = new FileUpload();

            fileList.forEach(file => {
                const $attachmentBox = this.addAttachmentBox(file.name, file.type);

                const $uploadingMsg = $attachmentBox.parent().find('.uploading-message');

                const mediator = {};

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
                        afterChunkUpload: (size) => {
                            const msg = Math.floor((size / file.size) * 100) + '%';

                            $uploadingMsg.html(msg);
                        },
                        afterAttachmentSave: (attachment) => {
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
                            this.model.trigger('attachment-uploaded:' + this.name, uploadedList);
                            this.afterAttachmentsUploaded.call(this);

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

    afterAttachmentsUploaded() {}

    afterRender() {
        if (this.mode === this.MODE_EDIT) {
            this.$attachments = this.$el.find('div.attachments');

            const ids = this.model.get(this.idsName) || [];

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

            this.$el.on('drop', e => {
                e.preventDefault();
                e.stopPropagation();

                const event = /** @type {DragEvent} */e.originalEvent;

                if (
                    event.dataTransfer &&
                    event.dataTransfer.files &&
                    event.dataTransfer.files.length
                ) {
                    this.uploadFiles(event.dataTransfer.files);
                }
            });

            this.$el.get(0).addEventListener('dragover', e => {
                e.preventDefault();
            });

            this.$el.get(0).addEventListener('dragleave', e => {
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

    isTypeIsImage(type) {
        if (~this.previewTypeList.indexOf(type)) {
            return true;
        }

        return false;
    }

    /**
     * @return {string}
     */
    getDetailPreview(name, type, id) {
        if (!this.isTypeIsImage(type)) {
            return $('<span>')
                .text(name)
                .get(0)
                .outerHTML;
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
                        maxWidth: (this.imageSizes[this.previewSize] || {})[0],
                        maxHeight: (this.imageSizes[this.previewSize] || {})[1],
                    })
            )
            .get(0)
            .outerHTML;
    }

    getValueForDisplay() {
        if (this.isDetailMode() || this.isListMode()) {
            const nameHash = this.nameHash;
            const typeHash = this.model.get(this.typeHashName) || {};
            const ids = /** @type {string[]} */this.model.get(this.idsName) || [];

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

            let containerClassName = null;

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

            return $container.get(0).innerHTML;
        }
    }

    /**
     * @private
     * @param {string} source
     */
    insertFromSource(source) {
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
    }

    // noinspection JSUnusedGlobalSymbols
    validateReady() {
        if (this.isUploading) {
            const msg = this.translate('fieldIsUploading', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, 'label');

            return true;
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validateMaxCount() {
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

    fetch() {
        const data = {};

        data[this.idsName] = this.model.get(this.idsName) || [];

        return data;
    }

    // noinspection JSUnusedLocalSymbols
    handleSearchType(type) {
        this.$el.find('div.link-group-container').addClass('hidden');
    }

    fetchSearch() {
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
