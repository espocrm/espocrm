define("app", ["exports", "backbone", "bullbone", "js-base64", "ui", "utils", "acl-manager", "cache", "storage", "models/settings", "language", "metadata", "field-manager", "models/user", "models/preferences", "model-factory", "collection-factory", "controllers/base", "router", "date-time", "layout-manager", "theme-manager", "session-storage", "view-helper", "web-socket-manager", "ajax", "number-util", "page-title", "broadcast-channel", "ui/app-init", "app-params", "di"], function (_exports, _backbone, _bullbone, _jsBase, _ui, _utils, _aclManager, _cache, _storage, _settings, _language, _metadata, _fieldManager, _user, _preferences, _modelFactory, _collectionFactory, _base, _router, _dateTime, _layoutManager, _themeManager, _sessionStorage, _viewHelper, _webSocketManager, _ajax, _numberUtil, _pageTitle, _broadcastChannel, _appInit, _appParams, _di) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _backbone = _interopRequireDefault(_backbone);
  _jsBase = _interopRequireDefault(_jsBase);
  _ui = _interopRequireDefault(_ui);
  _utils = _interopRequireDefault(_utils);
  _aclManager = _interopRequireDefault(_aclManager);
  _cache = _interopRequireDefault(_cache);
  _storage = _interopRequireDefault(_storage);
  _settings = _interopRequireDefault(_settings);
  _language = _interopRequireDefault(_language);
  _metadata = _interopRequireDefault(_metadata);
  _fieldManager = _interopRequireDefault(_fieldManager);
  _user = _interopRequireDefault(_user);
  _preferences = _interopRequireDefault(_preferences);
  _modelFactory = _interopRequireDefault(_modelFactory);
  _collectionFactory = _interopRequireDefault(_collectionFactory);
  _base = _interopRequireDefault(_base);
  _router = _interopRequireDefault(_router);
  _dateTime = _interopRequireDefault(_dateTime);
  _layoutManager = _interopRequireDefault(_layoutManager);
  _themeManager = _interopRequireDefault(_themeManager);
  _sessionStorage = _interopRequireDefault(_sessionStorage);
  _viewHelper = _interopRequireDefault(_viewHelper);
  _webSocketManager = _interopRequireDefault(_webSocketManager);
  _ajax = _interopRequireDefault(_ajax);
  _numberUtil = _interopRequireDefault(_numberUtil);
  _pageTitle = _interopRequireDefault(_pageTitle);
  _broadcastChannel = _interopRequireDefault(_broadcastChannel);
  _appInit = _interopRequireDefault(_appInit);
  _appParams = _interopRequireDefault(_appParams);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  /** @module app */

  /**
   * A main application class.
   *
   * @mixes Bull.Events
   */
  class App {
    /**
     * @param {module:app~Options} options Options.
     * @param {function(App): void} callback A callback.
     */
    constructor(options, callback) {
      options = options || {};

      /**
       * An application ID.
       *
       * @private
       * @type {string}
       */
      this.id = options.id || 'espocrm';

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

      /**
       * @private
       */
      this.themeName = options.theme || null;

      /**
       * A list of bundled modules.
       *
       * @private
       * @type {string[]}
       */
      this.bundledModuleList = options.bundledModuleList || [];
      this.appTimestamp = options.appTimestamp;
      this.initCache(options).then(async () => {
        await this.init(options);
        callback(this);
      });
      (0, _appInit.default)();
    }

    /**
     * @private
     * @type {boolean}
     */
    useCache = false;

    /**
     * @protected
     * @type {User}
     */
    user = null;

    /**
     * @private
     * @type {Preferences}
     */
    preferences = null;

    /**
     * @protected
     * @type {module:models/settings}
     */
    settings = null;

    /**
     * @private
     * @type {Metadata}
     */
    metadata = null;

    /**
     * @private
     * @type {module:language}
     */
    language = null;

    /**
     * @private
     * @type {module:field-manager}
     */
    fieldManager = null;

    /**
     * @private
     * @type {module:cache|null}
     */
    cache = null;

    /**
     * @private
     * @type {module:storage|null}
     */
    storage = null;

    /**
     * @private
     */
    loader = null;

    /**
     * An API URL.
     *
     * @private
     */
    apiUrl = 'api/v1';

    /**
     * An auth credentials string.
     *
     * @private
     * @type {?string}
     */
    auth = null;

    /**
     * Another user to login as.
     *
     * @private
     * @type {?string}
     */
    anotherUser = null;

    /**
     * A base controller.
     *
     * @private
     * @type {module:controllers/base}
     */
    baseController = null;

    /**
     * @private
     */
    controllers = null;

    /**
     * @private
     * @type {module:router}
     */
    router = null;

    /**
     * @private
     * @type {module:model-factory}
     */
    modelFactory = null;

    /**
     * @private
     * @type {module:collection-factory}
     */
    collectionFactory = null;

    /**
     * A view factory.
     *
     * @private
     * @type {Factory}
     */
    viewFactory = null;

    /**
     * App params.
     *
     * @private
     * @type {import('app-params').default}
     */
    appParams;

    /**
     * @type {function(string, function(View))}
     * @private
     */
    viewLoader = null;

    /**
     * @private
     * @type {module:view-helper}
     */
    viewHelper = null;

    /**
     * A body view.
     *
     * @protected
     * @type {string}
     */
    masterView = 'views/site/master';

    /**
     * @private
     * @type {Cache|null}
     */
    responseCache = null;

    /**
     * @private
     * @type {module:broadcast-channel|null}
     */
    broadcastChannel = null;

    /**
     * @private
     * @type {module:date-time|null}
     */
    dateTime = null;

    /**
     * @private
     * @type {module:num-util|null}
     */
    numberUtil = null;

    /**
     * @private
     * @type {module:web-socket-manager}
     */
    webSocketManager;

    /**
     * @private
     * @type {AclManager}
     */
    acl;

    /**
     * An application timestamp. Used for asset cache busting and update detection.
     *
     * @private
     * @type {Number|null}
     */
    appTimestamp = null;

    /** @private */
    started = false;

    /** @private */
    aclName = 'acl';

    /**
     * @private
     * @param {module:app~Options} options
     */
    async initCache(options) {
      if (!this.useCache) {
        return;
      }
      const timestamp = options.cacheTimestamp || null;
      this.cache = new _cache.default(timestamp);
      const storedTimestamp = this.cache.getCacheTimestamp();
      timestamp ? this.cache.handleActuality(timestamp) : this.cache.storeTimestamp();
      if (!window.caches) {
        return;
      }
      const deleteCache = !timestamp || !storedTimestamp || timestamp !== storedTimestamp;
      try {
        if (deleteCache) {
          await caches.delete('espo');
        }
        this.responseCache = await caches.open('espo');
      } catch (e) {
        console.error(`Could not open 'espo' cache.`);
      }
    }

    /**
     * @private
     * @param {module:app~Options} options
     */
    async init(options) {
      this.appParams = new _appParams.default();
      this.controllers = {};

      /**
       * @type {Espo.loader}
       * @private
       */
      this.loader = Espo.loader;
      this.loader.setResponseCache(this.responseCache);
      if (this.useCache && !this.loader.getCacheTimestamp() && options.cacheTimestamp) {
        this.loader.setCacheTimestamp(options.cacheTimestamp);
      }
      this.storage = new _storage.default();
      this.sessionStorage = new _sessionStorage.default();
      this.setupAjax();
      this.settings = new _settings.default(null);
      this.language = new _language.default(this.cache);
      this.metadata = new _metadata.default(this.cache);
      this.fieldManager = new _fieldManager.default();
      _di.container.set(_appParams.default, this.appParams);
      _di.container.set(_storage.default, this.storage);
      _di.container.set(_sessionStorage.default, this.sessionStorage);
      _di.container.set(_settings.default, this.settings);
      _di.container.set(_language.default, this.language);
      _di.container.set(_metadata.default, this.metadata);
      _di.container.set(_fieldManager.default, this.fieldManager);
      this.initBroadcastChannel();
      await Promise.all([this.settings.load(), this.language.loadDefault(), this.initTemplateBundles()]);
      this.loader.setIsDeveloperMode(this.settings.get('isDeveloperMode'));
      this.user = new _user.default();
      this.preferences = new _preferences.default();
      this.preferences.setSettings(this.settings);
      this.acl = this.createAclManager();
      this.fieldManager.acl = this.acl;
      this.themeManager = new _themeManager.default(this.settings, this.preferences, this.metadata, this.themeName);
      this.modelFactory = new _modelFactory.default(this.metadata);
      this.collectionFactory = new _collectionFactory.default(this.modelFactory, this.settings, this.metadata);
      this.webSocketManager = new _webSocketManager.default(this.settings);
      _di.container.set(_aclManager.default, this.acl);
      _di.container.set(_user.default, this.user);
      _di.container.set(_preferences.default, this.preferences);
      _di.container.set(_themeManager.default, this.themeManager);
      _di.container.set(_modelFactory.default, this.modelFactory);
      _di.container.set(_collectionFactory.default, this.collectionFactory);
      _di.container.set(_webSocketManager.default, this.webSocketManager);
      this.initUtils();
      this.initView();
      this.initBaseController();
    }

    /**
     * Start the application.
     */
    start() {
      this.initAuth();
      this.started = true;
      if (!this.auth) {
        this.baseController.login();
        return;
      }
      this.initUserData(null, () => this.onAuth());
    }

    /**
     * @private
     * @param {boolean} [afterLogin]
     */
    async onAuth() {
      let afterLogin = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      await this.metadata.load();
      this.fieldManager.defs = this.metadata.get('fields') || {};
      this.fieldManager.metadata = this.metadata;
      this.settings.setDefs(this.metadata.get('entityDefs.Settings') || {});
      this.preferences.setDefs(this.metadata.get('entityDefs.Preferences') || {});
      this.viewHelper.layoutManager.setUserId(this.user.id);
      if (this.themeManager.isUserTheme()) {
        this.loadStylesheet();
      }
      this.applyUserStyle();
      if (this.anotherUser) {
        this.viewHelper.webSocketManager = null;
      }
      if (this.settings.get('useWebSocket') && !this.anotherUser) {
        this.webSocketManager.setEnabled();
      }
      if (this.webSocketManager.isEnabled()) {
        this.webSocketManager.connect(this.auth, this.user.id);
      }
      const promiseList = [];
      const aclImplementationClassMap = {};
      const clientDefs = this.metadata.get('clientDefs') || {};
      Object.keys(clientDefs).forEach(scope => {
        const o = clientDefs[scope];
        const implClassName = (o || {})[this.aclName];
        if (!implClassName) {
          return;
        }
        const promise = new Promise(resolve => {
          this.loader.require(implClassName, Class => {
            aclImplementationClassMap[scope] = Class;
            resolve();
          });
        });
        promiseList.push(promise);
      });
      if (!this.themeManager.isApplied() && this.themeManager.isUserTheme()) {
        const promise = new Promise(resolve => {
          const check = i => {
            if (this.themeManager.isApplied() || i === 50) {
              resolve();
              return;
            }
            i = i || 0;
            setTimeout(() => check(i + 1), 10);
          };
          check();
        });
        promiseList.push(promise);
      }
      const promise = Promise.all(promiseList);
      if (afterLogin) {
        this.broadcastChannel.postMessage('logged-in');
      }
      await promise;
      this.acl.implementationClassMap = aclImplementationClassMap;
      this.initRouter();
      this.webSocketManager.subscribe('appParamsUpdate', () => this.appParams.load());
    }

    /**
     * @private
     */
    initRouter() {
      const routes = this.metadata.get('app.clientRoutes') || {};
      this.router = new _router.default({
        routes: routes
      });
      _di.container.set(_router.default, this.router);
      this.viewHelper.router = this.router;
      this.baseController.setRouter(this.router);
      this.router.confirmLeaveOutMessage = this.language.translate('confirmLeaveOutMessage', 'messages');
      this.router.confirmLeaveOutConfirmText = this.language.translate('Yes');
      this.router.confirmLeaveOutCancelText = this.language.translate('Cancel');
      this.router.on('routed', params => this.doAction(params));
      try {
        _backbone.default.history.start({
          root: window.location.pathname
        });
      } catch (e) {
        _backbone.default.history.loadUrl();
      }
    }

    /**
     * Do an action.
     *
     * @public
     * @param {{
     *   controller?: string,
     *   action: string,
     *   options?: Object.<string,*>,
     *   controllerClassName?: string,
     * }} params
     */
    doAction(params) {
      this.trigger('action', params);
      this.baseController.trigger('action');
      const callback = controller => {
        try {
          controller.doAction(params.action, params.options);
          this.trigger('action:done');
        } catch (e) {
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
      };
      if (params.controllerClassName) {
        this.createController(params.controllerClassName, null, callback);
        return;
      }
      this.getController(params.controller, callback);
    }

    /**
     * @private
     */
    initBaseController() {
      this.baseController = new _base.default({}, this.getControllerInjection());
      this.viewHelper.baseController = this.baseController;
    }

    /**
     * @private
     */
    getControllerInjection() {
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
        baseController: this.baseController
      };
    }

    /**
     * @param {string} name
     * @param {function(module:controller): void} callback
     * @private
     */
    getController(name, callback) {
      if (!name) {
        callback(this.baseController);
        return;
      }
      if (name in this.controllers) {
        callback(this.controllers[name]);
        return;
      }
      try {
        let className = this.metadata.get(`clientDefs.${name}.controller`);
        if (!className) {
          const module = this.metadata.get(`scopes.${name}.module`);
          if (!/^[A-Za-z0-9]+$/.test(name)) {
            console.error(`Bad controller name ${name}.`);
            this.baseController.error404();
            return;
          }
          className = _utils.default.composeClassName(module, name, 'controllers');
        }
        this.createController(className, name, callback);
      } catch (e) {
        this.baseController.error404();
      }
    }

    /**
     * @private
     * @return {module:controller}
     */
    createController(className, name, callback) {
      Espo.loader.require(className, controllerClass => {
        const injections = this.getControllerInjection();
        const controller = new controllerClass(this.baseController.params, injections);
        controller.name = name;
        controller.masterView = this.masterView;
        this.controllers[name] = controller;
        callback(controller);
      }, () => this.baseController.error404());
    }

    /**
     * @private
     */
    initUtils() {
      this.dateTime = new _dateTime.default();
      this.dateTime.setSettingsAndPreferences(this.settings, this.preferences);
      this.numberUtil = new _numberUtil.default(this.settings, this.preferences);
      _di.container.set(_dateTime.default, this.dateTime);
      _di.container.set(_numberUtil.default, this.numberUtil);
    }

    /**
     * Create an acl-manager.
     *
     * @protected
     * @return {module:acl-manager}
     */
    createAclManager() {
      return new _aclManager.default(this.user, null, this.settings.get('aclAllowDeleteCreated'));
    }

    /**
     * @private
     */
    initView() {
      const helper = this.viewHelper = new _viewHelper.default();
      helper.layoutManager = new _layoutManager.default(this.cache, this.id);
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
      helper.numberUtil = this.numberUtil;
      helper.pageTitle = new _pageTitle.default(this.settings);
      helper.basePath = this.basePath;
      helper.appParams = this.appParams;
      helper.broadcastChannel = this.broadcastChannel;
      helper.webSocketManager = this.settings.get('useWebSocket') ? this.webSocketManager : null;
      _di.container.set(_viewHelper.default, this.viewHelper);
      _di.container.set(_layoutManager.default, helper.layoutManager);
      _di.container.set(_pageTitle.default, helper.pageTitle);
      this.viewLoader = (viewName, callback) => {
        this.loader.require(_utils.default.composeViewClassName(viewName), callback);
      };
      const internalModuleMap = {};
      const isModuleInternal = module => {
        if (!(module in internalModuleMap)) {
          internalModuleMap[module] = this.internalModuleList.indexOf(module) !== -1;
        }
        return internalModuleMap[module];
      };
      const getResourceInnerPath = (type, name) => {
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
        }
        return path;
      };
      const getResourcePath = (type, name) => {
        if (!name.includes(':')) {
          return 'client/' + getResourceInnerPath(type, name);
        }
        const [mod, path] = name.split(':');
        if (mod === 'custom') {
          return 'client/custom/' + getResourceInnerPath(type, path);
        }
        if (isModuleInternal(mod)) {
          return 'client/modules/' + mod + '/' + getResourceInnerPath(type, path);
        }
        return 'client/custom/modules/' + mod + '/' + getResourceInnerPath(type, path);
      };
      this.viewFactory = new _bullbone.Factory({
        defaultViewName: 'views/base',
        helper: helper,
        viewLoader: this.viewLoader,
        resources: {
          loaders: {
            template: (name, callback) => {
              const path = getResourcePath('template', name);
              this.loader.require('res!' + path, callback);
            },
            layoutTemplate: (name, callback) => {
              if (Espo.layoutTemplates && name in Espo.layoutTemplates) {
                callback(Espo.layoutTemplates[name]);
                return;
              }
              const path = getResourcePath('layoutTemplate', name);
              this.loader.require('res!' + path, callback);
            }
          }
        },
        preCompiledTemplates: Espo.preCompiledTemplates || {}
      });
    }

    /**
     * @typedef {Record} module:app~LoginData
     * @property {Record} user
     * @property {Record} preferences
     * @property {Record} acl
     * @property {Record} settings
     * @property {Record} appParams
     * @property {string} language
     * @property {{
     *    userName: string,
     *    token: string,
     *    anotherUser?: string,
     * }} auth
     */

    /**
     * @public
     */
    initAuth() {
      this.auth = this.storage.get('user', 'auth') || null;
      this.anotherUser = this.storage.get('user', 'anotherUser') || null;
      this.baseController.on('login', /** module:app~LoginData */data => {
        const userId = data.user.id;
        const userName = data.auth.userName;
        const token = data.auth.token;
        const anotherUser = data.auth.anotherUser || null;
        this.auth = _jsBase.default.encode(userName + ':' + token);
        this.anotherUser = anotherUser;
        const lastUserId = this.storage.get('user', 'lastUserId');
        if (lastUserId !== userId) {
          this.metadata.clearCache();
          this.language.clearCache();
        }
        this.storage.set('user', 'auth', this.auth);
        this.storage.set('user', 'lastUserId', userId);
        this.storage.set('user', 'anotherUser', this.anotherUser);
        this.setCookieAuth(userName, token);
        this.initUserData(data, () => this.onAuth(true));
      });
      this.baseController.on('logout', () => this.logout());
    }

    /**
     * @private
     * @param {boolean} [afterFail]
     * @param {boolean} [silent]
     */
    logout(afterFail, silent) {
      let logoutWait = false;
      if (this.auth && !afterFail) {
        const arr = _jsBase.default.decode(this.auth).split(':');
        if (arr.length > 1) {
          logoutWait = this.appParams.get('logoutWait') || false;
          _ajax.default.postRequest('App/destroyAuthToken', {
            token: arr[1]
          }, {
            resolveWithXhr: true
          }).then(/** XMLHttpRequest */xhr => {
            const redirectUrl = xhr.getResponseHeader('X-Logout-Redirect-Url');
            if (redirectUrl) {
              setTimeout(() => window.location.href = redirectUrl, 50);
              return;
            }
            if (logoutWait) {
              this.doAction({
                action: 'login'
              });
            }
          });
        }
      }
      if (this.webSocketManager.isEnabled()) {
        this.webSocketManager.close();
      }
      silent = silent || afterFail && this.auth && this.auth !== this.storage.get('user', 'auth');
      this.auth = null;
      this.anotherUser = null;
      this.user.clear();
      this.preferences.clear();
      this.acl.clear();
      if (!silent) {
        this.storage.clear('user', 'auth');
        this.storage.clear('user', 'anotherUser');
      }
      const action = logoutWait ? 'logoutWait' : 'login';
      this.doAction({
        action: action
      });
      if (!silent) {
        this.unsetCookieAuth();
      }
      if (this.broadcastChannel.object) {
        if (!silent) {
          this.broadcastChannel.postMessage('logged-out');
        }
      }
      if (!silent) {
        this.sendLogoutRequest();
      }
      this.loadStylesheet();
    }

    /**
     * @private
     */
    sendLogoutRequest() {
      const xhr = new XMLHttpRequest();
      xhr.open('GET', this.basePath + this.apiUrl + '/');
      xhr.setRequestHeader('Authorization', 'Basic ' + _jsBase.default.encode('**logout:logout'));
      xhr.send('');
      xhr.abort();
    }

    /**
     * @private
     */
    loadStylesheet() {
      if (!this.metadata.get(['themes'])) {
        return;
      }
      const path = this.basePath + this.themeManager.getStylesheet();
      const element = document.querySelector('#main-stylesheet');
      if (!element) {
        return;
      }
      element.setAttribute('href', path);
    }

    /**
     * @private
     */
    applyUserStyle() {
      const pageContentWidth = this.preferences.get('pageContentWidth');
      if (pageContentWidth) {
        document.body.dataset.contentWidth = pageContentWidth;
      }
    }

    /**
     * @private
     * @param {string} username
     * @param {string} token
     */
    setCookieAuth(username, token) {
      const date = new Date();
      date.setTime(date.getTime() + 1000 * 24 * 60 * 60 * 1000);
      document.cookie = `auth-token=${token}; SameSite=Lax; expires=${date.toUTCString()}; path=/`;
    }

    /**
     * @private
     */
    unsetCookieAuth() {
      document.cookie = `auth-token=; SameSite=Lax; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/`;
    }

    /**
     * User data.
     *
     * @typedef {Object} module:app~UserData
     * @property {Record} user
     * @property {Record} preferences
     * @property {Record} acl
     * @property {Record} settings
     * @property {Record} appParams
     * @property {string} language
     */

    /**
     * @private
     * @param {module:app~UserData|null} data
     * @param {function} callback
     */
    async initUserData(data, callback) {
      data = data || {};
      if (this.auth === null) {
        return;
      }
      if (!data.user) {
        data = await this.requestUserData();
      }
      this.language.name = data.language;
      await this.language.load();
      this.dateTime.setLanguage(this.language);
      const userData = data.user || null;
      const preferencesData = data.preferences || null;
      const aclData = data.acl || null;
      const settingData = data.settings || {};
      this.user.setMultiple(userData);
      this.preferences.setMultiple(preferencesData);
      this.settings.clear();
      this.settings.setMultiple(settingData);
      this.acl.set(aclData);
      this.appParams.setAll(data.appParams);
      if (!this.auth) {
        return;
      }
      const xhr = new XMLHttpRequest();
      xhr.open('GET', `${this.basePath}${this.apiUrl}/`);
      xhr.setRequestHeader('Authorization', `Basic ${this.auth}`);
      xhr.onreadystatechange = () => {
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
          const arr = _jsBase.default.decode(this.auth).split(':');
          this.setCookieAuth(arr[0], arr[1]);
          callback();
        }
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 401) {
          _ui.default.error('Auth error');
        }
      };
      xhr.send('');
    }

    /**
     * @private
     * @return {Promise<module:app~UserData>}
     */
    async requestUserData() {
      return _ajax.default.getRequest('App/user', {}, {
        appStart: true
      });
    }

    /**
     * @private
     */
    setupAjax() {
      var _this = this;
      /**
       * @param {XMLHttpRequest} xhr
       * @param {Object.<string, *>} options
       */
      const beforeSend = (xhr, options) => {
        if (this.auth !== null && !options.login) {
          xhr.setRequestHeader('Authorization', 'Basic ' + this.auth);
          xhr.setRequestHeader('Espo-Authorization', this.auth);
          xhr.setRequestHeader('Espo-Authorization-By-Token', 'true');
        }
        if (this.anotherUser !== null && !options.login) {
          xhr.setRequestHeader('X-Another-User', this.anotherUser);
        }
      };
      let appTimestampChangeProcessed = false;

      /**
       * @param {XMLHttpRequest} xhr
       * @param {Object.<string, *>} options
       */
      const onSuccess = (xhr, options) => {
        const appTimestampHeader = xhr.getResponseHeader('X-App-Timestamp');
        if (!appTimestampHeader || appTimestampChangeProcessed) {
          return;
        }
        const appTimestamp = parseInt(appTimestampHeader);

        // noinspection JSUnresolvedReference
        const bypassAppReload = options.bypassAppReload;
        if (this.appTimestamp &&
        // this.appTimestamp is set to current time if cache disabled.
        appTimestamp > this.appTimestamp && !bypassAppReload) {
          appTimestampChangeProcessed = true;
          _ui.default.confirm(this.language.translate('confirmAppRefresh', 'messages'), {
            confirmText: this.language.translate('Refresh'),
            cancelText: this.language.translate('Cancel'),
            backdrop: 'static',
            confirmStyle: 'success'
          }).then(() => {
            window.location.reload();
            if (this.broadcastChannel) {
              this.broadcastChannel.postMessage('reload');
            }
          });
        }
      };

      /**
       * @param {module:ajax.Xhr} xhr
       * @param {Object.<string, *>} options
       */
      const onError = function (xhr) {
        let options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
        setTimeout(() => {
          if (xhr.errorIsHandled) {
            return;
          }
          switch (xhr.status) {
            case 200:
              _ui.default.error(_this.language.translate('Bad server response'));
              console.error('Bad server response: ' + xhr.responseText);
              break;
            case 401:
              // noinspection JSUnresolvedReference
              if (options.login) {
                break;
              }
              if (_this.auth && _this.router && !_this.router.hasConfirmLeaveOut()) {
                _this.logout(true);
                break;
              }
              if (_this.auth && _this.router && _this.router.hasConfirmLeaveOut()) {
                _ui.default.error(_this.language.translate('loggedOutLeaveOut', 'messages'), true);
                _this.router.trigger('logout');
                break;
              }
              if (_this.auth) {
                // noinspection JSUnresolvedReference
                const silent = !options.appStart;
                _this.logout(true, silent);
              }
              console.error('Error 401: Unauthorized.');
              break;
            case 403:
              // noinspection JSUnresolvedReference
              if (options.main) {
                _this.baseController.error403();
                break;
              }
              _this._processErrorAlert(xhr, 'Access denied');
              break;
            case 400:
              _this._processErrorAlert(xhr, 'Bad request');
              break;
            case 404:
              // noinspection JSUnresolvedReference
              if (options.main) {
                _this.baseController.error404();
                break;
              }
              _this._processErrorAlert(xhr, 'Not found', true);
              break;
            default:
              _this._processErrorAlert(xhr, null);
          }
          const statusReason = xhr.getResponseHeader('X-Status-Reason');
          if (statusReason) {
            console.error('Server side error ' + xhr.status + ': ' + statusReason);
          }
        }, 0);
      };
      const onTimeout = () => {
        _ui.default.error(this.language.translate('Timeout'), true);
      };
      const onOffline = () => {
        _ui.default.error(this.language.translate('No internet'));
      };
      _ajax.default.configure({
        apiUrl: this.basePath + this.apiUrl,
        timeout: this.ajaxTimeout,
        beforeSend: beforeSend,
        onSuccess: onSuccess,
        onError: onError,
        onTimeout: onTimeout,
        onOffline: onOffline
      });
    }

    /**
     * @private
     * @param {XMLHttpRequest} xhr
     * @param {string|null} label
     * @param {boolean} [noDetail]
     */
    _processErrorAlert(xhr, label, noDetail) {
      let msg = '';
      if (!label) {
        if (xhr.status === 0) {
          msg += this.language.translate('Network error');
        } else {
          msg += this.language.translate('Error') + ' ' + xhr.status;
        }
      } else {
        msg += this.language.translate(label);
      }
      const obj = {
        msg: msg,
        closeButton: true
      };
      let isMessageDone = false;
      if (noDetail) {
        isMessageDone = true;
      }
      if (!isMessageDone && xhr.responseText && xhr.responseText[0] === '{') {
        /** @type {Object.<string, *>|null} */
        let data = null;
        try {
          data = JSON.parse(xhr.responseText);
        } catch (e) {}
        if (data && data.messageTranslation && data.messageTranslation.label) {
          let msgDetail = this.language.translate(data.messageTranslation.label, 'messages', data.messageTranslation.scope);
          const msgData = data.messageTranslation.data || {};
          for (const key in msgData) {
            msgDetail = msgDetail.replace('{' + key + '}', msgData[key]);
          }
          obj.msg += '\n' + msgDetail;
          obj.closeButton = true;
          isMessageDone = true;
        }
        if (!isMessageDone && data && 'message' in data && data.message) {
          obj.msg += '\n' + data.message;
          obj.closeButton = true;
          isMessageDone = true;
        }
      }
      if (!isMessageDone) {
        const statusReason = xhr.getResponseHeader('X-Status-Reason');
        if (statusReason) {
          obj.msg += '\n' + statusReason;
          obj.closeButton = true;
        }
      }
      _ui.default.error(obj.msg, obj.closeButton);
    }

    /**
     * @private
     */
    initBroadcastChannel() {
      this.broadcastChannel = new _broadcastChannel.default();
      this.broadcastChannel.subscribe(event => {
        if (!this.auth && this.started) {
          if (event.data === 'logged-in') {
            // This works if the same instance opened in different tabs.
            // This does not work for different instances on the same domain
            // which may be the case in dev environment.
            window.location.reload();
          }
          return;
        }
        if (event.data === 'update:all') {
          this.metadata.loadSkipCache();
          this.settings.load();
          this.language.loadSkipCache();
          this.viewHelper.layoutManager.clearLoadedData();
          return;
        }
        if (event.data === 'update:metadata') {
          this.metadata.loadSkipCache();
          return;
        }
        if (event.data === 'update:config') {
          this.settings.load();
          return;
        }
        if (event.data === 'update:language') {
          this.language.loadSkipCache();
          return;
        }
        if (event.data === 'update:layout') {
          this.viewHelper.layoutManager.clearLoadedData();
          return;
        }
        if (event.data === 'update:appParams') {
          this.appParams.load();
          return;
        }
        if (event.data === 'reload') {
          window.location.reload();
          return;
        }
        if (event.data === 'logged-out' && this.started) {
          if (this.auth && this.router.hasConfirmLeaveOut()) {
            _ui.default.error(this.language.translate('loggedOutLeaveOut', 'messages'), true);
            this.router.trigger('logout');
            return;
          }
          this.logout(true);
        }
      });
      _di.container.set(_broadcastChannel.default, this.broadcastChannel);
    }

    /**
     * @private
     */
    async initTemplateBundles() {
      if (!this.responseCache) {
        return;
      }
      const key = 'templateBundlesCached';
      if (this.cache.get('app', key)) {
        return;
      }
      const files = ['client/lib/templates.tpl'];
      this.bundledModuleList.forEach(mod => {
        const file = this.internalModuleList.includes(mod) ? `client/modules/${mod}/lib/templates.tpl` : `client/custom/modules/${mod}/lib/templates.tpl`;
        files.push(file);
      });
      const baseUrl = _utils.default.obtainBaseUrl();
      const timestamp = this.loader.getCacheTimestamp();
      const promiseList = files.map(file => {
        const url = new URL(baseUrl + this.basePath + file);
        url.searchParams.append('t', this.appTimestamp);
        return new Promise(resolve => {
          fetch(url).then(response => {
            if (!response.ok) {
              console.error(`Could not fetch ${url}.`);
              resolve();
              return;
            }
            const promiseList = [];
            response.text().then(text => {
              const index = text.indexOf('\n');
              if (index <= 0) {
                resolve();
                return;
              }
              const delimiter = text.slice(0, index + 1);
              text = text.slice(index + 1);
              text.split(delimiter).forEach(item => {
                const index = item.indexOf('\n');
                const file = item.slice(0, index).trim();
                let content = item.slice(index + 1);

                // noinspection RegExpDuplicateCharacterInClass
                content = content.replace(/[\r|\n|\r\n]$/, '');
                const url = baseUrl + this.basePath + 'client/' + file;
                const urlObj = new URL(url);
                urlObj.searchParams.append('r', timestamp);
                promiseList.push(this.responseCache.put(urlObj, new Response(content)));
              });
            });
            Promise.all(promiseList).then(() => resolve());
          });
        });
      });
      await Promise.all(promiseList);
      this.cache.set('app', key, true);
    }
  }

  /**
   * @callback module:app~callback
   * @param {App} app A created application instance.
   */

  /**
   * Application options.
   *
   * @typedef {Object} module:app~Options
   * @property {string} [id] An application ID.
   * @property {string} [basePath] A base path.
   * @property {boolean} [useCache] Use cache.
   * @property {string} [apiUrl] An API URL.
   * @property {Number} [ajaxTimeout] A default ajax request timeout.
   * @property {string} [internalModuleList] A list of internal modules.
   *   Internal modules located in the `client/modules` directory.
   * @property {string} [bundledModuleList] A list of bundled modules.
   * @property {Number|null} [cacheTimestamp] A cache timestamp.
   * @property {Number|null} [appTimestamp] An application timestamp.
   * @property {string|null} [theme] A theme name.
   */

  Object.assign(App.prototype, _bullbone.Events);
  App.extend = _bullbone.View.extend;
  var _default = _exports.default = App;
});
//# sourceMappingURL=app.js.map ;