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

import $ from 'jquery';

/**
 * @internal
 */
class StickyBarHelper {

    /** @private */
    $stickedBar

    /**
     * @param {import('views/record/list').default} view
     */
    constructor(view) {
        this.view = view;
        this.themeManager = this.view.getThemeManager();

        this.$el = view.$el;
    }

    init() {
        const controlSticking = () => {
            if (this.view.getCheckedIds().length === 0 && !this.view.isAllResultChecked()) {
                return;
            }

            const scrollTop = $scrollable.scrollTop();

            const stickTop = buttonsTop;
            const edge = middleTop + $middle.outerHeight(true);

            if (isSmallWindow && $('#navbar .navbar-body').hasClass('in')) {
                return;
            }

            if (scrollTop >= edge) {
                $stickedBar.removeClass('hidden');
                $navbarRight.addClass('has-sticked-bar');

                return;
            }

            if (scrollTop > stickTop) {
                $stickedBar.removeClass('hidden');
                $navbarRight.addClass('has-sticked-bar');

                return;
            }

            $stickedBar.addClass('hidden');
            $navbarRight.removeClass('has-sticked-bar');
        };

        const $stickedBar = this.$stickedBar = this.$el.find('.sticked-bar');
        const $middle = this.$el.find('> .list');

        const $window = $(window);

        let $scrollable = $window;
        let $navbarRight = $('#navbar .navbar-right');

        this.view.on('render', () => {
            this.$stickedBar = null;
        });

        const isModal = !!this.$el.closest('.modal-body').length;

        const screenWidthXs = this.themeManager.getParam('screenWidthXs');
        const navbarHeight = this.themeManager.getParam('navbarHeight');

        const isSmallWindow = $(window.document).width() < screenWidthXs;

        const getOffsetTop = (element) => {
            let offsetTop = 0;

            const withHeader = !isSmallWindow && !isModal;

            do {
                if (element.classList.contains('modal-body')) {
                    break;
                }

                if (!isNaN(element.offsetTop)) {
                    offsetTop += element.offsetTop;
                }

                element = element.offsetParent;
            } while (element);

            if (withHeader) {
                offsetTop -= navbarHeight;
            }

            return offsetTop;
        };

        if (isModal) {
            $scrollable = this.$el.closest('.modal-body');
            $navbarRight = $scrollable.parent().find('.modal-footer');
        }

        let middleTop = getOffsetTop($middle.get(0));
        let buttonsTop =  getOffsetTop(this.$el.find('.list-buttons-container').get(0));

        if (!isModal) {
            // padding
            middleTop -= 5;
            buttonsTop -= 5;
        }

        $scrollable.off('scroll.list-' + this.view.cid);
        $scrollable.on('scroll.list-' + this.view.cid, () => controlSticking());

        $window.off('resize.list-' + this.view.cid);
        $window.on('resize.list-' + this.view.cid, () => controlSticking());

        this.view.on('check', () => {
            if (this.view.getCheckedIds().length === 0 && !this.view.isAllResultChecked()) {
                return;
            }

            controlSticking();
        });

        this.view.on('remove', () => {
            $scrollable.off('scroll.list-' + this.view.cid);
            $window.off('resize.list-' + this.view.cid);
        });
    }

    hide() {
        this.$stickedBar.addClass('hidden');
    }
}

export default StickyBarHelper;
