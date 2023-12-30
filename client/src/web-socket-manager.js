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

/** @module web-socket-manager */

import Base64 from 'js-base64';

/**
 * A web-socket manager.
 */
class WebSocketManager {

    /**
     * @param {module:models/settings} config A config.
     */
    constructor(config) {
        /**
         * @private
         * @type {module:models/settings}
         */
        this.config = config;

        /**
         * @private
         * @type {{category: string, callback: Function}[]}
         */
        this.subscribeQueue = [];

        /**
         * @private
         * @type {boolean}
         */
        this.isConnected = false;

        /**
         * @private
         */
        this.connection = null;

        /**
         * @private
         * @type {string}
         */
        this.url = '';

        /**
         * @private
         * @type {string}
         */
        this.protocolPart = '';

        const url = this.config.get('webSocketUrl');

        if (url) {
            if (url.indexOf('wss://') === 0) {
                this.url = url.substring(6);
                this.protocolPart = 'wss://';
            }
            else {
                this.url = url.substring(5);
                this.protocolPart = 'ws://';
            }
        }
        else {
            const siteUrl = this.config.get('siteUrl') || '';

            if (siteUrl.indexOf('https://') === 0) {
                this.url = siteUrl.substring(8);
                this.protocolPart = 'wss://';
            }
            else {
                this.url = siteUrl.substring(7);
                this.protocolPart = 'ws://';
            }

            if (~this.url.indexOf('/')) {
                this.url = this.url.replace(/\/$/, '');
            }

            const port = this.protocolPart === 'wss://' ? 443 : 8080;

            const si = this.url.indexOf('/');

            if (~si) {
                this.url = this.url.substring(0, si) + ':' + port;
            }
            else {
                this.url += ':' + port;
            }

            if (this.protocolPart === 'wss://') {
                this.url += '/wss';
            }
        }
    }

    /**
     * Connect.
     *
     * @param {string} auth An auth string.
     * @param {string} userId A user ID.
     */
    connect(auth, userId) {
        const authArray = Base64.decode(auth).split(':');

        const authToken = authArray[1];

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
                        setTimeout(() => this.connect(auth, userId), 3000);
                    }
                },
                {skipSubprotocolCheck: true}
            );
        }
        catch (e) {
            console.error(e.message);

            this.connection = null;
        }
    }

    /**
     * Subscribe to a topic.
     *
     * @param {string} category A topic.
     * @param {Function} callback A callback.
     */
    subscribe(category, callback) {
        if (!this.connection) {
            return;
        }

        if (!this.isConnected) {
            this.subscribeQueue.push({
                category: category,
                callback: callback,
            });

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
    }

    /**
     * Unsubscribe.
     *
     * @param {string} category A topic.
     * @param {Function} [callback] A callback.
     */
    unsubscribe(category, callback) {
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
    }

    /**
     * Close a connection.
     */
    close() {
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
    }
}

export default WebSocketManager;
