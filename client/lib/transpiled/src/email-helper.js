define("email-helper", ["exports", "di", "language", "models/user", "date-time", "acl-manager"], function (_exports, _di, _language, _user, _dateTime, _aclManager) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _language = _interopRequireDefault(_language);
  _user = _interopRequireDefault(_user);
  _dateTime = _interopRequireDefault(_dateTime);
  _aclManager = _interopRequireDefault(_aclManager);
  var _staticBlock;
  let _init_language, _init_extra_language, _init_user, _init_extra_user, _init_dateTime, _init_extra_dateTime, _init_acl, _init_extra_acl;
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
  /** @module email-helper */
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  /**
   * An email helper.
   */
  class EmailHelper {
    /**
     * @private
     * @type {Language}
     */
    language = _init_language(this);

    /**
     * @private
     * @type {User}
     */
    user = (_init_extra_language(this), _init_user(this));

    /**
     * @private
     * @type {DateTime}
     */
    dateTime = (_init_extra_user(this), _init_dateTime(this));

    /**
     * @private
     * @type {AclManager}
     */
    acl = (_init_extra_dateTime(this), _init_acl(this));
    constructor() {
      _init_extra_acl(this);
      /** @private */
      this.erasedPlaceholder = 'ERASED:';
    }

    /**
     * Get reply email attributes.
     *
     * @param {module:model} model An email model.
     * @param {Object|null} [data=null] Action data. Unused.
     * @param {boolean} [cc=false] To include CC (reply-all).
     * @returns {Object.<string, *>}
     */
    getReplyAttributes(model, data, cc) {
      const attributes = {
        status: 'Draft',
        isHtml: model.attributes.isHtml
      };
      const subject = model.attributes.name || '';
      attributes['name'] = subject.toUpperCase().indexOf('RE:') !== 0 ? 'Re: ' + subject : subject;
      let to = '';
      let isReplyOnSent = false;
      const nameHash = model.attributes.nameHash || {};
      const replyToAddressString = model.attributes.replyTo || null;
      const replyToString = model.attributes.replyToString || null;
      const userEmailAddressList = this.user.attributes.emailAddressList || [];
      const idHash = model.attributes.idHash || {};
      const typeHash = model.attributes.typeHash || {};
      if (replyToAddressString) {
        const replyToAddressList = replyToAddressString.split(';');
        to = replyToAddressList.join(';');
      } else if (replyToString) {
        const a = [];
        replyToString.split(';').forEach(item => {
          const part = item.trim();
          const address = this.parseAddressFromStringAddress(item);
          if (address) {
            a.push(address);
            const name = this.parseNameFromStringAddress(part);
            if (name && name !== address) {
              nameHash[address] = name;
            }
          }
        });
        to = a.join(';');
      }
      if ((!to || !to.includes('@')) && model.attributes.from) {
        if (!userEmailAddressList.includes(model.attributes.from)) {
          to = model.attributes.from;
          if (!nameHash[to]) {
            const fromString = model.attributes.fromString || model.attributes.fromName;
            if (fromString) {
              const name = this.parseNameFromStringAddress(fromString);
              if (name !== to) {
                nameHash[to] = name;
              }
            }
          }
        } else {
          isReplyOnSent = true;
        }
      }
      attributes.to = to;
      if (cc) {
        attributes.cc = model.attributes.cc || '';

        /** @type {string[]} */
        const excludeFromReplyEmailAddressList = this.user.get('excludeFromReplyEmailAddressList') || [];
        (model.get('to') || '').split(';').forEach(item => {
          item = item.trim();
          if (item === this.user.get('emailAddress')) {
            return;
          }
          if (excludeFromReplyEmailAddressList.includes(item)) {
            return;
          }
          if (isReplyOnSent) {
            if (attributes.to) {
              attributes.to += ';';
            }
            attributes.to += item;
            return;
          }
          if (attributes.cc) {
            attributes.cc += ';';
          }
          attributes.cc += item;
        });
        attributes.cc = attributes.cc.replace(/^(; )/, "");
      }
      if (attributes.to) {
        let toList = attributes.to.split(';');
        toList = toList.filter(item => {
          if (item.indexOf(this.erasedPlaceholder) === 0) {
            return false;
          }
          return true;
        });
        attributes.to = toList.join(';');
      }

      /** @type {string[]} */
      const personalAddresses = this.user.get('userEmailAddressList') || [];
      const lcPersonalAddresses = personalAddresses.map(it => it.toLowerCase());
      if (attributes.cc) {
        const ccList = attributes.cc.split(';').filter(item => {
          if (lcPersonalAddresses.includes(item.toLowerCase())) {
            return false;
          }
          if (item.indexOf(this.erasedPlaceholder) === 0) {
            return false;
          }
          return true;
        });
        attributes.cc = ccList.join(';');
      }
      if (model.get('parentId')) {
        attributes['parentId'] = model.get('parentId');
        attributes['parentName'] = model.get('parentName');
        attributes['parentType'] = model.get('parentType');
      }
      if (model.get('teamsIds') && model.get('teamsIds').length) {
        attributes.teamsIds = Espo.Utils.clone(model.get('teamsIds'));
        attributes.teamsNames = Espo.Utils.clone(model.get('teamsNames') || {});
        const defaultTeamId = this.user.get('defaultTeamId');
        if (defaultTeamId && !~attributes.teamsIds.indexOf(defaultTeamId)) {
          attributes.teamsIds.push(this.user.get('defaultTeamId'));
          attributes.teamsNames[this.user.get('defaultTeamId')] = this.user.get('defaultTeamName');
        }
        attributes.teamsIds = attributes.teamsIds.filter(teamId => this.acl.checkTeamAssignmentPermission(teamId));
      }
      attributes.nameHash = nameHash;
      attributes.typeHash = typeHash;
      attributes.idHash = idHash;
      attributes.repliedId = model.id;
      attributes.inReplyTo = model.get('messageId');

      /** @type {string[]} */
      const lcToAddresses = (model.attributes.to || '').split(';').map(it => it.toLowerCase());
      for (const address of personalAddresses) {
        if (lcToAddresses.includes(address.toLowerCase())) {
          attributes.from = address;
          break;
        }
      }
      this.addReplyBodyAttributes(model, attributes);
      return attributes;
    }

    /**
     * Get forward email attributes.
     *
     * @param {module:model} model An email model.
     * @returns {Object}
     */
    getForwardAttributes(model) {
      const attributes = {
        status: 'Draft',
        isHtml: model.get('isHtml')
      };
      const subject = model.get('name');
      if (~!subject.toUpperCase().indexOf('FWD:') && ~!subject.toUpperCase().indexOf('FW:')) {
        attributes['name'] = 'Fwd: ' + subject;
      } else {
        attributes['name'] = subject;
      }
      if (model.get('parentId')) {
        attributes['parentId'] = model.get('parentId');
        attributes['parentName'] = model.get('parentName');
        attributes['parentType'] = model.get('parentType');
      }
      this.addForwardBodyAttributes(model, attributes);
      return attributes;
    }

    /**
     * Add body attributes for a forward email.
     *
     * @param {module:model} model An email model.
     * @param {Object} attributes
     */
    addForwardBodyAttributes(model, attributes) {
      let prepending = '';
      if (model.get('isHtml')) {
        prepending = '<br>' + '------' + this.language.translate('Forwarded message', 'labels', 'Email') + '------';
      } else {
        prepending = '\n\n' + '------' + this.language.translate('Forwarded message', 'labels', 'Email') + '------';
      }
      const list = [];
      if (model.get('from')) {
        const from = model.get('from');
        let line = this.language.translate('from', 'fields', 'Email') + ': ';
        const nameHash = model.get('nameHash') || {};
        if (from in nameHash) {
          line += nameHash[from] + ' ';
        }
        if (model.get('isHtml')) {
          line += '&lt;' + from + '&gt;';
        } else {
          line += '<' + from + '>';
        }
        list.push(line);
      }
      if (model.get('dateSent')) {
        let line = this.language.translate('dateSent', 'fields', 'Email') + ': ';
        line += this.dateTime.toDisplay(model.get('dateSent'));
        list.push(line);
      }
      if (model.get('name')) {
        let line = this.language.translate('subject', 'fields', 'Email') + ': ';
        line += model.get('name');
        list.push(line);
      }
      if (model.get('to')) {
        let line = this.language.translate('to', 'fields', 'Email') + ': ';
        const partList = [];
        model.get('to').split(';').forEach(to => {
          const nameHash = model.get('nameHash') || {};
          let line = '';
          if (to in nameHash) {
            line += nameHash[to] + ' ';
          }
          if (model.get('isHtml')) {
            line += '&lt;' + to + '&gt;';
          } else {
            line += '<' + to + '>';
          }
          partList.push(line);
        });
        line += partList.join(';');
        list.push(line);
      }
      list.forEach(line => {
        if (model.get('isHtml')) {
          prepending += '<br>' + line;
        } else {
          prepending += '\n' + line;
        }
      });
      if (model.get('isHtml')) {
        const body = model.get('body');
        attributes['body'] = prepending + '<br><br>' + body;
      } else {
        const bodyPlain = model.get('body') || model.get('bodyPlain') || '';
        attributes['bodyPlain'] = attributes['body'] = prepending + '\n\n' + bodyPlain;
      }
    }

    /**
     * Parse a name from a string address.
     *
     * @param {string} value A string address. E.g. `Test Name <address@domain>`.
     * @returns {string|null}
     */
    parseNameFromStringAddress(value) {
      if (!value.includes('<')) {
        return null;
      }
      let name = value.replace(/<(.*)>/, '').trim();
      if (name.charAt(0) === '"' && name.charAt(name.length - 1) === '"') {
        name = name.slice(1, name.length - 2);
      }
      return name;
    }

    /**
     * Parse an address from a string address.
     *
     * @param {string} value A string address. E.g. `Test Name <address@domain>`.
     * @returns {string|null}
     */
    parseAddressFromStringAddress(value) {
      const r = value.match(/<(.*)>/);
      let address;
      if (r && r.length > 1) {
        address = r[1];
      } else {
        address = value.trim();
      }
      return address;
    }

    /**
     * Add body attributes for a reply email.
     *
     * @param {module:model} model An email model.
     * @param {Object.<string, *>} attributes
     */
    addReplyBodyAttributes(model, attributes) {
      const format = this.dateTime.getReadableShortDateTimeFormat();
      const dateSent = model.get('dateSent');
      let dateSentString = null;
      if (dateSent) {
        const dateSentMoment = this.dateTime.toMoment(dateSent);
        dateSentString = dateSentMoment.format(format);
        if (dateSentMoment.year() !== this.dateTime.getNowMoment().year()) {
          dateSentString += ', ' + dateSentMoment.year();
        }
      }
      let replyHeadString = dateSentString || this.language.translate('Original message', 'labels', 'Email');
      let fromName = model.get('fromName');
      if (!fromName && model.get('from')) {
        fromName = (model.get('nameHash') || {})[model.get('from')];
        if (fromName) {
          replyHeadString += ', ' + fromName;
        }
      }
      replyHeadString += ':';
      if (model.get('isHtml')) {
        const body = model.get('body');
        attributes['body'] = `<p data-quote-start="true"><br></p>` + `<p>${replyHeadString}</p><blockquote>${body}</blockquote>`;
        return;
      }
      let bodyPlain = model.get('body') || model.get('bodyPlain') || '';
      let b = '\n\n';
      b += replyHeadString + '\n';
      bodyPlain.split('\n').forEach(line => {
        b += '> ' + line + '\n';
      });
      bodyPlain = b;
      attributes['body'] = bodyPlain;
      attributes['bodyPlain'] = bodyPlain;
    }
    static #_ = _staticBlock = () => [_init_language, _init_extra_language, _init_user, _init_extra_user, _init_dateTime, _init_extra_dateTime, _init_acl, _init_extra_acl] = _applyDecs(this, [], [[(0, _di.inject)(_language.default), 0, "language"], [(0, _di.inject)(_user.default), 0, "user"], [(0, _di.inject)(_dateTime.default), 0, "dateTime"], [(0, _di.inject)(_aclManager.default), 0, "acl"]]).e;
  }
  _staticBlock();
  var _default = _exports.default = EmailHelper;
});
//# sourceMappingURL=email-helper.js.map ;