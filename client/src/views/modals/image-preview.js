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

import ModalView from 'views/modal';

let Exif;

class ImagePreviewModalView extends ModalView {

    template = 'modals/image-preview'

    cssName = 'image-preview'
    size = ''
    backdrop = true

    transformClassList = [
        'transform-flip',
        'transform-rotate-180',
        'transform-flip-and-rotate-180',
        'transform-flip-and-rotate-270',
        'transform-rotate-90',
        'transform-flip-and-rotate-90',
        'transform-rotate-270',
    ]

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

        this.imageList = this.options.imageList || [];

        this.once('remove', () => {
            $(window).off('resize.image-review');
        });

        this.wait(
            Espo.loader.requirePromise('lib!exif-js')
                .then(Lib => Exif = Lib)
        );
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

    // noinspection JSUnusedGlobalSymbols
    onImageLoad() {}

    afterRender() {
        const $container = this.$el.find('.image-container');
        const $img = this.$img = this.$el.find('.image-container img');

        $img.on('load', () => {
            const imgEl = $img.get(0);

            Exif.getData(imgEl, () => {
                if ($img.css('image-orientation') === 'from-image') {
                    return;
                }

                const orientation = Exif.getTag(this, 'Orientation');

                switch (orientation) {
                    case 2:
                        $img.addClass('transform-flip');
                        break;
                    case 3:
                        $img.addClass('transform-rotate-180');
                        break;
                    case 4:
                        $img.addClass('transform-rotate-180');
                        $img.addClass('transform-flip');
                        break;
                    case 5:
                        $img.addClass('transform-rotate-270');
                        $img.addClass('transform-flip');
                        break;
                    case 6:
                        $img.addClass('transform-rotate-90');
                        break;
                    case 7:
                        $img.addClass('transform-rotate-90');
                        $img.addClass('transform-flip');
                        break;
                    case 8:
                        $img.addClass('transform-rotate-270');
                        break;
                }
            });

            if (imgEl.naturalWidth > imgEl.clientWidth) {
                this.$el.find('.original-link-container').removeClass('hidden');
            }
        });

        if (this.navigationEnabled) {
            $img.css('cursor', 'pointer');

            $img.click(() => {
                this.switchToNext();
            });
        }

        const manageSize = () => {
            const width = $container.width();

            $img.css('maxWidth', width);
        };

        $(window).off('resize.image-review');

        $(window).on('resize.image-review', () => {
            manageSize();
        });

        setTimeout(() => manageSize(), 100);
    }

    isMultiple() {
        return this.imageList.length > 1;
    }

    switchToPrevious(noLoop) {
        if (!this.isMultiple()) {
            return;
        }

        let index = -1;

        this.imageList.forEach((d, i) => {
            if (d.id === this.options.id) {
                index = i;
            }
        });

        if (noLoop && index === 0) {
            return;
        }

        this.transformClassList.forEach(item => {
            this.$img.removeClass(item);
        });

        index--;

        if (index < 0) {
            index = this.imageList.length - 1;
        }

        this.options.id = this.imageList[index].id;
        this.options.name = this.imageList[index].name;

        this.reRender();
    }

    switchToNext(noLoop) {
        if (!this.isMultiple()) {
            return;
        }

        let index = -1;

        this.imageList.forEach((d, i) => {
            if (d.id === this.options.id) {
                index = i;
            }
        });

        if (noLoop && index === this.imageList.length - 1) {
            return;
        }

        this.transformClassList.forEach(item => {
            this.$img.removeClass(item);
        });

        index++;

        if (index > this.imageList.length - 1) {
            index = 0;
        }

        this.options.id = this.imageList[index].id;
        this.options.name = this.imageList[index].name;

        this.reRender();
    }
}

export default ImagePreviewModalView;
