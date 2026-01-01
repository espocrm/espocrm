define("helpers/record-modal", ["exports", "di", "metadata", "acl-manager", "router", "helpers/site/modal-bar-provider", "views/modals/edit", "language"], function (_exports, _di, _metadata, _aclManager, _router, _modalBarProvider, _edit, _language) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _metadata = _interopRequireDefault(_metadata);
  _aclManager = _interopRequireDefault(_aclManager);
  _router = _interopRequireDefault(_router);
  _modalBarProvider = _interopRequireDefault(_modalBarProvider);
  _edit = _interopRequireDefault(_edit);
  _language = _interopRequireDefault(_language);
  var _staticBlock;
  let _init_metadata, _init_extra_metadata, _init_acl, _init_extra_acl, _init_router, _init_extra_router, _init_language, _init_extra_language, _init_modalBarProvider, _init_extra_modalBarProvider;
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
  /**
   * A record-modal helper. Use to render the quick view and quick edit modals.
   */
  class RecordModalHelper {
    constructor() {
      _init_extra_modalBarProvider(this);
    }
    /**
     * @private
     * @type {Metadata}
     */
    metadata = _init_metadata(this);

    /**
     * @private
     * @type {AclManager}
     */
    acl = (_init_extra_metadata(this), _init_acl(this));

    /**
     * @private
     * @type {Router}
     */
    router = (_init_extra_acl(this), _init_router(this));

    /**
     * @private
     * @type {Language}
     */
    language = (_init_extra_router(this), _init_language(this));

    /**
     * @private
     * @type {ModalBarProvider}
     */
    modalBarProvider = (_init_extra_language(this), _init_modalBarProvider(this));

    /**
     * Show the 'detail' modal.
     *
     * @param {import('view').default} view
     * @param {{
     *   id: string,
     *   entityType: string,
     *   model?: import('model').default,
     *   editDisabled?: boolean,
     *   removeDisabled?: boolean,
     *   fullFormDisabled?: boolean,
     *   rootUrl?: string,
     *   fullFormUrl?: string,
     *   layoutName?: string,
     *   beforeSave?: function(import('model').default, Record),
     *   afterSave?: function(import('model').default, {bypassClose: boolean} & Record),
     *   beforeDestroy?: function(import('model').default),
     *   afterDestroy?: function(import('model').default),
     *   beforeRender?: function(import('views/modals/detail').default),
     *   onClose?: function(),
     *   collapseDisabled?: boolean,
     * }} params
     * @return {Promise<import('views/modals/detail').default>}
     */
    async showDetail(view, params) {
      const id = params.id;
      // noinspection JSUnresolvedReference
      const entityType = params.entityType || params.scope;
      const model = params.model;
      if (!id || !entityType) {
        console.error("Bad data.");
        return Promise.reject();
      }
      if (model && !this.acl.checkScope(model.entityType, 'read')) {
        return Promise.reject();
      }
      const viewName = this.metadata.get(`clientDefs.${entityType}.modalViews.detail`) || 'views/modals/detail';
      Espo.Ui.notifyWait();

      /** @type {module:views/modals/detail~options & module:views/modal~Options} */
      const options = {
        entityType: entityType,
        model: model,
        id: id,
        quickEditDisabled: params.editDisabled,
        rootUrl: params.rootUrl,
        removeDisabled: params.removeDisabled,
        layoutName: params.layoutName,
        fullFormDisabled: params.fullFormDisabled,
        fullFormUrl: params.fullFormUrl,
        collapseDisabled: params.collapseDisabled
      };
      Espo.Ui.notifyWait();
      const modalView = /** @type {import('views/modals/detail').default} */
      await view.createView('modal', viewName, options);

      // @todo Revise.
      view.listenToOnce(modalView, 'remove', () => view.clearView('modal'));
      if (params.beforeSave) {
        modalView.listenTo(modalView, 'before:save', (model, o) => {
          params.beforeSave(model, o);
        });
      }
      if (params.afterSave) {
        modalView.listenTo(modalView, 'after:save', (model, /** Record */o) => {
          params.afterSave(model, {
            ...o
          });
        });
      }
      if (params.beforeDestroy) {
        modalView.listenToOnce(modalView, 'before:delete', model => params.beforeDestroy(model));
      }
      if (params.afterDestroy) {
        modalView.listenToOnce(modalView, 'after:delete', model => params.afterDestroy(model));
      }
      if (params.beforeRender) {
        params.beforeRender(modalView);
      }
      if (params.onClose) {
        view.listenToOnce(modalView, 'close', () => params.onClose());
      }
      await modalView.render();
      Espo.Ui.notify();
      return modalView;
    }

    /**
     * Show the 'edit' modal.
     *
     * @param {import('view').default} view
     * @param {{
     *   entityType: string,
     *   id?: string,
     *   model?: import('model').default,
     *   rootUrl?: string,
     *   fullFormDisabled?: boolean,
     *   fullFormUrl?: string,
     *   returnUrl?: string,
     *   layoutName?: string,
     *   beforeSave?: function(import('model').default, Record),
     *   afterSave?: function(import('model').default, {bypassClose: boolean} & Record),
     *   beforeRender?: function(import('views/modals/edit').default),
     *   onClose?: function(),
     *   returnDispatchParams?: {
     *       controller: string,
     *       action: string|null,
     *       options: {isReturn?: boolean} & Record,
     *   },
     *   collapseDisabled?: boolean,
     * }} params
     * @return {Promise<import('views/modals/edit').default>}
     * @since 9.1.0
     */
    async showEdit(view, params) {
      const id = params.id;
      const entityType = params.entityType;
      const model = params.model;
      if (this.modalBarProvider.get()) {
        const barView = this.modalBarProvider.get();
        const foundModalView = barView.getModalViewList().find(view => {
          return view instanceof _edit.default && view.id === id && view.entityType === entityType;
        });
        if (foundModalView) {
          const message = this.language.translate('sameRecordIsAlreadyBeingEdited', 'messages');
          Espo.Ui.warning(message);
          throw new Error();
        }
      }
      const viewName = this.metadata.get(`clientDefs.${entityType}.modalViews.edit`) || 'views/modals/edit';

      /** @type {module:views/modals/edit~options & module:views/modal~Options} */
      const options = {
        entityType: entityType,
        id: id,
        model: model,
        fullFormDisabled: params.fullFormDisabled,
        returnUrl: params.returnUrl || this.router.getCurrentUrl(),
        returnDispatchParams: params.returnDispatchParams,
        layoutName: params.layoutName,
        fullFormUrl: params.fullFormUrl,
        collapseDisabled: params.collapseDisabled
      };
      if (params.rootUrl) {
        options.rootUrl = params.rootUrl;
      }
      Espo.Ui.notifyWait();
      const modalView = /** @type {import('views/modals/edit').default} */
      await view.createView('modal', viewName, options);

      // @todo Revise.
      modalView.listenToOnce(modalView, 'remove', () => view.clearView('modal'));
      if (params.beforeSave) {
        modalView.listenTo(modalView, 'before:save', (model, o) => {
          params.beforeSave(model, o);
        });
      }
      if (params.afterSave) {
        modalView.listenTo(modalView, 'after:save', (model, /** Record */o) => {
          params.afterSave(model, {
            ...o
          });
        });
      }
      if (params.beforeRender) {
        params.beforeRender(modalView);
      }
      if (params.onClose) {
        view.listenToOnce(modalView, 'close', () => params.onClose());
      }
      await modalView.render();
      Espo.Ui.notify();
      return modalView;
    }

    /**
     * Show the 'create' modal.
     *
     * @param {import('view').default} view
     * @param {{
     *   entityType: string,
     *   rootUrl?: string,
     *   fullFormDisabled?: boolean,
     *   fullFormUrl?: string,
     *   returnUrl?: string,
     *   relate?: model:model~setRelateItem | model:model~setRelateItem[],
     *   attributes?: Record.<string, *>,
     *   afterSave?: function(import('model').default, {bypassClose: boolean} & Record),
     *   beforeRender?: function(import('views/modals/edit').default),
     *   onClose?: function(),
     *   focusForCreate?: boolean,
     *   layoutName?: string,
     *   returnDispatchParams?: {
     *       controller: string,
     *       action: string|null,
     *       options: {isReturn?: boolean} & Record,
     *   },
     *   collapseDisabled?: boolean,
     * }} params
     * @return {Promise<import('views/modals/edit').default>}
     * @since 9.1.0
     */
    async showCreate(view, params) {
      const entityType = params.entityType;
      const viewName = this.metadata.get(`clientDefs.${entityType}.modalViews.edit`) || 'views/modals/edit';

      /** @type {module:views/modals/edit~options & module:views/modal~Options} */
      const options = {
        entityType: entityType,
        fullFormDisabled: params.fullFormDisabled,
        returnUrl: params.returnUrl || this.router.getCurrentUrl(),
        returnDispatchParams: params.returnDispatchParams,
        relate: params.relate,
        attributes: params.attributes,
        focusForCreate: params.focusForCreate,
        layoutName: params.layoutName,
        fullFormUrl: params.fullFormUrl,
        collapseDisabled: params.collapseDisabled
      };
      if (params.rootUrl) {
        options.rootUrl = params.rootUrl;
      }
      Espo.Ui.notifyWait();
      const modalView = /** @type {import('views/modals/edit').default} */
      await view.createView('modal', viewName, options);

      // @todo Revise.
      modalView.listenToOnce(modalView, 'remove', () => view.clearView('modal'));
      if (params.afterSave) {
        modalView.listenTo(modalView, 'after:save', (model, /** Record */o) => {
          params.afterSave(model, {
            ...o
          });
        });
      }
      if (params.beforeRender) {
        params.beforeRender(modalView);
      }
      if (params.onClose) {
        view.listenToOnce(modalView, 'close', () => params.onClose());
      }
      await modalView.render();
      Espo.Ui.notify();
      return modalView;
    }
    static #_ = _staticBlock = () => [_init_metadata, _init_extra_metadata, _init_acl, _init_extra_acl, _init_router, _init_extra_router, _init_language, _init_extra_language, _init_modalBarProvider, _init_extra_modalBarProvider] = _applyDecs(this, [], [[(0, _di.inject)(_metadata.default), 0, "metadata"], [(0, _di.inject)(_aclManager.default), 0, "acl"], [(0, _di.inject)(_router.default), 0, "router"], [(0, _di.inject)(_language.default), 0, "language"], [(0, _di.inject)(_modalBarProvider.default), 0, "modalBarProvider"]]).e;
  }
  _staticBlock();
  var _default = _exports.default = RecordModalHelper;
});
//# sourceMappingURL=record-modal.js.map ;