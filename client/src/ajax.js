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

/** @module ajax */

import $ from 'jquery';
import Utils from 'utils';

let isConfigured = false;
/** @type {number} */
let defaultTimeout;
/** @type {string} */
let apiUrl;
/** @type {Espo.Ajax~Handler} */
let beforeSend;
/** @type {Espo.Ajax~Handler} */
let onSuccess;
/** @type {Espo.Ajax~Handler} */
let onError;
/** @type {Espo.Ajax~Handler} */
let onTimeout;
/** @type {function()} */
let onOffline;

/**
 * @callback Espo.Ajax~Handler
 * @param {XMLHttpRequest} [xhr]
 * @param {Object.<string, *>} [options]
 */

/**
 * Options.
 *
 * @typedef {Object} Espo.Ajax~Options
 *
 * @property {Number} [timeout] A timeout.
 * @property {Object.<string, string>} [headers] A request headers.
 * @property {'json'|'text'} [dataType] A data type.
 * @property {string} [contentType] A content type.
 * @property {boolean} [resolveWithXhr] To resolve with `XMLHttpRequest`.
 */

const baseUrl = Utils.obtainBaseUrl();

// noinspection JSUnusedGlobalSymbols
/**
 * Functions for API HTTP requests.
 */
const Ajax = Espo.Ajax = {

    /**
     * Request.
     *
     * @param {string} url An URL.
     * @param {'GET'|'POST'|'PUT'|'DELETE'|'PATCH'|'OPTIONS'} method An HTTP method.
     * @param {*} [data] Data.
     * @param {Espo.Ajax~Options & Object.<string, *>} [options] Options.
     * @returns {AjaxPromise<any>}
     */
    request: function (url, method, data, options) {
        options = options || {};

        const timeout = 'timeout' in options ? options.timeout : defaultTimeout;
        const contentType = options.contentType || 'application/json';
        let body;

        if (options.data && !data) {
            data = options.data;
        }

        if (apiUrl) {
            url = Espo.Utils.trimSlash(apiUrl) + '/' + url;
        }

        if (!['GET', 'OPTIONS'].includes(method) && data) {
            body = data;

            if (contentType === 'application/json' && typeof data !== 'string') {
                body = JSON.stringify(data);
            }
        }

        if (method === 'GET' && data) {
            const part = $.param(data);

            url.includes('?') ?
                url += '&' :
                url += '?';

            url += part;
        }

        const urlObj = new URL(baseUrl + url);

        const xhr = new Xhr();
        xhr.timeout = timeout;
        xhr.open(method, urlObj);
        xhr.setRequestHeader('Content-Type', contentType);

        if (options.headers) {
            for (const key in options.headers) {
                xhr.setRequestHeader(key, options.headers[key]);
            }
        }

        if (beforeSend) {
            beforeSend(xhr, options);
        }

        const promiseWrapper = {};

        const promise = new AjaxPromise((resolve, reject) => {
            const onErrorGeneral = (isTimeout) => {
                if (options.error) {
                    options.error(xhr, options);
                }

                reject(xhr, options);

                if (isTimeout) {
                    if (onTimeout) {
                        onTimeout(xhr, options);
                    }

                    return;
                }

                if (xhr.status === 0 && !navigator.onLine && onOffline) {
                    onOffline();

                    return;
                }

                if (onError) {
                    onError(xhr, options);
                }
            };

            xhr.ontimeout = () => onErrorGeneral(true);
            xhr.onerror = () => onErrorGeneral();

            xhr.onload = () => {
                if (xhr.status >= 400) {
                    onErrorGeneral();

                    return;
                }

                let response = xhr.responseText;

                if ((options.dataType || 'json') === 'json') {
                    try {
                        response = JSON.parse(xhr.responseText);
                    }
                    catch (e) {
                        console.error('Could not parse API response.');

                        onErrorGeneral();
                    }
                }

                if (options.success) {
                    options.success(response);
                }

                onSuccess(xhr, options);

                if (options.resolveWithXhr) {
                    response = xhr;
                }

                resolve(response)
            }

            xhr.send(body);

            if (promiseWrapper.promise) {
                promiseWrapper.promise.xhr = xhr;

                return;
            }

            promiseWrapper.xhr = xhr;
        });

        promiseWrapper.promise = promise;
        promise.xhr = promise.xhr || promiseWrapper.xhr;

        return promise;
    },

    /**
     * POST request.
     *
     * @param {string} url An URL.
     * @param {*} [data] Data.
     * @param {Espo.Ajax~Options & Object.<string, *>} [options] Options.
     * @returns {Promise<any> & AjaxPromise}
     */
    postRequest: function (url, data, options) {
        if (data) {
            data = JSON.stringify(data);
        }

        return /** @type {Promise<any> & AjaxPromise} */ Ajax.request(url, 'POST', data, options);
    },

    /**
     * PATCH request.
     *
     * @param {string} url An URL.
     * @param {*} [data] Data.
     * @param {Espo.Ajax~Options & Object.<string, *>} [options] Options.
     * @returns {Promise<any> & AjaxPromise}
     */
    patchRequest: function (url, data, options) {
        if (data) {
            data = JSON.stringify(data);
        }

        return /** @type {Promise<any> & AjaxPromise} */ Ajax.request(url, 'PATCH', data, options);
    },

    /**
     * PUT request.
     *
     * @param {string} url An URL.
     * @param {*} [data] Data.
     * @param {Espo.Ajax~Options & Object.<string, *>} [options] Options.
     * @returns {Promise<any> & AjaxPromise}
     */
    putRequest: function (url, data, options) {
        if (data) {
            data = JSON.stringify(data);
        }

        return /** @type {Promise<any> & AjaxPromise} */ Ajax.request(url, 'PUT', data, options);
    },

    /**
     * DELETE request.
     *
     * @param {string} url An URL.
     * @param {*} [data] Data.
     * @param {Espo.Ajax~Options & Object.<string, *>} [options] Options.
     * @returns {Promise<any> & AjaxPromise}
     */
    deleteRequest: function (url, data, options) {
        if (data) {
            data = JSON.stringify(data);
        }

        return /** @type {Promise<any> & AjaxPromise} */ Ajax.request(url, 'DELETE', data, options);
    },

    /**
     * GET request.
     *
     * @param {string} url An URL.
     * @param {*} [data] Data.
     * @param {Espo.Ajax~Options & Object.<string, *>} [options] Options.
     * @returns {Promise<any> & AjaxPromise}
     */
    getRequest: function (url, data, options) {
        return /** @type {Promise<any> & AjaxPromise} */ Ajax.request(url, 'GET', data, options);
    },

    /**
     * @internal
     * @param {{
     *     apiUrl: string,
     *     timeout: number,
     *     beforeSend: Espo.Ajax~Handler,
     *     onSuccess: Espo.Ajax~Handler,
     *     onError: Espo.Ajax~Handler,
     *     onTimeout: Espo.Ajax~Handler,
     *     onOffline?: function(),
     * }} options Options.
     */
    configure: function (options) {
        if (isConfigured) {
            throw new Error("Ajax is already configured.");
        }

        apiUrl = options.apiUrl;
        defaultTimeout = options.timeout;
        beforeSend = options.beforeSend;
        onSuccess = options.onSuccess;
        onError = options.onError;
        onTimeout = options.onTimeout;
        onOffline = options.onOffline;

        isConfigured = true;
    },
};

/**
 * @memberOf module:ajax
 */
class AjaxPromise extends Promise {

    /**
     * @type {XMLHttpRequest|null}
     * @internal
     */
    xhr = null

    isAborted = false

    /**
     * Abort the request.
     */
    abort() {
        this.isAborted = true;

        if (this.xhr) {
            this.xhr.abort();
        }
    }

    /**
     * Get a ready state.
     *
     * @return {Number}
     */
    getReadyState() {
        if (!this.xhr) {
            return 0;
        }

        return this.xhr.readyState || 0;
    }

    /**
     * Get a status code
     *
     * @return {Number}
     */
    getStatus() {
        if (!this.xhr) {
            return 0;
        }

        return this.xhr.status;
    }
}

/**
 * @name module:ajax.Xhr
 */
class Xhr extends XMLHttpRequest {
    /**
     * To be set in an error handler to bypass default handling.
     */
    errorIsHandled = false
}

export default Ajax;
