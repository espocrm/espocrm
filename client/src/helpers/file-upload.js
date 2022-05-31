/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('helpers/file-upload', ['lib!espo'], function (Espo) {

    return class {
        constructor(config) {
            this.config = config;
        }

        upload(file, attachment, options) {
            options = options || {};

            options.afterChunkUpload = options.afterChunkUpload || (() => {});
            options.afterAttachmentSave = options.afterAttachmentSave || (() => {});
            options.mediator = options.mediator || {};

            attachment.set('name', file.name);
            attachment.set('type', file.type || 'text/plain');
            attachment.set('size', file.size);

            if (this._useChunks(file)) {
                return this._uploadByChunks(file, attachment, options);
            }

            return new Promise((resolve, reject) => {
                let fileReader = new FileReader();

                fileReader.onload = (e) => {
                    attachment.set('file', e.target.result);

                    attachment
                        .save({}, {timeout: 0})
                        .then(() => resolve())
                        .catch(() => reject());
                };

                fileReader.readAsDataURL(file);
            });
        }

        _uploadByChunks(file, attachment, options) {
            return new Promise((resolve, reject) => {
                attachment.set('isBeingUploaded', true);

                // todo test catching
                attachment
                    .save()
                    .then(() => {
                        options.afterAttachmentSave(attachment);

                        return this._uploadChunks(
                            file,
                            attachment,
                            resolve,
                            reject,
                            options
                        );
                    })
                    .catch(() => reject());
            });
        }

        _uploadChunks(file, attachment, resolve, reject, options, start) {
            start = start || 0;
            let end = start + this._getChunkSize() + 1;

            if (end > file.size) {
                end = file.size;
            }

            if (options.mediator.isCanceled) {
                reject();

                return;
            }

            let blob = file.slice(start, end);

            let fileReader = new FileReader();

            fileReader.onloadend = (e) => {
                if (e.target.readyState !== FileReader.DONE) {
                    return;
                }

                Espo.Ajax
                    .postRequest('Attachment/chunk/' + attachment.id, e.target.result, {
                        headers: {
                            contentType: 'multipart/form-data',
                        }
                    })
                    .then(() => {
                        options.afterChunkUpload(end);

                        if (end === file.size) {
                            resolve();

                            return;
                        }

                        this._uploadChunks(
                            file,
                            attachment,
                            resolve,
                            reject,
                            options,
                            end
                        );
                    })
                    .catch(() => reject());
            };

            fileReader.readAsDataURL(blob);
        }

        _useChunks(file) {
            let chunkSize = this._getChunkSize();

            if (!chunkSize) {
                return false;
            }

            if (file.size > chunkSize) {
                return true;
            }

            return false;
        }

        _getChunkSize() {
            return (this.config.get('attachmentUploadChunkSize') || 0) * 1024 * 1024;
        }
    };
});
