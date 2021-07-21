/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/user/fields/avatar', 'views/fields/image', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.on('after:inline-save', () => {
                this.suspendCache = true;

                this.reRender();
            });
        },

        handleFileUpload: function (file, contents, callback) {
            this.createView('crop', 'views/modals/image-crop', {
                contents: contents,
            }, (view) => {
                view.render();

                let cropped = false;

                this.listenToOnce(view, 'crop', (croppedContents, params) => {
                    cropped = true;

                    setTimeout(() => {
                        params = params || {};
                        params.name = 'avatar.jpg';
                        params.type = 'image/jpeg';

                        callback(croppedContents, params);
                    }, 10);
                });

                this.listenToOnce(view, 'remove', () => {
                    if (!cropped) {
                        setTimeout(() => this.render(), 10);
                    }

                    this.clearView('crop');
                });
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
