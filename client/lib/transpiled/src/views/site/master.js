define("views/site/master", ["exports", "view", "jquery", "views/collapsed-modal-bar", "di", "helpers/site/shortcut-manager"], function (_exports, _view, _jquery, _collapsedModalBar, _di, _shortcutManager) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _jquery = _interopRequireDefault(_jquery);
  _collapsedModalBar = _interopRequireDefault(_collapsedModalBar);
  _shortcutManager = _interopRequireDefault(_shortcutManager);
  var _staticBlock;
  let _init_shortcutManager, _init_extra_shortcutManager;
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
  /** @module views/site/master */
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  class MasterSiteView extends _view.default {
    constructor() {
      super(...arguments);
      _init_extra_shortcutManager(this);
    }
    template = 'site/master';
    views = {
      header: {
        id: 'header',
        view: 'views/site/header'
      },
      main: {
        id: 'main',
        view: false
      },
      footer: {
        fullSelector: 'body > footer',
        view: 'views/site/footer'
      }
    };

    /**
     * @type {string}
     */
    currentViewKey;

    /**
     * @type {string}
     */
    currentName;

    /**
     * @internal
     * @type {CollapsedModalBarView}
     */
    collapsedModalBarView;

    /**
     * Injected to be loaded early.
     *
     * @private
     * @type {ShortcutManager}
     */
    shortcutManager = _init_shortcutManager(this);
    showLoadingNotification() {
      Espo.Ui.notifyWait();
    }
    hideLoadingNotification() {
      Espo.Ui.notify(false);
    }
    setup() {
      (0, _jquery.default)(window).on('resize.' + this.cid, () => {
        this.adjustContent();
      });
      this.collapsedModalBarView = new _collapsedModalBar.default();
      this.assignView('collapsedModalBar', this.collapsedModalBarView, '> .collapsed-modal-bar');
    }

    /**
     * @return {Bull.View|null}
     */
    getMainView() {
      return this.getView('main');
    }
    onRemove() {
      (0, _jquery.default)(window).off('resize.' + this.cid);
    }
    afterRender() {
      /** @type {Object.<string, Record>} */
      const params = this.getThemeManager().getParam('params');
      const body = document.body;
      for (const param of Object.keys(params)) {
        body.dataset[param] = this.getThemeManager().getParam(param);
      }
      body.dataset.isDark = this.getThemeManager().getParam('isDark') ?? false;
      body.dataset.themeName = this.getThemeManager().getName();
      const footerView = this.getView('footer');
      if (footerView) {
        const html = footerView.$el.html() || '';
        if ((html.match(/espocrm/gi) || []).length < 2) {
          const text = 'PHAgY2xhc3M9ImNyZWRpdCBzbWFsbCI+JmNvcHk7IDxhIGhyZWY9Imh0dHA6Ly93d3cuZXNwb2Nyb' + 'S5jb20iPkVzcG9DUk08L2E+PC9wPg==';
          let decText;
          if (typeof window.atob === "function") {
            decText = window.atob(text);
          } else if (typeof atob === "function") {
            decText = atob(text);
          }
          if (decText) {
            footerView.$el.html(decText);
          }
        }
      }
      this.$content = this.$el.find('> #content');
      this.adjustContent();
      const extensions = this.getHelper().getAppParam('extensions') || [];
      if (this.getConfig().get('maintenanceMode')) {
        this.createView('dialog', 'views/modal', {
          templateContent: '<div class="text-danger">{{complexText viewObject.options.message}}</div>',
          headerText: this.translate('maintenanceMode', 'fields', 'Settings'),
          backdrop: true,
          message: this.translate('maintenanceMode', 'messages'),
          buttonList: [{
            name: 'close',
            label: this.translate('Close')
          }]
        }, view => {
          view.render();
        });
      } else if (this.getHelper().getAppParam('auth2FARequired')) {
        this.createView('dialog', 'views/modals/auth2fa-required', {}, view => {
          view.render();
        });
      } else if (extensions.length !== 0) {
        this.processExtensions(extensions);
      }
    }
    adjustContent() {
      if (!this.isRendered()) {
        return;
      }
      if (window.innerWidth < this.getThemeManager().getParam('screenWidthXs')) {
        this.isSmallScreen = true;
        let height = window.innerHeight - this.$content.get(0).getBoundingClientRect().top;
        const $navbarCollapse = (0, _jquery.default)('#navbar .navbar-body');
        if ($navbarCollapse.hasClass('in') || $navbarCollapse.hasClass('collapsing')) {
          height += $navbarCollapse.height();
        }
        const footerHeight = (0, _jquery.default)('#footer').height() || 26;
        height -= footerHeight;
        if (height <= 0) {
          this.$content.css('minHeight', '');
          return;
        }
        this.$content.css('minHeight', height + 'px');
        return;
      }
      if (this.isSmallScreen) {
        this.$content.css('minHeight', '');
      }
      this.isSmallScreen = false;
    }

    /**
     * @param {{
     *     name: string,
     *     licenseStatus: string,
     *     licenseStatusMessage:? string,
     *     notify: boolean,
     * }[]} list
     */
    processExtensions(list) {
      const messageList = [];
      list.forEach(item => {
        if (!item.notify) {
          return;
        }
        const message = item.licenseStatusMessage ?? 'extensionLicense' + Espo.Utils.upperCaseFirst(Espo.Utils.hyphenToCamelCase(item.licenseStatus.toLowerCase()));
        messageList.push(this.translate(message, 'messages').replace('{name}', item.name));
      });
      if (!messageList.length) {
        return;
      }
      let message = messageList.join('\n\n');
      message = this.getHelper().transformMarkdownText(message);
      const dialog = new Espo.Ui.Dialog({
        backdrop: 'static',
        buttonList: [{
          name: 'close',
          text: this.translate('Close'),
          className: 'btn-s-wide',
          onClick: () => dialog.close()
        }],
        className: 'dialog-confirm text-danger',
        body: message.toString()
      });
      dialog.show();
    }
    static #_ = _staticBlock = () => [_init_shortcutManager, _init_extra_shortcutManager] = _applyDecs(this, [], [[(0, _di.inject)(_shortcutManager.default), 0, "shortcutManager"]], 0, void 0, _view.default).e;
  }
  _staticBlock();
  var _default = _exports.default = MasterSiteView;
});
//# sourceMappingURL=master.js.map ;