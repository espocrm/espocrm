define("helpers/model/defaults-populator", ["exports", "di", "metadata", "view-helper", "models/settings", "models/user", "acl-manager", "models/preferences"], function (_exports, _di, _metadata, _viewHelper, _settings, _user, _aclManager, _preferences) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _metadata = _interopRequireDefault(_metadata);
  _viewHelper = _interopRequireDefault(_viewHelper);
  _settings = _interopRequireDefault(_settings);
  _user = _interopRequireDefault(_user);
  _aclManager = _interopRequireDefault(_aclManager);
  _preferences = _interopRequireDefault(_preferences);
  var _staticBlock;
  let _init_metadata, _init_extra_metadata, _init_viewHelper, _init_extra_viewHelper, _init_config, _init_extra_config, _init_user, _init_extra_user, _init_preferences, _init_extra_preferences, _init_acl, _init_extra_acl;
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
   * Defaults populator.
   */
  class DefaultsPopulator {
    constructor() {
      _init_extra_acl(this);
    }
    /**
     * @private
     * @type {Metadata}
     */
    metadata = _init_metadata(this);

    /**
     * @private
     * @type {ViewHelper}
     */
    viewHelper = (_init_extra_metadata(this), _init_viewHelper(this));

    /**
     * @private
     * @type {Settings}
     */
    config = (_init_extra_viewHelper(this), _init_config(this));

    /**
     * @private
     * @type {User}
     */
    user = (_init_extra_config(this), _init_user(this));

    /**
     * @private
     * @type {Preferences}
     */
    preferences = (_init_extra_user(this), _init_preferences(this));

    /**
     * @private
     * @type {AclManager}
     */
    acl = (_init_extra_preferences(this), _init_acl(this));

    /**
     * Populate default values.
     *
     * @param {module:model} model A model.
     * @return {Promise}
     */
    populate(model) {
      model.populateDefaults();
      const defaultHash = {};
      if (!this.user.isPortal()) {
        this.prepare(model, defaultHash);
      }
      if (this.user.isPortal()) {
        this.prepareForPortal(model, defaultHash);
      }
      this.prepareFields(model, defaultHash);
      for (const attr in defaultHash) {
        if (model.has(attr)) {
          delete defaultHash[attr];
        }
      }
      model.set(defaultHash, {
        silent: true
      });
      const preparatorClass = this.metadata.get(`clientDefs.${model.entityType}.modelDefaultsPreparator`);
      if (!preparatorClass) {
        return Promise.resolve();
      }
      return Espo.loader.requirePromise(preparatorClass).then(Class => {
        /** @type {import('handlers/model/defaults-preparator').default} */
        const preparator = new Class(this.viewHelper);
        return preparator.prepare(model);
      }).then(attributes => {
        model.set(attributes, {
          silent: true
        });
      });
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepare(model, defaultHash) {
      const hasAssignedUsers = model.hasField('assignedUsers') && model.getLinkParam('assignedUsers', 'entity') === 'User';
      if (model.hasField('assignedUser') || hasAssignedUsers) {
        let assignedUserField = 'assignedUser';
        if (hasAssignedUsers) {
          assignedUserField = 'assignedUsers';
        }
        if (this.toFillAssignedUser(model, assignedUserField)) {
          if (hasAssignedUsers) {
            defaultHash['assignedUsersIds'] = [this.user.id];
            defaultHash['assignedUsersNames'] = {};
            defaultHash['assignedUsersNames'][this.user.id] = this.user.get('name');
          } else {
            defaultHash['assignedUserId'] = this.user.id;
            defaultHash['assignedUserName'] = this.user.get('name');
          }
        }
      }
      const defaultTeamId = this.user.get('defaultTeamId');
      if (defaultTeamId) {
        if (model.hasField('teams') && !model.getFieldParam('teams', 'default') && Espo.Utils.lowerCaseFirst(model.getLinkParam('teams', 'relationName') || '') === 'entityTeam') {
          defaultHash['teamsIds'] = [defaultTeamId];
          defaultHash['teamsNames'] = {};
          defaultHash['teamsNames'][defaultTeamId] = this.user.get('defaultTeamName');
        }
      }
      const hasCollaborators = model.hasField('collaborators') && model.getLinkParam('collaborators', 'entity') === 'User' && this.metadata.get(`scopes.${model.entityType}.collaborators`);
      if (hasCollaborators) {
        defaultHash.collaboratorsIds = [this.user.id];
        defaultHash.collaboratorsNames = {
          [this.user.id]: this.user.attributes.name
        };
      }
    }

    /**
     *
     * @param {import('model').default} model
     * @param {string} assignedUserField
     */
    toFillAssignedUser(model, assignedUserField) {
      if (!this.preferences.get('doNotFillAssignedUserIfNotRequired')) {
        return true;
      }
      if (model.getFieldParam(assignedUserField, 'required')) {
        return true;
      }
      if (this.acl.getPermissionLevel('assignmentPermission') === 'no') {
        return true;
      }
      if (this.acl.getPermissionLevel('assignmentPermission') === 'team' && !this.user.get('defaultTeamId')) {
        return true;
      }
      if (this.acl.getLevel(model.entityType, 'read') === 'own') {
        return true;
      }
      if (!this.acl.checkField(model.entityType, assignedUserField, 'edit')) {
        return true;
      }
      return false;
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepareForPortal(model, defaultHash) {
      const accountLink = this.metadata.get(`aclDefs.${model.entityType}.accountLink`);
      const contactLink = this.metadata.get(`aclDefs.${model.entityType}.contactLink`);
      if (accountLink && model.hasField(accountLink) && ['belongsTo', 'hasOne'].includes(model.getLinkType(accountLink)) && model.getLinkParam(accountLink, 'entity') === 'Account') {
        if (this.user.attributes.accountId) {
          defaultHash[accountLink + 'Id'] = this.user.attributes.accountId;
          defaultHash[accountLink + 'Name'] = this.user.attributes.accuntName;
        }
      }
      if (contactLink && model.hasField(contactLink) && ['belongsTo', 'hasOne'].includes(model.getLinkType(contactLink)) && model.getLinkParam(contactLink, 'entity') === 'Contact') {
        if (this.user.attributes.contactId) {
          defaultHash[contactLink + 'Id'] = this.user.attributes.contactId;
          defaultHash[contactLink + 'Name'] = this.user.attributes.contactName;
        }
      }
      if (accountLink && model.hasField(accountLink) && model.getLinkType(accountLink) === 'hasMany' && model.getLinkParam(accountLink, 'entity') === 'Account') {
        if (this.user.attributes.accountsIds) {
          defaultHash['accountsIds'] = [...this.user.attributes.accountsIds];
          defaultHash['accountsNames'] = {
            ...this.user.attributes.accountsNames
          };
        }
      }
      if (contactLink && model.hasField(contactLink) && model.getLinkType(contactLink) === 'hasMany' && model.getLinkParam(contactLink, 'entity') === 'Contact') {
        if (this.user.attributes.contactId) {
          defaultHash['contactsIds'] = [this.user.attributes.contactId];
          defaultHash['contactsNames'] = {
            [this.user.attributes.contactId]: this.user.attributes.contactName
          };
        }
      }
      if (model.hasField('parent') && model.getLinkType('parent') === 'belongsToParent') {
        if (!this.config.get('b2cMode')) {
          if (this.user.attributes.accountId && (model.getFieldParam('parent', 'entityList') || []).includes('Account')) {
            defaultHash['parentId'] = this.user.attributes.accountId;
            defaultHash['parentName'] = this.user.attributes.accountName;
            defaultHash['parentType'] = 'Account';
          }
        } else {
          if (this.user.attributes.contactId && (model.getFieldParam('parent', 'entityList') || []).includes('Contact')) {
            defaultHash['parentId'] = this.user.attributes.contactId;
            defaultHash['parentName'] = this.user.attributes.contactName;
            defaultHash['parentType'] = 'Contact';
          }
        }
      }
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepareFields(model, defaultHash) {
      const set = (attribute, value) => {
        if (attribute in defaultHash || model.has(attribute)) {
          return;
        }
        defaultHash[attribute] = value;
      };
      model.getFieldList().forEach(field => {
        const type = model.getFieldType(field);
        if (!type) {
          return;
        }
        if (model.getFieldParam(field, 'disabled') || model.getFieldParam(field, 'utility')) {
          return;
        }
        if (type === 'enum') {
          /** @type {string[]} */
          const options = model.getFieldParam(field, 'options') || [];
          let value = options[0] || '';
          value = value !== '' ? value : null;
          if (value) {
            set(field, value);
          }
        }
      });
    }
    static #_ = _staticBlock = () => [_init_metadata, _init_extra_metadata, _init_viewHelper, _init_extra_viewHelper, _init_config, _init_extra_config, _init_user, _init_extra_user, _init_preferences, _init_extra_preferences, _init_acl, _init_extra_acl] = _applyDecs(this, [], [[(0, _di.inject)(_metadata.default), 0, "metadata"], [(0, _di.inject)(_viewHelper.default), 0, "viewHelper"], [(0, _di.inject)(_settings.default), 0, "config"], [(0, _di.inject)(_user.default), 0, "user"], [(0, _di.inject)(_preferences.default), 0, "preferences"], [(0, _di.inject)(_aclManager.default), 0, "acl"]]).e;
  }
  _staticBlock();
  var _default = _exports.default = DefaultsPopulator;
});
//# sourceMappingURL=defaults-populator.js.map ;