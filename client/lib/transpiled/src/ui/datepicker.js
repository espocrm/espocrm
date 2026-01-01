define("ui/datepicker", ["exports", "jquery", "language", "models/settings", "di", "moment"], function (_exports, _jquery, _language, _settings, _di, _moment) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _jquery = _interopRequireDefault(_jquery);
  _language = _interopRequireDefault(_language);
  _settings = _interopRequireDefault(_settings);
  _moment = _interopRequireDefault(_moment);
  var _staticBlock;
  let _init_language, _init_extra_language, _init_config, _init_extra_config;
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
   * A datepicker.
   *
   * @since 9.0.0
   */
  class Datepicker {
    /**
     * @private
     * @type {Language}
     */
    language = _init_language(this);

    /**
     * @private
     * @type {Settings}
     */
    config = (_init_extra_language(this), _init_config(this));

    /**
     * @param {HTMLElement} element
     * @param {{
     *     format: string,
     *     weekStart: number,
     *     todayButton?: boolean,
     *     date?: string,
     *     startDate?: string|undefined,
     *     onChange?: function(),
     *     hasDay?: function(string): boolean,
     *     hasMonth?: function(string): boolean,
     *     hasYear?: function(string): boolean,
     *     onChangeDate?: function(),
     *     onChangeMonth?: function(string),
     *     defaultViewDate?: string,
     * }} options
     */
    constructor(element, options) {
      _init_extra_config(this);
      /**
       * @private
       */
      this.$element = (0, _jquery.default)(element);

      /**
       * @private
       * @type {string}
       */
      this.format = options.format;
      if (element instanceof HTMLInputElement) {
        if (options.date) {
          element.value = options.date;
        }
        let wait = false;
        this.$element.on('change', /** Record */e => {
          if (!wait) {
            if (options.onChange) {
              options.onChange();
            }
            wait = true;
            setTimeout(() => wait = false, 100);
          }
          if (e.isTrigger && document.activeElement !== this.$element.get(0)) {
            this.$element.focus();
          }
        });
        this.$element.on('click', () => this.show());
      } else {
        if (options.date) {
          element.dataset.date = options.date;
        }
      }
      const modalBodyElement = element.closest('.modal-body');
      const language = this.config.get('language');
      const format = options.format;
      const datepickerOptions = {
        autoclose: true,
        todayHighlight: true,
        keyboardNavigation: true,
        assumeNearbyYear: true,
        format: format.toLowerCase(),
        weekStart: options.weekStart,
        todayBtn: options.todayButton || false,
        startDate: options.startDate,
        orientation: 'bottom auto',
        templates: {
          leftArrow: '<span class="fas fa-chevron-left fa-sm"></span>',
          rightArrow: '<span class="fas fa-chevron-right fa-sm"></span>'
        },
        container: modalBodyElement ? (0, _jquery.default)(modalBodyElement) : 'body',
        language: language,
        maxViewMode: 2,
        defaultViewDate: options.defaultViewDate
      };
      if (options.hasDay) {
        datepickerOptions.beforeShowDay = (/** Date */date) => {
          const stringDate = (0, _moment.default)(date).format(this.format);
          return {
            enabled: options.hasDay(stringDate)
          };
        };
      }
      if (options.hasMonth) {
        datepickerOptions.beforeShowMonth = (/** Date */date) => {
          const stringDate = (0, _moment.default)(date).format(this.format);
          return {
            enabled: options.hasMonth(stringDate)
          };
        };
      }
      if (options.hasYear) {
        datepickerOptions.beforeShowYear = (/** Date */date) => {
          const stringDate = (0, _moment.default)(date).format(this.format);
          return {
            enabled: options.hasYear(stringDate)
          };
        };
      }

      // noinspection JSUnresolvedReference
      if (!(language in _jquery.default.fn.datepicker.dates)) {
        // noinspection JSUnresolvedReference
        _jquery.default.fn.datepicker.dates[language] = {
          days: this.language.get('Global', 'lists', 'dayNames'),
          daysShort: this.language.get('Global', 'lists', 'dayNamesShort'),
          daysMin: this.language.get('Global', 'lists', 'dayNamesMin'),
          months: this.language.get('Global', 'lists', 'monthNames'),
          monthsShort: this.language.get('Global', 'lists', 'monthNamesShort'),
          today: this.language.translate('Today'),
          clear: this.language.translate('Clear')
        };
      }
      this.$element.datepicker(datepickerOptions).on('changeDate', () => {
        if (options.onChangeDate) {
          options.onChangeDate();
        }
      }).on('changeMonth', (/** {date: Date} */event) => {
        if (options.onChangeMonth) {
          const dateString = (0, _moment.default)(event.date).startOf('month').format(options.format);
          options.onChangeMonth(dateString);
        }
      });
      if (element.classList.contains('input-group') && !(element instanceof HTMLInputElement)) {
        element.querySelectorAll('input').forEach(input => {
          (0, _jquery.default)(input).on('click', () => (0, _jquery.default)(input).datepicker('show'));
        });
      }
    }

    /**
     * Set a start date.
     *
     * @param {string|undefined} startDate
     */
    setStartDate(startDate) {
      this.$element.datepicker('setStartDate', startDate);
    }

    /**
     * Show.
     */
    show() {
      this.$element.datepicker('show');
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Get the value.
     *
     * @return {string|null}
     */
    getDate() {
      const date = this.$element.datepicker('getDate');
      if (!date) {
        return null;
      }
      return (0, _moment.default)(date).format(this.format);
    }

    /**
     * Refresh.
     */
    refresh() {
      const picker = this.$element.data('datepicker');
      if (!picker) {
        return;
      }
      picker.fill();
    }
    static #_ = _staticBlock = () => [_init_language, _init_extra_language, _init_config, _init_extra_config] = _applyDecs(this, [], [[(0, _di.inject)(_language.default), 0, "language"], [(0, _di.inject)(_settings.default), 0, "config"]]).e;
  }
  _staticBlock();
  var _default = _exports.default = Datepicker;
});
//# sourceMappingURL=datepicker.js.map ;