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

import Controller from 'controller';
import SearchManager from 'search-manager';
import SettingsEditView from 'views/settings/edit';
import AdminIndexView from 'views/admin/index';
import {inject} from 'di';
import Language from 'language';

class AdminController extends Controller {

    /**
     * @private
     * @type {Language}
     */
    @inject(Language)
    language

    checkAccessGlobal() {
        if (this.getUser().isAdmin()) {
            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    actionPage(options) {
        const page = options.page;

        if (options.options) {
            options = {
                ...Espo.Utils.parseUrlOptionsParam(options.options),
                ...options,
            };

            delete options.options;
        }

        if (!page) {
            throw new Error();
        }

        const methodName = 'action' + Espo.Utils.upperCaseFirst(page);

        if (this[methodName]) {
            this[methodName](options);

            return;
        }

        const defs = this.getPageDefs(page);

        if (!defs) {
            throw new Espo.Exceptions.NotFound();
        }

        if (defs.view) {
            this.main(defs.view, options);

            return;
        }

        if (!defs.recordView) {
            throw new Espo.Exceptions.NotFound();
        }

        const model = this.getSettingsModel();

        model.fetch().then(() => {
            model.id = '1';

            const editView = new SettingsEditView({
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

            this.main(editView);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionIndex(options) {
        let isReturn = options.isReturn;
        const key = 'index';

        if (this.getRouter().backProcessed) {
            isReturn = true;
        }

        if (!isReturn && this.getStoredMainView(key)) {
            this.clearStoredMainView(key);
        }

        const view = new AdminIndexView();

        this.main(view, null, view => {
            view.render();

            this.listenTo(view, 'clear-cache', this.clearCache);
            this.listenTo(view, 'rebuild', this.rebuild);
        }, {
            useStored: isReturn,
            key: key,
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionUsers() {
        this.getRouter().dispatch('User', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionPortalUsers() {
        this.getRouter().dispatch('PortalUser', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionApiUsers() {
        this.getRouter().dispatch('ApiUser', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionTeams() {
        this.getRouter().dispatch('Team', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionRoles() {
        this.getRouter().dispatch('Role', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionPortalRoles() {
        this.getRouter().dispatch('PortalRole', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionPortals() {
        this.getRouter().dispatch('Portal', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionLeadCapture() {
        this.getRouter().dispatch('LeadCapture', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailFilters() {
        this.getRouter().dispatch('EmailFilter', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionGroupEmailFolders() {
        this.getRouter().dispatch('GroupEmailFolder', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailTemplates() {
        this.getRouter().dispatch('EmailTemplate', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionPdfTemplates() {
        this.getRouter().dispatch('Template', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionDashboardTemplates() {
        this.getRouter().dispatch('DashboardTemplate', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionWebhooks() {
        this.getRouter().dispatch('Webhook', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionLayoutSets() {
        this.getRouter().dispatch('LayoutSet', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionWorkingTimeCalendar() {
        this.getRouter().dispatch('WorkingTimeCalendar', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionAttachments() {
        this.getRouter().dispatch('Attachment', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionAuthenticationProviders() {
        this.getRouter().dispatch('AuthenticationProvider', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionAddressCountries() {
        this.getRouter().dispatch('AddressCountry', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailAddresses() {
        this.getRouter().dispatch('EmailAddress', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionPhoneNumbers() {
        this.getRouter().dispatch('PhoneNumber', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionPersonalEmailAccounts() {
        this.getRouter().dispatch('EmailAccount', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionGroupEmailAccounts() {
        this.getRouter().dispatch('InboundEmail', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionActionHistory() {
        this.getRouter().dispatch('ActionHistoryRecord', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionImport() {
        this.getRouter().dispatch('Import', 'index', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionLayouts(options) {
        const scope = options.scope || null;
        const type = options.type || null;
        const em = options.em || false;

        this.main('views/admin/layouts/index', {scope: scope, type: type, em: em});
    }

    // noinspection JSUnusedGlobalSymbols
    actionLabelManager(options) {
        const scope = options.scope || null;
        const language = options.language || null;

        this.main('views/admin/label-manager/index', {scope: scope, language: language});
    }

    // noinspection JSUnusedGlobalSymbols
    actionTemplateManager(options) {
        const name = options.name || null;

        this.main('views/admin/template-manager/index', {name: name});
    }

    // noinspection JSUnusedGlobalSymbols
    actionFieldManager(options) {
        const scope = options.scope || null;
        const field = options.field || null;

        this.main('views/admin/field-manager/index', {scope: scope, field: field});
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @param {Record} options
     */
    actionEntityManager(options) {
        const scope = options.scope || null;

        if (scope && options.edit) {
            this.main('views/admin/entity-manager/edit', {scope: scope});

            return;
        }

        if (options.create) {
            this.main('views/admin/entity-manager/edit');

            return;
        }

        if (scope && options.formula) {
            this.main('views/admin/entity-manager/formula', {scope: scope, type: options.type});

            return;
        }

        if (scope) {
            this.main('views/admin/entity-manager/scope', {scope: scope});

            return;
        }

        this.main('views/admin/entity-manager/index');
    }

    // noinspection JSUnusedGlobalSymbols
    actionLinkManager(options) {
        const scope = options.scope || null;

        this.main('views/admin/link-manager/index', {scope: scope});
    }

    // noinspection JSUnusedGlobalSymbols
    actionSystemRequirements() {
        this.main('views/admin/system-requirements/index');
    }

    /**
     * @returns {module:models/settings}
     */
    getSettingsModel() {
        const model = this.getConfig().clone();
        model.defs = this.getConfig().defs;

        this.listenTo(model, 'after:save', () => {
            this.getConfig().load();

            this._broadcastChannel.postMessage('update:config');
        });

        // noinspection JSValidateTypes
        return model;
    }

    // noinspection JSUnusedGlobalSymbols
    actionAuthTokens() {
        this.collectionFactory.create('AuthToken', collection => {
            const searchManager = new SearchManager(collection, {storageKey: 'list'});

            searchManager.loadStored();
            collection.where = searchManager.getWhere();
            collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

            this.main('views/admin/auth-token/list', {
                scope: 'AuthToken',
                collection: collection,
                searchManager: searchManager
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAuthLog() {
        this.collectionFactory.create('AuthLogRecord', collection => {
            const searchManager = new SearchManager(collection, {storageKey: 'list'});

            searchManager.loadStored();

            collection.where = searchManager.getWhere();
            collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

            this.main('views/admin/auth-log-record/list', {
                scope: 'AuthLogRecord',
                collection: collection,
                searchManager: searchManager
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAppSecrets() {
        this.getRouter().dispatch('AppSecret', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionOAuthProviders() {
        this.getRouter().dispatch('OAuthProvider', 'list', {fromAdmin: true});
    }

    // noinspection JSUnusedGlobalSymbols
    actionJobs() {
        this.collectionFactory.create('Job', collection => {
            const searchManager = new SearchManager(collection, {storageKey: 'list'});

            searchManager.loadStored();

            collection.where = searchManager.getWhere();
            collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

            this.main('views/admin/job/list', {
                scope: 'Job',
                collection: collection,
                searchManager: searchManager,
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAppLog() {
        this.collectionFactory.create('AppLogRecord', collection => {
            const searchManager = new SearchManager(collection, {storageKey: 'list'});

            searchManager.loadStored();

            collection.where = searchManager.getWhere();
            collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;

            this.main('views/list', {
                scope: 'AppLogRecord',
                collection: collection,
                searchManager: searchManager
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionIntegrations(options) {
        const integration = options.name || null;

        this.main('views/admin/integrations/index', {integration: integration});
    }

    // noinspection JSUnusedGlobalSymbols
    actionExtensions() {
        this.main('views/admin/extensions/index');
    }

    rebuild() {
        if (this.rebuildRunning) {
            return;
        }

        this.rebuildRunning = true;

        Espo.Ui.notify(this.language.translate('pleaseWait', 'messages'));

        Espo.Ajax
            .postRequest('Admin/rebuild')
            .then(() => {
                const msg = this.language.translate('Rebuild has been done', 'labels', 'Admin');

                Espo.Ui.success(msg);

                this.rebuildRunning = false;
            })
            .catch(() => {
                this.rebuildRunning = false;
            });
    }

    clearCache() {
        if (this.clearCacheRunning) {
            return;
        }

        this.clearCacheRunning = true;

        Espo.Ui.notify(this.language.translate('pleaseWait', 'messages'));

        Espo.Ajax.postRequest('Admin/clearCache')
            .then(() => {
                const msg = this.language.translate('Cache has been cleared', 'labels', 'Admin');

                Espo.Ui.success(msg);

                this.clearCacheRunning = false;
            })
            .catch(() => {
                this.clearCacheRunning = false;
            });
    }

    /**
     * @returns {Object|null}
     */
    getPageDefs(page) {
        const panelsDefs = this.getMetadata().get(['app', 'adminPanel']) || {};

        let resultDefs = null;

        for (const panelKey in panelsDefs) {
            const itemList = panelsDefs[panelKey].itemList || [];

            for (const defs of itemList) {
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
    }
}

export default AdminController;
