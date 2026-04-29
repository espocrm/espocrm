/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import View from 'view';
import $ from 'jquery';

/**
 * To be extended with an own template.
 *
 * @abstract
 */
class PopupNotificationView extends View {

    type = 'default'
    style = 'default'

    /**
     * @protected
     * @type {boolean}
     */
    closeButton = true


    /**
     * @protected
     * @type {boolean}
     * @since 10.0
     */
    collapseButton = true

    /**
     * @type {boolean}
     * @internal
     */
    isCollapsed = false

    soundPath = 'client/sounds/pop_cork'

    /**
     * @param {{
     *     id: string,
     *     notificationData: Record,
     *     notificationId: string|null,
     *     isFirstCheck: boolean,
     *     onCollapse: function(),
     *     onExpand: function(),
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;
    }

    init() {
        super.init();

        const id = this.options.id;
        const containerSelector = this.containerSelector = `#${id}`;

        this.setSelector(containerSelector);

        this.notificationSoundsDisabled = this.getConfig().get('notificationSoundsDisabled') ?? true;

        this.soundPath = this.getBasePath() +
            (this.getConfig().get('popupNotificationSound') || this.soundPath);

        this.on('render', () => {
            this.hide();

            if (this.isCollapsed) {
                return;
            }

            const className = 'popup-notification-' + Espo.Utils.toDom(this.type);

            $('<div>')
                .attr('id', id)
                .addClass('popup-notification')
                .addClass(className)
                .addClass('popup-notification-' + this.style)
                .appendTo('#popup-notifications-container');

            this.setElement(containerSelector);
        });

        this.on('after:render', () => {
            this.$el.find('[data-action="close"]').on('click', () =>{
                this.resolveCancel();
            });
        });

        this.once('after:render', () => {
            this.onShow();
        });

        this.once('remove', function () {
            $(containerSelector).remove();
        });

        this.notificationData = this.options.notificationData;
        this.notificationId = this.options.notificationId;
        this.id = this.options.id;

        if (!this.notificationId) {
            this.collapseButton = false;
        }

        this.addActionHandler('collapse', () => this.collapse());
    }

    data() {
        return {
            closeButton: this.closeButton,
            notificationData: this.notificationData,
            notificationId: this.notificationId,
            collapseButton: true,
        };
    }

    /**
     * @internal
     * @since 10.0
     */
    hide() {
        this.element = undefined;

        $(this.containerSelector).remove();
    }

    playSound() {
        if (this.notificationSoundsDisabled) {
            return;
        }

        const html =
            '<audio autoplay="autoplay">' +
            '<source src="' + this.soundPath + '.mp3" type="audio/mpeg" />' +
            '<source src="' + this.soundPath + '.ogg" type="audio/ogg" />' +
            '<embed hidden="true" autostart="true" loop="false" src="' + this.soundPath + '.mp3" />' +
            '</audio>';

        const $audio = $(html);

        $audio.get(0).volume = 0.3;
        // noinspection JSUnresolvedReference
        $audio.get(0).play();
    }

    /**
     * @protected
     */
    onShow() {
        if (!this.options.isFirstCheck) {
            this.playSound();
        }
    }

    /**
     * An on-confirm action. To be extended.
     *
     * @protected
     */
    onConfirm() {}

    /**
     * An on-cancel action. To be extended.
     *
     * @protected
     */
    onCancel() {}

    resolveConfirm() {
        this.onConfirm();
        this.trigger('confirm');
        this.remove();
    }

    resolveCancel() {
        this.onCancel();
        this.trigger('cancel');
        this.remove();
    }

    // noinspection JSCheckFunctionSignatures
    /**
     * @deprecated Use `resolveConfirm`.
     */
    confirm() {
        console.warn(`Method 'confirm' in views/popup-notification is deprecated. Use 'resolveConfirm' instead.`);

        this.resolveConfirm();
    }

    /**
     * @deprecated Use `resolveCancel`.
     */
    cancel() {
        console.warn(`Method 'cancel' in views/popup-notification is deprecated. Use 'resolveCancel' instead.`);

        this.resolveCancel();
    }

    /**
     * Collapse.
     *
     * @since 10.0
     * @private
     */
    collapse() {
        this.isCollapsed = true;

        this.options.onCollapse();

        this.hide();
    }

    /**
     * Expand.
     *
     * @since 10.0
     * @internal
     */
    expand() {
        this.isCollapsed = false;

        this.options.onExpand();

        this.reRender(true);
    }

    /**
     * Collapse silently.
     *
     * @since 10.0
     * @internal
     */
    makeCollapsed() {
        this.isCollapsed = true;

        this.hide();
    }

    /**
     * Expand silently.
     *
     * @since 10.0
     * @internal
     */
    makeExpanded() {
        this.isCollapsed = false;

        this.reRender(true);
    }

    /**
     * Get title.
     *
     * @return string|null
     * @since 10.0
     */
    getTitle() {
        return null;
    }
}

export default PopupNotificationView;
