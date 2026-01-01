define("search-manager", ["exports", "di", "date-time", "storage"], function (_exports, _di, _dateTime, _storage) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _dateTime = _interopRequireDefault(_dateTime);
  _storage = _interopRequireDefault(_storage);
  var _staticBlock;
  let _init_dateTime, _init_extra_dateTime, _init_storage, _init_extra_storage;
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
  /** @module search-manager */
  /**
   * Search data.
   *
   * @typedef {Object} module:search-manager~data
   *
   * @property {string} [presetName] A preset.
   * @property {string} [textFilter] A text filter.
   * @property {string} [primary] A primary filter.
   * @property {Object.<string, boolean>} [bool] Bool filters.
   * @property {Record<module:search-manager~advancedFilter>} [advanced] Advanced filters (field filters).
   *     Contains data needed for both the backend and frontend. Keys are field names.
   */
  /**
   * A where item. Sent to the backend.
   *
   * @typedef {Object} module:search-manager~whereItem
   *
   * @property {string} type A type.
   * @property {string} [attribute] An attribute (field).
   * @property {module:search-manager~whereItem[]|string|number|boolean|string[]|null} [value] A value.
   * @property {boolean} [dateTime] Is a date-time item.
   * @property {string} [timeZone] A time-zone.
   */
  /**
   * An advanced filter
   *
   * @typedef {Object} module:search-manager~advancedFilter
   *
   * @property {string} type A type. E.g. `equals`.
   * @property {string} [attribute] An attribute.
   * @property {*} [value] A value.
   * @property {Object.<string, *>} [data] Additional data for UI.
   */
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  /**
   * A search manager.
   */
  class SearchManager {
    /**
     * @type {string|null}
     * @private
     */
    timeZone = null;

    /**
     * @private
     * @type {module:search-manager~data}
     */
    defaultData;

    /**
     * @private
     * @type {DateTime}
     */
    dateTime = _init_dateTime(this);

    /**
     * @private
     * @type {Storage}
     */
    storage = (_init_extra_dateTime(this), _init_storage(this));

    /**
     * @typedef {Object} module:search-manager~Options
     * @property {string} [storageKey] A storage key. If not specified, the storage won't be used.
     * @property {module:search-manager~data} [defaultData] Default data.
     * @property {boolean} [emptyOnReset] To empty on reset.
     */

    /**
     * @param {module:collection} collection A collection.
     * @param {module:search-manager~Options} [options] Options. As of 9.1.
     */
    constructor(collection) {
      let options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      _init_extra_storage(this);
      /**
       * @private
       * @type {module:collection}
       */
      this.collection = collection;

      /**
       * An entity type.
       *
       * @private
       * @type {string}
       */
      this.scope = collection.entityType;

      /**
       * @private
       * @type {string}
       */
      this.storageKey = options.storageKey;

      /**
       * @private
       * @type {boolean}
       */
      this.useStorage = !!this.storageKey;

      /**
       * @private
       * @type {boolean}
       */
      this.emptyOnReset = options.emptyOnReset || false;

      /**
       * @private
       * @type {Object}
       */
      this.emptyData = {
        textFilter: '',
        bool: {},
        advanced: {},
        primary: null
      };
      let defaultData = options.defaultData;
      if (!defaultData && arguments[4]) {
        // For bc.
        defaultData = arguments[4];
      }
      if (defaultData) {
        this.defaultData = defaultData;
        for (const key in this.emptyData) {
          if (!(key in defaultData)) {
            defaultData[key] = Espo.Utils.clone(this.emptyData[key]);
          }
        }
      }

      /**
       * @type {module:search-manager~data}
       * @private
       */
      this.data = Espo.Utils.clone(defaultData) || this.emptyData;
      this.sanitizeData();
    }

    /**
     * @private
     */
    sanitizeData() {
      if (!('advanced' in this.data)) {
        this.data.advanced = {};
      }
      if (!('bool' in this.data)) {
        this.data.bool = {};
      }
      if (!('textFilter' in this.data)) {
        this.data.textFilter = '';
      }
    }

    /**
     * Get a where clause. The where clause to be sent to the backend.
     *
     * @returns {module:search-manager~whereItem[]}
     */
    getWhere() {
      const where = [];
      if (this.data.textFilter && this.data.textFilter !== '') {
        where.push({
          type: 'textFilter',
          value: this.data.textFilter
        });
      }
      if (this.data.bool) {
        const o = {
          type: 'bool',
          value: []
        };
        for (const name in this.data.bool) {
          if (this.data.bool[name]) {
            o.value.push(name);
          }
        }
        if (o.value.length) {
          where.push(o);
        }
      }
      if (this.data.primary) {
        const o = {
          type: 'primary',
          value: this.data.primary
        };
        if (o.value.length) {
          where.push(o);
        }
      }
      if (this.data.advanced) {
        for (const name in this.data.advanced) {
          const defs = this.data.advanced[name];
          if (!defs) {
            continue;
          }
          const part = this.getWherePart(name, defs);
          where.push(part);
        }
      }
      return where;
    }

    /**
     * @private
     */
    getWherePart(name, defs) {
      let attribute = name;
      if (typeof defs !== 'object') {
        console.error('Bad where clause');
        return {};
      }
      if ('where' in defs) {
        return defs.where;
      }
      const type = defs.type;
      let value;
      if (type === 'or' || type === 'and') {
        const a = [];
        value = defs.value || {};
        for (const n in value) {
          a.push(this.getWherePart(n, value[n]));
        }
        return {
          type: type,
          value: a
        };
      }
      if ('field' in defs) {
        // for backward compatibility
        attribute = defs.field;
      }
      if ('attribute' in defs) {
        attribute = defs.attribute;
      }
      if (defs.dateTime || defs.date) {
        const timeZone = this.timeZone !== undefined ? this.timeZone : this.dateTime.getTimeZone();
        const data = {
          type: type,
          attribute: attribute,
          value: defs.value
        };
        if (defs.dateTime) {
          data.dateTime = true;
        }
        if (defs.date) {
          data.date = true;
        }
        if (timeZone) {
          data.timeZone = timeZone;
        }
        return data;
      }
      value = defs.value;
      return {
        type: type,
        attribute: attribute,
        value: value
      };
    }

    /**
     * Load stored data.
     *
     * @returns {module:search-manager}
     */
    loadStored() {
      this.data = this.getFromStorageIfEnabled() || Espo.Utils.clone(this.defaultData) || Espo.Utils.clone(this.emptyData);
      this.sanitizeData();
      return this;
    }

    /**
     * @private
     * @return {module:search-manager~data|null}
     */
    getFromStorageIfEnabled() {
      if (!this.useStorage) {
        return null;
      }
      return this.storage.get(`${this.storageKey}Search`, this.scope);
    }

    /**
     * Get data.
     *
     * @returns {module:search-manager~data}
     */
    get() {
      return this.data;
    }

    /**
     * Set advanced filters.
     *
     * @param {Object.<string, module:search-manager~advancedFilter>} advanced Advanced filters.
     *   Pairs of field => advancedFilter.
     */
    setAdvanced(advanced) {
      this.data = Espo.Utils.clone(this.data);
      this.data.advanced = advanced;
    }

    /**
     * Set bool filters.
     *
     * @param {Record.<string, boolean>|string[]} bool Bool filters.
     */
    setBool(bool) {
      if (Array.isArray(bool)) {
        const data = {};
        bool.forEach(it => data[it] = true);
        bool = data;
      }
      this.data = Espo.Utils.clone(this.data);
      this.data.bool = bool;
    }

    /**
     * Set a primary filter.
     *
     * @param {string} primary A filter.
     */
    setPrimary(primary) {
      this.data = Espo.Utils.clone(this.data);
      this.data.primary = primary;
    }

    /**
     * Set data.
     *
     * @param {module:search-manager~data} data Data.
     */
    set(data) {
      this.data = data;
      if (this.useStorage) {
        data = Espo.Utils.clone(data);
        delete data['textFilter'];
        this.storage.set(this.storageKey + 'Search', this.scope, data);
      }
    }
    clearPreset() {
      delete this.data.presetName;
    }

    /**
     * Empty data.
     */
    empty() {
      this.data = Espo.Utils.clone(this.emptyData);
      if (this.useStorage) {
        this.storage.clear(this.storageKey + 'Search', this.scope);
      }
    }

    /**
     * Reset.
     */
    reset() {
      if (this.emptyOnReset) {
        this.empty();
        return;
      }
      this.data = Espo.Utils.clone(this.defaultData) || Espo.Utils.clone(this.emptyData);
      if (this.useStorage) {
        this.storage.clear(this.storageKey + 'Search', this.scope);
      }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Set a time zone. Null will not add a time zone.
     *
     * @type {string|null}
     * @internal Is used. Do not remove.
     */
    setTimeZone(timeZone) {
      this.timeZone = timeZone;
    }
    static #_ = _staticBlock = () => [_init_dateTime, _init_extra_dateTime, _init_storage, _init_extra_storage] = _applyDecs(this, [], [[(0, _di.inject)(_dateTime.default), 0, "dateTime"], [(0, _di.inject)(_storage.default), 0, "storage"]]).e;
  }
  _staticBlock();
  var _default = _exports.default = SearchManager;
});
//# sourceMappingURL=search-manager.js.map ;