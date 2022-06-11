/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('view', [], function () {

    /**
     * A base view.
     *
     * @class Espo.View
     * @extends Bull.View
     */
    return Bull.View.extend(/** @lends Espo.View.prototype */{

        /**
         * @callback Espo.View~actionHandlerCallback
         * @param {jQuery.Event} e A DOM event.
         */

        /**
         * A model.
         *
         * @name model
         * @type {?Espo.Model}
         * @memberof Espo.View.prototype
         * @public
         */

        /**
         * A collection.
         *
         * @name collection
         * @type {?Espo.Collection}
         * @memberof Espo.View.prototype
         * @public
         */

        /**
         * Add a DOM button-action event handler.
         *
         * @deprecated Use the `events` property.
         * @param {string} action
         * @param {Espo.View~actionHandlerCallback} handler
         */
        addActionHandler: function (action, handler) {
            let fullAction = 'click button[data-action=\"'+action+'\"]';

            this.events[fullAction] = handler;
        },

        /**
         * Escape a string.
         *
         * @param {string} string
         * @returns {string}
         */
        escapeString: function (string) {
            return Handlebars.Utils.escapeExpression(string);
        },

        /**
         * Show a notify-message.
         *
         * @deprecated Use `Espo.Ui.notify`.
         * @param {string} label
         * @param {string} [type]
         * @param {number} [timeout]
         * @param {string} [scope]
         */
        notify: function (label, type, timeout, scope) {
            if (label == false) {
                Espo.Ui.notify(false);

                return;
            }

            scope = scope || null;
            timeout = timeout || 2000;

            if (!type) {
                timeout = null;
            }

            let text = this.getLanguage().translate(label, 'labels', scope);

            Espo.Ui.notify(text, type, timeout);
        },

        /**
         * Get the view-helper.
         *
         * @returns {Espo.ViewHelper}
         */
        getHelper: function () {
            return this._helper;
        },

        /**
         * Get the current user.
         *
         * @returns {Espo.Models.User}
         */
        getUser: function () {
            if (this._helper) {
                return this._helper.user;
            }
        },

        /**
         * Get the preferences.
         *
         * @returns {Espo.Models.Preferences}
         */
        getPreferences: function () {
            if (this._helper) {
                return this._helper.preferences;
            }
        },

        /**
         * Get the config.
         *
         * @returns {Espo.Models.Settings}
         */
        getConfig: function () {
            if (this._helper) {
                return this._helper.settings;
            }
        },

        /**
         * Get the ACL.
         *
         * @returns {Espo.Acl}
         */
        getAcl: function () {
            if (this._helper) {
                return this._helper.acl;
            }
        },

        /**
         * Get the model factory.
         *
         * @returns {Espo.ModelFactory}
         */
        getModelFactory: function () {
            if (this._helper) {
                return this._helper.modelFactory;
            }
        },

        /**
         * Get the collection factory.
         *
         * @returns {Espo.CollectionFactory}
         */
        getCollectionFactory: function () {
            if (this._helper) {
                return this._helper.collectionFactory;
            }
        },

        /**
         * Get the router.
         *
         * @returns {Espo.Router}
         */
        getRouter: function () {
            if (this._helper) {
                return this._helper.router;
            }
        },

        /**
         * Get the storage-util.
         *
         * @returns {Espo.Storage}
         */
        getStorage: function () {
            if (this._helper) {
                return this._helper.storage;
            }
        },

        /**
         * Get the session-storage-util.
         *
         * @returns {Espo.SessionStorage}
         */
        getSessionStorage: function () {
            if (this._helper) {
                return this._helper.sessionStorage;
            }
        },

        /**
         * Get the language-util.
         *
         * @returns {Espo.Language}
         */
        getLanguage: function () {
            if (this._helper) {
                return this._helper.language;
            }
        },

        /**
         * Get metadata.
         *
         * @returns {Espo.Metadata}
         */
        getMetadata: function () {
            if (this._helper) {
                return this._helper.metadata;
            }
        },

        /**
         * Get the cache-util.
         *
         * @returns {Espo.Cache}
         */
        getCache: function () {
            if (this._helper) {
                return this._helper.cache;
            }
        },

        /**
         * Get the date-time util.
         *
         * @returns {Espo.DateTime}
         */
        getDateTime: function () {
            if (this._helper) {
                return this._helper.dateTime;
            }
        },

        /**
         * Get the number-util.
         *
         * @returns {Espo.Number}
         */
        getNumberUtil: function () {
            if (this._helper) {
                return this._helper.numberUtil;
            }
        },

        /**
         * Get the field manager.
         *
         * @returns {Espo.FieldManager}
         */
        getFieldManager: function () {
            if (this._helper) {
                return this._helper.fieldManager;
            }
        },

        /**
         * Get the base-controller.
         *
         * @returns {Espo.Controllers.Base}
         */
        getBaseController: function () {
            if (this._helper) {
                return this._helper.baseController;
            }
        },

        /**
         * Get the theme manager.
         *
         * @returns {Espo.ThemeManager}
         */
        getThemeManager: function () {
            if (this._helper) {
                return this._helper.themeManager;
            }
        },

        /**
         * Update a page title. Supposed to be overridden if needed.
         */
        updatePageTitle: function () {
            var title = this.getConfig().get('applicationName') || 'EspoCRM';

            this.setPageTitle(title);
        },

        /**
         * Set a page title.
         *
         * @param {string} title A title.
         */
        setPageTitle: function (title) {
            this.getHelper().pageTitle.setTitle(title);
        },

        /**
         * Translate a label.
         *
         * @param {string} label Label.
         * @param {string} category Category.
         * @param {string} scope Scope.
         * @returns {string}
         */
        translate: function (label, category, scope) {
            return this.getLanguage().translate(label, category, scope);
        },

        /**
         * Get a base path.
         *
         * @returns {string}
         */
        getBasePath: function () {
            return this._helper.basePath || '';
        },

        /**
         * Ajax request.
         *
         * @deprecated Use `Espo.Ajax`.
         * @param {string} url An URL.
         * @param {string} type A method.
         * @param {any} [data] Data.
         * @param {Object} [options] Options.
         * @returns {Promise<any>}
         */
        ajaxRequest: function (url, type, data, options) {
            options = options || {};

            options.type = type;
            options.url = url;
            options.context = this;

            if (data) {
                options.data = data;
            }

            let xhr = $.ajax(options);

            return xhr;
        },

        /**
         * POST request.
         *
         * @deprecated Use `Espo.Ajax.postRequest`.
         * @param {string} url An URL.
         * @param {any} [data] Data.
         * @param {Object} [options] Options.
         * @returns {Promise<any>}
         */
        ajaxPostRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'POST', data, options);
        },

        /**
         * PATCH request.
         *
         * @deprecated Use `Espo.Ajax.patchRequest`.
         * @param {string} url An URL.
         * @param {any} [data] Data.
         * @param {Object} [options] Options.
         * @returns {Promise<any>}
         */
        ajaxPatchRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'PATCH', data, options);
        },

        /**
         * PUT request.
         *
         * @deprecated Use `Espo.Ajax.putRequest`.
         * @param {string} url An URL.
         * @param {any} [data] Data.
         * @param {Object} [options] Options.
         * @returns {Promise<any>}
         */
        ajaxPutRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'PUT', data, options);
        },

        /**
         * GET request.
         *
         * @deprecated Use `Espo.Ajax.getRequest`.
         * @param {string} url An URL.
         * @param {any} [data] Data.
         * @param {Object} [options] Options.
         * @returns {Promise<any>}
         */
        ajaxGetRequest: function (url, data, options) {
            return this.ajaxRequest(url, 'GET', data, options);
        },

        /**
         * DELETE request.
         *
         * @deprecated Use `Espo.Ajax.deleteRequest`.
         * @param {string} url An URL.
         * @param {any} [data] Data.
         * @param {Object} [options] Options.
         * @returns {Promise<any>}
         */
        ajaxDeleteRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'DELETE', data, options);
        },

        /**
         * Show a confirmation dialog.
         *
         * @param {(string|Object)} o A message or options.
         * @param [callback]
         * @param [context]
         * @returns {Promise} To be resolved if confirmed.
         */
        confirm: function (o, callback, context) {
            let message;

            if (typeof o === 'string' || o instanceof String) {
                message = o;

                o = {};
            }
            else {
                o = o || {};

                message = o.message;
            }

            if (message) {
                message = this.getHelper().transfromMarkdownText(message, {linksInNewTab: true}).toString();
            }

            let confirmText = o.confirmText || this.translate('Yes');
            let confirmStyle = o.confirmStyle || null;
            let cancelText = o.cancelText || this.translate('Cancel');

            return Espo.Ui.confirm(message, {
                confirmText: confirmText,
                cancelText: cancelText,
                confirmStyle: confirmStyle,
                noCancelButton: o.noCancelButton,
                backdrop: ('backdrop' in o) ? o.backdrop : true,
            }, callback, context);
        },
    });
});
