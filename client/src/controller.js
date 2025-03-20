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

/** @module controller */

import Exceptions from 'exceptions';
import {Events, View as BullView} from 'bullbone';
import {inject} from 'di';
import ModalBarProvider from 'helpers/site/modal-bar-provider';

/**
 * @callback module:controller~viewCallback
 * @param {module:view} view A view.
 */

/**
 * @callback module:controller~masterViewCallback
 * @param {module:views/site/master} view A master view.
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

        this.setMasterRendered(false);
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
     * @private
     * @type {ModalBarProvider}
     */
    @inject(ModalBarProvider)
    modalBarProvider

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
     * @param {string} key
     * @param {string} [name]
     * @return {string}
     * @private
     */
    _composeScrollKey(key, name) {
        name = name || this.name;

        return `scrollTop-${name}-${key}`;
    }

    /**
     * @param {string} key
     * @return {string}
     * @private
     */
    _composeMainViewKey(key) {
        return `mainView-${this.name}-${key}`;
    }

    /**
     * Get a stored main view.
     *
     * @param {string} key A key.
     * @returns {module:view|null}
     */
    getStoredMainView(key) {
        return this.get(this._composeMainViewKey(key));
    }

    /**
     * Has a stored main view.
     * @param {string} key
     * @returns {boolean}
     */
    hasStoredMainView(key) {
        return this.has(this._composeMainViewKey(key));
    }

    /**
     * Clear a stored main view.
     * @param {string} key
     */
    clearStoredMainView(key) {
        const view = this.getStoredMainView(key);

        if (view) {
            view.remove(true);
        }

        this.unset(this._composeScrollKey(key));
        this.unset(this._composeMainViewKey(key));
    }

    /**
     * Store a main view.
     *
     * @param {string} key A key.
     * @param {module:view} view A view.
     */
    storeMainView(key, view) {
        this.set(this._composeMainViewKey(key), view);

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

        const msg = action ?
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

        const method = 'action' + Espo.Utils.upperCaseFirst(action);

        if (!(method in this)) {
            throw new Exceptions.NotFound("Action '" + this.name + "#" + action + "' is not found");
        }

        const preMethod = 'before' + Espo.Utils.upperCaseFirst(action);
        const postMethod = 'after' + Espo.Utils.upperCaseFirst(action);

        if (preMethod in this) {
            this[preMethod].call(this, options || {});
        }

        this[method].call(this, options || {});

        if (postMethod in this) {
            this[postMethod].call(this, options || {});
        }
    }

    /**
     * Serve a master view. Render if not already rendered.
     *
     * @param {module:controller~masterViewCallback} callback A callback with a created master view.
     * @private
     */
    master(callback) {
        const entireView = this.getEntireView();

        if (entireView) {
            entireView.remove();

            this.setEntireView(null);
        }

        const masterView = this.getMasterView();

        if (masterView) {
            callback.call(this, masterView);

            return;
        }

        const viewName = this.masterView || 'views/site/master';

        this.viewFactory.create(viewName, {fullSelector: 'body'},
            async /** import('views/site/master').default */masterView => {
                this.setMasterView(masterView);

                if (this.isMasterRendered()) {
                    callback.call(this, masterView);

                    return;
                }

                this.modalBarProvider.set(masterView.collapsedModalBarView || null)

                await masterView.render();

                this.setMasterRendered(true);

                callback.call(this, masterView);
            });
    }

    /**
     * @private
     * @return {import('view').default|null}
     */
    getEntireView() {
        return this.get('entire');
    }

    /**
     * @private
     * @param {import('view').default|null} view
     */
    setEntireView(view) {
        this.set('entire', view);
    }

    /**
     * @private
     * @return {import('view').default|null}
     */
    getMasterView() {
        return this.get('master');
    }

    /**
     * @private
     * @param {import('view').default|null} view
     */
    setMasterView(view) {
        if (!view) {
            this.modalBarProvider.set(null);
        }

        this.set('master', view);
    }

    /**
     * @private
     * @param {boolean} value
     */
    setMasterRendered(value) {
        this.set('masterRendered', value);
    }

    /**
     * @private
     * @return {boolean}
     */
    isMasterRendered() {
        return !!this.get('masterRendered');
    }

    /**
     * @param {import('views/site/master').default} masterView
     * @private
     */
    _unchainMainView(masterView) {
        if (
            !masterView.currentViewKey /*||
            !this.hasStoredMainView(masterView.currentViewKey)*/
        ) {
            return;
        }

        const currentMainView = masterView.getMainView();

        if (!currentMainView) {
            return;
        }

        currentMainView.propagateEvent('remove', {ignoreCleaning: true});
        masterView.unchainView('main');
    }

    /**
     * @typedef {Object} module:controller~mainParams
     * @property {boolean} [useStored] Use a stored view if available.
     * @property {string} [key] A stored view key.
     */

    /**
     * Create a main view in the master container and render it.
     *
     * @param {string|module:view} [view] A view name or view instance.
     * @param {Object.<string, *>} [options] Options for a view.
     * @param {module:controller~viewCallback} [callback] A callback with a created view.
     * @param {module:controller~mainParams} [params] Parameters.
     */
    main(view, options, callback, params = {}) {
        const dto = {
            isCanceled: false,
            key: params.key,
            useStored: params.useStored,
            callback: callback,
        };

        const selector = '#main';

        const useStored = params.useStored || false;
        const key = params.key;

        this.listenToOnce(this.baseController, 'action', () => dto.isCanceled = true);

        const mainView = view && typeof view === 'object' ?
            view : undefined;

        const viewName = !mainView ?
            (view || 'views/base') : undefined;

        this.master(async masterView => {
            if (dto.isCanceled) {
                return;
            }

            options = options || {};
            options.fullSelector = selector;

            if (useStored && this.hasStoredMainView(key)) {
                const mainView = this.getStoredMainView(key);

                let isActual = true;

                if (
                    mainView &&
                    ('isActualForReuse' in mainView) &&
                    typeof mainView.isActualForReuse === 'function'
                ) {
                    isActual = mainView.isActualForReuse();
                }

                const lastUrl = (mainView && 'lastUrl' in mainView) ? mainView.lastUrl : null;

                if (
                    isActual &&
                    (!lastUrl || lastUrl === this.getRouter().getCurrentUrl())
                ) {
                    this._processMain(mainView, masterView, dto);

                    if (
                        'setupReuse' in mainView &&
                        typeof mainView.setupReuse === 'function'
                    ) {
                        mainView.setupReuse(options.params || {});
                    }

                    return;
                }

                this.clearStoredMainView(key);
            }

            if (mainView) {
                this._unchainMainView(masterView);

                await masterView.assignView('main', mainView, selector)

                dto.isSet = true;

                this._processMain(view, masterView, dto);

                return;
            }

            this.viewFactory.create(viewName, options, view => {
                this._processMain(view, masterView, dto);
            });
        });
    }

    /**
     * @param {import('view').default} mainView
     * @param {import('views/site/master').default} masterView
     * @param {{
     *     isCanceled: boolean,
     *     key?: string,
     *     useStored?: boolean,
     *     callback?: module:controller~viewCallback,
     *     isSet?: boolean,
     * }} dto Data.
     * @private
     */
    _processMain(mainView, masterView, dto) {
        if (dto.isCanceled) {
            return;
        }

        const key = dto.key;

        if (key) {
            this.storeMainView(key, mainView);
        }

        const onAction = () => {
            mainView.cancelRender();
            dto.isCanceled = true;
        };

        mainView.listenToOnce(this.baseController, 'action', onAction);

        if (masterView.currentViewKey) {
            const scrollKey = this._composeScrollKey(masterView.currentViewKey, masterView.currentName);

            this.set(scrollKey, window.scrollY);

            if (!dto.isSet) {
                this._unchainMainView(masterView);
            }
        }

        masterView.currentViewKey = key;
        masterView.currentName = this.name;

        if (!dto.isSet) {
            masterView.setView('main', mainView);
        }

        const afterRender = () => {
            setTimeout(() => mainView.stopListening(this.baseController, 'action', onAction), 500);

            mainView.updatePageTitle();

            const scrollKey = this._composeScrollKey(key);

            if (dto.useStored && this.has(scrollKey)) {
                window.scrollTo({top: this.get(scrollKey)})

                return;
            }

            window.scrollTo({top: 0})
        };

        if (dto.callback) {
            this.listenToOnce(mainView, 'after:render', afterRender);

            dto.callback.call(this, mainView);

            return;
        }

        mainView.render()
            .then(afterRender);
    }

    /**
     * Show a loading notify-message.
     */
    showLoadingNotification() {
        const master = this.getMasterView();

        if (!master) {
            return;
        }

        master.showLoadingNotification();
    }

    /**
     * Hide a loading notify-message.
     */
    hideLoadingNotification() {
        const master = this.getMasterView();

        if (!master) {
            return;
        }

        master.hideLoadingNotification();
    }

    /**
     * Create a view in the BODY element. Use for rendering separate pages without the default navbar and footer.
     * If a callback is not passed, the view will be automatically rendered.
     *
     * @param {string|module:view} view A view name or view instance.
     * @param {Object.<string, *>} [options] Options for a view.
     * @param {module:controller~viewCallback} [callback] A callback with a created view.
     */
    entire(view, options, callback) {
        const masterView = this.getMasterView();

        if (masterView) {
            masterView.remove();
        }

        this.setMasterView(null);
        this.setMasterRendered(false);

        if (typeof view === 'object') {
            view.setElement('body');

            this.viewFactory.prepare(view, () => {
                if (!callback) {
                    view.render();

                    return;
                }

                callback(view);
            });

            return;
        }

        options = options || {};
        options.fullSelector = 'body';

        this.viewFactory.create(view, options, view => {
            this.setEntireView(view);

            if (!callback) {
                view.render();

                return;
            }

            callback(view);
        });
    }
}

Object.assign(Controller.prototype, Events);

/** For backward compatibility. */
Controller.extend = BullView.extend;

export default Controller;
