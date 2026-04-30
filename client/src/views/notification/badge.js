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
import {inject} from 'di';
import WebSocketManager from 'web-socket-manager';
import WindowPanelHelper from 'helpers/site/window-panel-helper';
import ModalBarProvider from 'helpers/site/modal-bar-provider';

class NotificationBadgeView extends View {

    template = 'notification/badge'

    /**
     * @private
     * @type {number}
     */
    notificationsCheckInterval = 10

    /**
     * @private
     * @type {number}
     */
    groupedCheckInterval = 15

    /**
     * @private
     * @type {number}
     */
    waitInterval = 2

    /** @private */
    useWebSocket = false

    /**
     * @private
     * @type {number|null}
     */
    timeout = null

    /**
     * @private
     * @type {number|null}
     */
    groupedTimeout = null

    /**
     * @private
     * @type {Object.<string, {
     *     portalDisabled?: boolean,
     *     grouped?: boolean,
     *     disabled?: boolean,
     *     interval?: Number,
     *     url?: string,
     *     useWebSocket?: boolean,
     *     view?: string,
     *     webSocketCategory?: string,
     * }>}
     */
    popupNotificationsData

    /**
     * @private
     * @type {string}
     */
    soundPath = 'client/sounds/cloud.ogg'

    /**
     * @private
     * @type {WebSocketManager}
     */
    @inject(WebSocketManager)
    webSocketManager

    /**
     * @private
     * @type {ModalBarProvider}
     */
    @inject(ModalBarProvider)
    modalBarProvider

    setup() {
        this.addActionHandler('showNotifications', () => this.showNotifications());

        this.soundPath = this.getBasePath() + (this.getConfig().get('notificationSound') || this.soundPath);
        this.useWebSocket = this.webSocketManager.isEnabled();

        const clearTimeouts = () => {
            if (this.timeout) {
                clearTimeout(this.timeout);
            }

            if (this.groupedTimeout) {
                clearTimeout(this.groupedTimeout);
            }

            for (const name in this.popupTimeouts) {
                clearTimeout(this.popupTimeouts[name]);
            }
        }

        this.once('remove', () => clearTimeouts());
        this.listenToOnce(this.getHelper().router, 'logout', () => clearTimeouts());

        this.notificationsCheckInterval = this.getConfig().get('notificationsCheckInterval') ||
            this.notificationsCheckInterval;

        this.groupedCheckInterval = this.getConfig().get('popupNotificationsCheckInterval') ||
            this.groupedCheckInterval;

        this.lastId = 0;
        this.shownNotificationIds = [];
        this.closedNotificationIds = [];
        this.popupTimeouts = {};

        delete localStorage['messageBlockPlayNotificationSound'];
        delete localStorage['messageClosePopupNotificationId'];
        delete localStorage['messageNotificationRead'];
        delete localStorage['messageCollapsePopupNotificationId'];
        delete localStorage['messageExpandPopupNotificationId'];

        const onWindowStorageBind = this.onWindowStorage.bind(this);

        window.addEventListener('storage', onWindowStorageBind, false);

        this.on('remove', () => window.removeEventListener('storage', onWindowStorageBind))
    }

    afterRender() {
        this.$badge = this.$el.find('.notifications-button');
        this.$number = this.$el.find('.number-badge');

        this.runCheckUpdates(true);

        this.$popupContainer = $('#popup-notifications-container');

        if (!$(this.$popupContainer).length) {
            this.$popupContainer = $('<div>')
                .attr('id', 'popup-notifications-container')
                .addClass('hidden')
                .appendTo('body');
        }

        const popupNotificationsData = this.popupNotificationsData =
            this.getMetadata().get('app.popupNotifications') || {};

        for (const name in popupNotificationsData) {
            this.checkPopupNotifications(name);
        }

        if (this.hasGroupedPopupNotifications()) {
            this.checkGroupedPopupNotifications();
        }
    }

    /**
     * @private
     * @param {StorageEvent} e
     */
    onWindowStorage(e) {
        if (e.key === 'messageClosePopupNotificationId') {
            const id = localStorage.getItem(e.key);

            this.closePopupNotification(id);

            delete localStorage[e.key];
        }

        if (e.key === 'messageCollapsePopupNotificationId') {
            const id = localStorage.getItem(e.key);

            this.collapsePopupNotification(id, true);

            delete localStorage[e.key];
        }

        if (e.key === 'messageExpandPopupNotificationId') {
            const id = localStorage.getItem(e.key);

            this.expandPopupNotification(id, true);

            delete localStorage[e.key];
        }

        if (e.key === 'messageNotificationRead') {
            if (
                !this.isBroadcastingNotificationRead &&
                localStorage.getItem('messageNotificationRead')
            ) {
                this.checkUpdates();
            }
        }
    }

    /**
     * @private
     * @param {string} id
     */
    closePopupNotification(id) {
        if (!id) {
            return;
        }

        const key = `popup-${id}`;
        const view = this.getPopupNotificationView(id);

        if (!view) {
            return;
        }

        this.modalBarProvider?.get().removeModalView(view);
        this.markPopupRemoved(id);
        this.clearView(key);
    }

    playSound() {
        if (!this.getPreferences().get('notificationSound')) {
            return;
        }

        const audio = new Audio(this.soundPath);
        audio.volume = 0.3;

        audio.play();
    }

    /**
     * @private
     * @param {number} count
     */
    showNotRead(count) {
        this.$badge.attr('title', this.translate('New notifications') + ': ' + count);

        this.$number.removeClass('hidden').html(count.toString());

        this.getHelper().pageTitle.setNotificationNumber(count);
    }

    /**
     * @private
     */
    hideNotRead() {
        this.$badge.attr('title', this.translate('Notifications'));
        this.$number.addClass('hidden').html('');

        this.getHelper().pageTitle.setNotificationNumber(0);
    }

    /**
     * @private
     */
    checkBypass() {
        const last = this.getRouter().getLast() || {};

        const pageAction = (last.options || {}).page || null;

        if (
            last.controller === 'Admin' &&
            last.action === 'page' &&
            ['upgrade', 'extensions'].includes(pageAction)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @private
     * @param {boolean} [isFirstCheck]
     */
    async checkUpdates(isFirstCheck) {
        if (this.checkBypass()) {
            return;
        }

        /** @type {number} */
        const count = await Espo.Ajax.getRequest('Notification/action/notReadCount');

        if (!isFirstCheck && count > this.unreadCount) {
            const blockSound = localStorage.getItem('messageBlockPlayNotificationSound');

            if (!blockSound) {
                this.playSound();

                localStorage.setItem('messageBlockPlayNotificationSound', 'true');

                setTimeout(() => {
                    delete localStorage['messageBlockPlayNotificationSound'];
                }, this.notificationsCheckInterval * 1000);
            }
        }

        this.unreadCount = count;

        if (count) {
            this.showNotRead(count);

            return;
        }

        this.hideNotRead();
    }

    runCheckUpdates(isFirstCheck) {
        this.checkUpdates(isFirstCheck);

        if (this.useWebSocket) {
            this.initWebSocketCheckUpdates();

            return;
        }

        this.timeout = setTimeout(
            () => this.runCheckUpdates(),
            this.notificationsCheckInterval * 1000
        );
    }

    /**
     * @private
     */
    initWebSocketCheckUpdates() {
        let isBlocked = false;
        let hasBeenBlocked = false;

        const onWebSocketNewNotification = () => {
            if (isBlocked) {
                hasBeenBlocked = true;

                return;
            }

            this.checkUpdates();

            isBlocked = true;

            setTimeout(() => {
                const reRun = hasBeenBlocked;

                isBlocked = false;
                hasBeenBlocked = false;

                if (reRun) {
                    onWebSocketNewNotification();
                }

            }, this.waitInterval * 1000);
        };

        this.webSocketManager.subscribe('newNotification', () => onWebSocketNewNotification());
        this.webSocketManager.subscribeToReconnect(onWebSocketNewNotification);

        this.once('remove', () => this.webSocketManager.unsubscribe('newNotification'));
        this.once('remove', () => this.webSocketManager.unsubscribeFromReconnect(onWebSocketNewNotification));
    }

    /**
     * @private
     * @return {boolean}
     */
    hasGroupedPopupNotifications() {
        for (const name in this.popupNotificationsData) {
            const data = this.popupNotificationsData[name] || {};

            if (!data.grouped) {
                continue;
            }

            if (data.portalDisabled && this.getUser().isPortal()) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @private
     */
    checkGroupedPopupNotifications() {
        if (!this.checkBypass()) {
            Espo.Ajax.getRequest('PopupNotification/action/grouped')
                .then(result => {
                    for (const type in result) {
                        const list = result[type];

                        list.forEach(item => this.showPopupNotification(type, item));
                    }
                });
        }

        if (this.useWebSocket) {
            return;
        }

        this.groupedTimeout = setTimeout(
            () => this.checkGroupedPopupNotifications(),
            this.groupedCheckInterval * 1000
        );
    }

    checkPopupNotifications(name, isNotFirstCheck) {
        const data = this.popupNotificationsData[name] || {};

        const url = data.url;
        const interval = data.interval;
        const disabled = data.disabled || false;

        if (disabled) {
            return;
        }

        if (data.portalDisabled && this.getUser().isPortal()) {
            return;
        }

        const useWebSocket = this.useWebSocket && data.useWebSocket;

        if (useWebSocket) {
            const category = 'popupNotifications.' + (data.webSocketCategory || name);

            this.webSocketManager.subscribe(category, (c, response) => {
                if (!response.list) {
                    return;
                }

                response.list.forEach(item => {
                    this.showPopupNotification(name, item);
                });
            });
        }

        if (data.grouped) {
            return;
        }

        if (!url) {
            return;
        }

        if (!interval) {
            return;
        }

        (
            new Promise(resolve => {
                if (this.checkBypass()) {
                    resolve();

                    return;
                }

                Espo.Ajax
                    .getRequest(url)
                    .then(list =>
                        list.forEach(item =>
                            this.showPopupNotification(name, item, isNotFirstCheck)
                        )
                    )
                    .finally(() => resolve());
            })
        )
        .then(() => {
            if (useWebSocket) {
                return;
            }

            this.popupTimeouts[name] = setTimeout(
                () => this.checkPopupNotifications(name, true),
                interval * 1000
            );
        });
    }

    /**
     * @private
     * @param {string} name
     * @param {Record} data
     * @param {boolean} isNotFirstCheck
     */
    async showPopupNotification(name, data, isNotFirstCheck = false) {
        const viewName = this.popupNotificationsData[name].view;

        if (!viewName) {
            return;
        }

        let id;

        const notificationId = data.id || null;

        if (notificationId) {
            id = name + '_' + notificationId;

            if (this.shownNotificationIds.includes(id)) {
                const notificationView = this.getPopupNotificationView(id);

                if (notificationView) {
                    notificationView.trigger('update-data', data.data);
                }

                return;
            }

            if (this.closedNotificationIds.includes(notificationId)) {
                return;
            }
        } else {
            id = this.lastId++;
        }

        this.shownNotificationIds.push(id);

        /** @type {import('views/popup-notification').default} */
        let view;

        view = /** @type {import('views/popup-notification').default} */
            await this.createView(`popup-${id}`, viewName, {
                notificationData: data.data ?? {},
                notificationId: data.id,
                id: id,
                isFirstCheck: !isNotFirstCheck,
                onCollapse: () => {
                    this.collapsePopupNotification(id);
                },
                onExpand: () => {
                    this.expandPopupNotification(id);
                },
            });

        this.$popupContainer.removeClass('hidden');

        this.listenTo(view, 'remove', () => {
            this.markPopupRemoved(id);

            localStorage.setItem('messageClosePopupNotificationId', id);
        });

        if (data.id && this.getStorage().get('state', this.getCollapsedStorageKey(id))) {
            this.collapsePopupNotification(id, true);

            return;
        }

        await view.render();
    }

    /**
     * @private
     * @param {string} id
     * @param {boolean} silent
     */
    collapsePopupNotification(id, silent = false) {
        const view = this.getPopupNotificationView(id);

        if (!view) {
            return;
        }

        if (!silent || !view.isCollapsed) {
            this.modalBarProvider.get()?.addModalView(view, {
                title: view.getTitle() ?? this.translate('Notification'),
            });
        }

        if (silent) {
            view.makeCollapsed();

            return;
        }

        localStorage.setItem('messageCollapsePopupNotificationId', id);

        this.getStorage().set('state', this.getCollapsedStorageKey(id), true);
    }

    /**
     * @private
     * @param {string} id
     * @param {boolean} silent
     */
    expandPopupNotification(id, silent = false) {
        const view = this.getPopupNotificationView(id);

        if (!view) {
            return;
        }

        if (!silent || view.isCollapsed) {
            this.modalBarProvider.get()?.removeModalView(view);
        }

        if (silent) {
            view.makeExpanded();

            return;
        }

        localStorage.setItem('messageExpandPopupNotificationId', id);

        this.getStorage().clear('state', this.getCollapsedStorageKey(id));
    }

    /**
     * @private
     * @param {string} id
     * @return {string}
     */
    getCollapsedStorageKey(id) {
        return `popupNotificationCollapsed-${id}`;
    }

    /**
     * @private
     * @param {string} id
     * @return {import('views/popup-notification').default}
     */
    getPopupNotificationView(id) {
        return this.getView(`popup-${id}`);
    }

    /**
     * @private
     * @param {string} id
     */
    markPopupRemoved(id) {
        const index = this.shownNotificationIds.indexOf(id);

        if (index > -1) {
            this.shownNotificationIds.splice(index, 1);
        }

        if (this.shownNotificationIds.length === 0) {
            this.$popupContainer.addClass('hidden');
        }

        this.closedNotificationIds.push(id);
    }

    broadcastNotificationsRead() {
        if (!this.useWebSocket) {
            return;
        }

        this.isBroadcastingNotificationRead = true;

        localStorage.setItem('messageNotificationRead', 'true');

        setTimeout(() => {
            this.isBroadcastingNotificationRead = false;
            delete localStorage['messageNotificationRead'];
        }, 500);
    }

    showNotifications() {
        this.closeNotifications();

        const $container = $('<div>').attr('id', 'notifications-panel');

        $container.appendTo(this.$el.find('.notifications-panel-container'));

        this.createView('panel', 'views/notification/panel', {
            fullSelector: '#notifications-panel',
        }).then(async view => {
            await view.render();

            new WindowPanelHelper(view);

            this.$el.closest('.navbar-body').removeClass('in');

            this.listenTo(view, 'all-read', () => {
                this.hideNotRead();
                this.$el.find('.badge-circle-warning').remove();
                this.broadcastNotificationsRead();
            });

            this.listenTo(view, 'collection-fetched', () => {
                this.checkUpdates();
                this.broadcastNotificationsRead();
            });

            this.listenToOnce(view, 'close', () => {
                this.closeNotifications();
            });
        });

        const $document = $(document);

        $document.on('mouseup.notification', e => {
            if (
                !$container.is(e.target) &&
                $container.has(e.target).length === 0 &&
                !$(e.target).closest('div.modal-dialog').length &&
                !e.target.classList.contains('modal')
            ) {
                this.closeNotifications();
            }
        });

        if (window.innerWidth < this.getThemeManager().getParam('screenWidthXs')) {
            this.listenToOnce(this.getRouter(), 'route', () => {
                this.closeNotifications();
            });
        }
    }

    closeNotifications() {
        const $container = $('#notifications-panel');

        $container.remove();

        const $document = $(document);

        if (this.hasView('panel')) {
            this.getView('panel').remove();
        }

        $document.off('mouseup.notification');
    }
}

export default NotificationBadgeView;
