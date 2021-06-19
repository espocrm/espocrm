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

define('web-socket-manager', [], function () {

    let WebSocketManager = function (config) {
        this.config = config;

        let url = this.config.get('webSocketUrl');

        if (url) {
            if (url.indexOf('wss://') === 0) {
                this.url = url.substr(6);
                this.protocolPart = 'wss://';
            }
            else {
                this.url = url.substr(5);
                this.protocolPart = 'ws://';
            }
        }
        else {
            let siteUrl = this.config.get('siteUrl') || '';

            if (siteUrl.indexOf('https://') === 0) {
                this.url = siteUrl.substr(8);
                this.protocolPart = 'wss://';
            }
            else {
                this.url = siteUrl.substr(7);
                this.protocolPart = 'ws://';
            }

            if (~this.url.indexOf('/')) {
                this.url = this.url.replace(/\/$/, '');
            }

            let port = this.protocolPart === 'wss://' ? 443 : 8080;

            let si = this.url.indexOf('/');

            if (~si) {
                this.url = this.url.substr(0, si) + ':' + port;
            }
            else {
                this.url += ':' + port;
            }

            if (this.protocolPart === 'wss://') {
                this.url += '/wss';
            }
        }

        this.subscribeQueue = [];
    };

    _.extend(WebSocketManager.prototype, {

        connect: function (auth, userId) {
            let authArray = base64.decode(auth).split(':');

            let authToken = authArray[1];

            let url = this.protocolPart + this.url;

            url += '?authToken=' + authToken + '&userId=' + userId;

            try {
                this.connection = new ab.Session(
                    url,
                    () => {
                        this.isConnected = true;

                        this.subscribeQueue.forEach(item => {
                            this.subscribe(item.category, item.callback);
                        });

                        this.subscribeQueue = [];
                    },
                    e => {
                        if (e === ab.CONNECTION_CLOSED) {
                            this.subscribeQueue = [];
                        }

                        if (e === ab.CONNECTION_LOST || e === ab.CONNECTION_UNREACHABLE) {
                            setTimeout(
                                () => {
                                    this.connect(auth, userId);
                                },
                                3000
                            );
                        }
                    },
                    {skipSubprotocolCheck: true}
                );
            }
            catch (e) {
                console.error(e.message);

                this.connection = null;
            }
        },

        subscribe: function (category, callback) {
            if (!this.connection) {
                return;
            }

            if (!this.isConnected) {
                this.subscribeQueue.push({category: category, callback: callback});

                return;
            }

            try {
                this.connection.subscribe(category, callback);
            }
            catch (e) {
                if (e.message) {
                    console.error(e.message);
                }
                else {
                    console.error("WebSocket: Could not subscribe to "+category+".");
                }
            }
        },

        unsubscribe: function (category, callback) {
            if (!this.connection) {
                return;
            }

            this.subscribeQueue = this.subscribeQueue.filter(item => {
                return item.category !== category && item.callback !== callback;
            });

            try {
                this.connection.unsubscribe(category, callback);
            }
            catch (e) {
                if (e.message) {
                    console.error(e.message);
                }
                else {
                    console.error("WebSocket: Could not unsubscribe from "+category+".");
                }
            }
        },

        close: function () {
            if (!this.connection) {
                return;
            }

            try {
                this.connection.close();
            }
            catch (e) {
                console.error(e.message);
            }

            this.isConnected = false;
        },
    });

    return WebSocketManager;
});
