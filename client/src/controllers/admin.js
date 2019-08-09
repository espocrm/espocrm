/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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


define('controllers/admin', ['controller', 'search-manager'], function (Dep, SearchManager) {

    return Dep.extend({

        checkAccessGlobal: function () {
            if (this.getUser().isAdmin()) {
                return true;
            }
            return false;
        },

        actionIndex: function (options) {
            var isReturn = options.isReturn;
            var key = this.name + 'Index';

            if (this.getRouter().backProcessed)
                isReturn = true;

            if (!isReturn && this.getStoredMainView(key))
                this.clearStoredMainView(key);

            this.main('views/admin/index', null, function (view) {
                view.render();

                this.listenTo(view, 'clear-cache', this.clearCache);
                this.listenTo(view, 'rebuild', this.rebuild);
            }.bind(this), isReturn, key);
        },

        actionLayouts: function (options) {
            var scope = options.scope || null;
            var type = options.type || null;

            this.main('views/admin/layouts/index', {scope: scope, type: type});
        },

        actionLabelManager: function (options) {
            var scope = options.scope || null;
            var language = options.language || null;

            this.main('views/admin/label-manager/index', {scope: scope, language: language});
        },

        actionTemplateManager: function (options) {
            var name = options.name || null;

            this.main('views/admin/template-manager/index', {name: name});
        },

        actionFieldManager: function (options) {
            var scope = options.scope || null;
            var field = options.field || null;

            this.main('views/admin/field-manager/index', {scope: scope, field: field});
        },

        actionEntityManager: function (options) {
            var scope = options.scope || null;

            this.main('views/admin/entity-manager/index', {scope: scope});
        },

        actionLinkManager: function (options) {
            var scope = options.scope || null;

            this.main('views/admin/link-manager/index', {scope: scope});
        },

        actionUpgrade: function (options) {
            this.main('views/admin/upgrade/index');
        },

        actionSystemRequirements: function (options) {
            this.main('views/admin/system-requirements/index');
        },

        getSettingsModel: function () {
            var model = this.getConfig().clone();
            model.defs = this.getConfig().defs;

            return model;
        },

        actionSettings: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/settings',
                    recordView: 'views/admin/settings'
                });
            }, this);
            model.fetch();
        },

        actionNotifications: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/notifications',
                    recordView: 'views/admin/notifications'
                });
            }, this);
            model.fetch();
        },

        actionOutboundEmails: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/outbound-emails',
                    recordView: 'views/admin/outbound-emails'
                });
            }, this);
            model.fetch();
        },

        actionInboundEmails: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/inbound-emails',
                    recordView: 'views/admin/inbound-emails'
                });
            }, this);
            model.fetch();
        },

        actionCurrency: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/currency',
                    recordView: 'views/admin/currency'
                });
            }, this);
            model.fetch();
        },

        actionAuthTokens: function () {
            this.collectionFactory.create('AuthToken', function (collection) {
                var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime());
                searchManager.loadStored();
                collection.where = searchManager.getWhere();
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

                this.main('views/admin/auth-token/list', {
                    scope: 'AuthToken',
                    collection: collection,
                    searchManager: searchManager
                });
            }, this);
        },

        actionAuthLog: function () {
            this.collectionFactory.create('AuthLogRecord', function (collection) {
                var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime());
                searchManager.loadStored();
                collection.where = searchManager.getWhere();
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

                this.main('views/admin/auth-log-record/list', {
                    scope: 'AuthLogRecord',
                    collection: collection,
                    searchManager: searchManager
                });
            }, this);
        },

        actionJobs: function () {
            this.collectionFactory.create('Job', function (collection) {
                var searchManager = new SearchManager(collection, 'list', this.getStorage(), this.getDateTime());
                searchManager.loadStored();
                collection.where = searchManager.getWhere();
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

                this.main('views/admin/job/list', {
                    scope: 'Job',
                    collection: collection,
                    searchManager: searchManager,
                });
            }, this);
        },

        actionUserInterface: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/user-interface',
                    recordView: 'views/admin/user-interface'
                });
            }, this);
            model.fetch();
        },

        actionAuthentication: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/authentication',
                    recordView: 'views/admin/authentication'
                });
            }, this);
            model.fetch();
        },

        actionJobsSettings: function () {
            var model = this.getSettingsModel();

            model.once('sync', function () {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'admin/settings/headers/jobs-settings',
                    recordView: 'views/admin/jobs-settings'
                });
            }, this);
            model.fetch();
        },

        actionIntegrations: function (options) {
            var integration = options.name || null;

            this.main('views/admin/integrations/index', {integration: integration});
        },

        actionExtensions: function (options) {
            this.main('views/admin/extensions/index');
        },

        rebuild: function (options) {
            if (this.rebuildRunning) return;
            this.rebuildRunning = true;

            var master = this.get('master');
            Espo.Ui.notify(master.translate('pleaseWait', 'messages'));

            Espo.Ajax.postRequest('Admin/rebuild')
                .then(function () {
                    var msg = master.translate('Rebuild has been done', 'labels', 'Admin');
                    Espo.Ui.success(msg);
                    this.rebuildRunning = false;
                }.bind(this))
                .fail(function () {
                    this.rebuildRunning = false;
                }.bind(this));
        },

        clearCache: function (options) {
            if (this.clearCacheRunning) return;
            this.clearCacheRunning = true;

            var master = this.get('master');
            Espo.Ui.notify(master.translate('pleaseWait', 'messages'));

            Espo.Ajax.postRequest('Admin/clearCache')
                .then(function () {
                    var msg = master.translate('Cache has been cleared', 'labels', 'Admin');
                    Espo.Ui.success(msg);
                    this.clearCacheRunning = false;
                }.bind(this))
                .fail(function () {
                    this.clearCacheRunning = false;
                }.bind(this));
        }
    });
});
