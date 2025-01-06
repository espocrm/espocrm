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
    closeButton = true
    soundPath = 'client/sounds/pop_cork'

    init() {
        super.init();

        const id = this.options.id;
        const containerSelector = this.containerSelector = '#' + id;

        this.setSelector(containerSelector);

        this.notificationSoundsDisabled = this.getConfig().get('notificationSoundsDisabled');

        this.soundPath = this.getBasePath() +
            (this.getConfig().get('popupNotificationSound') || this.soundPath);

        this.on('render', () => {
            this.element = undefined;

            $(containerSelector).remove();

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
    }

    data() {
        return {
            closeButton: this.closeButton,
            notificationData: this.notificationData,
            notificationId: this.notificationId,
        };
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
}

export default PopupNotificationView;
