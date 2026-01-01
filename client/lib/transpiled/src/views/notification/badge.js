define("views/notification/badge", ["exports", "view", "di", "web-socket-manager"], function (_exports, _view, _di, _webSocketManager) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _webSocketManager = _interopRequireDefault(_webSocketManager);
  var _staticBlock;
  let _init_webSocketManager, _init_extra_webSocketManager;
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
  class NotificationBadgeView extends _view.default {
    constructor() {
      super(...arguments);
      _init_extra_webSocketManager(this);
    }
    template = 'notification/badge';

    /**
     * @private
     * @type {number}
     */
    notificationsCheckInterval = 10;

    /**
     * @private
     * @type {number}
     */
    groupedCheckInterval = 15;

    /**
     * @private
     * @type {number}
     */
    waitInterval = 2;

    /** @private */
    useWebSocket = false;

    /**
     * @private
     * @type {number|null}
     */
    timeout = null;

    /**
     * @private
     * @type {number|null}
     */
    groupedTimeout = null;

    /**
     * @private
     * @type {Object.<string, {
     *     portalDisabled?: boolean,
     *     grouped?: boolean,
     *     disabled?: boolean,
     *     interval?: Number,
     *     url?: string,
     *     useWebSocket?: boolean,
     *     view?: string,
     *     webSocketCategory?: string,
     * }>}
     */
    popupNotificationsData;

    /**
     * @private
     * @type {string}
     */
    soundPath = 'client/sounds/pop_cork';

    /**
     * @private
     * @type {WebSocketManager}
     */
    webSocketManager = _init_webSocketManager(this);
    setup() {
      this.addActionHandler('showNotifications', () => this.showNotifications());
      this.soundPath = this.getBasePath() + (this.getConfig().get('notificationSound') || this.soundPath);
      this.notificationSoundsDisabled = true;
      this.useWebSocket = this.webSocketManager.isEnabled();
      const clearTimeouts = () => {
        if (this.timeout) {
          clearTimeout(this.timeout);
        }
        if (this.groupedTimeout) {
          clearTimeout(this.groupedTimeout);
        }
        for (const name in this.popupTimeouts) {
          clearTimeout(this.popupTimeouts[name]);
        }
      };
      this.once('remove', () => clearTimeouts());
      this.listenToOnce(this.getHelper().router, 'logout', () => clearTimeouts());
      this.notificationsCheckInterval = this.getConfig().get('notificationsCheckInterval') || this.notificationsCheckInterval;
      this.groupedCheckInterval = this.getConfig().get('popupNotificationsCheckInterval') || this.groupedCheckInterval;
      this.lastId = 0;
      this.shownNotificationIds = [];
      this.closedNotificationIds = [];
      this.popupTimeouts = {};
      delete localStorage['messageBlockPlayNotificationSound'];
      delete localStorage['messageClosePopupNotificationId'];
      delete localStorage['messageNotificationRead'];
      window.addEventListener('storage', e => {
        if (e.key === 'messageClosePopupNotificationId') {
          const id = localStorage.getItem('messageClosePopupNotificationId');
          if (id) {
            const key = 'popup-' + id;
            if (this.hasView(key)) {
              this.markPopupRemoved(id);
              this.clearView(key);
            }
          }
        }
        if (e.key === 'messageNotificationRead') {
          if (!this.isBroadcastingNotificationRead && localStorage.getItem('messageNotificationRead')) {
            this.checkUpdates();
          }
        }
      }, false);
    }
    afterRender() {
      this.$badge = this.$el.find('.notifications-button');
      this.$number = this.$el.find('.number-badge');
      this.runCheckUpdates(true);
      this.$popupContainer = $('#popup-notifications-container');
      if (!$(this.$popupContainer).length) {
        this.$popupContainer = $('<div>').attr('id', 'popup-notifications-container').addClass('hidden').appendTo('body');
      }
      const popupNotificationsData = this.popupNotificationsData = this.getMetadata().get('app.popupNotifications') || {};
      for (const name in popupNotificationsData) {
        this.checkPopupNotifications(name);
      }
      if (this.hasGroupedPopupNotifications()) {
        this.checkGroupedPopupNotifications();
      }
    }
    playSound() {
      if (this.notificationSoundsDisabled) {
        return;
      }
      const audioElement = /** @type {HTMLAudioElement} */$('<audio>').attr('autoplay', 'autoplay').append($('<source>').attr('src', this.soundPath + '.mp3').attr('type', 'audio/mpeg')).append($('<source>').attr('src', this.soundPath + '.ogg').attr('type', 'audio/ogg')).append($('<embed>').attr('src', this.soundPath + '.mp3').attr('hidden', 'true').attr('autostart', 'true').attr('false', 'false')).get(0);
      audioElement.volume = 0.3;
      audioElement.play();
    }

    /**
     * @private
     * @param {number} count
     */
    showNotRead(count) {
      this.$badge.attr('title', this.translate('New notifications') + ': ' + count);
      this.$number.removeClass('hidden').html(count.toString());
      this.getHelper().pageTitle.setNotificationNumber(count);
    }

    /**
     * @private
     */
    hideNotRead() {
      this.$badge.attr('title', this.translate('Notifications'));
      this.$number.addClass('hidden').html('');
      this.getHelper().pageTitle.setNotificationNumber(0);
    }

    /**
     * @private
     */
    checkBypass() {
      const last = this.getRouter().getLast() || {};
      const pageAction = (last.options || {}).page || null;
      if (last.controller === 'Admin' && last.action === 'page' && ['upgrade', 'extensions'].includes(pageAction)) {
        return true;
      }
      return false;
    }

    /**
     * @private
     * @param {boolean} [isFirstCheck]
     */
    async checkUpdates(isFirstCheck) {
      if (this.checkBypass()) {
        return;
      }

      /** @type {number} */
      const count = await Espo.Ajax.getRequest('Notification/action/notReadCount');
      if (!isFirstCheck && count > this.unreadCount) {
        const blockSound = localStorage.getItem('messageBlockPlayNotificationSound');
        if (!blockSound) {
          this.playSound();
          localStorage.setItem('messageBlockPlayNotificationSound', 'true');
          setTimeout(() => {
            delete localStorage['messageBlockPlayNotificationSound'];
          }, this.notificationsCheckInterval * 1000);
        }
      }
      this.unreadCount = count;
      if (count) {
        this.showNotRead(count);
        return;
      }
      this.hideNotRead();
    }
    runCheckUpdates(isFirstCheck) {
      this.checkUpdates(isFirstCheck);
      if (this.useWebSocket) {
        this.initWebSocketCheckUpdates();
        return;
      }
      this.timeout = setTimeout(() => this.runCheckUpdates(), this.notificationsCheckInterval * 1000);
    }

    /**
     * @private
     */
    initWebSocketCheckUpdates() {
      let isBlocked = false;
      let hasBeenBlocked = false;
      const onWebSocketNewNotification = () => {
        if (isBlocked) {
          hasBeenBlocked = true;
          return;
        }
        this.checkUpdates();
        isBlocked = true;
        setTimeout(() => {
          const reRun = hasBeenBlocked;
          isBlocked = false;
          hasBeenBlocked = false;
          if (reRun) {
            onWebSocketNewNotification();
          }
        }, this.waitInterval * 1000);
      };
      this.webSocketManager.subscribe('newNotification', () => onWebSocketNewNotification());
      this.webSocketManager.subscribeToReconnect(onWebSocketNewNotification);
      this.once('remove', () => this.webSocketManager.unsubscribe('newNotification'));
      this.once('remove', () => this.webSocketManager.unsubscribeFromReconnect(onWebSocketNewNotification));
    }

    /**
     * @private
     * @return {boolean}
     */
    hasGroupedPopupNotifications() {
      for (const name in this.popupNotificationsData) {
        const data = this.popupNotificationsData[name] || {};
        if (!data.grouped) {
          continue;
        }
        if (data.portalDisabled && this.getUser().isPortal()) {
          continue;
        }
        return true;
      }
      return false;
    }

    /**
     * @private
     */
    checkGroupedPopupNotifications() {
      if (!this.checkBypass()) {
        Espo.Ajax.getRequest('PopupNotification/action/grouped').then(result => {
          for (const type in result) {
            const list = result[type];
            list.forEach(item => this.showPopupNotification(type, item));
          }
        });
      }
      if (this.useWebSocket) {
        return;
      }
      this.groupedTimeout = setTimeout(() => this.checkGroupedPopupNotifications(), this.groupedCheckInterval * 1000);
    }
    checkPopupNotifications(name, isNotFirstCheck) {
      const data = this.popupNotificationsData[name] || {};
      const url = data.url;
      const interval = data.interval;
      const disabled = data.disabled || false;
      if (disabled) {
        return;
      }
      if (data.portalDisabled && this.getUser().isPortal()) {
        return;
      }
      const useWebSocket = this.useWebSocket && data.useWebSocket;
      if (useWebSocket) {
        const category = 'popupNotifications.' + (data.webSocketCategory || name);
        this.webSocketManager.subscribe(category, (c, response) => {
          if (!response.list) {
            return;
          }
          response.list.forEach(item => {
            this.showPopupNotification(name, item);
          });
        });
      }
      if (data.grouped) {
        return;
      }
      if (!url) {
        return;
      }
      if (!interval) {
        return;
      }
      new Promise(resolve => {
        if (this.checkBypass()) {
          resolve();
          return;
        }
        Espo.Ajax.getRequest(url).then(list => list.forEach(item => this.showPopupNotification(name, item, isNotFirstCheck))).finally(() => resolve());
      }).then(() => {
        if (useWebSocket) {
          return;
        }
        this.popupTimeouts[name] = setTimeout(() => this.checkPopupNotifications(name, true), interval * 1000);
      });
    }
    showPopupNotification(name, data, isNotFirstCheck) {
      const view = this.popupNotificationsData[name].view;
      if (!view) {
        return;
      }
      let id = data.id || null;
      if (id) {
        id = name + '_' + id;
        if (~this.shownNotificationIds.indexOf(id)) {
          const notificationView = this.getView('popup-' + id);
          if (notificationView) {
            notificationView.trigger('update-data', data.data);
          }
          return;
        }
        if (~this.closedNotificationIds.indexOf(id)) {
          return;
        }
      } else {
        id = this.lastId++;
      }
      this.shownNotificationIds.push(id);
      this.createView('popup-' + id, view, {
        notificationData: data.data || {},
        notificationId: data.id,
        id: id,
        isFirstCheck: !isNotFirstCheck
      }, view => {
        view.render();
        this.$popupContainer.removeClass('hidden');
        this.listenTo(view, 'remove', () => {
          this.markPopupRemoved(id);
          localStorage.setItem('messageClosePopupNotificationId', id);
        });
      });
    }
    markPopupRemoved(id) {
      const index = this.shownNotificationIds.indexOf(id);
      if (index > -1) {
        this.shownNotificationIds.splice(index, 1);
      }
      if (this.shownNotificationIds.length === 0) {
        this.$popupContainer.addClass('hidden');
      }
      this.closedNotificationIds.push(id);
    }
    broadcastNotificationsRead() {
      if (!this.useWebSocket) {
        return;
      }
      this.isBroadcastingNotificationRead = true;
      localStorage.setItem('messageNotificationRead', 'true');
      setTimeout(() => {
        this.isBroadcastingNotificationRead = false;
        delete localStorage['messageNotificationRead'];
      }, 500);
    }
    showNotifications() {
      this.closeNotifications();
      const $container = $('<div>').attr('id', 'notifications-panel');
      $container.appendTo(this.$el.find('.notifications-panel-container'));
      this.createView('panel', 'views/notification/panel', {
        fullSelector: '#notifications-panel'
      }, view => {
        view.render();
        this.$el.closest('.navbar-body').removeClass('in');
        this.listenTo(view, 'all-read', () => {
          this.hideNotRead();
          this.$el.find('.badge-circle-warning').remove();
          this.broadcastNotificationsRead();
        });
        this.listenTo(view, 'collection-fetched', () => {
          this.checkUpdates();
          this.broadcastNotificationsRead();
        });
        this.listenToOnce(view, 'close', () => {
          this.closeNotifications();
        });
      });
      const $document = $(document);
      $document.on('mouseup.notification', e => {
        if (!$container.is(e.target) && $container.has(e.target).length === 0 && !$(e.target).closest('div.modal-dialog').length && !e.target.classList.contains('modal')) {
          this.closeNotifications();
        }
      });
      if (window.innerWidth < this.getThemeManager().getParam('screenWidthXs')) {
        this.listenToOnce(this.getRouter(), 'route', () => {
          this.closeNotifications();
        });
      }
    }
    closeNotifications() {
      const $container = $('#notifications-panel');
      $container.remove();
      const $document = $(document);
      if (this.hasView('panel')) {
        this.getView('panel').remove();
      }
      $document.off('mouseup.notification');
    }
    static #_ = _staticBlock = () => [_init_webSocketManager, _init_extra_webSocketManager] = _applyDecs(this, [], [[(0, _di.inject)(_webSocketManager.default), 0, "webSocketManager"]], 0, void 0, _view.default).e;
  }
  _staticBlock();
  var _default = _exports.default = NotificationBadgeView;
});
//# sourceMappingURL=badge.js.map ;