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

define('controllers/admin', ['controller', 'search-manager', 'lib!underscore'], function (Dep, SearchManager, _) {

    return Dep.extend({

        checkAccessGlobal: function () {
            if (this.getUser().isAdmin()) {
                return true;
            }

            return false;
        },

        actionPage: function (options) {
            let page = options.page;

            if (options.options) {
                options = _.extend(
                    Espo.Utils.parseUrlOptionsParam(options.options),
                    options
                );

                delete options.options;
            }

            if (!page) {
                throw new Error();
            }

            let methodName = 'action' + Espo.Utils.upperCaseFirst(page);

            if (this[methodName]) {
                this[methodName](options);

                return;
            }

            let defs = this.getPageDefs(page);

            if (!defs) {
                throw new Espo.Exceptions.NotFound();
            }

            if (defs.view) {
                this.main(defs.view, options);

                return;
            }

            if (defs.recordView) {
                let model = this.getSettingsModel();

                model.fetch().then(() => {
                    model.id = '1';

                    this.main('views/settings/edit', {
                        model: model,
                        headerTemplate: 'admin/settings/headers/page',
                        recordView: defs.recordView,
                        page: page,
                        label: defs.label,
                        optionsToPass: [
                            'page',
                            'label',
                        ],
                    });
                });

                return;
            }

            throw new Espo.Exceptions.NotFound();
        },

        actionIndex: function (options) {
            var isReturn = options.isReturn;
            var key = this.name + 'Index';

            if (this.getRouter().backProcessed) {
                isReturn = true;
            }

            if (!isReturn && this.getStoredMainView(key)) {
                this.clearStoredMainView(key);
            }

            this.main('views/admin/index', null, view => {
                view.render();

                this.listenTo(view, 'clear-cache', this.clearCache);
                this.listenTo(view, 'rebuild', this.rebuild);
            }, isReturn, key);
        },

        actionLayouts: function (options) {
            var scope = options.scope || null;
            var type = options.type || null;
            var em = options.em || false;

            this.main('views/admin/layouts/index', {scope: scope, type: type, em: em});
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

            if (scope) {
                this.main('views/admin/entity-manager/scope', {scope: scope});

                return;
            }

            this.main('views/admin/entity-manager/index');
        },

        actionLinkManager: function (options) {
            var scope = options.scope || null;

            this.main('views/admin/link-manager/index', {scope: scope});
        },

        actionSystemRequirements: function () {
            this.main('views/admin/system-requirements/index');
        },

        getSettingsModel: function () {
            let model = this.getConfig().clone();
            model.defs = this.getConfig().defs;

            this.listenTo(model, 'after:save', () => {
                this.getConfig().load();

                this._broadcastChannel.postMessage('update:config');
            });

            return model;
        },

        actionAuthTokens: function () {
            this.collectionFactory.create('AuthToken', collection => {

                var searchManager = new SearchManager(
                    collection,
                    'list',
                    this.getStorage(),
                    this.getDateTime()
                );

                searchManager.loadStored();
                collection.where = searchManager.getWhere();
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

                this.main('views/admin/auth-token/list', {
                    scope: 'AuthToken',
                    collection: collection,
                    searchManager: searchManager
                });
            });
        },

        actionAuthLog: function () {
            this.collectionFactory.create('AuthLogRecord', collection => {
                var searchManager = new SearchManager(
                    collection,
                    'list',
                    this.getStorage(),
                    this.getDateTime()
                );

                searchManager.loadStored();

                collection.where = searchManager.getWhere();
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

                this.main('views/admin/auth-log-record/list', {
                    scope: 'AuthLogRecord',
                    collection: collection,
                    searchManager: searchManager
                });
            });
        },

        actionJobs: function () {
            this.collectionFactory.create('Job', collection => {
                var searchManager = new SearchManager(
                    collection,
                    'list',
                    this.getStorage(),
                    this.getDateTime()
                );

                searchManager.loadStored();

                collection.where = searchManager.getWhere();
                collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

                this.main('views/admin/job/list', {
                    scope: 'Job',
                    collection: collection,
                    searchManager: searchManager,
                });
            });
        },

        actionIntegrations: function (options) {
            var integration = options.name || null;

            this.main('views/admin/integrations/index', {integration: integration});
        },

        actionExtensions: function () {
            this.main('views/admin/extensions/index');
        },

        rebuild: function () {
            if (this.rebuildRunning) {
                return;
            }

            this.rebuildRunning = true;

            var master = this.get('master');

            Espo.Ui.notify(master.translate('pleaseWait', 'messages'));

            Espo.Ajax
                .postRequest('Admin/rebuild')
                .then(() => {
                    let msg = master.translate('Rebuild has been done', 'labels', 'Admin');

                    Espo.Ui.success(msg);

                    this.rebuildRunning = false;
                })
                .catch(() => {
                    this.rebuildRunning = false;
                });
        },

        clearCache: function () {
            if (this.clearCacheRunning) {
                return;
            }

            this.clearCacheRunning = true;

            var master = this.get('master');
            Espo.Ui.notify(master.translate('pleaseWait', 'messages'));

            Espo.Ajax.postRequest('Admin/clearCache')
                .then(() => {
                    let msg = master.translate('Cache has been cleared', 'labels', 'Admin');

                    Espo.Ui.success(msg);

                    this.clearCacheRunning = false;
                })
                .catch(() => {
                    this.clearCacheRunning = false;
                });
        },

        getPageDefs: function (page) {
            let panelsDefs = this.getMetadata().get(['app', 'adminPanel']) || {};

            let resultDefs = null;

            for (let panelKey in panelsDefs) {
                let itemList = panelsDefs[panelKey].itemList || [];

                for (let defs of itemList) {
                    if (defs.url === '#Admin/' + page) {
                        resultDefs = defs;

                        break;
                    }
                }

                if (resultDefs) {
                    break;
                }
            }

            return resultDefs;
        },
    });
});
