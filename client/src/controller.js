/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module controller */

import Exceptions from 'exceptions';
import {Events, View as BullView} from 'bullbone';

/**
 * @callback module:controller~viewCallback
 * @param {module:view} view A view.
 */

/**
 * A controller. To be extended.
 *
 * @mixes Bull.Events
 */
class Controller {

    /**
     * @internal
     * @param {Object.<string, *>} params
     * @param {Object} injections
     */
    constructor(params, injections) {
        this.params = params || {};

        /** @type {module:controllers/base} */
        this.baseController = injections.baseController;
        /** @type {Bull.Factory} */
        this.viewFactory = injections.viewFactory;
        /** @type {module:model} */
        this.modelFactory = injections.modelFactory;
        /** @type {module:collection-factory} */
        this.collectionFactory = injections.collectionFactory;

        this._settings = injections.settings || null;
        this._user = injections.user || null;
        this._preferences = injections.preferences || null;
        this._acl = injections.acl || null;
        this._cache = injections.cache || null;
        this._router = injections.router || null;
        this._storage = injections.storage || null;
        this._metadata = injections.metadata || null;
        this._dateTime = injections.dateTime || null;
        this._broadcastChannel = injections.broadcastChannel || null;

        if (!this.baseController) {
            this.on('logout', () => this.clearAllStoredMainViews());
        }

        this.set('masterRendered', false);
    }

    /**
     * A default action.
     *
     * @type {string}
     */
    defaultAction = 'index'

    /**
     * A name.
     *
     * @type {string|null}
     */
    name = null

    /**
     * Params.
     *
     * @type {Object}
     * @private
     */
    params = null

    /**
     * A view factory.
     *
     * @type {Bull.Factory}
     * @protected
     */
    viewFactory = null

    /**
     * A model factory.
     *
     * @type {module:model-factory}
     * @protected
     */
    modelFactory = null

    /**
     * A body view.
     *
     * @public
     * @type {string|null}
     */
    masterView = null

    /**
     * Set the router.
     *
     * @internal
     * @param {module:router} router
     */
    setRouter(router) {
        this._router = router;

        this.trigger('router-set', router);
    }

    /**
     * @protected
     * @returns {module:models/settings}
     */
    getConfig() {
        return this._settings;
    }

    /**
     * @protected
     * @returns {module:models/user}
     */
    getUser() {
        return this._user;
    }

    /**
     * @protected
     * @returns {module:models/preferences}
     */
    getPreferences() {
        return this._preferences;
    }

    /**
     * @protected
     * @returns {module:acl-manager}
     */
    getAcl() {
        return this._acl;
    }

    /**
     * @protected
     * @returns {module:cache}
     */
    getCache() {
        return this._cache;
    }

    /**
     * @protected
     * @returns {module:router}
     */
    getRouter() {
        return this._router;
    }

    /**
     * @protected
     * @returns {module:storage}
     */
    getStorage() {
        return this._storage;
    }

    /**
     * @protected
     * @returns {module:metadata}
     */
    getMetadata() {
        return this._metadata;
    }

    /**
     * @protected
     * @returns {module:date-time}
     */
    getDateTime() {
        return this._dateTime;
    }

    /**
     * Get a parameter of all controllers.
     *
     * @param {string} key A key.
     * @return {*} Null if a key doesn't exist.
     */
    get(key) {
        if (key in this.params) {
            return this.params[key];
        }

        return null;
    }

    /**
     * Set a parameter for all controllers.
     *
     * @param {string} key A name of a view.
     * @param {*} value
     */
    set(key, value) {
        this.params[key] = value;
    }

    /**
     * Unset a parameter.
     *
     * @param {string} key A key.
     */
    unset(key) {
        delete this.params[key];
    }

    /**
     * Has a parameter.
     *
     * @param {string} key A key.
     * @returns {boolean}
     */
    has(key) {
        return key in this.params;
    }

    /**
     * Get a stored main view.
     *
     * @param {string} key A key.
     * @returns {module:view|null}
     */
    getStoredMainView(key) {
        return this.get('storedMainView-' + key);
    }

    /**
     * Has a stored main view.
     * @param {string} key
     * @returns {boolean}
     */
    hasStoredMainView(key) {
        return this.has('storedMainView-' + key);
    }

    /**
     * Clear a stored main view.
     * @param {string} key
     */
    clearStoredMainView(key) {
        let view = this.getStoredMainView(key);

        if (view) {
            view.remove(true);
        }

        this.unset('storedMainView-' + key);
    }

    /**
     * Store a main view.
     *
     * @param {string} key A key.
     * @param {module:view} view A view.
     */
    storeMainView(key, view) {
        this.set('storedMainView-' + key, view);

        this.listenTo(view, 'remove', (o) => {
            o = o || {};

            if (o.ignoreCleaning) {
                return;
            }

            this.stopListening(view, 'remove');

            this.clearStoredMainView(key);
        });
    }

    /**
     * Clear all stored main views.
     */
    clearAllStoredMainViews() {
        for (let k in this.params) {
            if (k.indexOf('storedMainView-') !== 0) {
                continue;
            }

            let key = k.slice(15);

            this.clearStoredMainView(key);
        }
    }

    /**
     * Check access to an action.
     *
     * @param {string} action An action.
     * @returns {boolean}
     */
    checkAccess(action) {
        return true;
    }

    /**
     * Process access check to the controller.
     */
    handleAccessGlobal() {
        if (!this.checkAccessGlobal()) {
            throw new Exceptions.AccessDenied("Denied access to '" + this.name + "'");
        }
    }

    /**
     * Check access to the controller.
     *
     * @returns {boolean}
     */
    checkAccessGlobal() {
        return true;
    }

    /**
     * Check access to an action. Throwing an exception.
     *
     * @param {string} action An action.
     */
    handleCheckAccess(action) {
        if (this.checkAccess(action)) {
            return;
        }

        let msg = action ?
            "Denied access to action '" + this.name + "#" + action + "'" :
            "Denied access to scope '" + this.name + "'";

        throw new Exceptions.AccessDenied(msg);
    }

    /**
     * Process an action.
     *
     * @param {string} action
     * @param {Object} options
     */
    doAction(action, options) {
        this.handleAccessGlobal();

        action = action || this.defaultAction;

        let method = 'action' + Espo.Utils.upperCaseFirst(action);

        if (!(method in this)) {
            throw new Exceptions.NotFound("Action '" + this.name + "#" + action + "' is not found");
        }

        let preMethod = 'before' + Espo.Utils.upperCaseFirst(action);
        let postMethod = 'after' + Espo.Utils.upperCaseFirst(action);

        if (preMethod in this) {
            this[preMethod].call(this, options || {});
        }

        this[method].call(this, options || {});

        if (postMethod in this) {
            this[postMethod].call(this, options || {});
        }
    }

    /**
     * Create a master view, render if not already rendered.
     *
     * @param {module:controller~viewCallback} callback A callback with a created master view.
     */
    master(callback) {
        let entire = this.get('entire');

        if (entire) {
            entire.remove();

            this.set('entire', null);
        }

        let master = this.get('master');

        if (master) {
            callback.call(this, master);

            return;
        }

        let masterView = this.masterView || 'views/site/master';

        this.viewFactory.create(masterView, {el: 'body'}, (master) => {
            this.set('master', master);

            if (!this.get('masterRendered')) {
                master.render(() => {
                    this.set('masterRendered', true);

                    callback.call(this, master);
                });

                return;
            }

            callback.call(this, master);
        });
    }

    /**
     * Create a main view in the master.
     *
     * @param {string} [view] A view name.
     * @param {Object} [options] Options for view.
     * @param {module:controller~viewCallback} [callback] A callback with a created view.
     * @param {boolean} [useStored] Use a stored view if available.
     * @param {string} [storedKey] A stored view key.
     */
    main(view, options, callback, useStored, storedKey) {
        let isCanceled = false;
        let isRendered = false;

        this.listenToOnce(this.baseController, 'action', () => {
            isCanceled = true;
        });

        view = view || 'views/base';

        this.master(master => {
            if (isCanceled) {
                return;
            }

            options = options || {};
            options.fullSelector = '#main';

            let process = main => {
                if (isCanceled) {
                    return;
                }

                if (storedKey) {
                    this.storeMainView(storedKey, main);
                }

                const onAction = () => {
                    main.cancelRender();
                    isCanceled = true;
                };

                main.listenToOnce(this.baseController, 'action', onAction);

                if (master.currentViewKey) {
                    this.set('storedScrollTop-' + master.currentViewKey, $(window).scrollTop());

                    if (this.hasStoredMainView(master.currentViewKey)) {
                        let mainView = master.getView('main');

                        if (mainView) {
                            mainView.propagateEvent('remove', {ignoreCleaning: true});
                        }

                        master.unchainView('main');
                    }
                }

                master.currentViewKey = storedKey;
                master.setView('main', main);

                let afterRender = () => {
                    setTimeout(() => main.stopListening(this.baseController, 'action', onAction), 500);

                    isRendered = true;

                    main.updatePageTitle();

                    if (useStored && this.has('storedScrollTop-' + storedKey)) {
                        $(window).scrollTop(this.get('storedScrollTop-' + storedKey));

                        return;
                    }

                    $(window).scrollTop(0);
                };

                if (callback) {
                    this.listenToOnce(main, 'after:render', afterRender);

                    callback.call(this, main);

                    return;
                }

                main.render()
                    .then(afterRender);
            };

            if (useStored && this.hasStoredMainView(storedKey)) {
                let main = this.getStoredMainView(storedKey);

                let isActual = true;

                if (
                    main &&
                    ('isActualForReuse' in main) &&
                    typeof main.isActualForReuse === 'function'
                ) {
                    isActual = main.isActualForReuse();
                }

                let lastUrl = (main && 'lastUrl' in main) ? main.lastUrl : null;

                if (
                    isActual &&
                    (!lastUrl || lastUrl === this.getRouter().getCurrentUrl())
                ) {
                    process(main);

                    if (
                        'setupReuse' in main &&
                        typeof main.setupReuse === 'function'
                    ) {
                        main.setupReuse(options.params || {});
                    }

                    return;
                }

                this.clearStoredMainView(storedKey);
            }

            this.viewFactory.create(view, options, process);
        });
    }

    /**
     * Show a loading notify-message.
     */
    showLoadingNotification() {
        let master = this.get('master');

        if (!master) {
            return;
        }

        master.showLoadingNotification();
    }

    /**
     * Hide a loading notify-message.
     */
    hideLoadingNotification() {
        let master = this.get('master');

        if (!master) {
            return;
        }

        master.hideLoadingNotification();
    }

    /**
     * Create a view in the <body> element.
     *
     * @param {String} view A view name.
     * @param {Bull.View~Options} options Options for a view.
     * @param {module:controller~viewCallback} [callback] A callback with a created view.
     */
    entire(view, options, callback) {
        let master = this.get('master');

        if (master) {
            master.remove();
        }

        this.set('master', null);
        this.set('masterRendered', false);

        options = options || {};
        options.fullSelector = 'body';

        this.viewFactory.create(view, options, view => {
            this.set('entire', view);

            callback(view);
        });
    }
}

Object.assign(Controller.prototype, Events);

/** For backward compatibility. */
Controller.extend = BullView.extend;

export default Controller;
