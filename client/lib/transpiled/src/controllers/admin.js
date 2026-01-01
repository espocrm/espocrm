define("controllers/admin", ["exports", "controller", "search-manager", "views/settings/edit", "views/admin/index", "di", "language", "views/edit"], function (_exports, _controller, _searchManager, _edit, _index, _di, _language, _edit2) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _controller = _interopRequireDefault(_controller);
  _searchManager = _interopRequireDefault(_searchManager);
  _edit = _interopRequireDefault(_edit);
  _index = _interopRequireDefault(_index);
  _language = _interopRequireDefault(_language);
  _edit2 = _interopRequireDefault(_edit2);
  var _staticBlock;
  let _init_language, _init_extra_language;
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
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  class AdminController extends _controller.default {
    constructor() {
      super(...arguments);
      _init_extra_language(this);
    }
    /**
     * @private
     * @type {Language}
     */
    language = _init_language(this);
    checkAccessGlobal() {
      if (this.getUser().isAdmin()) {
        return true;
      }
      return false;
    }

    // noinspection JSUnusedGlobalSymbols
    async actionPage(options) {
      const page = options.page;
      if (options.options) {
        options = {
          ...Espo.Utils.parseUrlOptionsParam(options.options),
          ...options
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
      if (defs.view && !defs.recordView) {
        this.main(defs.view, options);
        return;
      }
      if (!defs.recordView) {
        throw new Espo.Exceptions.NotFound();
      }
      const model = this.getSettingsModel();
      await model.fetch();
      model.id = '1';
      const view = defs.view ?? 'views/settings/edit';
      const ViewClass = await Espo.loader.requirePromise(view);
      if (!_edit2.default.isPrototypeOf(ViewClass)) {
        throw new Error("View should inherit views/edit.");
      }
      const editView = new ViewClass({
        model: model,
        headerTemplate: 'admin/settings/headers/page',
        recordView: defs.recordView,
        page: page,
        label: defs.label,
        optionsToPass: ['page', 'label']
      });
      this.main(editView);
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
      const view = new _index.default();
      this.main(view, null, view => {
        view.render();
        this.listenTo(view, 'clear-cache', this.clearCache);
        this.listenTo(view, 'rebuild', this.rebuild);
      }, {
        useStored: isReturn,
        key: key
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionUsers() {
      this.getRouter().dispatch('User', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPortalUsers() {
      this.getRouter().dispatch('PortalUser', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionApiUsers() {
      this.getRouter().dispatch('ApiUser', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionTeams() {
      this.getRouter().dispatch('Team', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionRoles() {
      this.getRouter().dispatch('Role', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPortalRoles() {
      this.getRouter().dispatch('PortalRole', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPortals() {
      this.getRouter().dispatch('Portal', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionLeadCapture() {
      this.getRouter().dispatch('LeadCapture', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailFilters() {
      this.getRouter().dispatch('EmailFilter', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionGroupEmailFolders() {
      this.getRouter().dispatch('GroupEmailFolder', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailTemplates() {
      this.getRouter().dispatch('EmailTemplate', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPdfTemplates() {
      this.getRouter().dispatch('Template', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionDashboardTemplates() {
      this.getRouter().dispatch('DashboardTemplate', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionWebhooks() {
      this.getRouter().dispatch('Webhook', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionLayoutSets() {
      this.getRouter().dispatch('LayoutSet', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionWorkingTimeCalendar() {
      this.getRouter().dispatch('WorkingTimeCalendar', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAttachments() {
      this.getRouter().dispatch('Attachment', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAuthenticationProviders() {
      this.getRouter().dispatch('AuthenticationProvider', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAddressCountries() {
      this.getRouter().dispatch('AddressCountry', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionEmailAddresses() {
      this.getRouter().dispatch('EmailAddress', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPhoneNumbers() {
      this.getRouter().dispatch('PhoneNumber', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPersonalEmailAccounts() {
      this.getRouter().dispatch('EmailAccount', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionGroupEmailAccounts() {
      this.getRouter().dispatch('InboundEmail', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionActionHistory() {
      this.getRouter().dispatch('ActionHistoryRecord', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionImport() {
      this.getRouter().dispatch('Import', 'index', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionLayouts(options) {
      const scope = options.scope || null;
      const type = options.type || null;
      const em = options.em || false;
      this.main('views/admin/layouts/index', {
        scope: scope,
        type: type,
        em: em
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionLabelManager(options) {
      const scope = options.scope || null;
      const language = options.language || null;
      this.main('views/admin/label-manager/index', {
        scope: scope,
        language: language
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionTemplateManager(options) {
      const name = options.name || null;
      this.main('views/admin/template-manager/index', {
        name: name
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionFieldManager(options) {
      const scope = options.scope || null;
      const field = options.field || null;
      this.main('views/admin/field-manager/index', {
        scope: scope,
        field: field
      });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @param {Record} options
     */
    actionEntityManager(options) {
      const scope = options.scope || null;
      if (scope && options.edit) {
        this.main('views/admin/entity-manager/edit', {
          scope: scope
        });
        return;
      }
      if (options.create) {
        this.main('views/admin/entity-manager/edit');
        return;
      }
      if (scope && options.formula) {
        this.main('views/admin/entity-manager/formula', {
          scope: scope,
          type: options.type
        });
        return;
      }
      if (scope) {
        this.main('views/admin/entity-manager/scope', {
          scope: scope
        });
        return;
      }
      this.main('views/admin/entity-manager/index');
    }

    // noinspection JSUnusedGlobalSymbols
    actionLinkManager(options) {
      const scope = options.scope || null;
      this.main('views/admin/link-manager/index', {
        scope: scope
      });
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
        const searchManager = new _searchManager.default(collection, {
          storageKey: 'list'
        });
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
        const searchManager = new _searchManager.default(collection, {
          storageKey: 'list'
        });
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
      this.getRouter().dispatch('AppSecret', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionOAuthProviders() {
      this.getRouter().dispatch('OAuthProvider', 'list', {
        fromAdmin: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionJobs() {
      this.collectionFactory.create('Job', collection => {
        const searchManager = new _searchManager.default(collection, {
          storageKey: 'list'
        });
        searchManager.loadStored();
        collection.where = searchManager.getWhere();
        collection.maxSize = this.getConfig().get('recordsPerPage') || collection.maxSize;
        this.main('views/admin/job/list', {
          scope: 'Job',
          collection: collection,
          searchManager: searchManager
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    actionAppLog() {
      this.collectionFactory.create('AppLogRecord', collection => {
        const searchManager = new _searchManager.default(collection, {
          storageKey: 'list'
        });
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
      this.main('views/admin/integrations/index', {
        integration: integration
      });
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
      Espo.Ajax.postRequest('Admin/rebuild').then(() => {
        const msg = this.language.translate('Rebuild has been done', 'labels', 'Admin');
        Espo.Ui.success(msg);
        this.rebuildRunning = false;
      }).catch(() => {
        this.rebuildRunning = false;
      });
    }
    clearCache() {
      if (this.clearCacheRunning) {
        return;
      }
      this.clearCacheRunning = true;
      Espo.Ui.notify(this.language.translate('pleaseWait', 'messages'));
      Espo.Ajax.postRequest('Admin/clearCache').then(() => {
        const msg = this.language.translate('Cache has been cleared', 'labels', 'Admin');
        Espo.Ui.success(msg);
        this.clearCacheRunning = false;
      }).catch(() => {
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
    static #_ = _staticBlock = () => [_init_language, _init_extra_language] = _applyDecs(this, [], [[(0, _di.inject)(_language.default), 0, "language"]], 0, void 0, _controller.default).e;
  }
  _staticBlock();
  var _default = _exports.default = AdminController;
});
//# sourceMappingURL=admin.js.map ;