define("modules/crm/views/calendar/calendar-page", ["exports", "view", "crm:views/calendar/modals/edit-view", "di", "helpers/site/shortcut-manager", "helpers/util/debounce", "web-socket-manager", "utils"], function (_exports, _view, _editView, _di, _shortcutManager, _debounce, _webSocketManager, _utils) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _editView = _interopRequireDefault(_editView);
  _shortcutManager = _interopRequireDefault(_shortcutManager);
  _debounce = _interopRequireDefault(_debounce);
  _webSocketManager = _interopRequireDefault(_webSocketManager);
  _utils = _interopRequireDefault(_utils);
  var _staticBlock;
  let _init_shortcutManager, _init_extra_shortcutManager, _init_webSocketManager, _init_extra_webSocketManager;
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
  class CalendarPage extends _view.default {
    template = 'crm:calendar/calendar-page';

    //el = '#main'

    fullCalendarModeList = ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay', 'listWeek'];
    events = {
      /** @this CalendarPage */
      'click [data-action="createCustomView"]': function () {
        this.createCustomView();
      },
      /** @this CalendarPage */
      'click [data-action="editCustomView"]': function () {
        this.editCustomView();
      }
    };

    /**
     * @private
     * @type {ShortcutManager}
     */
    shortcutManager = _init_shortcutManager(this);

    /**
     * @private
     * @type {DebounceHelper|null}
     */
    webSocketDebounceHelper = (_init_extra_shortcutManager(this), null);

    /**
     * @private
     * @type {number}
     */
    webSocketDebounceInterval = 500;

    /**
     * @private
     * @type {number}
     */
    webSocketBlockInterval = 1000;

    /**
     * @private
     * @type {WebSocketManager}
     */
    webSocketManager = _init_webSocketManager(this);

    /**
     * A shortcut-key => action map.
     *
     * @protected
     * @type {?Object.<string, function (KeyboardEvent): void>}
     */
    shortcutKeys = (_init_extra_webSocketManager(this), {
      /** @this CalendarPage */
      'Home': function (e) {
        this.handleShortcutKeyHome(e);
      },
      /** @this CalendarPage */
      'Numpad7': function (e) {
        this.handleShortcutKeyHome(e);
      },
      /** @this CalendarPage */
      'Numpad4': function (e) {
        this.handleShortcutKeyArrowLeft(e);
      },
      /** @this CalendarPage */
      'Numpad6': function (e) {
        this.handleShortcutKeyArrowRight(e);
      },
      /** @this CalendarPage */
      'ArrowLeft': function (e) {
        this.handleShortcutKeyArrowLeft(e);
      },
      /** @this CalendarPage */
      'ArrowRight': function (e) {
        this.handleShortcutKeyArrowRight(e);
      },
      /** @this CalendarPage */
      'Control+ArrowLeft': function (e) {
        this.handleShortcutKeyArrowLeft(e);
      },
      /** @this CalendarPage */
      'Control+ArrowRight': function (e) {
        this.handleShortcutKeyArrowRight(e);
      },
      /** @this CalendarPage */
      'Minus': function (e) {
        this.handleShortcutKeyMinus(e);
      },
      /** @this CalendarPage */
      'Equal': function (e) {
        this.handleShortcutKeyPlus(e);
      },
      /** @this CalendarPage */
      'NumpadSubtract': function (e) {
        this.handleShortcutKeyMinus(e);
      },
      /** @this CalendarPage */
      'NumpadAdd': function (e) {
        this.handleShortcutKeyPlus(e);
      },
      /** @this CalendarPage */
      'Digit1': function (e) {
        this.handleShortcutKeyDigit(e, 1);
      },
      /** @this CalendarPage */
      'Digit2': function (e) {
        this.handleShortcutKeyDigit(e, 2);
      },
      /** @this CalendarPage */
      'Digit3': function (e) {
        this.handleShortcutKeyDigit(e, 3);
      },
      /** @this CalendarPage */
      'Digit4': function (e) {
        this.handleShortcutKeyDigit(e, 4);
      },
      /** @this CalendarPage */
      'Digit5': function (e) {
        this.handleShortcutKeyDigit(e, 5);
      },
      /** @this CalendarPage */
      'Digit6': function (e) {
        this.handleShortcutKeyDigit(e, 6);
      },
      /** @this CalendarPage */
      'Control+Space': function (e) {
        this.handleShortcutKeyControlSpace(e);
      }
    });

    /**
     * @param {{
     *     userId?: string,
     *     userName?: string|null,
     *     mode?: string|null,
     *     date?: string|null,
     * }} options
     */
    constructor(options) {
      super(options);
      this.options = options;
    }
    setup() {
      this.mode = this.mode || this.options.mode || null;
      this.date = this.date || this.options.date || null;
      if (!this.mode) {
        this.mode = this.getStorage().get('state', 'calendarMode') || null;
        if (this.mode && this.mode.indexOf('view-') === 0) {
          const viewId = this.mode.slice(5);
          const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
          let isFound = false;
          calendarViewDataList.forEach(item => {
            if (item.id === viewId) {
              isFound = true;
            }
          });
          if (!isFound) {
            this.mode = null;
          }
          if (this.options.userId) {
            this.mode = null;
          }
        }
      }
      this.shortcutManager.add(this, this.shortcutKeys);
      this.on('remove', () => {
        this.shortcutManager.remove(this);
      });
      if (!this.mode || ~this.fullCalendarModeList.indexOf(this.mode) || this.mode.indexOf('view-') === 0) {
        this.setupCalendar();
      } else if (this.mode === 'timeline') {
        this.setupTimeline();
      }
      this.initWebSocket();
    }

    /**
     * @private
     */
    initWebSocket() {
      if (this.options.userId && this.getUser().id !== this.options.userId) {
        return;
      }
      this.webSocketDebounceHelper = new _debounce.default({
        interval: this.webSocketDebounceInterval,
        blockInterval: this.webSocketBlockInterval,
        handler: () => this.handleWebSocketUpdate()
      });
      if (!this.webSocketManager.isEnabled()) {
        const testHandler = () => this.webSocketDebounceHelper.process();
        this.on('remove', () => window.removeEventListener('calendar-update', testHandler));

        // For testing purpose.
        window.addEventListener('calendar-update', testHandler);
        return;
      }
      this.webSocketManager.subscribe('calendarUpdate', () => this.webSocketDebounceHelper.process());
      this.on('remove', () => this.webSocketManager.unsubscribe('calendarUpdate'));
    }

    /**
     * @private
     */
    handleWebSocketUpdate() {
      var _this$getCalendarView;
      (_this$getCalendarView = this.getCalendarView()) === null || _this$getCalendarView === void 0 || _this$getCalendarView.actionRefresh({
        suppressLoadingAlert: true
      });
    }

    /**
     * @private
     */
    onSave() {
      if (!this.webSocketDebounceHelper) {
        return;
      }
      this.webSocketDebounceHelper.block();
    }
    afterRender() {
      this.$el.focus();
    }
    updateUrl(trigger) {
      let url = '#Calendar/show';
      if (this.mode || this.date) {
        url += '/';
      }
      if (this.mode) {
        url += 'mode=' + this.mode;
      }
      if (this.date) {
        url += '&date=' + this.date;
      }
      if (this.options.userId) {
        url += '&userId=' + this.options.userId;
        if (this.options.userName) {
          url += '&userName=' + encodeURIComponent(this.options.userName);
        }
      }
      this.getRouter().navigate(url, {
        trigger: trigger
      });
    }

    /**
     * @private
     */
    setupCalendar() {
      const viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) || 'crm:views/calendar/calendar';
      this.createView('calendar', viewName, {
        date: this.date,
        userId: this.options.userId,
        userName: this.options.userName,
        mode: this.mode,
        fullSelector: '#main > .calendar-container',
        onSave: () => this.onSave()
      }, view => {
        let initial = true;
        this.listenTo(view, 'view', (date, mode) => {
          this.date = date;
          this.mode = mode;
          if (!initial) {
            this.updateUrl();
          }
          initial = false;
        });
        this.listenTo(view, 'change:mode', (mode, refresh) => {
          this.mode = mode;
          if (!this.options.userId) {
            this.getStorage().set('state', 'calendarMode', mode);
          }
          if (refresh) {
            this.updateUrl(true);
            return;
          }
          if (!~this.fullCalendarModeList.indexOf(mode)) {
            this.updateUrl(true);
          }
          this.$el.focus();
        });
      });
    }

    /**
     * @private
     */
    setupTimeline() {
      const viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) || 'crm:views/calendar/timeline';
      this.createView('calendar', viewName, {
        date: this.date,
        userId: this.options.userId,
        userName: this.options.userName,
        fullSelector: '#main > .calendar-container',
        onSave: () => this.onSave()
      }, view => {
        let initial = true;
        this.listenTo(view, 'view', (date, mode) => {
          this.date = date;
          this.mode = mode;
          if (!initial) {
            this.updateUrl();
          }
          initial = false;
        });
        this.listenTo(view, 'change:mode', mode => {
          this.mode = mode;
          if (!this.options.userId) {
            this.getStorage().set('state', 'calendarMode', mode);
          }
          this.updateUrl(true);
        });
      });
    }
    updatePageTitle() {
      this.setPageTitle(this.translate('Calendar', 'scopeNames'));
    }
    async createCustomView() {
      const view = new _editView.default({
        afterSave: data => {
          this.mode = `view-${data.id}`;
          this.date = null;
          this.updateUrl(true);
        }
      });
      await this.assignView('modal', view);
      await view.render();
    }
    async editCustomView() {
      const viewId = this.getCalendarView().viewId;
      if (!viewId) {
        return;
      }
      const view = new _editView.default({
        id: viewId,
        afterSave: () => {
          this.getCalendarView().setupMode();
          this.getCalendarView().reRender();
        },
        afterRemove: () => {
          this.mode = null;
          this.date = null;
          this.updateUrl(true);
        }
      });
      await this.assignView('modal', view);
      await view.render();
    }

    /**
     * @private
     * @return {import('./calendar').default|import('./timeline').default}
     */
    getCalendarView() {
      return this.getView('calendar');
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyHome(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionToday();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyArrowLeft(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (e.target instanceof HTMLElement && e.target.parentElement instanceof HTMLLIElement) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionPrevious();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyArrowRight(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (e.target instanceof HTMLElement && e.target.parentElement instanceof HTMLLIElement) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionNext();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyMinus(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (!this.getCalendarView().actionZoomOut) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionZoomOut();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyPlus(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (!this.getCalendarView().actionZoomIn) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().actionZoomIn();
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     * @param {Number} digit
     */
    handleShortcutKeyDigit(e, digit) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      const modeList = this.getCalendarView().hasView('modeButtons') ? this.getCalendarView().getModeButtonsView().getModeDataList(true).map(item => item.mode) : this.getCalendarView().modeList;
      const mode = modeList[digit - 1];
      if (!mode) {
        return;
      }
      e.preventDefault();
      if (mode === this.mode) {
        this.getCalendarView().actionRefresh();
        return;
      }
      this.getCalendarView().selectMode(mode);
    }

    /**
     * @private
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyControlSpace(e) {
      if (_utils.default.isKeyEventInTextInput(e)) {
        return;
      }
      if (!this.getCalendarView().createEvent) {
        return;
      }
      e.preventDefault();
      this.getCalendarView().createEvent();
    }
    static #_ = _staticBlock = () => [_init_shortcutManager, _init_extra_shortcutManager, _init_webSocketManager, _init_extra_webSocketManager] = _applyDecs(this, [], [[(0, _di.inject)(_shortcutManager.default), 0, "shortcutManager"], [(0, _di.inject)(_webSocketManager.default), 0, "webSocketManager"]], 0, void 0, _view.default).e;
  }

  // noinspection JSUnusedGlobalSymbols
  _staticBlock();
  var _default = _exports.default = CalendarPage;
});
//# sourceMappingURL=calendar-page.js.map ;