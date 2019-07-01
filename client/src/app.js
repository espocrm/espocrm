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


define(
    'app',
    [
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
    ],
    function (
        Ui,
        Utils,
        AclManager,
        Cache,
        Storage,
        Settings,
        Language,
        Metadata,
        FieldManager,
        User,
        Preferences,
        ModelFactory,
        CollectionFactory,
        PreLoader,
        BaseController,
        Router,
        DateTime,
        LayoutManager,
        ThemeManager,
        SessionStorage,
        ViewHelper,
        WebSocketManager,
        Ajax,
        NumberUtil
    ) {

    var App = function (options, callback) {
        var options = options || {};

        this.id = options.id || 'espocrm-application-id';

        this.useCache = options.useCache || this.useCache;
        this.url = options.url || this.url;
        this.basePath = options.basePath || '';

        this.appParams = {};

        this.loader = Espo.loader;
        this.loader.basePath = this.basePath;

        this.controllers = {};

        if (this.useCache) {
            this.cache = new Cache(options.cacheTimestamp);
            if (options.cacheTimestamp) {
                this.cache.handleActuality(options.cacheTimestamp);
            } else {
                this.cache.storeTimestamp();
            }
        }

        this.storage = new Storage();
        this.sessionStorage = new SessionStorage();

        this.loader.cache = this.cache;

        if (this.useCache && !this.loader.cacheTimestamp && options.cacheTimestamp) {
            this.loader.cacheTimestamp = options.cacheTimestamp;
        }

        this.setupAjax();

        this.settings = new Settings(null);
        this.language = new Language(this.cache);
        this.metadata = new Metadata(this.cache);
        this.fieldManager = new FieldManager();

        Promise.all([
            new Promise(function (resolve) {
                this.settings.load(function () {
                    resolve();
                });
            }.bind(this)),
            new Promise(function (resolve) {
                this.language.load(function () {
                    resolve();
                }, false, true);
            }.bind(this))
        ]).then(function () {
            this.loader.addLibsConfig(this.settings.get('jsLibs') || {});

            this.user = new User();
            this.preferences = new Preferences();
            this.preferences.settings = this.settings;
            this.acl = this.createAclManager();

            this.themeManager = new ThemeManager(this.settings, this.preferences, this.metadata);

            this.modelFactory = new ModelFactory(this.loader, this.metadata, this.user);
            this.collectionFactory = new CollectionFactory(this.loader, this.modelFactory);

            if (this.settings.get('useWebSocket')) {
                this.webSocketManager = new WebSocketManager(this.settings);
            }

            this.initUtils();
            this.initView();
            this.initBaseController();

            this.preLoader = new PreLoader(this.cache, this.viewFactory, this.basePath);

            this.preLoad(function () {
                callback.call(this, this);
            });
        }.bind(this));
    }

    _.extend(App.prototype, {

        useCache: false,

        user: null,

        preferences: null,

        settings: null,

        metadata: null,

        language: null,

        fieldManager: null,

        cache: null,

        loader: null,

        url: 'api/v1',

        auth: null,

        baseController: null,

        controllers: null,

        router: null,

        modelFactory: null,

        collectionFactory: null,

        viewFactory: null,

        viewLoader: null,

        viewHelper: null,

        masterView: 'views/site/master',

        start: function () {
            this.initAuth();

            if (!this.auth) {
                this.baseController.login();
            } else {
                this.initUserData(null, function () {
                    this.onAuth.call(this);
                }.bind(this));
            }

            this.on('auth', this.onAuth, this);
        },

        onAuth: function () {
            this.metadata.load(function () {
                this.fieldManager.defs = this.metadata.get('fields');
                this.fieldManager.metadata = this.metadata;

                this.settings.defs = this.metadata.get('entityDefs.Settings');
                this.user.defs = this.metadata.get('entityDefs.User');
                this.preferences.defs = this.metadata.get('entityDefs.Preferences');
                this.viewHelper.layoutManager.userId = this.user.id;

                if (this.themeManager.isUserTheme()) {
                    this.loadStylesheet();
                }

                if (this.webSocketManager) {
                    this.webSocketManager.connect(this.auth, this.user.id);
                }

                var promiseList = [];
                var aclImplementationClassMap = {};

                var clientDefs = this.metadata.get('clientDefs') || {};
                Object.keys(clientDefs).forEach(function (scope) {
                    var o = clientDefs[scope];
                    var implClassName = (o || {})[this.aclName || 'acl'];
                    if (implClassName) {
                        promiseList.push(new Promise(function (resolve) {
                            this.loader.load(implClassName, function (implClass) {
                                aclImplementationClassMap[scope] = implClass;
                                resolve();
                            });
                        }.bind(this)))
                    }
                }, this);

                if (!this.themeManager.isApplied() && this.themeManager.isUserTheme()) {
                    promiseList.push(
                        new Promise(function (resolve) {
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
                        }.bind(this))
                    );
                }

                Promise.all(promiseList).then(function () {
                    this.acl.implementationClassMap = aclImplementationClassMap;
                    this.initRouter();
                }.bind(this));
            }.bind(this));

        },

        initRouter: function () {
            var routes = this.metadata.get(['app', 'clientRoutes']) || {};
            this.router = new Router({routes: routes});
            this.viewHelper.router = this.router;
            this.baseController.setRouter(this.router);
            this.router.confirmLeaveOutMessage = this.language.translate('confirmLeaveOutMessage', 'messages');
            this.router.confirmLeaveOutConfirmText = this.language.translate('Yes');
            this.router.confirmLeaveOutCancelText = this.language.translate('Cancel');

            this.router.on('routed', function (params) {
                this.doAction(params);
            }.bind(this));

            try {
                Backbone.history.start({
                    root: window.location.pathname
                });
            } catch (e) {
                Backbone.history.loadUrl();
            }
        },

        doAction: function (params) {
            this.trigger('action', params);
            this.baseController.trigger('action');

            this.getController(params.controller, function (controller) {
                try {
                    controller.doAction(params.action, params.options);
                    this.trigger('action:done');
                } catch (e) {
                    console.log(e);
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
            }.bind(this));
        },

        initBaseController: function () {
            this.baseController = new BaseController({}, this.getControllerInjection());
            this.viewHelper.baseController = this.baseController;
        },

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
            };
        },

        getController: function (name, callback) {
            if (!(name || false)) {
                callback(this.baseController);
                return;
            }
            if (!(name in this.controllers)) {
                try {
                    var className = this.metadata.get('clientDefs.' + name + '.controller');
                    if (!className) {
                        var module = this.metadata.get('scopes.' + name + '.module');
                        className = Espo.Utils.composeClassName(module, name, 'controllers');
                    }
                    Espo.require(className, function (controllerClass) {
                        var injections = this.getControllerInjection();
                        injections.baseController = this.baseController;
                        this.controllers[name] = new controllerClass(this.baseController.params, injections);
                        this.controllers[name].name = name;
                        this.controllers[name].masterView = this.masterView;
                        callback(this.controllers[name]);
                    }, this, function () {
                        this.baseController.error404();
                    }.bind(this));
                    return;
                } catch (e) {
                    this.baseController.error404();
                }
            }
            callback(this.controllers[name]);
        },

        preLoad: function (callback) {
            this.preLoader.load(callback, this);
        },

        initUtils: function () {
            this.dateTime = new DateTime();
            this.modelFactory.dateTime = this.dateTime;
            this.dateTime.setSettingsAndPreferences(this.settings, this.preferences);

            this.numberUtil = new NumberUtil(this.settings, this.preferences);
        },

        createAclManager: function () {
            return new AclManager(this.user, null, this.settings.get('aclAllowDeleteCreated'));
        },

        initView: function () {
            var helper = this.viewHelper = new ViewHelper();

            helper.layoutManager = new LayoutManager({cache: this.cache, applicationId: this.id});
            helper.settings = this.settings;
            helper.config = this.settings;
            helper.user = this.user;
            helper.preferences = this.preferences;
            helper.acl = this.acl;
            helper.modelFactory = this.modelFactory;
            helper.collectionFactory = this.collectionFactory;
            helper.storage = this.storage;
            helper.dateTime = this.dateTime;
            helper.language = this.language;
            helper.metadata = this.metadata;
            helper.fieldManager = this.fieldManager;
            helper.cache = this.cache;
            helper.storage = this.storage;
            helper.themeManager = this.themeManager;
            helper.sessionStorage = this.sessionStorage;
            helper.basePath = this.basePath;
            helper.appParams = this.appParams;
            helper.webSocketManager = this.webSocketManager;
            helper.numberUtil = this.numberUtil;

            this.viewLoader = function (viewName, callback) {
                Espo.require(Espo.Utils.composeViewClassName(viewName), callback);
            }.bind(this);

            var self = this;

            var getResourceInnerPath = function (type, name) {
                var path = null;
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
            }.bind(this);

            var getResourcePath = function (type, name) {
                var path;
                if (name.indexOf(':') != -1) {
                    var arr = name.split(':');
                    name = arr[1];
                    var mod = arr[0];
                    if (mod == 'custom') {
                        path = 'client/custom/' + getResourceInnerPath(type, name);
                    } else {
                        path = 'client/modules/' + mod + '/' + getResourceInnerPath(type, name);
                    }
                } else {
                    path = 'client/' + getResourceInnerPath(type, name);
                }
                return path;
            }.bind(this);

            this.viewFactory = new Bull.Factory({
                useCache: false,
                defaultViewName: 'views/base',
                helper: helper,
                viewLoader: this.viewLoader,
                resources: {
                    loaders: {
                        'template': function (name, callback) {
                            var path = getResourcePath('template', name);
                            self.loader.load('res!'    + path, callback);
                        },
                        'layoutTemplate': function (name, callback) {
                            var path = getResourcePath('layoutTemplate', name);
                            self.loader.load('res!'    + path, callback);
                        }
                    }
                }
            });
        },

        initAuth: function () {
            this.auth = this.storage.get('user', 'auth') || null;

            this.baseController.on('login', function (data) {
                this.auth = Base64.encode(data.auth.userName  + ':' + data.auth.token);
                this.storage.set('user', 'auth', this.auth);

                this.setCookieAuth(data.auth.userName, data.auth.token);

                this.initUserData(data, function () {
                    this.trigger('auth');
                }.bind(this));

            }.bind(this));

            this.baseController.on('logout', function () {
                this.logout();
            }.bind(this));
        },

        logout: function () {
            if (this.auth) {
                var arr = Base64.decode(this.auth).split(':');
                if (arr.length > 1) {
                    Espo.Ajax.postRequest('App/action/destroyAuthToken', {
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

            xhr = new XMLHttpRequest;

            xhr.open('GET', this.basePath + this.url + '/');
            xhr.setRequestHeader('Authorization', 'Basic ' + Base64.encode('**logout:logout'));
            xhr.send('');
            xhr.abort();

            this.loadStylesheet();
        },

        loadStylesheet: function () {
            if (!this.metadata.get(['themes'])) return;

            var stylesheetPath = this.basePath + this.themeManager.getStylesheet();
            $('#main-stylesheet').attr('href', stylesheetPath);
        },

        setCookieAuth: function (username, token) {
            var date = new Date();
            date.setTime(date.getTime() + (1000 * 24*60*60*1000));
            document.cookie = 'auth-username='+username+'; expires='+date.toGMTString()+'; path=/';
            document.cookie = 'auth-token='+token+'; expires='+date.toGMTString()+'; path=/';
        },

        unsetCookieAuth: function () {
            document.cookie = 'auth-username' + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
            document.cookie = 'auth-token' + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
        },

        initUserData: function (options, callback) {
            options = options || {};

            if (this.auth === null) return;

            Promise.all([
                new Promise(function (resolve) {
                    if (options.user) {
                        resolve();
                        return;
                    };
                    this.requestUserData(function (data) {
                        options = data;
                        resolve();
                    });
                }.bind(this))
            ]).then(function () {
                (new Promise(function (resolve) {
                    this.language.name = options.language;
                    this.language.load(function () {
                        resolve();
                    }.bind(this));
                }.bind(this))).then(function () {
                    this.dateTime.setLanguage(this.language);

                    var userData = options.user || null;
                    var preferencesData = options.preferences || null;
                    var aclData = options.acl || null;

                    var settingData = options.settings || {};

                    this.user.set(userData);
                    this.preferences.set(preferencesData);

                    this.settings.set(settingData);
                    this.acl.set(aclData);

                    for (var param in options.appParams) {
                        this.appParams[param] = options.appParams[param];
                    }

                    if (!this.auth) {
                        return;
                    }

                    var xhr = new XMLHttpRequest();

                    xhr.open('GET', this.basePath + this.url + '/');
                    xhr.setRequestHeader('Authorization', 'Basic ' + this.auth);

                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {

                            var arr = Base64.decode(this.auth).split(':');
                            this.setCookieAuth(arr[0], arr[1]);
                            callback();
                        }
                    }.bind(this);

                    xhr.send('');
                }.bind(this));
            }.bind(this));
        },

        requestUserData: function (callback) {
            Espo.Ajax.getRequest('App/user').then(callback);
        },

        setupAjax: function () {
            var self = this;
            $.ajaxSetup({
                beforeSend: function (xhr, options) {
                    if (!options.local && self.url) {
                        options.url = Espo.Utils.trimSlash(self.url) + '/' + options.url;
                    }

                    if (!options.local && self.basePath !== '') {
                        options.url = self.basePath + options.url;
                    }
                    if (self.auth !== null) {
                        xhr.setRequestHeader('Authorization', 'Basic ' + self.auth);
                        xhr.setRequestHeader('Espo-Authorization', self.auth);
                        xhr.setRequestHeader('Espo-Authorization-By-Token', true);
                    }
                },
                dataType: 'json',
                timeout: 60000,
                contentType: 'application/json'
            });

            $(document).ajaxError(function (event, xhr, options) {
                if (xhr.errorIsHandled) {
                    return;
                }
                var statusReason = xhr.getResponseHeader('X-Status-Reason');

                switch (xhr.status) {
                    case 0:
                        if (xhr.statusText == 'timeout') {
                            Espo.Ui.error(self.language.translate('Timeout'));
                        }
                        break;
                    case 200:
                        Espo.Ui.error(self.language.translate('Bad server response'));
                        console.error('Bad server response: ' + xhr.responseText);
                        break;
                    case 401:
                        if (!options.login) {
                            if (self.auth) {
                                self.logout();
                            } else {
                                Espo.Ui.error(self.language.translate('Auth error'));
                            }
                        }
                        break;
                    case 403:
                        if (options.main) {
                            self.baseController.error403();
                        } else {
                            var msg = self.language.translate('Error') + ' ' + xhr.status;
                            msg += ': ' + self.language.translate('Access denied');
                            Espo.Ui.error(msg);
                        }
                        break;
                    case 400:
                        var msg = self.language.translate('Error') + ' ' + xhr.status;
                        msg += ': ' + self.language.translate('Bad request');
                        Espo.Ui.error(msg);
                        break;
                    case 404:
                        if (options.main) {
                            self.baseController.error404();
                        } else {
                            var msg = self.language.translate('Error') + ' ' + xhr.status;
                            msg += ': ' + self.language.translate('Not found');
                            Espo.Ui.error(msg);
                        }
                        break;
                    default:
                        var msg = self.language.translate('Error') + ' ' + xhr.status;
                        if (statusReason) {
                            msg += ': ' + statusReason;
                        }
                        Espo.Ui.error(msg);
                }

                if (statusReason) {
                    console.error('Server side error '+xhr.status+': ' + statusReason);
                }
            });
        },

    }, Backbone.Events);

    App.extend = Backbone.Router.extend;

    return App;
});
