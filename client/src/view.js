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

define('view', [], function () {

    return Bull.View.extend({

        addActionHandler: function (action, handler) {
            this.events = this.events || {};

            var fullAction = 'click button[data-action=\"'+action+'\"]';
            this.events[fullAction] = handler;
        },

        escapeString: function (string) {
            return Handlebars.Utils.escapeExpression(string);
        },

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

        getHelper: function () {
            return this._helper;
        },

        getUser: function () {
            if (this._helper) {
                return this._helper.user;
            }
        },

        getPreferences: function () {
            if (this._helper) {
                return this._helper.preferences;
            }
        },

        getConfig: function () {
            if (this._helper) {
                return this._helper.settings;
            }
        },

        getAcl: function () {
            if (this._helper) {
                return this._helper.acl;
            }
        },

        getModelFactory: function () {
            if (this._helper) {
                return this._helper.modelFactory;
            }
        },

        getCollectionFactory: function () {
            if (this._helper) {
                return this._helper.collectionFactory;
            }
        },

        getRouter: function () {
            if (this._helper) {
                return this._helper.router;
            }
        },

        getStorage: function () {
            if (this._helper) {
                return this._helper.storage;
            }
        },

        getSessionStorage: function () {
            if (this._helper) {
                return this._helper.sessionStorage;
            }
        },

        getLanguage: function () {
            if (this._helper) {
                return this._helper.language;
            }
        },

        getMetadata: function () {
            if (this._helper) {
                return this._helper.metadata;
            }
        },

        getCache: function () {
            if (this._helper) {
                return this._helper.cache;
            }
        },

        getDateTime: function () {
            if (this._helper) {
                return this._helper.dateTime;
            }
        },

        getNumberUtil: function () {
            if (this._helper) {
                return this._helper.numberUtil;
            }
        },

        getFieldManager: function () {
            if (this._helper) {
                return this._helper.fieldManager;
            }
        },

        getBaseController: function () {
            if (this._helper) {
                return this._helper.baseController;
            }
        },

        getThemeManager: function () {
            if (this._helper) {
                return this._helper.themeManager;
            }
        },

        updatePageTitle: function () {
            var title = this.getConfig().get('applicationName') || 'EspoCRM';
            this.setPageTitle(title);
        },

        setPageTitle: function (title) {
            this.getHelper().pageTitle.setTitle(title);
        },

        translate: function (label, category, scope) {
            return this.getLanguage().translate(label, category, scope);
        },

        getBasePath: function () {
            return this._helper.basePath || '';
        },

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

        ajaxPostRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'POST', data, options);
        },

        ajaxPatchRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'PATCH', data, options);
        },

        ajaxPutRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'PUT', data, options);
        },

        ajaxGetRequest: function (url, data, options) {
            return this.ajaxRequest(url, 'GET', data, options);
        },

        ajaxDeleteRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }

            return this.ajaxRequest(url, 'DELETE', data, options);
        },

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
                backdrop: ('backdrop' in o) ? o.backdrop : false,
            }, callback, context);
        }
    });
});
