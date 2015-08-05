/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
Espo.define('view', [], function () {

    return Bull.View.extend({

        addActionHandler: function (action, handler) {
            this.events = this.events || {};

            var fullAction = 'click button[data-action=\"'+action+'\"]';
            this.events[fullAction] = handler;
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
            var text = this.getLanguage().translate(label, 'labels', scope);
            Espo.Ui.notify(text, type, timeout);
        },

        reRender: function () {
            if (this.isRendered()) {
                this.render();
            } else if (this.isBeingRendered()) {
                this.once('after:render', function () {
                    this.render();
                }, this);
            }
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

        getStorage: function () {
            if (this._helper) {
                return this._helper.storage;
            }
        },

        getDateTime: function () {
            if (this._helper) {
                return this._helper.dateTime;
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
            var title = this.getConfig().get('applicationTitle') || 'EspoCRM';
            this.setPageTitle(title);
        },

        setPageTitle: function (title) {
            $('head title').text(title);
        },

        translate: function (label, category, scope) {
            return this.getLanguage().translate(label, category, scope);
        },
    });

});
