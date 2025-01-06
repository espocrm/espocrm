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
import {Events} from 'bullbone';

/**
 * @internal
 *
 * @mixes Bull.Events
 */
class StickyBarHelper {

    /** @private */
    $bar
    /** @private */
    $scrollable
    /** @private */
    $window
    /** @private */
    $navbarRight
    /** @private */
    $middle
    /** @private */
    _isReady = false

    /**
     * @param {import('views/record/list').default} view
     * @param {{force?: boolean}} options
     */
    constructor(view, options = {}) {
        this.view = view;

        /**
         * @private
         * @type {import('theme-manager').default}
         */

        this.themeManager = this.view.getThemeManager();

        this.$el = view.$el;

        /** @private */
        this.force = options.force || false;

        this.init();
    }

    init() {
        this.$bar = this.$el.find('.sticked-bar');
        this.$middle = this.$el.find('> .list');

        if (!this.$middle.get(0)) {
            return;
        }

        this.$window = $(window);
        this.$scrollable = this.$window;
        this.$navbarRight = $('#navbar .navbar-right');

        this.isModal = !!this.$el.closest('.modal-body').length;

        this.isSmallWindow = $(window.document).width() < this.themeManager.getParam('screenWidthXs');

        if (this.isModal) {
            this.$scrollable = this.$el.closest('.modal-body');
            this.$navbarRight = this.$scrollable.parent().find('.modal-footer');
        }

        if (!this.force) {
            this.$scrollable.off(`scroll.list-${this.view.cid}`);
            this.$scrollable.on(`scroll.list-${this.view.cid}`, () => this._controlSticking());

            this.$window.off(`resize.list-${this.view.cid}`);
            this.$window.on(`resize.list-${this.view.cid}`, () => this._controlSticking());
        }

        this.listenTo(this.view, 'check', () => {
            if (this.view.getCheckedIds().length === 0 && !this.view.isAllResultChecked()) {
                return;
            }

            this._controlSticking();
        });

        this._isReady = true;
    }

    _getMiddleTop() {
        if (this._middleTop !== undefined && this._middleTop >= 0) {
            return this._middleTop;
        }

        this._middleTop = this._getOffsetTop(this.$middle.get(0));

        return this._middleTop;
    }

    _getButtonsTop() {
        if (this._buttonsTop !== undefined && this._buttonsTop >= 0) {
            return this._buttonsTop;
        }

        this._buttonsTop = this._getOffsetTop(this.$el.find('.list-buttons-container').get(0));

        return this._buttonsTop;
    }

    /**
     * @private
     */
    _controlSticking() {
        if (!this.view.toShowStickyBar()) {
            return;
        }

        if (this.isSmallWindow && $('#navbar .navbar-body').hasClass('in')) {
            return;
        }

        const scrollTop = this.$scrollable.scrollTop();
        const stickTop = !this.force ? this._getButtonsTop() : 0;
        const edge = this._getMiddleTop() + this.$middle.outerHeight(true);

        const hide = () => {
            this.$bar.addClass('hidden');
            this.$navbarRight.removeClass('has-sticked-bar');
        };

        const show = () => {
            this.$bar.removeClass('hidden');
            this.$navbarRight.addClass('has-sticked-bar');
        };

        if (scrollTop >= edge) {
            hide();

            return;
        }

        if (scrollTop > stickTop || this.force) {
            show();

            return;
        }

        hide();
    }

    /**
     * @private
     * @param {HTMLElement} element
     */
    _getOffsetTop(element) {
        if (!element) {
            return 0;
        }

        const navbarHeight = this.themeManager.getParam('navbarHeight') * this.themeManager.getFontSizeFactor();
        const withHeader = !this.isSmallWindow && !this.isModal;

        let offsetTop = 0;

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

        if (!this.isModal) {
            // padding
            offsetTop -= 5;
        }

        return offsetTop;
    }

    hide() {
        this.$bar.addClass('hidden');
    }

    destroy() {
        this.stopListening(this.view, 'check');

        if (!this._isReady) {
            return;
        }

        this.$window.off(`resize.list-${this.view.cid}`);
        this.$scrollable.off(`scroll.list-${this.view.cid}`);
    }
}

Object.assign(StickyBarHelper.prototype, Events);

export default StickyBarHelper;
