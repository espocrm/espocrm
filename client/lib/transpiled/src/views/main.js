define("views/main", ["exports", "view", "di", "helpers/site/shortcut-manager"], function (_exports, _view, _di, _shortcutManager) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
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
  /** @module views/main */
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  /**
   * A base main view. The detail, edit, list views to be extended from.
   */
  class MainView extends _view.default {
    /**
     * A scope name.
     *
     * @type {string} scope
     */
    scope = '';

    /**
     * A name.
     *
     * @type {string} name
     */
    name = '';

    /**
     * A top-right menu item (button or dropdown action).
     * Handled by a class method `action{Action}`, a click handler or a handler class.
     *
     * @typedef {Object} module:views/main~MenuItem
     *
     * @property {string} [name] A name.
     * @property {string} [action] An action.
     * @property {string} [link] A link.
     * @property {string} [label] A translatable label.
     * @property {string} [labelTranslation] A label translation path.
     * @property {'default'|'danger'|'success'|'warning'} [style] A style. Only for buttons.
     * @property {boolean} [hidden] Hidden.
     * @property {boolean} [disabled] Disabled.
     * @property {Object.<string,string|number|boolean>} [data] Data attribute values.
     * @property {string} [title] A title.
     * @property {string} [iconHtml] An icon HTML.
     * @property {string} [iconClass] An icon class.
     * @property {string} [html] An HTML.
     * @property {string} [text] A text.
     * @property {string} [className] An additional class name. Only for buttons.
     * @property {'create'|'read'|'edit'|'stream'|'delete'} [acl] Access to a record (or a scope if `aclScope` specified)
     *   required for a menu item.
     * @property {string} [aclScope] A scope to check access to with the `acl` parameter.
     * @property {string} [configCheck] A config parameter defining a menu item availability.
     *   If starts with `!`, then the result is negated.
     * @property {module:utils~AccessDefs[]} [accessDataList] Access definitions.
     * @property {string} [handler] A handler.
     * @property {string} [initFunction] An init method in the handler.
     * @property {string} [actionFunction] An action method in the handler.
     * @property {string} [checkVisibilityFunction] A method in the handler that determine whether an item is available.
     * @property {function()} [onClick] A click handler.
     */

    /**
     * Top-right menu definitions.
     *
     * @type {{
     *     buttons: module:views/main~MenuItem[],
     *     dropdown: module:views/main~MenuItem[],
     *     actions: module:views/main~MenuItem[],
     * }} menu
     * @private
     * @internal
     */
    menu = {};

    /**
     * @private
     * @type {JQuery|null}
     */
    $headerActionsContainer = null;

    /**
     * A shortcut-key => action map.
     *
     * @protected
     * @type {?Object.<string, string|function (KeyboardEvent): void>}
     */
    shortcutKeys = null;

    /**
     * @private
     * @type {ShortcutManager}
     */
    shortcutManager = _init_shortcutManager(this);

    /** @inheritDoc */
    events = (_init_extra_shortcutManager(this), {
      /** @this MainView */
      'click .action': function (e) {
        Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget, {
          actionItems: [...this.menu.buttons, ...this.menu.dropdown],
          className: 'main-header-manu-action'
        });
      }
    });
    lastUrl;

    /** @inheritDoc */
    init() {
      this.scope = this.options.scope || this.scope;
      this.menu = {};
      this.options.params = this.options.params || {};
      if (this.name && this.scope) {
        const key = this.name.charAt(0).toLowerCase() + this.name.slice(1);
        this.menu = this.getMetadata().get(['clientDefs', this.scope, 'menu', key]) || {};
      }

      /**
       * @private
       * @type {string[]}
       */
      this.headerActionItemTypeList = ['buttons', 'dropdown', 'actions'];
      this.menu = Espo.Utils.cloneDeep(this.menu);
      let globalMenu = {};
      if (this.name) {
        globalMenu = Espo.Utils.cloneDeep(this.getMetadata().get(['clientDefs', 'Global', 'menu', this.name.charAt(0).toLowerCase() + this.name.slice(1)]) || {});
      }
      this._reRenderHeaderOnSync = false;
      this._menuHandlers = {};
      this.headerActionItemTypeList.forEach(type => {
        this.menu[type] = this.menu[type] || [];
        this.menu[type] = this.menu[type].concat(globalMenu[type] || []);
        const itemList = this.menu[type];
        itemList.forEach(item => {
          const viewObject = this;

          // @todo Set _reRenderHeaderOnSync to true if `acl` is set `ascScope` is not set?
          //     Set _reRenderHeaderOnSync in `addMenuItem` method.

          if ((item.initFunction || item.checkVisibilityFunction) && (item.handler || item.data && item.data.handler)) {
            this.wait(new Promise(resolve => {
              const handler = item.handler || item.data.handler;
              Espo.loader.require(handler, Handler => {
                const handler = new Handler(viewObject);
                const name = item.name || item.action;
                if (name) {
                  this._menuHandlers[name] = handler;
                }
                if (item.initFunction) {
                  handler[item.initFunction].call(handler);
                }
                if (item.checkVisibilityFunction && this.model) {
                  this._reRenderHeaderOnSync = true;
                }
                resolve();
              });
            }));
          }
        });
      });
      if (this.model) {
        this.whenReady().then(() => {
          if (!this._reRenderHeaderOnSync) {
            return;
          }
          this.listenTo(this.model, 'sync', () => {
            if (!this.getHeaderView()) {
              return;
            }
            this.getHeaderView().reRender();
          });
        });
      }
      this.updateLastUrl();
      this.on('after:render-internal', () => {
        this.$headerActionsContainer = this.$el.find('.page-header .header-buttons');
      });
      this.on('header-rendered', () => {
        this.$headerActionsContainer = this.$el.find('.page-header .header-buttons');
        this.adjustButtons();
      });
      this.on('after:render', () => this.adjustButtons());
      if (this.shortcutKeys) {
        this.shortcutKeys = Espo.Utils.cloneDeep(this.shortcutKeys);
      }
    }

    /**
     * @private
     */
    initShortcuts() {
      if (!this.shortcutKeys) {
        return;
      }
      this.shortcutManager.add(this, this.shortcutKeys);
      this.once('remove', () => {
        this.shortcutManager.remove(this);
      });
    }
    setupFinal() {
      this.initShortcuts();
    }

    /**
     * Update a last history URL.
     */
    updateLastUrl() {
      this.lastUrl = this.getRouter().getCurrentUrl();
    }

    /**
     * @internal
     * @returns {{
     *     buttons?: module:views/main~MenuItem[],
     *     dropdown?: module:views/main~MenuItem[],
     *     actions?: module:views/main~MenuItem[],
     * }}
     */
    getMenu() {
      if (this.menuDisabled || !this.menu) {
        return {};
      }
      const menu = {};
      this.headerActionItemTypeList.forEach(type => {
        (this.menu[type] || []).forEach(item => {
          if (item === false) {
            menu[type].push(false);
            return;
          }
          item = Espo.Utils.clone(item);
          menu[type] = menu[type] || [];
          if (!Espo.Utils.checkActionAvailability(this.getHelper(), item)) {
            return;
          }
          if (!Espo.Utils.checkActionAccess(this.getAcl(), this.model || this.scope, item)) {
            return;
          }
          if (item.accessDataList) {
            if (!Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
              return;
            }
          }
          item.name = item.name || item.action;
          item.action = item.action || null;
          if (this._menuHandlers[item.name] && item.checkVisibilityFunction) {
            const handler = this._menuHandlers[item.name];
            if (!handler[item.checkVisibilityFunction](item.name)) {
              return;
            }
          }
          if (item.labelTranslation) {
            item.html = this.getHelper().escapeString(this.getLanguage().translatePath(item.labelTranslation));
          }
          menu[type].push(item);
        });
      });
      return menu;
    }

    /**
     * Get a header HTML. To be overridden.
     *
     * @returns {string} HTML.
     */
    getHeader() {
      return '';
    }

    /**
     * Build a header HTML. To be called from the #getHeader method.
     * Beware of XSS.
     *
     * @param {(string|Element|JQuery)[]} itemList A breadcrumb path. Like: Account > Name > edit.
     * @returns {string} HTML
     */
    buildHeaderHtml(itemList) {
      const $itemList = itemList.map(item => {
        return $('<div>').addClass('breadcrumb-item').append(item);
      });
      const $div = $('<div>').addClass('header-breadcrumbs');
      $itemList.forEach(($item, i) => {
        $div.append($item);
        if (i === $itemList.length - 1) {
          return;
        }
        $div.append($('<div>').addClass('breadcrumb-separator').append($('<span>')));
      });
      return $div.get(0).outerHTML;
    }

    /**
     * Get an icon HTML.
     *
     * @returns {string} HTML
     */
    getHeaderIconHtml() {
      return this.getHelper().getScopeColorIconHtml(this.scope);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'showModal'.
     *
     * @todo Revise. To be removed?
     *
     * @param {Object} data
     */
    actionShowModal(data) {
      const view = data.view;
      if (!view) {
        return;
      }
      this.createView('modal', view, {
        model: this.model,
        collection: this.collection
      }, view => {
        view.render();
        this.listenTo(view, 'after:save', () => {
          if (this.model) {
            this.model.fetch();
          }
          if (this.collection) {
            this.collection.fetch();
          }
        });
      });
    }

    /**
     * Update a menu item.
     *
     * @param {string} name An item name.
     * @param {module:views/main~MenuItem} item New item definitions to write.
     * @param {boolean} [doNotReRender=false] Skip re-render.
     *
     * @since 8.2.0
     */
    updateMenuItem(name, item, doNotReRender) {
      const actionItem = this._getHeaderActionItem(name);
      if (!actionItem) {
        return;
      }
      for (const key in item) {
        actionItem[key] = item[key];
      }
      if (doNotReRender) {
        return;
      }
      if (this.isRendered()) {
        this.getHeaderView().reRender();
        return;
      }
      if (this.isBeingRendered()) {
        this.whenRendered().then(() => {
          this.getHeaderView().reRender();
        });
      }
    }

    /**
     * Add a menu item.
     *
     * @param {'buttons'|'dropdown'} type A type.
     * @param {module:views/main~MenuItem|false} item Item definitions.
     * @param {boolean} [toBeginning=false] To beginning.
     * @param {boolean} [doNotReRender=false] Skip re-render.
     */
    addMenuItem(type, item, toBeginning, doNotReRender) {
      if (item) {
        item.name = item.name || item.action || Espo.Utils.generateId();
        const name = item.name;
        let index = -1;
        this.menu[type].forEach((data, i) => {
          data = data || {};
          if (data.name === name) {
            index = i;
          }
        });
        if (~index) {
          this.menu[type].splice(index, 1);
        }
      }
      let method = 'push';
      if (toBeginning) {
        method = 'unshift';
      }
      this.menu[type][method](item);
      if (!doNotReRender && this.isRendered()) {
        this.getHeaderView().reRender();
        return;
      }
      if (!doNotReRender && this.isBeingRendered()) {
        this.once('after:render', () => {
          this.getHeaderView().reRender();
        });
      }
    }

    /**
     * Remove a menu item.
     *
     * @param {string} name An item name.
     * @param {boolean} [doNotReRender] Skip re-render.
     */
    removeMenuItem(name, doNotReRender) {
      let index = -1;
      let type = false;
      this.headerActionItemTypeList.forEach(t => {
        (this.menu[t] || []).forEach((item, i) => {
          item = item || {};
          if (item.name === name) {
            index = i;
            type = t;
          }
        });
      });
      if (~index && type) {
        this.menu[type].splice(index, 1);
      }
      if (!doNotReRender && this.isRendered()) {
        this.getHeaderView().reRender();
        return;
      }
      if (!doNotReRender && this.isBeingRendered()) {
        this.once('after:render', () => {
          this.getHeaderView().reRender();
        });
        return;
      }
      if (doNotReRender && this.isRendered()) {
        this.$headerActionsContainer.find('[data-name="' + name + '"]').remove();
      }
    }

    /**
     * Disable a menu item.
     *
     * @param {string} name A name.
     */
    disableMenuItem(name) {
      const item = this._getHeaderActionItem(name);
      if (item) {
        item.disabled = true;
      }
      const process = () => {
        this.$headerActionsContainer.find(`[data-name="${name}"]`).addClass('disabled').attr('disabled');
      };
      if (this.isBeingRendered()) {
        this.whenRendered().then(() => process());
        return;
      }
      if (!this.isRendered()) {
        return;
      }
      process();
    }

    /**
     * Enable a menu item.
     *
     * @param {string} name A name.
     */
    enableMenuItem(name) {
      const item = this._getHeaderActionItem(name);
      if (item) {
        item.disabled = false;
      }
      const process = () => {
        this.$headerActionsContainer.find(`[data-name="${name}"]`).removeClass('disabled').removeAttr('disabled');
      };
      if (this.isBeingRendered()) {
        this.whenRendered().then(() => process());
        return;
      }
      if (!this.isRendered()) {
        return;
      }
      process();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'navigateToRoot'.
     *
     * @param {Object} data
     * @param {MouseEvent} event
     */
    actionNavigateToRoot(data, event) {
      event.stopPropagation();
      this.getRouter().checkConfirmLeaveOut(() => {
        const rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
        this.getRouter().navigate(rootUrl, {
          trigger: true,
          isReturn: true
        });
      });
    }

    /**
     * @private
     * @param {string} name
     * @return {module:views/main~MenuItem|undefined}
     */
    _getHeaderActionItem(name) {
      for (const type of this.headerActionItemTypeList) {
        if (!this.menu[type]) {
          continue;
        }
        for (const item of this.menu[type]) {
          if (item && item.name === name) {
            return item;
          }
        }
      }
      return undefined;
    }

    /**
     * Hide a menu item.
     *
     * @param {string} name A name.
     */
    hideHeaderActionItem(name) {
      const item = this._getHeaderActionItem(name);
      if (item) {
        item.hidden = true;
      }
      if (!this.isRendered()) {
        return;
      }
      this.$headerActionsContainer.find(`li > .action[data-name="${name}"]`).parent().addClass('hidden');
      this.$headerActionsContainer.find(`a.action[data-name="${name}"]`).addClass('hidden');
      this.controlMenuDropdownVisibility();
      this.adjustButtons();
      if (this.getHeaderView()) {
        this.getHeaderView().trigger('action-item-update');
      }
    }

    /**
     * Show a hidden menu item.
     *
     * @param {string} name A name.
     */
    showHeaderActionItem(name) {
      const item = this._getHeaderActionItem(name);
      if (item) {
        item.hidden = false;
      }
      const processUi = () => {
        const $dropdownItem = this.$headerActionsContainer.find(`li > .action[data-name="${name}"]`).parent();
        const $button = this.$headerActionsContainer.find(`a.action[data-name="${name}"]`);

        // Item can be available but not rendered as it was skipped by access check in getMenu.
        if (item && !$dropdownItem.length && !$button.length) {
          if (this.getHeaderView()) {
            this.getHeaderView().reRender();
          }
          return;
        }
        $dropdownItem.removeClass('hidden');
        $button.removeClass('hidden');
        this.controlMenuDropdownVisibility();
        this.adjustButtons();
        if (this.getHeaderView()) {
          this.getHeaderView().trigger('action-item-update');
        }
      };
      if (!this.isRendered()) {
        if (this.isBeingRendered()) {
          this.whenRendered().then(() => processUi());
        }
        return;
      }
      processUi();
    }

    /**
     * Whether a menu has any non-hidden dropdown items.
     *
     * @private
     * @returns {boolean}
     */
    hasMenuVisibleDropdownItems() {
      let hasItems = false;
      (this.menu.dropdown || []).forEach(item => {
        if (!item.hidden) {
          hasItems = true;
        }
      });
      return hasItems;
    }

    /**
     * @private
     */
    controlMenuDropdownVisibility() {
      const $group = this.$headerActionsContainer.find('.dropdown-group');
      if (this.hasMenuVisibleDropdownItems()) {
        $group.removeClass('hidden');
        $group.find('> button').removeClass('hidden');
        return;
      }
      $group.addClass('hidden');
      $group.find('> button').addClass('hidden');
    }

    /**
     * @protected
     * @return {module:views/header}
     */
    getHeaderView() {
      return this.getView('header');
    }

    /**
     * @private
     */
    adjustButtons() {
      const $buttons = this.$headerActionsContainer.find('.btn');
      $buttons.removeClass('radius-left').removeClass('radius-right');
      const $buttonsVisible = $buttons.filter(':not(.hidden)');
      $buttonsVisible.first().addClass('radius-left');
      $buttonsVisible.last().addClass('radius-right');
    }

    /**
     * Called when a stored view is reused (by the controller).
     *
     * @public
     * @param {Object.<string, *>} params Routing params.
     */
    setupReuse(params) {
      this.initShortcuts();
    }
    static #_ = _staticBlock = () => [_init_shortcutManager, _init_extra_shortcutManager] = _applyDecs(this, [], [[(0, _di.inject)(_shortcutManager.default), 0, "shortcutManager"]], 0, void 0, _view.default).e;
  }
  _staticBlock();
  var _default = _exports.default = MainView;
});
//# sourceMappingURL=main.js.map ;