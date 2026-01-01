define("helpers/list/misc/list-tree-draggable", ["exports", "@shopify/draggable", "di", "language"], function (_exports, _draggable, _di, _language) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _language = _interopRequireDefault(_language);
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
  /**
   * @internal
   */
  class ListTreeDraggableHelper {
    /**
     * @type {Language}
     */
    language = _init_language(this);

    /**
     * @private
     * @type {boolean}
     */
    blockDraggable = (_init_extra_language(this), false);

    /**
     * @private
     * {Draggable}
     */
    draggable;

    /**
     * @param {import('views/record/list-tree').default} view
     */
    constructor(view) {
      this.view = view;
    }
    destroy() {
      if (this.draggable) {
        this.draggable.destroy();
      }
    }
    init() {
      if (this.draggable) {
        this.draggable.destroy();
      }
      const draggable = this.draggable = new _draggable.Draggable(this.view.element, {
        distance: 8,
        draggable: '.list-group-item > .cell > [data-role="moveHandle"]',
        mirror: {
          cursorOffsetX: 5,
          cursorOffsetY: 5,
          appendTo: 'body'
        }
      });

      /** @type {HTMLElement[]} */
      let rows;
      /** @type {Map<HTMLElement, number>} */
      let levelMap;
      /** @type {HTMLElement|null} */
      let movedHandle = null;
      /** @type {HTMLElement|null} */
      let movedLink = null;
      /** @type {HTMLElement|null} */
      let movedFromLi = null;
      draggable.on('mirror:created', event => {
        const mirror = event.mirror;
        const source = event.source;
        const originalSource = event.originalSource;
        originalSource.style.display = '';
        source.style.display = 'none';
        mirror.style.display = 'block';
        mirror.style.cursor = 'grabbing';
        mirror.classList.add('draggable-helper', 'draggable-helper-transparent', 'text-info');
        mirror.classList.remove('link');
        mirror.style.pointerEvents = 'auto';
        mirror.removeAttribute('href');
        mirror.style.textDecoration = 'none';
        mirror.innerText = mirror.dataset.title;
      });
      draggable.on('mirror:move', event => {
        event.mirror.style.pointerEvents = 'auto';
      });
      draggable.on('drag:start', event => {
        if (this.blockDraggable) {
          event.cancel();
          return;
        }
        rows = Array.from(this.view.element.querySelectorAll('.list-group-tree > .list-group-item'));
        levelMap = new Map();
        rows.forEach(row => {
          let depth = 0;
          let current = row;
          while (current && current !== this.view.element) {
            current = current.parentElement;
            depth++;
          }
          levelMap.set(row, depth);
        });
        rows.sort((a, b) => levelMap.get(b) - levelMap.get(a));
        this.view.movedId = event.source.dataset.id;
        movedHandle = event.originalSource;
        movedFromLi = movedHandle.parentElement.parentElement;
        movedLink = movedHandle.parentElement.querySelector(`:scope > a.link`);
        movedLink.classList.add('text-info');
      });
      let overId = null;
      let overParentId = null;
      let isAfter = false;
      let wasOutOfSelf = false;
      draggable.on('drag:move', event => {
        isAfter = false;
        overId = null;
        let rowFound = null;
        for (const row of rows) {
          const rect = row.getBoundingClientRect();
          const isIn = rect.left < event.sensorEvent.clientX && rect.right > event.sensorEvent.clientX && rect.top < event.sensorEvent.clientY && rect.bottom >= event.sensorEvent.clientY;
          if (!isIn) {
            continue;
          }
          let itemId = row.dataset.id ?? null;
          let itemParentId = null;
          if (!itemId) {
            const parent = row.closest(`.list-group-item[data-id]`);
            if (parent instanceof HTMLElement) {
              // Over a plus row.
              itemParentId = parent.dataset.id;
            }
          }
          const itemIsAfter = event.sensorEvent.clientY - rect.top >= rect.bottom - event.sensorEvent.clientY;
          if (itemParentId && itemIsAfter) {
            continue;
          }
          if (itemId === this.view.movedId) {
            break;
          }
          if (movedFromLi.contains(row)) {
            break;
          }
          if (!itemId && !itemParentId) {
            continue;
          }
          if (itemParentId) {
            const parent = row.closest(`.list-group-item[data-id]`);
            if (parent) {
              /** @type {NodeListOf<HTMLElement>} */
              const items = parent.querySelectorAll(':scope > .children > .list > .list-group > [data-id]');
              if (items.length) {
                itemId = Array.from(items).pop().dataset.id;
                itemParentId = null;
              }
            }
          }
          isAfter = itemIsAfter;
          overParentId = itemParentId;
          overId = itemId;
          rowFound = row;
          break;
        }
        for (const row of rows) {
          row.classList.remove('border-top-highlighted');
          row.classList.remove('border-bottom-highlighted');
        }
        if (!rowFound) {
          return;
        }
        if (isAfter) {
          rowFound.classList.add('border-bottom-highlighted');
          rowFound.classList.remove('border-top-highlighted');
        } else {
          rowFound.classList.add('border-top-highlighted');
          rowFound.classList.remove('border-bottom-highlighted');
        }
      });
      draggable.on('drag:stop', async () => {
        const finalize = () => {
          if (movedLink) {
            movedLink.classList.remove('text-info');
          }
          rows.forEach(row => {
            row.classList.remove('border-bottom-highlighted');
            row.classList.remove('border-top-highlighted');
          });
          rows = undefined;
        };
        let moveType;
        let referenceId = overId;
        if (overParentId || overId) {
          if (overParentId) {
            moveType = 'into';
            referenceId = overParentId;
          } else if (isAfter) {
            moveType = 'after';
          } else {
            moveType = 'before';
          }
        }
        if (moveType) {
          this.blockDraggable = true;
          const movedId = this.view.movedId;
          const affectedId = referenceId;
          Espo.Ui.notifyWait();
          Espo.Ajax.postRequest(`${this.view.entityType}/action/move`, {
            id: this.view.movedId,
            referenceId: referenceId,
            type: moveType
          }).then(async () => {
            const promises = [];
            if (movedId) {
              promises.push(this.updateAfter(this.view, movedId));
            }
            if (affectedId) {
              promises.push(this.updateAfter(this.view, affectedId));
            }
            await Promise.all(promises);
            Espo.Ui.success(this.language.translate('Done'));
          }).finally(() => {
            this.blockDraggable = false;
            finalize();
          });
        }
        if (!moveType) {
          finalize();
        }
        this.view.movedId = null;
        movedHandle = null;
        movedFromLi = null;
        levelMap = undefined;
        overParentId = null;
        overId = null;
        isAfter = false;
        wasOutOfSelf = false;
      });
    }

    /**
     * @private
     * @param {ListTreeRecordView} view
     * @param {string} movedId
     * @return {Promise}
     */
    async updateAfter(view, movedId) {
      if (view.collection.has(movedId)) {
        const unfoldedIds = view.getItemViews().filter(view => view.isUnfolded && view.model).map(view => view.model.id);
        await view.collection.fetch({
          noRebuild: false
        });
        view.getItemViews().filter(view => view && view.model && unfoldedIds.includes(view.model.id)).forEach(view => view.unfold());
        return;
      }
      for (const subView of view.getItemViews()) {
        if (!subView.getChildrenView()) {
          continue;
        }
        await this.updateAfter(subView.getChildrenView(), movedId);
      }
    }
    static #_ = _staticBlock = () => [_init_language, _init_extra_language] = _applyDecs(this, [], [[(0, _di.inject)(_language.default), 0, "language"]]).e;
  }
  _exports.default = ListTreeDraggableHelper;
  _staticBlock();
});
//# sourceMappingURL=list-tree-draggable.js.map ;