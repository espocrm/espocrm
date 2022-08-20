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

define('controller', [], function () {

    /**
     * @callback module:controller.Class~viewCallback
     * @param {module:view.Class} view A view.
     */

    /**
     * A controller. To be extended.
     *
     * @class
     * @name Class
     * @mixes Backbone.Events
     * @memberOf module:controller
     *
     * @param {Object} params
     * @param {Object} injections
     */
    var Controller = function (params, injections) {
        this.params = params || {};

        this.baseController = injections.baseController;
        this.viewFactory = injections.viewFactory;
        this.modelFactory = injections.modelFactory;
        this.collectionFactory = injections.collectionFactory;

        this.initialize();

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
    };

    _.extend(Controller.prototype, /** @lends module:controller.Class# */ {

        /**
         * A default action.
         *
         * @type {string}
         */
        defaultAction: 'index',

        /**
         * A name.
         *
         * @type {string|null}
         * @public
         */
        name: null,

        /**
         * Params.
         *
         * @type {Object}
         * @private
         */
        params: null,

        /**
         * A view factory.
         *
         * @type {Bull.Factory}
         * @protected
         */
        viewFactory: null,

        /**
         * A model factory.
         *
         * @type {module:model-factory.Class}
         * @protected
         */
        modelFactory: null,

        /**
         * A body view.
         *
         * @public
         * @type {string|null}
         */
        masterView: null,

        /**
         * Initialize.
         *
         * @protected
         */
        initialize: function () {},

        /**
         * Set the router.
         *
         * @internal
         * @param {module:router.Class} router
         */
        setRouter: function (router) {
            this._router = router;
        },

        /**
         * @protected
         * @returns {module:models/settings.Class}
         */
        getConfig: function () {
            return this._settings;
        },

        /**
         * @protected
         * @returns {module:models/user.Class}
         */
        getUser: function () {
            return this._user;
        },

        /**
         * @protected
         * @returns {module:models/preferences.Class}
         */
        getPreferences: function () {
            return this._preferences;
        },

        /**
         * @protected
         * @returns {module:acl-manager.Class}
         */
        getAcl: function () {
            return this._acl;
        },

        /**
         * @protected
         * @returns {module:cache.Class}
         */
        getCache: function () {
            return this._cache;
        },

        /**
         * @protected
         * @returns {module:router.Class}
         */
        getRouter: function () {
            return this._router;
        },

        /**
         * @protected
         * @returns {module:storage.Class}
         */
        getStorage: function () {
            return this._storage;
        },

        /**
         * @protected
         * @returns {module:metadata.Class}
         */
        getMetadata: function () {
            return this._metadata;
        },

        /**
         * @protected
         * @returns {module:date-time.Class}
         */
        getDateTime: function () {
            return this._dateTime;
        },

        /**
         * Get a parameter of all controllers.
         *
         * @param {string} key A key.
         * @return {*} Null if a key doesn't exist.
         */
        get: function (key) {
            if (key in this.params) {
                return this.params[key];
            }

            return null;
        },

        /**
         * Set a parameter for all controllers.
         *
         * @param {string} key A name of a view.
         * @param {*} value
         */
        set: function (key, value) {
            this.params[key] = value;
        },

        /**
         * Unset a parameter.
         *
         * @param {string} key A key.
         */
        unset: function (key) {
            delete this.params[key];
        },

        /**
         * Has a parameter.
         *
         * @param {string} key A key.
         * @returns {boolean}
         */
        has: function (key) {
            return key in this.params;
        },

        /**
         * Get a stored main view.
         *
         * @param {string} key A key.
         * @returns {module:view.Class|null}
         */
        getStoredMainView: function (key) {
            return this.get('storedMainView-' + key);
        },

        /**
         * Has a stored main view.
         * @param {string} key
         * @returns {boolean}
         */
        hasStoredMainView: function (key) {
            return this.has('storedMainView-' + key);
        },

        /**
         * Clear a stored main view.
         * @param {string} key
         */
        clearStoredMainView: function (key) {
            var view = this.getStoredMainView(key);

            if (view) {
                view.remove(true);
            }

            this.unset('storedMainView-' + key);
        },

        /**
         * Store a main view.
         *
         * @param {string} key A key.
         * @param {module:view.Class} view A view.
         */
        storeMainView: function (key, view) {
            this.set('storedMainView-' + key, view);

            this.listenTo(view, 'remove', (o) => {
                o = o || {};

                if (o.ignoreCleaning) {
                    return;
                }

                this.stopListening(view, 'remove');

                this.clearStoredMainView(key);
            });
        },

        /**
         * Clear all stored main views.
         */
        clearAllStoredMainViews: function () {
            for (let k in this.params) {
                if (k.indexOf('storedMainView-') !== 0) {
                    continue;
                }

                let key = k.substr(15);

                this.clearStoredMainView(key);
            }
        },

        /**
         * Check access to an action.
         *
         * @param {string} action An action.
         * @returns {boolean}
         */
        checkAccess: function (action) {
            return true;
        },

        /**
         * Process access check to the controller.
         */
        handleAccessGlobal: function () {
            if (!this.checkAccessGlobal()) {
                throw new Espo.Exceptions.AccessDenied("Denied access to '" + this.name + "'");
            }
        },

        /**
         * Check access to the controller.
         *
         * @returns {boolean}
         */
        checkAccessGlobal: function () {
            return true;
        },

        /**
         * Check access to an action. Throwing an exception.
         *
         * @param {string} action An action.
         */
        handleCheckAccess: function (action) {
            if (!this.checkAccess(action)) {
                let msg;

                if (action) {
                    msg = "Denied access to action '" + this.name + "#" + action + "'";
                }
                else {
                    msg = "Denied access to scope '" + this.name + "'";
                }

                throw new Espo.Exceptions.AccessDenied(msg);
            }
        },

        /**
         * Process an action.
         *
         * @param {string} action
         * @param {Object} options
         */
        doAction: function (action, options) {
            this.handleAccessGlobal();

            action = action || this.defaultAction;

            var method = 'action' + Espo.Utils.upperCaseFirst(action);

            if (!(method in this)) {
                throw new Espo.Exceptions.NotFound("Action '" + this.name + "#" + action + "' is not found");
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
        },

        /**
         * Create a master view, render if not already rendered.
         *
         * @param {module:controller.Class~viewCallback} callback A callback with a created master view.
         */
        master: function (callback) {
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
        },

        /**
         * Create a main view in the master.
         * @param {String} view A view name.
         * @param {Object} options Options for view.
         * @param {module:controller.Class~viewCallback} [callback] A callback with a created view.
         * @param {boolean} [useStored] Use a stored view if available.
         * @param {string} [storedKey] A stored view key.
         */
        main: function (view, options, callback, useStored, storedKey) {
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
                options.el = '#main';

                let process = main => {
                    if (isCanceled) {
                        return;
                    }

                    if (storedKey) {
                        this.storeMainView(storedKey, main);
                    }

                    main.listenToOnce(this.baseController, 'action', () => {
                        if (isRendered) {
                            return;
                        }

                        main.cancelRender();

                        isCanceled = true;
                    });

                    if (master.currentViewKey) {
                        this.set('storedScrollTop-' + master.currentViewKey, $(window).scrollTop());

                        if (this.hasStoredMainView(master.currentViewKey)) {
                            var mainView = master.getView('main');

                            if (mainView) {
                                mainView.propagateEvent('remove', {ignoreCleaning: true});
                            }

                            master.unchainView('main');
                        }
                    }

                    master.currentViewKey = storedKey;
                    master.setView('main', main);

                    let afterRender = () => {
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

                    if (main && typeof main.isActualForReuse === 'function') {
                        isActual = main.isActualForReuse();
                    }

                    if (
                        (!main.lastUrl || main.lastUrl === this.getRouter().getCurrentUrl()) &&
                        isActual
                    ) {
                        process(main);

                        if (main && typeof main.applyRoutingParams === 'function') {
                            main.applyRoutingParams(options.params || {});
                        }

                        return;
                    }

                    this.clearStoredMainView(storedKey);
                }

                this.viewFactory.create(view, options, process);
            });
        },

        /**
         * Show a loading notify-message.
         */
        showLoadingNotification: function () {
            let master = this.get('master');

            if (master) {
                master.showLoadingNotification();
            }
        },

        /**
         * Hide a loading notify-message.
         */
        hideLoadingNotification: function () {
            let master = this.get('master');

            if (master) {
                master.hideLoadingNotification();
            }
        },

        /**
         * Create a view in the <body> element.
         *
         * @param {String} view A view name.
         * @param {Object} options Options for a view.
         * @param {module:controller.Class~viewCallback} [callback] A callback with a created view.
         */
        entire: function (view, options, callback) {
            let master = this.get('master');

            if (master) {
                master.remove();
            }

            this.set('master', null);
            this.set('masterRendered', false);

            options = options || {};
            options.el = 'body';

            this.viewFactory.create(view, options, view => {
                this.set('entire', view);

                callback(view);
            });
        }

    }, Backbone.Events);

    Controller.extend = Backbone.Router.extend;

    return Controller;
});
