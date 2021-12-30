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

define('views/notification/badge', 'view', function (Dep) {

    return Dep.extend({

        template: 'notification/badge',

        notificationsCheckInterval: 10,

        timeout: null,

        popupNotificationsData: null,

        soundPath: 'client/sounds/pop_cork',

        events: {
            'click a[data-action="showNotifications"]': function () {
                this.showNotifications();
            },
        },

        setup: function () {
            this.soundPath = this.getBasePath() + (this.getConfig().get('notificationSound') || this.soundPath);

            this.notificationSoundsDisabled = true;

            this.useWebSocket = this.getConfig().get('useWebSocket');

            this.once('remove', () => {
                if (this.timeout) {
                    clearTimeout(this.timeout);
                }

                for (var name in this.popupTimeouts) {
                    clearTimeout(this.popupTimeouts[name]);
                }
            });

            this.notificationsCheckInterval = this.getConfig().get('notificationsCheckInterval') ||
                this.notificationsCheckInterval;

            this.lastId = 0;
            this.shownNotificationIds = [];
            this.closedNotificationIds = [];
            this.popupTimeouts = {};

            delete localStorage['messageBlockPlayNotificationSound'];
            delete localStorage['messageClosePopupNotificationId'];
            delete localStorage['messageNotificationRead'];

            window.addEventListener('storage', e => {
                if (e.key === 'messageClosePopupNotificationId') {
                    var id = localStorage.getItem('messageClosePopupNotificationId');

                    if (id) {
                        var key = 'popup-' + id;

                        if (this.hasView(key)) {
                            this.markPopupRemoved(id);
                            this.clearView(key);
                        }
                    }
                }

                if (e.key === 'messageNotificationRead') {
                    if (
                        !this.isBroadcastingNotificationRead &&
                        localStorage.getItem('messageNotificationRead')
                    ) {
                        this.checkUpdates();
                    }
                }
            }, false);
        },

        afterRender: function () {
            this.$badge = this.$el.find('.notifications-button');
            this.$icon = this.$el.find('.notifications-button .icon');
            this.$number = this.$el.find('.number-badge');

            this.runCheckUpdates(true);

            this.$popupContainer = $('#popup-notifications-container');

            if (!$(this.$popupContainer).length) {
                this.$popupContainer = $('<div>')
                    .attr('id', 'popup-notifications-container')
                    .addClass('hidden')
                    .appendTo('body');
            }

            var popupNotificationsData = this.popupNotificationsData =
                this.getMetadata().get('app.popupNotifications') || {};

            for (var name in popupNotificationsData) {
                this.checkPopupNotifications(name);
            }

            this.checkGroupedPopupNotifications();
        },

        playSound: function () {
            if (this.notificationSoundsDisabled) {
                return;
            }

            var html = '' +
                '<audio autoplay="autoplay">'+
                    '<source src="' + this.soundPath + '.mp3" type="audio/mpeg" />' +
                    '<source src="' + this.soundPath + '.ogg" type="audio/ogg" />' +
                    '<embed hidden="true" autostart="true" loop="false" src="' + this.soundPath +'.mp3" />' +
                '</audio>';

            $(html).get(0).volume = 0.3;
            $(html).get(0).play();
        },

        showNotRead: function (count) {
            this.$badge.attr('title', this.translate('New notifications') + ': ' + count);

            this.$number.removeClass('hidden').html(count.toString());

            this.getHelper().pageTitle.setNotificationNumber(count);
        },

        hideNotRead: function () {
            this.$badge.attr('title', this.translate('Notifications'));
            this.$number.addClass('hidden').html('');

            this.getHelper().pageTitle.setNotificationNumber(0);
        },

        checkBypass: function () {
            let last = this.getRouter().getLast() || {};

            let pageAction = (last.options || {}).page || null;

            if (
                last.controller === 'Admin' &&
                last.action === 'page' &&
                ~['upgrade', 'extensions'].indexOf(pageAction)
            ) {
                return true;
            }
        },

        checkUpdates: function (isFirstCheck) {
            if (this.checkBypass()) {
                return;
            }

            Espo.Ajax
                .getRequest('Notification/action/notReadCount')
                .then(count => {
                    if (!isFirstCheck && count > this.unreadCount) {
                        var messageBlockPlayNotificationSound =
                            localStorage.getItem('messageBlockPlayNotificationSound');

                        if (!messageBlockPlayNotificationSound) {
                            this.playSound();

                            localStorage.setItem('messageBlockPlayNotificationSound', true);

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
                });
        },

        runCheckUpdates: function (isFirstCheck) {
            this.checkUpdates(isFirstCheck);

            if (this.useWebSocket) {
                this.getHelper().webSocketManager.subscribe('newNotification', () => {
                    this.checkUpdates();
                });

                return;
            }

            this.timeout = setTimeout(
                () => this.runCheckUpdates(),
                this.notificationsCheckInterval * 1000
            );
        },

        checkGroupedPopupNotifications: function () {
            var toCheck = false;

            for (var name in this.popupNotificationsData) {
                var data = this.popupNotificationsData[name] || {};

                if (!data.grouped) {
                    continue;
                }

                if (data.portalDisabled && this.getUser().isPortal()) {
                    return;
                }

                toCheck = true;
            }

            if (!toCheck) {
                return;
            }

            Espo.Ajax.getRequest('PopupNotification/action/grouped')
                .then(result => {
                    for (const type in result) {
                        const list = result[type];

                        list.forEach(item => this.showPopupNotification(type, item));
                    }
                });
        },

        checkPopupNotifications: function (name, isNotFirstCheck) {
            var data = this.popupNotificationsData[name] || {};

            var url = data.url;
            var interval = data.interval;
            var disabled = data.disabled || false;

            var isFirstCheck = !isNotFirstCheck;

            if (disabled) {
                return;
            }

            if (data.portalDisabled && this.getUser().isPortal()) {
                return;
            }

            var useWebSocket = this.useWebSocket && data.useWebSocket;

            if (useWebSocket) {
                var category = 'popupNotifications.' + (data.webSocketCategory || name);

                this.getHelper().webSocketManager.subscribe(category, (c, response) => {
                    if (!response.list) {
                        return;
                    }

                    response.list.forEach(item => {
                        this.showPopupNotification(name, item);
                    });
                });
            }

            if (data.grouped && interval && !useWebSocket && isFirstCheck) {
                this.popupTimeouts[name] = setTimeout(
                    () => this.checkPopupNotifications(name, true),
                    interval * 1000
                );

                return;
            }

            if (data.grouped && isFirstCheck) {
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
                        .always(() => resolve());
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
        },

        showPopupNotification: function (name, data, isNotFirstCheck) {
            var view = this.popupNotificationsData[name].view;

            if (!view) {
                return;
            }

            var id = data.id || null;

            if (id) {
                id = name + '_' + id;

                if (~this.shownNotificationIds.indexOf(id)) {
                    var notificationView = this.getView('popup-' + id);

                    if (notificationView) {
                        notificationView.trigger('update-data', data.data);
                    }

                    return;
                }

                if (~this.closedNotificationIds.indexOf(id)) {
                    return;
                }
            }
            else {
                id = this.lastId++;
            }

            this.shownNotificationIds.push(id);

            this.createView('popup-' + id, view, {
                notificationData: data.data || {},
                notificationId: data.id,
                id: id,
                isFirstCheck: !isNotFirstCheck,
            }, view => {
                view.render();

                this.$popupContainer.removeClass('hidden');

                this.listenTo(view, 'remove', () => {
                    this.markPopupRemoved(id);

                    localStorage.setItem('messageClosePopupNotificationId', id);
                });
            });
        },

        markPopupRemoved: function (id) {
            var index = this.shownNotificationIds.indexOf(id);

            if (index > -1) {
                this.shownNotificationIds.splice(index, 1);
            }

            if (this.shownNotificationIds.length === 0) {
                this.$popupContainer.addClass('hidden');
            }

            this.closedNotificationIds.push(id);
        },

        broadcastNotificationsRead: function () {
            if (!this.useWebSocket) {
                return;
            }

            this.isBroadcastingNotificationRead = true;

            localStorage.setItem('messageNotificationRead', true);

            setTimeout(() => {
                this.isBroadcastingNotificationRead = false;
                delete localStorage['messageNotificationRead'];
            }, 500);
        },

        showNotifications: function () {
            this.closeNotifications();

            var $container = $('<div>').attr('id', 'notifications-panel');

            $container.appendTo(this.$el.find('.notifications-panel-container'));

            this.createView('panel', 'views/notification/panel', {
                el: '#notifications-panel',
            }, view => {
                view.render();

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

            $document = $(document);

            $document.on('mouseup.notification', e => {
                if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                    if (!$(e.target).closest('div.modal-dialog').length) {
                        this.closeNotifications();
                    }
                }
            });

            if (window.innerWidth < this.getThemeManager().getParam('screenWidthXs')) {
                this.listenToOnce(this.getRouter(), 'route', () => {
                    this.closeNotifications();
                });
            }
        },

        closeNotifications: function () {
            let $container = $('#notifications-panel');

            $('#notifications-panel').remove();

            let $document = $(document);

            if (this.hasView('panel')) {
                this.getView('panel').remove();
            }

            $document.off('mouseup.notification');

            $container.remove();
        },

    });
});
