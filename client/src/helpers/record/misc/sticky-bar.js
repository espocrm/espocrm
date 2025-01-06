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

import $ from 'jquery';

/**
 * @internal
 */
class StickyBarHelper {


    /**
     * @param {import('views/record/detail').default} view
     * @param {boolean} stickButtonsFormBottomSelector
     * @param {boolean} stickButtonsContainerAllTheWay
     * @param {number} numId
     */
    constructor(view, stickButtonsFormBottomSelector, stickButtonsContainerAllTheWay, numId) {
        this.view = view;
        this.stickButtonsFormBottomSelector = stickButtonsFormBottomSelector;
        this.stickButtonsContainerAllTheWay = stickButtonsContainerAllTheWay;
        this.numId = numId;

        this.themeManager = view.getThemeManager();
        this.$el = view.$el;
    }

    init() {
        const $containers = this.$el.find('.detail-button-container');
        const $container = this.$el.find('.detail-button-container.record-buttons');

        if (!$container.length) {
            return;
        }

        const navbarHeight = this.themeManager.getParam('navbarHeight') * this.themeManager.getFontSizeFactor();
        const screenWidthXs = this.themeManager.getParam('screenWidthXs');

        const isSmallScreen = $(window.document).width() < screenWidthXs;

        const getOffsetTop = (/** JQuery */$element) => {
            let element = /** @type {HTMLElement} */$element.get(0);

            let value = 0;

            while (element) {
                value += !isNaN(element.offsetTop) ? element.offsetTop : 0;

                element = element.offsetParent;
            }

            if (isSmallScreen) {
                return value;
            }

            return value - navbarHeight;
        };

        let stickTop = getOffsetTop($container);
        const blockHeight = $container.outerHeight();

        stickTop -= 5; // padding;

        const $block = $('<div>')
            .css('height', blockHeight + 'px')
            .html('&nbsp;')
            .hide()
            .insertAfter($container);

        let $middle = this.view.getMiddleView().$el;
        const $window = $(window);
        const $navbarRight = $('#navbar .navbar-right');

        if (this.stickButtonsFormBottomSelector) {
            const $bottom = this.$el.find(this.stickButtonsFormBottomSelector);

            if ($bottom.length) {
                $middle = $bottom;
            }
        }

        $window.off('scroll.detail-' + this.numId);

        $window.on('scroll.detail-' + this.numId, () => {
            const edge = $middle.position().top + $middle.outerHeight(false) - blockHeight;
            const scrollTop = $window.scrollTop();

            if (scrollTop >= edge && !this.stickButtonsContainerAllTheWay) {
                $containers.hide();
                $navbarRight.removeClass('has-sticked-bar');
                $block.show();

                return;
            }

            if (isSmallScreen && $('#navbar .navbar-body').hasClass('in')) {
                return;
            }

            if (scrollTop > stickTop) {
                if (!$containers.hasClass('stick-sub')) {
                    $containers.addClass('stick-sub');
                    $block.show();
                }

                $navbarRight.addClass('has-sticked-bar');

                $containers.show();

                return;
            }

            if ($containers.hasClass('stick-sub')) {
                $containers.removeClass('stick-sub');
                $navbarRight.removeClass('has-sticked-bar');
                $block.hide();
            }

            $containers.show();
        });
    }
}

export default StickyBarHelper;
