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

/** @module controllers/base */

import Controller from 'controller';
import BaseView from 'views/base';

/**
 * A base controller.
 */
class BaseController extends Controller {

    constructor(params, injections) {
        super(params, injections);

        this.on('logout', () => this._clearAllStoredMainViews());
    }

    /**
     * @private
     */
    _clearAllStoredMainViews() {
        for (const name in this.params) {
            if (!name.startsWith('mainView-')) {
                continue;
            }

            const [, scope, key] = name.split('-', 3);
            const actualKey = `mainView-${scope}-${key}`;

            const view = /** @type {module:view} */this.get(actualKey);

            if (view) {
                view.remove(true);
            }

            this.unset(actualKey);
        }
    }

    /**
     * Clear a stored main view.
     *
     * @param {string} scope
     */
    clearScopeStoredMainView(scope) {
        for (const key in this.params) {
            if (!key.startsWith(`mainView-${scope}-`)) {
                continue;
            }

            const view = /** @type {module:view} */this.get(key);

            if (view) {
                view.remove(true);
            }

            this.unset(key);
        }
    }

    /**
     * Log in.
     *
     * @param {{
     *     anotherUser?: string,
     *     username?: string,
     * }} [options]
     */
    login(options) {
        const viewName = this.getConfig().get('loginView') || 'views/login';

        const anotherUser = (options || {}).anotherUser;
        const prefilledUsername = (options || {}).username;

        const viewOptions = {
            anotherUser: anotherUser,
            prefilledUsername: prefilledUsername,
        };

        this.entire(viewName, viewOptions, loginView => {
            loginView.render();

            loginView.on('login', (userName, data) => {
                this.trigger('login', this.normalizeLoginData(userName, data));
            });

            loginView.once('redirect', (viewName, headers, userName, password, data) => {
                loginView.remove();

                this.entire(viewName, {
                    loginData: data,
                    userName: userName,
                    password: password,
                    anotherUser: anotherUser,
                    headers: headers,
                }, secondStepView => {
                    secondStepView.render();

                    secondStepView.once('login', (userName, data) => {
                        this.trigger('login', this.normalizeLoginData(userName, data));
                    });

                    secondStepView.once('back', () => {
                        secondStepView.remove();

                        this.login();
                    });
                });
            });
        });
    }

    /** @private */
    normalizeLoginData(userName, data) {
        return {
            auth: {
                userName: userName,
                token: data.token,
                anotherUser: data.anotherUser,
            },
            user: data.user,
            preferences: data.preferences,
            acl: data.acl,
            settings: data.settings,
            appParams: data.appParams,
            language: data.language,
        };
    }

    /**
     * Log out.
     */
    logout() {
        const title = this.getConfig().get('applicationName') || 'EspoCRM';

        $('head title').text(title);

        this.trigger('logout');
    }

    /**
     * Clear cache.
     */
    clearCache() {
        this.entire('views/clear-cache', {
            cache: this.getCache(),
        }, view => {
            view.render();
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionLogin() {
        this.login();
    }

    // noinspection JSUnusedGlobalSymbols
    actionLogout() {
        this.logout();
    }

    // noinspection JSUnusedGlobalSymbols
    actionLogoutWait() {
        this.entire('views/base', {template: 'logout-wait'}, view => {
            view.render()
                .then(() => Espo.Ui.notifyWait())
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionClearCache() {
        this.clearCache();
    }

    /**
     * Error Not Found.
     */
    error404() {
        const view = new BaseView({template: 'errors/404'});

        this.entire(view);
    }

    /**
     * Error Forbidden.
     */
    error403() {
        const view = new BaseView({template: 'errors/403'});

        this.entire(view);
    }

    // noinspection JSUnusedGlobalSymbols
    actionError404() {
        this.error404();
    }

    // noinspection JSUnusedGlobalSymbols
    actionError403() {
        this.error403();
    }
}

export default BaseController;
