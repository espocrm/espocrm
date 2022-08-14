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

define(
    'app',
[
    'lib!espo',
    'lib!jquery',
    'lib!backbone',
    'lib!underscore',
    'lib!bullbone',
    'ui',
    'utils',
    'acl-manager',
    'cache',
    'storage',
    'models/settings',
    'language',
    'metadata',
    'field-manager',
    'models/user',
    'models/preferences',
    'model-factory',
    'collection-factory',
    'pre-loader',
    'controllers/base',
    'router',
    'date-time',
    'layout-manager',
    'theme-manager',
    'session-storage',
    'view-helper',
    'web-socket-manager',
    'ajax',
    'number',
    'page-title',
    'broadcast-channel',
    'exceptions'
],
function (
    /** Espo */Espo,
    /** $ */$,
    /** Backbone */Backbone,
    /** _ */_,
    /** Bull */Bull,
    Ui,
    Utils,
    /** typeof module:acl-manager.Class */AclManager,
    /** typeof module:cache.Class */Cache,
    /** typeof module:storage.Class */Storage,
    /** typeof module:models/settings.Class */Settings,
    /** typeof module:language.Class */Language,
    /** typeof module:metadata.Class */Metadata,
    /** typeof module:field-manager.Class */FieldManager,
    /** typeof module:models/user.Class */User,
    /** typeof module:models/preferences.Class */Preferences,
    /** typeof module:model-factory.Class */ModelFactory,
    /** typeof module:collection-factory.Class */CollectionFactory,
    /** typeof module:pre-loader.Class */PreLoader,
    /** typeof module:controllers/base.Class */BaseController,
    /** typeof module:router.Class */Router,
    /** typeof module:date-time.Class */DateTime,
    /** typeof module:layout-manager.Class */LayoutManager,
    /** typeof module:theme-manager.Class */ThemeManager,
    /** typeof module:session-storage.Class */SessionStorage,
    /** typeof module:view-helper.Class */ViewHelper,
    /** typeof module:web-socket-manager.Class */WebSocketManager,
    Ajax,
    /** typeof module:number.Class */NumberUtil,
    /** typeof module:page-title.Class */PageTitle,
    /** typeof module:broadcast-channel.Class */BroadcastChannel,
    Exceptions
) {
    /**
     * A main application class.
     *
     * @class
     * @name Class
     * @memberOf module:app
     * @param {module:app.Class~Options} options Options.
     * @param {module:app.Class~callback} callback A callback.
     */
    let App = function (options, callback) {
        options = options || {};

        /**
         * An application ID.
         *
         * @private
         * @type {string}
         */
        this.id = options.id || 'espocrm-application-id';

        /**
         * Use cache.
         *
         * @private
         * @type {boolean}
         */
        this.useCache = options.useCache || this.useCache;

        this.apiUrl = options.apiUrl || this.apiUrl;

        /**
         * A base path.
         *
         * @type {string}
         */
        this.basePath = options.basePath || '';

        /**
         * A default ajax request timeout.
         *
         * @private
         * @type {Number}
         */
        this.ajaxTimeout = options.ajaxTimeout || 0;

        /**
         * A list of internal modules.
         *
         * @private
         * @type {string[]}
         */
        this.internalModuleList = options.internalModuleList || [];

        this.initCache(options)
            .then(() => this.init(options, callback));

        this.initDomEventListeners();
    };

    /**
     * @callback module:app.Class~callback
     *
     * @param {module:app.Class} app A created application instance.
     */

    /**
     * Application options.
     *
     * @typedef {Object} module:app.Class~Options
     *
     * @property {string} [id] An application ID.
     * @property {string} [basePath] A base path.
     * @property {boolean} [useCache] Use cache.
     * @property {string} [apiUrl] An API URL.
     * @property {Number} [ajaxTimeout] A default ajax request timeout.
     * @property {string} [internalModuleList] A list of internal modules.
     *   Internal modules located in the `client/modules` directory.
     * @property {Number|null} [cacheTimestamp] A cache timestamp.
     */

    _.extend(App.prototype, /** @lends module:app.Class# */{

        /**
         * @private
         * @type {boolean}
         */
        useCache: false,

        /**
         * @private
         * @type {module:models/user.Class}
         */
        user: null,

        /**
         * @private
         * @type {module:models/preferences.Class}
         */
        preferences: null,

        /**
         * @private
         * @type {module:models/settings.Class}
         */
        settings: null,

        /**
         * @private
         * @type {module:metadata.Class}
         */
        metadata: null,

        /**
         * @private
         * @type {module:language.Class}
         */
        language: null,

        /**
         * @private
         * @type {module:field-manager.Class}
         */
        fieldManager: null,

        /**
         * @private
         * @type {module:cache.Class|null}
         */
        cache: null,

        /**
         * @private
         * @type {module:storage.Class|null}
         */
        storage: null,

        /**
         * @private
         */
        loader: null,

        /**
         * An API URL.
         *
         * @private
         */
        apiUrl: 'api/v1',

        /**
         * An auth credentials string.
         *
         * @private
         * @type {string|null}
         */
        auth: null,

        /**
         * A base controller.
         *
         * @private
         * @type {module:controllers/base.Class}
         */
        baseController: null,

        /**
         * @private
         */
        controllers: null,

        /**
         * @private
         * @type {module:router.Class}
         */
        router: null,

        /**
         * @private
         * @type {module:model-factory.Class}
         */
        modelFactory: null,

        /**
         * @private
         * @type {module:collection-factory.Class}
         */
        collectionFactory: null,

        /**
         * A view factory.
         *
         * @private
         * @type {Bull.Factory}
         */
        viewFactory: null,

        /**
         * @type {Function}
         * @private
         */
        viewLoader: null,

        /**
         * @private
         * @type {module:view-helper.Class}
         */
        viewHelper: null,

        /**
         * A body view.
         *
         * @protected
         * @type {string}
         */
        masterView: 'views/site/master',

        /**
         * @private
         * @type {Cache|null}
         */
        responseCache: null,

        /**
         * @private
         * @type {module:broadcast-channel.Class|null}
         */
        broadcastChannel: null,

        /**
         * @private
         * @type {module:date-time.Class|null}
         */
        dateTime: null,

        /**
         * @private
         * @type {module:number.Class|null}
         */
        numberUtil: null,

        /**
         * @private
         */
        initCache: function (options) {
            let cacheTimestamp = options.cacheTimestamp || null;
            let storedCacheTimestamp = null;

            if (this.useCache) {
                this.cache = new Cache(cacheTimestamp);

                storedCacheTimestamp = this.cache.getCacheTimestamp();

                if (cacheTimestamp) {
                    this.cache.handleActuality(cacheTimestamp);
                }
                else {
                    this.cache.storeTimestamp();
                }
            }

            let handleActuality = () => {
                if (
                    !cacheTimestamp ||
                    !storedCacheTimestamp ||
                    cacheTimestamp !== storedCacheTimestamp
                ) {
                    return caches.delete('espo');
                }

                return new Promise(resolve => resolve());
            };

            return new Promise(resolve => {
                if (!this.useCache) {
                    resolve();
                }

                if (!window.caches) {
                    resolve();
                }

                handleActuality()
                    .then(() => caches.open('espo'))
                    .then(responseCache => {
                        this.responseCache = responseCache;

                        resolve();
                    })
                    .catch(() => {
                        console.error("Could not open `espo` cache.");
                        resolve();
                    });
            });
        },

        /**
         * @private
         */
        init: function (options, callback) {
            this.appParams = {};
            this.controllers = {};

            this.loader = Espo.loader;

            this.loader.setCache(this.cache);
            this.loader.setResponseCache(this.responseCache);

            if (this.useCache && !this.loader.getCacheTimestamp() && options.cacheTimestamp) {
                this.loader.setCacheTimestamp(options.cacheTimestamp);
            }

            this.storage = new Storage();
            this.sessionStorage = new SessionStorage();

            this.setupAjax();

            this.settings = new Settings(null);
            this.language = new Language(this.cache);
            this.metadata = new Metadata(this.cache);
            this.fieldManager = new FieldManager();

            Promise
            .all([
                this.settings.load(),
                this.language.loadDefault()
            ])
            .then(() => {
                this.loader.setIsDeveloperMode(this.settings.get('isDeveloperMode'));
                this.loader.addLibsConfig(this.settings.get('jsLibs') || {});

                this.user = new User();
                this.preferences = new Preferences();

                this.preferences.settings = this.settings;

                this.acl = this.createAclManager();

                this.fieldManager.acl = this.acl;

                this.themeManager = new ThemeManager(this.settings, this.preferences, this.metadata);
                this.modelFactory = new ModelFactory(this.metadata, this.user);
                this.collectionFactory = new CollectionFactory(this.modelFactory, this.settings);

                if (this.settings.get('useWebSocket')) {
                    this.webSocketManager = new WebSocketManager(this.settings);
                }

                this.initUtils();
                this.initView();
                this.initBaseController();

                this.preLoader = new PreLoader(this.cache, this.viewFactory, this.basePath);

                this.preLoad(() => {
                    callback.call(this, this);
                });
            });
        },

        /**
         * Start the application.
         */
        start: function () {
            this.initAuth();

            if (!this.auth) {
                this.baseController.login();
            }
            else {
                this.initUserData(null, () => {
                    this.onAuth.call(this);
                });
            }

            this.on('auth', this.onAuth, this);
        },

        /**
         * @private
         */
        onAuth: function () {
            this.metadata.load().then(() => {
                this.fieldManager.defs = this.metadata.get('fields');
                this.fieldManager.metadata = this.metadata;

                this.settings.defs = this.metadata.get('entityDefs.Settings') || {};
                this.user.defs = this.metadata.get('entityDefs.User');
                this.preferences.defs = this.metadata.get('entityDefs.Preferences');
                this.viewHelper.layoutManager.userId = this.user.id;

                if (this.themeManager.isUserTheme()) {
                    this.loadStylesheet();
                }

                if (this.webSocketManager) {
                    this.webSocketManager.connect(this.auth, this.user.id);
                }

                this.initBroadcastChannel();

                let promiseList = [];
                let aclImplementationClassMap = {};

                let clientDefs = this.metadata.get('clientDefs') || {};

                Object.keys(clientDefs).forEach(scope => {
                    let o = clientDefs[scope];

                    let implClassName = (o || {})[this.aclName || 'acl'];

                    if (!implClassName) {
                        return;
                    }

                    promiseList.push(
                        new Promise(resolve => {
                            this.loader.require(implClassName, implClass => {
                                aclImplementationClassMap[scope] = implClass;

                                resolve();
                            });
                        })
                    );
                });

                if (!this.themeManager.isApplied() && this.themeManager.isUserTheme()) {
                    promiseList.push(
                        new Promise(resolve => {
                            (function check (i) {
                                i = i || 0;

                                if (!this.themeManager.isApplied()) {
                                    if (i === 50) {
                                        resolve();

                                        return;
                                    }

                                    setTimeout(check.bind(this, i + 1), 10);

                                    return;
                                }

                                resolve();
                            }).call(this);
                        })
                    );
                }

                Promise
                    .all(promiseList)
                    .then(() => {
                        this.acl.implementationClassMap = aclImplementationClassMap;

                        this.initRouter();
                    });
            });
        },

        /**
         * @private
         */
        initRouter: function () {
            let routes = this.metadata.get(['app', 'clientRoutes']) || {};

            this.router = new Router({routes: routes});

            this.viewHelper.router = this.router;

            this.baseController.setRouter(this.router);

            this.router.confirmLeaveOutMessage = this.language.translate('confirmLeaveOutMessage', 'messages');
            this.router.confirmLeaveOutConfirmText = this.language.translate('Yes');
            this.router.confirmLeaveOutCancelText = this.language.translate('Cancel');

            this.router.on('routed', params => this.doAction(params));

            try {
                Backbone.history.start({
                    root: window.location.pathname
                });
            }
            catch (e) {
                Backbone.history.loadUrl();
            }
        },

        /**
         * @private
         */
        doAction: function (params) {
            this.trigger('action', params);

            this.baseController.trigger('action');

            this.getController(params.controller, controller => {
                try {
                    controller.doAction(params.action, params.options);

                    this.trigger('action:done');
                }
                catch (e) {
                    console.error(e);

                    switch (e.name) {
                        case 'AccessDenied':
                            this.baseController.error403();

                            break;

                        case 'NotFound':
                            this.baseController.error404();

                            break;

                        default:
                            throw e;
                    }
                }
            });
        },

        /**
         * @private
         */
        initBaseController: function () {
            this.baseController = new BaseController({}, this.getControllerInjection());

            this.viewHelper.baseController = this.baseController;
        },

        /**
         * @private
         */
        getControllerInjection: function () {
            return {
                viewFactory: this.viewFactory,
                modelFactory: this.modelFactory,
                collectionFactory: this.collectionFactory,
                settings: this.settings,
                user: this.user,
                preferences: this.preferences,
                acl: this.acl,
                cache: this.cache,
                router: this.router,
                storage: this.storage,
                metadata: this.metadata,
                dateTime: this.dateTime,
                broadcastChannel: this.broadcastChannel,
            };
        },

        /**
         * @private
         */
        getController: function (name, callback) {
            if (!name) {
                callback(this.baseController);

                return;
            }

            if (name in this.controllers) {
                callback(this.controllers[name]);

                return;
            }

            try {
                let className = this.metadata.get(['clientDefs', name, 'controller']);

                if (!className) {
                    let module = this.metadata.get(['scopes', name, 'module']);

                    className = Utils.composeClassName(module, name, 'controllers');
                }

                require(
                    className,
                    /** typeof module:controller.Class */
                    controllerClass => {
                        let injections = this.getControllerInjection();

                        injections.baseController = this.baseController;

                        let controller = new controllerClass(this.baseController.params, injections);

                        controller.name = name;
                        controller.masterView = this.masterView;

                        this.controllers[name] = controller

                        callback(controller);
                    },
                    this,
                    () => this.baseController.error404()
                );
            }
            catch (e) {
                this.baseController.error404();
            }
        },

        /**
         * @private
         */
        preLoad: function (callback) {
            this.preLoader.load(callback, this);
        },

        /**
         * @private
         */
        initUtils: function () {
            this.dateTime = new DateTime();
            this.modelFactory.dateTime = this.dateTime;
            this.dateTime.setSettingsAndPreferences(this.settings, this.preferences);
            this.numberUtil = new NumberUtil(this.settings, this.preferences);
        },

        /**
         * Create an acl-manager.
         *
         * @protected
         * @return {module:acl-manager.Class}
         */
        createAclManager: function () {
            return new AclManager(this.user, null, this.settings.get('aclAllowDeleteCreated'));
        },

        /**
         * @private
         */
        initView: function () {
            let helper = this.viewHelper = new ViewHelper();

            helper.layoutManager = new LayoutManager(this.cache, this.id);
            helper.settings = this.settings;
            helper.config = this.settings;
            helper.user = this.user;
            helper.preferences = this.preferences;
            helper.acl = this.acl;
            helper.modelFactory = this.modelFactory;
            helper.collectionFactory = this.collectionFactory;
            helper.storage = this.storage;
            helper.sessionStorage = this.sessionStorage;
            helper.dateTime = this.dateTime;
            helper.language = this.language;
            helper.metadata = this.metadata;
            helper.fieldManager = this.fieldManager;
            helper.cache = this.cache;
            helper.themeManager = this.themeManager;
            helper.webSocketManager = this.webSocketManager;
            helper.numberUtil = this.numberUtil;
            helper.pageTitle = new PageTitle(this.settings);
            helper.basePath = this.basePath;
            helper.appParams = this.appParams;

            this.viewLoader = (viewName, callback) => {
                require(Utils.composeViewClassName(viewName), callback);
            };

            let internalModuleMap = {};

            let isModuleInternal = (module) => {
                if (!(module in internalModuleMap)) {
                    internalModuleMap[module] = this.internalModuleList.indexOf(module) !== -1;
                }

                return internalModuleMap[module];
            };

            let getResourceInnerPath = (type, name) => {
                let path = null;

                switch (type) {
                    case 'template':
                        if (~name.indexOf('.')) {
                            console.warn(name + ': template name should use slashes for a directory separator.');
                        }

                        path = 'res/templates/' + name.split('.').join('/') + '.tpl';

                        break;

                    case 'layoutTemplate':
                        path = 'res/layout-types/' + name + '.tpl';

                        break;

                    case 'layout':
                        path = 'res/layouts/' + name + '.json';

                        break;
                }

                return path;
            };

            let getResourcePath = (type, name) => {
                if (name.indexOf(':') === -1) {
                    return 'client/' + getResourceInnerPath(type, name);
                }

                let arr = name.split(':');
                let mod = arr[0];
                let path = arr[1];

                if (mod === 'custom') {
                    return 'client/custom/' + getResourceInnerPath(type, path);
                }

                if (isModuleInternal(mod)) {
                    return 'client/modules/' + mod + '/' + getResourceInnerPath(type, path);
                }

                return 'client/custom/modules/' + mod + '/' + getResourceInnerPath(type, path);
            };

            this.viewFactory = new Bull.Factory({
                useCache: false,
                defaultViewName: 'views/base',
                helper: helper,
                viewLoader: this.viewLoader,
                resources: {
                    loaders: {
                        template: (name, callback) => {
                            let path = getResourcePath('template', name);

                            this.loader.require('res!' + path, callback);
                        },
                        layoutTemplate: (name, callback) => {
                            let path = getResourcePath('layoutTemplate', name);

                            this.loader.require('res!' + path, callback);
                        },
                    },
                },
            });
        },

        /**
         * @private
         */
        initAuth: function () {
            this.auth = this.storage.get('user', 'auth') || null;

            this.baseController.on('login', data => {
                let userId = data.user.id;
                let userName = data.auth.userName;
                let token = data.auth.token;

                this.auth = Base64.encode(userName  + ':' + token);

                let lastUserId = this.storage.get('user', 'lastUserId');

                if (lastUserId !== userId) {
                    this.metadata.clearCache();
                    this.language.clearCache();
                }

                this.storage.set('user', 'auth', this.auth);
                this.storage.set('user', 'lastUserId', userId);

                this.setCookieAuth(userName, token);

                this.initUserData(data, () => this.trigger('auth'));
            });

            this.baseController.on('logout', () => this.logout());
        },

        /**
         * @private
         */
        logout: function () {
            if (this.auth) {
                let arr = Base64.decode(this.auth).split(':');

                if (arr.length > 1) {
                    Ajax.postRequest('App/action/destroyAuthToken', {
                        token: arr[1]
                    });
                }
            }

            if (this.webSocketManager) {
                this.webSocketManager.close();
            }

            this.auth = null;

            this.user.clear();
            this.preferences.clear();

            this.acl.clear();

            this.storage.clear('user', 'auth');

            this.doAction({action: 'login'});

            this.unsetCookieAuth();

            if (this.broadcastChannel && this.broadcastChannel.object) {
                this.broadcastChannel.object.close();
            }

            this.broadcastChannel = null;

            let xhr = new XMLHttpRequest;

            xhr.open('GET', this.basePath + this.apiUrl + '/');
            xhr.setRequestHeader('Authorization', 'Basic ' + Base64.encode('**logout:logout'));
            xhr.send('');
            xhr.abort();

            this.loadStylesheet();
        },

        /**
         * @private
         */
        loadStylesheet: function () {
            if (!this.metadata.get(['themes'])) {
                return;
            }

            let stylesheetPath = this.basePath + this.themeManager.getStylesheet();

            $('#main-stylesheet').attr('href', stylesheetPath);
        },

        /**
         * @private
         */
        setCookieAuth: function (username, token) {
            let date = new Date();

            date.setTime(date.getTime() + (1000 * 24*60*60*1000));

            document.cookie = 'auth-token='+token+'; SameSite=Lax; expires='+date.toGMTString()+'; path=/';
        },

        /**
         * @private
         */
        unsetCookieAuth: function () {
            document.cookie = 'auth-token' + '=; SameSite=Lax; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        },

        /**
         * @private
         */
        initUserData: function (options, callback) {
            options = options || {};

            if (this.auth === null) {
                return;
            }

            new Promise(resolve => {
                if (options.user) {
                    resolve(options);

                    return;
                };

                this.requestUserData(data => {
                    options = data;

                    resolve(options);
                });
            })
            .then(options => {
                this.language.name = options.language;

                return this.language.load();
            })
            .then(() => {
                this.dateTime.setLanguage(this.language);

                let userData = options.user || null;
                let preferencesData = options.preferences || null;
                let aclData = options.acl || null;

                let settingData = options.settings || {};

                this.user.set(userData);
                this.preferences.set(preferencesData);

                this.settings.set(settingData);
                this.acl.set(aclData);

                for (let param in options.appParams) {
                    this.appParams[param] = options.appParams[param];
                }

                if (!this.auth) {
                    return;
                }

                let xhr = new XMLHttpRequest();

                xhr.open('GET', this.basePath + this.apiUrl + '/');

                xhr.setRequestHeader('Authorization', 'Basic ' + this.auth);

                xhr.onreadystatechange = () => {
                    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {

                        let arr = Base64.decode(this.auth).split(':');

                        this.setCookieAuth(arr[0], arr[1]);

                        callback();
                    }

                    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 401) {
                        Ui.error('Auth error');
                    }
                };

                xhr.send('');
            });
        },

        /**
         * @private
         */
        requestUserData: function (callback) {
            Ajax
                .getRequest('App/user')
                .then(callback);
        },

        /**
         * @private
         */
        setupAjax: function () {
            $.ajaxSetup({
                beforeSend: (xhr, options) => {
                    if (!options.local && this.apiUrl) {
                        options.url = Utils.trimSlash(this.apiUrl) + '/' + options.url;
                    }

                    if (!options.local && this.basePath !== '') {
                        options.url = this.basePath + options.url;
                    }

                    if (this.auth !== null) {
                        xhr.setRequestHeader('Authorization', 'Basic ' + this.auth);
                        xhr.setRequestHeader('Espo-Authorization', this.auth);
                        xhr.setRequestHeader('Espo-Authorization-By-Token', 'true');
                    }

                },
                dataType: 'json',
                timeout: this.ajaxTimeout,
                contentType: 'application/json',
            });

            $(document).ajaxError((e, xhr, options) => {
                if (xhr.errorIsHandled) {
                    return;
                }

                switch (xhr.status) {
                    case 0:
                        if (xhr.statusText === 'timeout') {
                            Ui.error(this.language.translate('Timeout'), true);
                        }

                        break;

                    case 200:
                        Ui.error(this.language.translate('Bad server response'));

                        console.error('Bad server response: ' + xhr.responseText);

                        break;

                    case 401:
                        if (!options.login) {
                            if (this.auth) {
                                this.logout();

                                break
                            }

                            console.error('Error 401: Unauthorized.');
                        }

                        break;

                    case 403:
                        if (options.main) {
                            this.baseController.error403();

                            break;
                        }

                        this._processErrorAlert(xhr, 'Access denied');

                        break;

                    case 400:
                        this._processErrorAlert(xhr, 'Bad request');

                        break;

                    case 404:
                        if (options.main) {
                            this.baseController.error404();

                            break
                        }

                        this._processErrorAlert(xhr, 'Not found', true);

                        break;

                    default:
                        this._processErrorAlert(xhr, null);
                }

                let statusReason = xhr.getResponseHeader('X-Status-Reason');

                if (statusReason) {
                    console.error('Server side error ' + xhr.status + ': ' + statusReason);
                }
            });
        },

        /**
         * @private
         */
        _processErrorAlert: function (xhr, label, noDetail) {
            let msg = this.language.translate('Error') + ' ' + xhr.status;

            if (label) {
                msg += ': ' + this.language.translate(label);
            }

            let obj = {
                msg: msg,
                closeButton: false,
            };

            let isMessageDone = false;

            if (noDetail) {
                isMessageDone = true;
            }

            if (!isMessageDone && xhr.responseText && xhr.responseText[0] === '{') {
                let data = null;

                try {
                    data = JSON.parse(xhr.responseText);
                }
                catch (e) {}

                if (data && data.messageTranslation && data.messageTranslation.label) {
                    let msgDetail = this.language.translate(
                        data.messageTranslation.label,
                        'messages',
                        data.messageTranslation.scope
                    );

                    let msgData = data.messageTranslation.data || {};

                    for (let key in msgData) {
                        msgDetail = msgDetail.replace('{' + key + '}', msgData[key]);
                    }

                    obj.msg += '\n' + msgDetail;
                    obj.closeButton = true;

                    isMessageDone = true;
                }
            }

            if (!isMessageDone) {
                let statusReason = xhr.getResponseHeader('X-Status-Reason');

                if (statusReason) {
                    obj.msg += '\n' + statusReason;
                    obj.closeButton = true;

                    isMessageDone = true;
                }
            }

            Ui.error(obj.msg, obj.closeButton);
        },

        /**
         * @private
         */
        initBroadcastChannel: function () {
            this.broadcastChannel = new BroadcastChannel();

            this.broadcastChannel.subscribe(event => {
                if (event.data === 'update:all') {
                    this.metadata.loadSkipCache();
                    this.settings.loadSkipCache();
                    this.language.loadSkipCache();
                    this.viewHelper.layoutManager.clearLoadedData();
                }

                if (event.data === 'update:metadata') {
                    this.metadata.loadSkipCache();
                }

                if (event.data === 'update:config') {
                    this.settings.load();
                }

                if (event.data === 'update:language') {
                    this.language.loadSkipCache();
                }

                if (event.data === 'update:layout') {
                    this.viewHelper.layoutManager.clearLoadedData();
                }
            });

            this.viewHelper.broadcastChannel = this.broadcastChannel;
        },

        initDomEventListeners: function () {
            $(document).on('keydown.espo.button', e => {
                if (
                    e.code !== 'Enter' ||
                    e.target.tagName !== 'A' ||
                    e.target.getAttribute('role') !== 'button' ||
                    e.target.getAttribute('href') ||
                    e.ctrlKey ||
                    e.altKey ||
                    e.metaKey
                ) {
                    return;
                }

                $(e.target).click();

                e.preventDefault();
            });
        },

    }, Backbone.Events);

    App.extend = Backbone.Router.extend;

    return App;
});
