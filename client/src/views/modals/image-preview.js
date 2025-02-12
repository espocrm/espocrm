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

import ModalView from 'views/modal';

let Exif;

class ImagePreviewModalView extends ModalView {

    template = 'modals/image-preview'

    cssName = 'image-preview'
    size = ''
    backdrop = true
    isMaximizable = true

    transformClassList = [
        'transform-flip',
        'transform-rotate-180',
        'transform-flip-and-rotate-180',
        'transform-flip-and-rotate-270',
        'transform-rotate-90',
        'transform-flip-and-rotate-90',
        'transform-rotate-270',
    ]

    /**
     * @protected
     * @type {HTMLElement}
     */
    imageContainerElement

    /**
     * @protected
     * @type {HTMLImageElement}
     */
    imageElement

    events = {
        /** @this ImagePreviewModalView */
        'keydown': function (e) {
            if (e.code === 'ArrowLeft') {
                this.switchToPrevious(true);

                return;
            }

            if (e.code === 'ArrowRight') {
                this.switchToNext(true);
            }
        },
    }

    data() {
        return {
            name: this.options.name,
            url: this.getImageUrl(),
            originalUrl: this.getOriginalImageUrl(),
            showOriginalLink: this.size,
        };
    }

    setup() {
        this.buttonList = [];
        this.headerHtml = '&nbsp;';

        this.navigationEnabled = (this.options.imageList && this.options.imageList.length > 1);

        /** @type {Record[]} */
        this.imageList = this.options.imageList || [];

        this.wait(
            Espo.loader.requirePromise('lib!exif-js')
                .then(Lib => Exif = Lib)
        );

        /** @private */
        this.onImageLoadBind = this.onImageLoad.bind(this);
        /** @private */
        this.onImageClickBind = this.onImageClick.bind(this);
        /** @private */
        this.onWindowResizeBind = this.onWindowResize.bind(this);
    }

    onRemove() {
        window.removeEventListener('resize', this.onWindowResizeBind);

        if (this.imageElement) {
            this.imageElement.removeEventListener('load', this.onImageLoadBind);
            this.imageElement.removeEventListener('click', this.onImageClickBind);
        }

    }

    getImageUrl() {
        let url = this.getBasePath() + '?entryPoint=image&id=' + this.options.id;

        if (this.size) {
            url += '&size=' + this.size;
        }

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    getOriginalImageUrl() {
        let url = this.getBasePath() + '?entryPoint=image&id=' + this.options.id;

        if (this.getUser().get('portalId')) {
            url += '&portalId=' + this.getUser().get('portalId');
        }

        return url;
    }

    /**
     * @private
     */
    onImageLoad() {
        const image = this.imageElement;

        Exif.getData(image, () => {
            // noinspection JSDeprecatedSymbols
            if (window.getComputedStyle(image).imageOrientation === 'from-image') {
                return;
            }

            const orientation = Exif.getTag(image, 'Orientation');

            switch (orientation) {
                case 2:
                    image.classList.add('transform-flip');

                    break;

                case 3:
                    image.classList.add('transform-rotate-180');

                    break;

                case 4:
                    image.classList.add('transform-rotate-180');
                    image.classList.add('transform-flip');

                    break;

                case 5:
                    image.classList.add('transform-rotate-270');
                    image.classList.add('transform-flip');

                    break;

                case 6:
                    image.classList.add('transform-rotate-90');

                    break;

                case 7:
                    image.classList.add('transform-rotate-90');
                    image.classList.add('transform-flip');

                    break;

                case 8:
                    image.classList.add('transform-rotate-270');

                    break;
            }
        });

        /*if (image.naturalWidth > image.clientWidth) {}*/
    }

    afterRender() {
        if (!this.element) {
            // @todo Remove when views are not rendered, after change in bull.
            return;
        }

        this.imageContainerElement = this.element.querySelector('.image-container');
        this.imageElement = this.imageContainerElement.querySelector('img')

        this.imageElement.addEventListener('load', this.onImageLoadBind);

        if (this.navigationEnabled) {
            this.imageElement.style.cursor = 'pointer';
        }

        this.imageElement.addEventListener('click', this.onImageClickBind);

        window.removeEventListener('resize', this.onWindowResizeBind);
        window.addEventListener('resize', this.onWindowResizeBind);

        setTimeout(() => this.onWindowResize(), 100);
    }

    /**
     * @private
     */
    onWindowResize() {
        if (!this.imageContainerElement) {
            return;
        }

        const width = this.imageContainerElement.clientWidth;

        this.imageElement.style.maxWidth = width + 'px';
    }

    /**
     * @private
     */
    onImageClick() {
        this.switchToNext();
    }

    isMultiple() {
        return this.imageList.length > 1;
    }

    /**
     * @private
     * @param {boolean} [noLoop]
     */
    switchToPrevious(noLoop) {
        if (!this.isMultiple()) {
            return;
        }

        let index = -1;

        this.imageList.forEach((item, i) => {
            if (item.id === this.options.id) {
                index = i;
            }
        });

        if (noLoop && index === 0) {
            return;
        }

        if (this.imageElement) {
            this.transformClassList.forEach(item => {
                this.imageElement.classList.remove(item);
            });
        }

        index--;

        if (index < 0) {
            index = this.imageList.length - 1;
        }

        this.options.id = this.imageList[index].id;
        this.options.name = this.imageList[index].name;

        this.reRender();
    }

    /**
     * @private
     * @param {boolean} [noLoop]
     */
    switchToNext(noLoop) {
        if (!this.isMultiple()) {
            return;
        }

        let index = -1;

        this.imageList.forEach((item, i) => {
            if (item.id === this.options.id) {
                index = i;
            }
        });

        if (noLoop && index === this.imageList.length - 1) {
            return;
        }

        if (this.imageElement) {
            this.transformClassList.forEach(item => {
                this.imageElement.classList.remove(item);
            });
        }

        index++;

        if (index > this.imageList.length - 1) {
            index = 0;
        }

        this.options.id = this.imageList[index].id;
        this.options.name = this.imageList[index].name;

        this.reRender();
    }

    onMaximize() {
        const width = this.imageContainerElement.clientWidth

        this.imageElement.style.maxWidth = width  + 'px';
    }

    onMinimize() {
        const width = this.imageContainerElement.clientWidth

        this.imageElement.style.maxWidth = width + 'px';
    }
}

export default ImagePreviewModalView;
