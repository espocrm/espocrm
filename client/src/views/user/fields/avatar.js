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

import ImageFieldView from 'views/fields/image';
import Model from 'model';
import ColorpickerFieldView from 'views/fields/colorpicker';

class UserAvatarFieldView extends ImageFieldView {

    getAttributeList() {
        if (this.isEditMode()) {
            return [...super.getAttributeList(), 'avatarColor'];
        }

        return [];
    }

    setup() {
        super.setup();

        this.on('after:inline-save', () => {
            this.suspendCache = true;

            this.reRender();
        });

        this.setupSub();
    }

    setupSub() {
        this.subModel = new Model();

        const syncModels = () => {
            this.subModel.set({color: this.model.attributes.avatarColor});
        };

        syncModels();

        this.listenTo(this.model, 'change:avatarColor', (m, v, o) => {
            if (o.fromView !== this) {
                syncModels();
            }
        });

        this.listenTo(this.subModel, 'change', (m, o) => {
            if (o.ui) {
                this.trigger('change');
            }
        });
    }

    onEditModeSet() {
        if (!this.hasColor()) {
            return;
        }

        this.colorView = new ColorpickerFieldView({
            name: 'color',
            model: this.subModel,
            labelText: this.translate('avatarColor', 'fields', 'User'),
            mode: 'edit',
        });

        return this.assignView('colorField', this.colorView, '[data-sub-field="color"]')
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            const colorEl = document.createElement('div');
            colorEl.setAttribute('data-sub-field', 'color');
            colorEl.classList.add('avatar-field-color');

            // noinspection JSCheckFunctionSignatures
            this.element.appendChild(colorEl);

            if (this.colorView) {
                this.colorView.reRender()
                    .then(() => {
                        const el = this.colorView.element.querySelector('input');

                        el.placeholder = this.translate('avatarColor', 'fields', 'User');
                    });
            }
        }
    }

    hasColor() {
        if (this.recordHelper && this.recordHelper.getFieldStateParam('avatarColor', 'readOnly')) {
            return false;
        }

        const userType = this.model.get('type');

        return [
            'regular',
            'admin',
            'api',
        ].includes(userType);
    }

    fetch() {
        if (!this.hasColor()) {
            return super.fetch();
        }

        // noinspection JSValidateTypes
        return {
            ...super.fetch(),
            avatarColor: this.subModel.attributes.color,
        };
    }

    /**
     * @protected
     * @param {File} file
     * @return {Promise<unknown>}
     */
    handleUploadingFile(file) {
        return new Promise((resolve, reject) => {
            const fileReader = new FileReader();

            fileReader.onload = (e) => {
                this.createView('crop', 'views/modals/image-crop', {contents: e.target.result})
                    .then(view => {
                        view.render();

                        let cropped = false;

                        this.listenToOnce(view, 'crop', dataUrl => {
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
    }

    getValueForDisplay() {
        if (!this.isReadMode()) {
            return '';
        }

        const id = this.model.get(this.idName);
        const userId = this.model.id;

        let t = this.cacheTimestamp = this.cacheTimestamp || Date.now();

        if (this.suspendCache) {
            t = Date.now();
        }

        const src = this.getBasePath() +
            '?entryPoint=avatar&size=' + this.previewSize + '&id=' + userId +
            '&t=' + t + '&attachmentId=' + (id || 'false');

        // noinspection HtmlRequiredAltAttribute,RequiredAttributes
        const $img = $('<img>')
            .attr('src', src)
            .attr('alt', this.labelText)
            .css({
                maxWidth: (this.imageSizes[this.previewSize] || {})[0],
                maxHeight: (this.imageSizes[this.previewSize] || {})[1],
            });

        if (!this.isDetailMode()) {
            if (this.getCache()) {
                t = this.getCache().get('app', 'timestamp');
            }

            const src = `${this.getBasePath()}?entryPoint=avatar&size=${this.previewSize}&id=${userId}&t=${t}`;

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
    }
}

export default UserAvatarFieldView;
