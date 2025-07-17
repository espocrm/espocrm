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

/** @module views/fields/file */

import LinkFieldView from 'views/fields/link';
import FileUpload from 'helpers/file-upload';
import AttachmentInsertSourceFromHelper from 'helpers/misc/attachment-insert-from-source';

/**
 * A file field.
 *
 * @extends LinkFieldView<module:views/fields/file~params>
 */
class FileFieldView extends LinkFieldView {

    /**
     * @typedef {Object} module:views/fields/file~options
     * @property {
     *     module:views/fields/file~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/file~params
     * @property {boolean} [required] Required.
     * @property {boolean} [showPreview] Show preview.
     * @property {'x-small'|'small'|'medium'|'large'} [previewSize] A preview size.
     * @property {'x-small'|'small'|'medium'|'large'} [listPreviewSize] A list preview size.
     * @property {string[]} [sourceList] A source list.
     * @property {string[]} [accept] Formats to accept.
     * @property {number} [maxFileSize] A max file size (in Mb).
     */

    /**
     * @param {
     *     module:views/fields/file~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'file'

    listTemplate = 'fields/file/list'
    listLinkTemplate = 'fields/file/list'
    detailTemplate = 'fields/file/detail'
    editTemplate = 'fields/file/edit'

    showPreview = false
    accept = false
    defaultType = false
    previewSize = 'small'
    validations = ['ready', 'required']
    searchTypeList = ['isNotEmpty', 'isEmpty']

    events = {
        /** @this FileFieldView */
        'click a.remove-attachment': function (e) {
            const $div = $(e.currentTarget).parent();

            this.deleteAttachment();

            $div.parent().remove();

            this.$el.find('input.file').val(null);

            setTimeout(() => this.focusOnUploadButton(), 10);
        },
        /** @this FileFieldView */
        'change input.file': function (e) {
            const $file = $(e.currentTarget);
            const files = e.currentTarget.files;

            if (!files.length) {
                return;
            }

            this.uploadFile(files[0]);

            e.target.value = null;

            $file.replaceWith($file.clone(true));
        },
        /** @this FileFieldView */
        'click a[data-action="showImagePreview"]': function (e) {
            e.preventDefault();

            const id = this.model.get(this.idName);

            this.createView('preview', 'views/modals/image-preview', {
                id: id,
                model: this.model,
                name: this.model.get(this.nameName),
            }, view => {
                view.render();
            });
        },
        /** @this FileFieldView */
        'click a.action[data-action="insertFromSource"]': function (e) {
            const name = $(e.currentTarget).data('name');

            this.insertFromSource(name);
        },
        /** @this FileFieldView */
        'keydown label.attach-file-label': function (e) {
            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (key === 'Enter') {
                const el = /** @type {HTMLInputElement} */this.$el.find('input.file').get(0);

                el.click();
            }
        },
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = {
            ...super.data(),
            id: this.model.get(this.idName),
            acceptAttribute: this.acceptAttribute,
        };

        if (this.mode === this.MODE_EDIT) {
            data.sourceList = this.sourceList;
        }

        data.valueIsSet = this.model.has(this.idName);

        // noinspection JSValidateTypes
        return data;
    }

    showValidationMessage(msg, selector, view) {
        const $label = this.$el.find('label');

        const title = $label.attr('title');

        $label.attr('title', '');

        super.showValidationMessage(msg, selector, view);

        $label.attr('title', title);
    }

    validateRequired() {
        if (!this.isRequired()) {
            return;
        }

        if (this.model.get(this.idName) == null) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            let $target;

            if (this.isUploading) {
                $target = this.$el.find('.gray-box');
            } else {
                $target = this.$el.find('.attachment-button label');
            }

            this.showValidationMessage(msg, $target);

            return true;
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validateReady() {
        if (this.isUploading) {
            const $target = this.$el.find('.gray-box');

            const msg = this.translate('fieldIsUploading', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, $target);

            return true;
        }
    }

    setup() {
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
            this.showPreview = this.params.showPreview;
        }

        if ('accept' in this.params) {
            this.accept = this.params.accept;
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

    afterRender() {
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

            this.$el.on('drop', e => {
                e.preventDefault();
                e.stopPropagation();

                const event = /** @type {DragEvent} */e.originalEvent;

                if (
                    event.dataTransfer &&
                    event.dataTransfer.files &&
                    event.dataTransfer.files.length
                ) {
                    this.uploadFile(event.dataTransfer.files[0]);
                }
            });

            this.$el.on('dragover', e => {
                e.preventDefault();
            });

            this.$el.on('dragleave', e =>{
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

    /**
     * @return {string}
     */
    getDetailPreview(name, type, id) {
        if (!~this.previewTypeList.indexOf(type)) {
            return name;
        }

        let previewSize = this.previewSize;

        if (this.isListMode()) {
            previewSize = this.params.listPreviewSize || 'small';
        }

        const src = this.getBasePath() + '?entryPoint=image&size=' + previewSize + '&id=' + id;

        let maxHeight = (this.imageSizes[previewSize] || {})[1];

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
            const link = '#' + this.model.entityType + '/view/' + this.model.id;

            return $('<a>')
                .attr('href', link)
                .append($img)
                .get(0)
                .outerHTML;
        }

        return $('<a>')
            .attr('data-action', 'showImagePreview')
            .attr('data-id', id)
            .attr('title', name)
            .attr('href', this.getImageUrl(id))
            .append($img)
            .get(0)
            .outerHTML;
    }

    getEditPreview(name, type, id) {
        if (!~this.previewTypeList.indexOf(type)) {
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
            .outerHTML;
    }

    getValueForDisplay() {
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
                .get(0).outerHTML;
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

    getImageUrl(id, size) {
        let url = this.getBasePath() + '?entryPoint=image&id=' + id;

        if (size) {
            url += '&size=' + size;
        }

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    getDownloadUrl(id) {
        let url = this.getBasePath() + '?entryPoint=download&id=' + id;

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    deleteAttachment() {
        const id = this.model.get(this.idName);

        const o = {};

        o[this.idName] = null;
        o[this.nameName] = null;

        this.model.set(o);

        this.$attachment.empty();

        if (id) {
            if (this.model.isNew()) {
                this.getModelFactory().create('Attachment', (attachment) => {
                    attachment.id = id;
                    attachment.destroy();
                });
            }
        }
    }

    setAttachment(attachment, ui) {
        const o = {};

        o[this.idName] = attachment.id;
        o[this.nameName] = attachment.get('name');

        this.model.set(o, {ui: ui});
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
     * @param {File} file
     */
    uploadFile(file) {
        let isCanceled = false;

        let exceedsMaxFileSize = false;

        const maxFileSize = this.getMaxFileSize();

        if (maxFileSize && file.size > maxFileSize * 1024 * 1024) {
            exceedsMaxFileSize = true;
        }

        if (exceedsMaxFileSize) {
            const msg = this.translate('fieldMaxFileSizeError', 'messages')
                .replace('{field}', this.getLabelText())
                .replace('{max}', maxFileSize);

            this.showValidationMessage(msg, '.attachment-button label');

            return;
        }

        this.isUploading = true;

        const uploadHelper = new FileUpload();

        this.getModelFactory().create('Attachment', attachment => {
            const $attachmentBox = this.addAttachmentBox(file.name, file.type);

            const $uploadingMsg = $attachmentBox.parent().find('.uploading-message');

            this.$el.find('.attachment-button').addClass('hidden');

            const mediator = {};

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

    /**
     * @protected
     * @param {File} file
     * @return {Promise<unknown>}
     */
    handleUploadingFile(file) {
        return new Promise(resolve => resolve(file));
    }

    getBoxPreviewHtml(name, type, id) {
        const $text = $('<span>').text(name);

        if (!id) {
            return $text.get(0).outerHTML;
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
            .get(0).outerHTML;
    }

    addAttachmentBox(name, type, id) {
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

    /**
     * @private
     * @param {string} source
     */
    insertFromSource(source) {
        const helper = new AttachmentInsertSourceFromHelper(this);

        helper.insert({
            source: source,
            onInsert: models => {
                models.forEach(model => this.setAttachment(model));
            },
        });
    }

    fetch() {
        const data = {};

        data[this.idName] = this.model.get(this.idName);

        return data;
    }
}

export default FileFieldView;
