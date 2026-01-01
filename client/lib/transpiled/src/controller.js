define("controller", ["exports", "exceptions", "bullbone", "di", "helpers/site/modal-bar-provider"], function (_exports, _exceptions, _bullbone, _di, _modalBarProvider) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _exceptions = _interopRequireDefault(_exceptions);
  _modalBarProvider = _interopRequireDefault(_modalBarProvider);
  var _staticBlock;
  let _init_modalBarProvider, _init_extra_modalBarProvider;
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
  /** @module controller */
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
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
      _init_extra_modalBarProvider(this);
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
    defaultAction = 'index';

    /**
     * A name.
     *
     * @type {string|null}
     */
    name = null;

    /**
     * Params.
     *
     * @type {Object}
     * @private
     */
    params = null;

    /**
     * A view factory.
     *
     * @type {Bull.Factory}
     * @protected
     */
    viewFactory = null;

    /**
     * A model factory.
     *
     * @type {module:model-factory}
     * @protected
     */
    modelFactory = null;

    /**
     * A body view.
     *
     * @public
     * @type {string|null}
     */
    masterView = null;

    /**
     * @private
     * @type {ModalBarProvider}
     */
    modalBarProvider = _init_modalBarProvider(this);

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
      this.listenTo(view, 'remove', o => {
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
        throw new _exceptions.default.AccessDenied("Denied access to '" + this.name + "'");
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
      const msg = action ? "Denied access to action '" + this.name + "#" + action + "'" : "Denied access to scope '" + this.name + "'";
      throw new _exceptions.default.AccessDenied(msg);
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
        throw new _exceptions.default.NotFound("Action '" + this.name + "#" + action + "' is not found");
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
      this.viewFactory.create(viewName, {
        fullSelector: 'body'
      }, async (/** import('views/site/master').default */masterView) => {
        this.setMasterView(masterView);
        if (this.isMasterRendered()) {
          callback.call(this, masterView);
          return;
        }
        this.modalBarProvider.set(masterView.collapsedModalBarView || null);
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
      if (!masterView.currentViewKey /*||
                                     !this.hasStoredMainView(masterView.currentViewKey)*/) {
        return;
      }
      const currentMainView = masterView.getMainView();
      if (!currentMainView) {
        return;
      }
      currentMainView.propagateEvent('remove', {
        ignoreCleaning: true
      });
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
    main(view, options, callback) {
      let params = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
      const dto = {
        isCanceled: false,
        key: params.key,
        useStored: params.useStored,
        callback: callback
      };
      const selector = '#main';
      const useStored = params.useStored || false;
      const key = params.key;
      this.listenToOnce(this.baseController, 'action', () => dto.isCanceled = true);
      const mainView = view && typeof view === 'object' ? view : undefined;
      const viewName = !mainView ? view || 'views/base' : undefined;
      this.master(async masterView => {
        if (dto.isCanceled) {
          return;
        }
        options = options || {};
        options.fullSelector = selector;
        if (useStored && this.hasStoredMainView(key)) {
          const mainView = this.getStoredMainView(key);
          let isActual = true;
          if (mainView && 'isActualForReuse' in mainView && typeof mainView.isActualForReuse === 'function') {
            isActual = mainView.isActualForReuse();
          }
          const lastUrl = mainView && 'lastUrl' in mainView ? mainView.lastUrl : null;
          if (isActual && (!lastUrl || lastUrl === this.getRouter().getCurrentUrl())) {
            this._processMain(mainView, masterView, dto);
            if ('setupReuse' in mainView && typeof mainView.setupReuse === 'function') {
              mainView.setupReuse(options.params || {});
            }
            return;
          }
          this.clearStoredMainView(key);
        }
        if (mainView) {
          this._unchainMainView(masterView);
          await masterView.assignView('main', mainView, selector);
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
          window.scrollTo({
            top: this.get(scrollKey)
          });
          return;
        }
        window.scrollTo({
          top: 0
        });
      };
      if (dto.callback) {
        this.listenToOnce(mainView, 'after:render', afterRender);
        dto.callback.call(this, mainView);
        return;
      }
      mainView.render().then(afterRender);
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
    static #_ = _staticBlock = () => [_init_modalBarProvider, _init_extra_modalBarProvider] = _applyDecs(this, [], [[(0, _di.inject)(_modalBarProvider.default), 0, "modalBarProvider"]]).e;
  }
  _staticBlock();
  Object.assign(Controller.prototype, _bullbone.Events);

  /** For backward compatibility. */
  Controller.extend = _bullbone.View.extend;
  var _default = _exports.default = Controller;
});
//# sourceMappingURL=controller.js.map ;