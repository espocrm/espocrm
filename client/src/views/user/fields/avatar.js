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

define('views/user/fields/avatar', ['views/fields/image'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.on('after:inline-save', () => {
                this.suspendCache = true;

                this.reRender();
            });
        },

        handleUploadingFile: function (file) {
            return new Promise((resolve, reject) => {
                let fileReader = new FileReader();

                fileReader.onload = (e) => {
                    this.createView('crop', 'views/modals/image-crop', {contents: e.target.result})
                        .then(view => {
                            view.render();

                            let cropped = false;

                            this.listenToOnce(view, 'crop', (dataUrl) => {
                                cropped = true;

                                setTimeout(() => {
                                    fetch(dataUrl)
                                        .then(result => result.blob())
                                        .then(blob => {
                                            resolve(
                                                new File([blob], 'avatar.jpg', {type: 'image/jpeg'})
                                            );
                                        });
                                }, 10);
                            });

                            this.listenToOnce(view, 'remove', () => {
                                if (!cropped) {
                                    setTimeout(() => this.render(), 10);

                                    reject();
                                }

                                this.clearView('crop');
                            });
                        });
                };

                fileReader.readAsDataURL(file);
            });
        },

        getValueForDisplay: function () {
            if (!this.isReadMode()) {
                return '';
            }

            let id = this.model.get(this.idName);
            let userId = this.model.id;

            let t = this.cacheTimestamp = this.cacheTimestamp || Date.now();

            if (this.suspendCache) {
                t = Date.now();
            }

            let src = this.getBasePath() +
                '?entryPoint=avatar&size=' + this.previewSize + '&id=' + userId +
                '&t=' + t + '&attachmentId=' + (id || 'false');

            let $img = $('<img>')
                .attr('src', src)
                .css({
                    maxWidth: (this.imageSizes[this.previewSize] || {})[0],
                    maxHeight: (this.imageSizes[this.previewSize] || {})[1],
                });

            if (!this.isDetailMode()) {
                if (this.getCache()) {
                    t = this.getCache().get('app', 'timestamp');
                }

                let src = this.getBasePath() + '?entryPoint=avatar&size=' +
                    this.previewSize + '&id=' + userId + '&t=' + t;

                $img
                    .attr('width', '16')
                    .attr('src', src)
                    .css('maxWidth', '16px');
            }

            if (!id) {
                return $img
                    .get(0)
                    .outerHTML;
            }

            return $('<a>')
                .attr('data-id', id)
                .attr('data-action', 'showImagePreview')
                .attr('href', this.getBasePath() + '?entryPoint=image&id=' + id)
                .append($img)
                .get(0)
                .outerHTML;
        },
    });
});
