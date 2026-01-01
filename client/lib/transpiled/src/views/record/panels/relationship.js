define("views/record/panels/relationship", ["exports", "views/record/panels/bottom", "search-manager", "helpers/record-modal", "helpers/record/create-related", "helpers/record/select-related", "di", "web-socket-manager"], function (_exports, _bottom, _searchManager, _recordModal, _createRelated, _selectRelated, _di, _webSocketManager) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _bottom = _interopRequireDefault(_bottom);
  _searchManager = _interopRequireDefault(_searchManager);
  _recordModal = _interopRequireDefault(_recordModal);
  _createRelated = _interopRequireDefault(_createRelated);
  _selectRelated = _interopRequireDefault(_selectRelated);
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
  /** @module views/record/panels/relationship */
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  function _applyDecs(e, t, n, r, o, i) { var a, c, u, s, f, l, p, d = Symbol.metadata || Symbol.for("Symbol.metadata"), m = Object.defineProperty, h = Object.create, y = [h(null), h(null)], v = t.length; function g(t, n, r) { return function (o, i) { n && (i = o, o = e); for (var a = 0; a < t.length; a++) i = t[a].apply(o, r ? [i] : []); return r ? i : o; }; } function b(e, t, n, r) { if ("function" != typeof e && (r || void 0 !== e)) throw new TypeError(t + " must " + (n || "be") + " a function" + (r ? "" : " or undefined")); return e; } function applyDec(e, t, n, r, o, i, u, s, f, l, p) { function d(e) { if (!p(e)) throw new TypeError("Attempted to access private element on non-instance"); } var h = [].concat(t[0]), v = t[3], w = !u, D = 1 === o, S = 3 === o, j = 4 === o, E = 2 === o; function I(t, n, r) { return function (o, i) { return n && (i = o, o = e), r && r(o), P[t].call(o, i); }; } if (!w) { var P = {}, k = [], F = S ? "get" : j || D ? "set" : "value"; if (f ? (l || D ? P = { get: _setFunctionName(function () { return v(this); }, r, "get"), set: function (e) { t[4](this, e); } } : P[F] = v, l || _setFunctionName(P[F], r, E ? "" : F)) : l || (P = Object.getOwnPropertyDescriptor(e, r)), !l && !f) { if ((c = y[+s][r]) && 7 !== (c ^ o)) throw Error("Decorating two elements with the same name (" + P[F].name + ") is not supported yet"); y[+s][r] = o < 3 ? 1 : o; } } for (var N = e, O = h.length - 1; O >= 0; O -= n ? 2 : 1) { var T = b(h[O], "A decorator", "be", !0), z = n ? h[O - 1] : void 0, A = {}, H = { kind: ["field", "accessor", "method", "getter", "setter", "class"][o], name: r, metadata: a, addInitializer: function (e, t) { if (e.v) throw new TypeError("attempted to call addInitializer after decoration was finished"); b(t, "An initializer", "be", !0), i.push(t); }.bind(null, A) }; if (w) c = T.call(z, N, H), A.v = 1, b(c, "class decorators", "return") && (N = c);else if (H.static = s, H.private = f, c = H.access = { has: f ? p.bind() : function (e) { return r in e; } }, j || (c.get = f ? E ? function (e) { return d(e), P.value; } : I("get", 0, d) : function (e) { return e[r]; }), E || S || (c.set = f ? I("set", 0, d) : function (e, t) { e[r] = t; }), N = T.call(z, D ? { get: P.get, set: P.set } : P[F], H), A.v = 1, D) { if ("object" == typeof N && N) (c = b(N.get, "accessor.get")) && (P.get = c), (c = b(N.set, "accessor.set")) && (P.set = c), (c = b(N.init, "accessor.init")) && k.unshift(c);else if (void 0 !== N) throw new TypeError("accessor decorators must return an object with get, set, or init properties or undefined"); } else b(N, (l ? "field" : "method") + " decorators", "return") && (l ? k.unshift(N) : P[F] = N); } return o < 2 && u.push(g(k, s, 1), g(i, s, 0)), l || w || (f ? D ? u.splice(-1, 0, I("get", s), I("set", s)) : u.push(E ? P[F] : b.call.bind(P[F])) : m(e, r, P)), N; } function w(e) { return m(e, d, { configurable: !0, enumerable: !0, value: a }); } return void 0 !== i && (a = i[d]), a = h(null == a ? null : a), f = [], l = function (e) { e && f.push(g(e)); }, p = function (t, r) { for (var i = 0; i < n.length; i++) { var a = n[i], c = a[1], l = 7 & c; if ((8 & c) == t && !l == r) { var p = a[2], d = !!a[3], m = 16 & c; applyDec(t ? e : e.prototype, a, m, d ? "#" + p : _toPropertyKey(p), l, l < 2 ? [] : t ? s = s || [] : u = u || [], f, !!t, d, r, t && d ? function (t) { return _checkInRHS(t) === e; } : o); } } }, p(8, 0), p(0, 0), p(8, 1), p(0, 1), l(u), l(s), c = f, v || w(e), { e: c, get c() { var n = []; return v && [w(e = applyDec(e, [t], r, e.name, 5, n)), g(n, 1)]; } }; }
  function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
  function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
  function _setFunctionName(e, t, n) { "symbol" == typeof t && (t = (t = t.description) ? "[" + t + "]" : ""); try { Object.defineProperty(e, "name", { configurable: !0, value: n ? n + " " + t : t }); } catch (e) {} return e; }
  function _checkInRHS(e) { if (Object(e) !== e) throw TypeError("right-hand side of 'in' should be an object, got " + (null !== e ? typeof e : "null")); return e; }
  /**
   * A relationship panel.
   *
   * @property {Object} defs
   */
  class RelationshipPanelView extends _bottom.default {
    constructor() {
      super(...arguments);
      _init_extra_webSocketManager(this);
    }
    /** @inheritDoc */
    template = 'record/panels/relationship';

    /**
     * A row-actions view.
     *
     * @protected
     */
    rowActionsView = 'views/record/row-actions/relationship';

    /**
     * An API URL.
     *
     * @protected
     * @type {string|null}
     */
    url = null;

    /**
     * @type {string}
     * @deprecated Use `entityType`.
     */
    scope;

    /**
     * An entity type.
     * @type {string}
     */
    entityType;

    /**
     * Read-only.
     */
    readOnly = false;

    /**
     * Fetch a collection on a model 'after:relate' event.
     *
     * @protected
     */
    fetchOnModelAfterRelate = false;

    /**
     * @protected
     */
    noCreateScopeList = ['User', 'Team', 'Role', 'Portal'];

    /**
     * @private
     */
    recordsPerPage = null;

    /**
     * @protected
     */
    viewModalView = null;

    /**
     * @protected
     */
    listLayoutName;

    /**
     * Also used by the stream panel view.
     *
     * @protected
     * @type {WebSocketManager}
     * @since 9.2.0
     */
    webSocketManager = _init_webSocketManager(this);
    setup() {
      super.setup();
      this.link = this.link || this.defs.link || this.panelName;
      if (!this.link) {
        throw new Error(`No link or panelName.`);
      }

      // noinspection JSDeprecatedSymbols
      if (!this.scope && !this.entityType) {
        if (!this.model) {
          throw new Error(`No model passed.`);
        }
        if (!(this.link in this.model.defs.links)) {
          throw new Error(`Link '${this.link}' is not defined in model '${this.model.entityType}'.`);
        }
      }

      // noinspection JSDeprecatedSymbols
      if (this.scope && !this.entityType) {
        // For backward compatibility.
        // noinspection JSDeprecatedSymbols
        this.entityType = this.scope;
      }
      this.entityType = this.entityType || this.model.defs.links[this.link].entity;

      // For backward compatibility.
      // noinspection JSDeprecatedSymbols
      this.scope = this.entityType;

      /** @type {Record} */
      const linkDefs = this.getMetadata().get(`entityDefs.${this.model.entityType}.links.${this.link}`) || {};
      const url = this.url = this.url || `${this.model.entityType}/${this.model.id}/${this.link}`;
      if (!('create' in this.defs)) {
        this.defs.create = true;
      }
      if (!('select' in this.defs)) {
        this.defs.select = true;
      }
      if (!('view' in this.defs)) {
        this.defs.view = true;
      }
      if (linkDefs.readOnly) {
        let hasCreate = false;
        if (this.entityType && linkDefs.foreign) {
          const foreign = linkDefs.foreign;

          /** @type {Record} */
          const foreignLinkDefs = this.getMetadata().get(`entityDefs.${this.entityType}.links.${foreign}`) || {};
          if (foreignLinkDefs.type === 'belongsTo') {
            hasCreate = true;
          } else if (foreignLinkDefs.type === 'hasMany' && this.getMetadata().get(`entityDefs.${this.entityType}.fields.${foreign}.type`) === 'linkMultiple') {
            hasCreate = true;
          }
        }
        if (!hasCreate) {
          this.defs.create = false;
        }
        this.defs.select = false;
      }
      this.filterList = this.defs.filterList || this.filterList || null;
      if (this.filterList && this.filterList.length) {
        this.filter = this.getStoredFilter() || this.filterList[0];
        if (this.filter === 'all') {
          this.filter = null;
        }
      }
      this.setupCreateAvailability();
      this.setupTitle();
      if (this.defs.createDisabled) {
        this.defs.create = false;
      }
      if (this.defs.selectDisabled) {
        this.defs.select = false;
      }
      if (this.defs.viewDisabled) {
        this.defs.view = false;
      }
      let hasCreate = false;
      if (this.defs.create) {
        if (this.getAcl().check(this.entityType, 'create') && !~this.noCreateScopeList.indexOf(this.entityType)) {
          this.buttonList.push({
            title: 'Create',
            action: this.defs.createAction || 'createRelated',
            link: this.link,
            html: '<span class="fas fa-plus"></span>',
            data: {
              link: this.link
            },
            acl: this.defs.createRequiredAccess || null
          });
          hasCreate = true;
        }
      }
      if (this.defs.select) {
        const data = {
          link: this.link
        };
        if (this.defs.selectPrimaryFilterName) {
          data.primaryFilterName = this.defs.selectPrimaryFilterName;
        }
        if (this.defs.selectBoolFilterList) {
          data.boolFilterList = this.defs.selectBoolFilterList;
        }
        data.massSelect = this.defs.massSelect;
        data.createButton = hasCreate;
        this.actionList.unshift({
          label: 'Select',
          action: this.defs.selectAction || 'selectRelated',
          data: data,
          acl: this.defs.selectRequiredAccess || 'edit'
        });
      }
      if (this.defs.view) {
        this.actionList.unshift({
          label: 'View List',
          action: this.defs.viewAction || 'viewRelatedList'
        });
      }
      this.setupActions();
      let layoutName = 'listSmall';
      this.setupListLayout();
      if (this.listLayoutName) {
        layoutName = this.listLayoutName;
      }
      let listLayout = null;
      const layout = this.defs.layout || null;
      if (layout) {
        if (typeof layout === 'string') {
          layoutName = layout;
        } else {
          layoutName = 'listRelationshipCustom';
          listLayout = layout;
        }
      }
      this.listLayout = listLayout;
      this.layoutName = layoutName;
      this.setupSorting();
      this.wait(true);
      this.getCollectionFactory().create(this.entityType, collection => {
        collection.maxSize = this.recordsPerPage || this.getConfig().get('recordsPerPageSmall') || 5;
        if (this.defs.filters) {
          const searchManager = new _searchManager.default(collection);
          searchManager.setAdvanced(this.defs.filters);
          collection.where = searchManager.getWhere();
        }
        if (this.defs.primaryFilter) {
          this.filter = this.defs.primaryFilter;
        }
        collection.url = collection.urlRoot = url;
        if (this.defaultOrderBy) {
          collection.setOrder(this.defaultOrderBy, this.defaultOrder || false, true);
        }
        this.collection = collection;
        collection.parentModel = this.model;
        this.setFilter(this.filter);
        if (this.fetchOnModelAfterRelate) {
          this.listenTo(this.model, 'after:relate', () => collection.fetch());
        }
        this.listenTo(this.model, `update-related:${this.link} update-all`, () => collection.fetch());
        this.listenTo(this.collection, 'change', () => {
          this.model.trigger(`after:related-change:${this.link}`);
        });
        if (this.defs.syncWithModel) {
          this.listenTo(this.model, 'sync', (m, a, o) => {
            if (!o.patch && !o.highlight) {
              // Skip if not save and not web-socket update.
              return;
            }
            if (this.collection.lastSyncPromise && this.collection.lastSyncPromise.getReadyState() < 4) {
              return;
            }
            this.collection.fetch();
          });
        }
        const viewName = this.defs.recordListView || this.getMetadata().get(['clientDefs', this.entityType, 'recordViews', 'listRelated']) || this.getMetadata().get(['clientDefs', this.entityType, 'recordViews', 'list']) || 'views/record/list';
        this.listViewName = viewName;
        this.rowActionsView = this.defs.readOnly ? false : this.defs.rowActionsView || this.rowActionsView;
        this.once('after:render', () => {
          this.createView('list', viewName, {
            collection: collection,
            layoutName: layoutName,
            listLayout: listLayout,
            checkboxes: false,
            rowActionsView: this.rowActionsView,
            buttonsDisabled: true,
            selector: '.list-container',
            skipBuildRows: true,
            rowActionsOptions: {
              unlinkDisabled: this.defs.unlinkDisabled,
              editDisabled: this.defs.editDisabled,
              removeDisabled: this.defs.removeDisabled
            },
            displayTotalCount: false,
            additionalRowActionList: this.defs.rowActionList
          }, view => {
            view.getSelectAttributeList(selectAttributeList => {
              if (selectAttributeList) {
                if (this.defs.mandatoryAttributeList) {
                  selectAttributeList = [...selectAttributeList, ...this.defs.mandatoryAttributeList];
                  selectAttributeList = selectAttributeList.filter((it, i) => selectAttributeList.indexOf(it) === i);
                }
                collection.data.select = selectAttributeList.join(',');
              }
              if (!this.defs.hidden) {
                collection.fetch();
                return;
              }
              this.once('show', () => collection.fetch());
            });
            if (this.defs.syncBackWithModel) {
              this.listenTo(view, 'after:save after:delete', () => this.processSyncBack());
            }
          });
        });
        this.wait(false);
      });
      this.setupFilterActions();
      this.setupLast();
    }

    /**
     * Set up lastly.
     *
     * @protected
     */
    setupLast() {}

    /**
     * Set up title.
     *
     * @protected
     */
    setupTitle() {
      this.title = this.title || this.translate(this.link, 'links', this.model.entityType);
      let iconHtml = '';
      if (!this.getConfig().get('scopeColorsDisabled')) {
        iconHtml = this.getHelper().getScopeColorIconHtml(this.entityType);
      }
      this.titleHtml = this.title;
      if (this.defs.label) {
        this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.entityType);
      } else {
        this.titleHtml = iconHtml + this.title;
      }
      if (this.filter && this.filter !== 'all') {
        this.titleHtml += ' &middot; ' + this.translateFilter(this.filter);
      }
    }

    /**
     * Set up sorting.
     *
     * @protected
     */
    setupSorting() {
      let orderBy = this.defs.orderBy || this.defs.sortBy || this.orderBy;
      let order = this.defs.orderDirection || this.orderDirection || this.order;
      if ('asc' in this.defs) {
        // @todo Remove.
        order = this.defs.asc ? 'asc' : 'desc';
      }
      if (!orderBy) {
        orderBy = this.getMetadata().get(['entityDefs', this.entityType, 'collection', 'orderBy']);
        order = this.getMetadata().get(['entityDefs', this.entityType, 'collection', 'order']);
      }
      if (orderBy && !order) {
        order = 'asc';
      }
      this.defaultOrderBy = orderBy;
      this.defaultOrder = order;
    }

    /**
     * Set up a list layout.
     *
     * @protected
     */
    setupListLayout() {}

    /**
     * Set up actions.
     *
     * @protected
     */
    setupActions() {}

    /**
     * Set up filter actions.
     *
     * @protected
     */
    setupFilterActions() {
      if (!(this.filterList && this.filterList.length)) {
        return;
      }
      this.actionList.push(false);
      this.filterList.slice(0).forEach(item => {
        let selected;
        selected = item === 'all' ? !this.filter : item === this.filter;
        const label = this.translateFilter(item);
        const $item = $('<div>').append($('<span>').addClass('check-icon fas fa-check pull-right').addClass(!selected ? 'hidden' : '')).append($('<div>').text(label));
        this.actionList.push({
          action: 'selectFilter',
          html: $item.get(0).innerHTML,
          data: {
            name: item
          }
        });
      });
    }

    /**
     * Translate a filter.
     *
     * @param {string} name A name.
     * @return {string}
     */
    translateFilter(name) {
      return this.translate(name, 'presetFilters', this.entityType);
    }

    /**
     * @protected
     */
    getStoredFilter() {
      const key = 'panelFilter' + this.model.entityType + '-' + (this.panelName || this.name);
      return this.getStorage().get('state', key) || null;
    }

    /**
     * @private
     */
    storeFilter(filter) {
      const key = 'panelFilter' + this.model.entityType + '-' + (this.panelName || this.name);
      if (filter) {
        this.getStorage().set('state', key, filter);
      } else {
        this.getStorage().clear('state', key);
      }
    }

    /**
     * Set a filter.
     *
     * @param {string} filter A filter.
     */
    setFilter(filter) {
      this.filter = filter;
      this.collection.data.primaryFilter = null;
      if (filter && filter !== 'all') {
        this.collection.data.primaryFilter = filter;
      }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * A `select-filter` action.
     *
     * @protected
     */
    actionSelectFilter(data) {
      const filter = data.name;
      let filterInternal = filter;
      if (filter === 'all') {
        filterInternal = false;
      }
      this.storeFilter(filterInternal);
      this.setFilter(filterInternal);
      this.filterList.forEach(item => {
        const $el = this.$el.closest('.panel').find('[data-name="' + item + '"] span');
        if (item === filter) {
          $el.removeClass('hidden');
        } else {
          $el.addClass('hidden');
        }
      });
      this.collection.abortLastFetch();
      this.collection.reset();
      const listView = this.getView('list');
      if (listView && listView.$el) {
        const height = listView.$el.parent().get(0).clientHeight;
        listView.$el.empty();
        if (height) {
          listView.$el.parent().css('height', height + 'px');
        }
      }
      this.collection.fetch().then(() => {
        listView.$el.parent().css('height', '');
      });
      this.setupTitle();
      if (this.isRendered()) {
        this.$el.closest('.panel').find('> .panel-heading > .panel-title > span').html(this.titleHtml);
      }
    }

    /**
     * A `refresh` action.
     *
     * @protected
     */
    async actionRefresh() {
      Espo.Ui.notifyWait();
      await this.collection.fetch();
      Espo.Ui.notify();
    }

    /**
     * A `view-related-list` action.
     *
     * @protected
     * @param {{
     *     scope?: string,
     *     entityType: string,
     *     title?: string,
     *     url?: string,
     *     viewOptions?: Record,
     * }} data
     */
    actionViewRelatedList(data) {
      const entityType = data.scope || data.entityType || this.entityType;
      const viewName = this.getMetadata().get(`clientDefs.${this.model.entityType}.relationshipPanels.${this.name}.viewModalView`) || this.getMetadata().get(`clientDefs.${entityType}.modalViews.relatedList`) || this.viewModalView || 'views/modals/related-list';
      let filter = this.filter;
      if (this.relatedListFiltersDisabled) {
        filter = null;
      }
      const options = {
        model: this.model,
        panelName: this.panelName,
        link: this.link,
        entityType: entityType,
        defs: this.defs,
        title: data.title || this.title,
        filterList: this.filterList,
        filter: filter,
        layoutName: this.layoutName,
        defaultOrder: this.defaultOrder,
        defaultOrderBy: this.defaultOrderBy,
        url: data.url || this.url,
        listViewName: this.listViewName,
        createDisabled: !this.isCreateAvailable(entityType),
        selectDisabled: !this.isSelectAvailable(entityType),
        rowActionsView: this.rowActionsView,
        panelCollection: this.collection,
        filtersDisabled: this.relatedListFiltersDisabled
      };
      if (data.viewOptions) {
        for (const item in data.viewOptions) {
          options[item] = data.viewOptions[item];
        }
      }
      Espo.Ui.notifyWait();
      this.createView('modalRelatedList', viewName, options, view => {
        Espo.Ui.notify(false);
        view.render();
        this.listenTo(view, 'action', (event, element) => {
          Espo.Utils.handleAction(this, event, element);
        });
        this.listenToOnce(view, 'close', () => {
          this.clearView('modalRelatedList');
        });
      });
    }

    /**
     * Is create available.
     *
     * @protected
     * @param {string} scope A scope (entity type).
     * @return {boolean};
     */
    isCreateAvailable(scope) {
      return !!this.defs.create;
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * Is select available.
     *
     * @protected
     * @param {string} scope A scope (entity type).
     * @return {boolean};
     */
    isSelectAvailable(scope) {
      return !!this.defs.select;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * A `view-related` action.
     *
     * @protected
     */
    actionViewRelated(data) {
      const id = data.id;
      const model = this.collection.get(id);
      if (!model) {
        return;
      }
      const scope = model.entityType;
      const helper = new _recordModal.default();
      helper.showDetail(this, {
        entityType: scope,
        id: id,
        model: model
      }).then(view => {
        // @todo Move to afterSave?
        this.listenTo(view, 'after:save', () => {
          this.collection.fetch();
          this.processSyncBack();
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * An `edit-related` action.
     *
     * @protected
     */
    actionEditRelated(data) {
      const id = data.id;
      const entityType = this.collection.get(id).entityType;
      const helper = new _recordModal.default();
      helper.showEdit(this, {
        entityType: entityType,
        id: id,
        afterSave: () => {
          this.collection.fetch();
          this.processSyncBack();
        }
      });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * An `unlink-related` action.
     *
     * @protected
     */
    actionUnlinkRelated(data) {
      const id = data.id;
      this.confirm({
        message: this.translate('unlinkRecordConfirmation', 'messages'),
        confirmText: this.translate('Unlink')
      }, () => {
        Espo.Ui.notifyWait();
        Espo.Ajax.deleteRequest(this.collection.url, {
          id: id
        }).then(() => {
          Espo.Ui.success(this.translate('Unlinked'));
          this.collection.fetch();
          this.model.trigger('after:unrelate');
          this.model.trigger('after:unrelate:' + this.link);
          this.processSyncBack();
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * A `remove-related` action.
     *
     * @protected
     * @param {{id?: string}} [data]
     * @return {Promise<void>}
     */
    async actionRemoveRelated(data) {
      const id = data.id;
      const model = this.collection.get(id);
      const index = this.collection.indexOf(model);
      if (!model) {
        throw new Error("No model.");
      }
      await this.confirm({
        message: this.translate('removeRecordConfirmation', 'messages'),
        confirmText: this.translate('Remove')
      });
      Espo.Ui.notifyWait();
      try {
        await model.destroy({
          wait: true
        });
      } catch (e) {
        if (!this.collection.models.includes(model)) {
          this.collection.add(model, {
            at: index
          });
        }
        return;
      }
      Espo.Ui.success(this.translate('Removed'));
      this.collection.fetch().then(() => {});
      this.model.trigger('after:unrelate');
      this.model.trigger(`after:unrelate:${this.link}`);
      this.processSyncBack();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * An `unlink-all-related` action.
     *
     * @protected
     */
    actionUnlinkAllRelated(data) {
      this.confirm(this.translate('unlinkAllConfirmation', 'messages'), () => {
        Espo.Ui.notifyWait();
        Espo.Ajax.postRequest(this.model.entityType + '/action/unlinkAll', {
          link: data.link,
          id: this.model.id
        }).then(() => {
          Espo.Ui.success(this.translate('Unlinked'));
          this.collection.fetch();
          this.model.trigger('after:unrelate');
          this.model.trigger('after:unrelate:' + this.link);
          this.processSyncBack();
        });
      });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @since 8.4.0
     */
    actionCreateRelated() {
      const helper = new _createRelated.default(this);
      helper.process(this.model, this.link, {
        afterSave: () => {
          this.processSyncBack();
        }
      });
    }

    /**
     * @protected
     */
    processSyncBack() {
      if (!this.defs.syncBackWithModel || this.webSocketManager.isEnabled()) {
        return;
      }
      this.model.fetch({
        highlight: true
      });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @since 8.4.0
     */
    actionSelectRelated() {
      const helper = new _selectRelated.default(this);
      helper.process(this.model, this.link, {
        hasCreate: this.defs.create,
        onCreate: () => this.actionCreateRelated()
      });
    }

    /**
     * @private
     */
    setupCreateAvailability() {
      if (!this.link || !this.entityType || !this.model) {
        return;
      }

      /** @type {module:model} */
      const model = this.model;
      const entityType = model.getLinkParam(this.link, 'entity');
      const foreignLink = model.getLinkParam(this.link, 'foreign');
      if (!entityType || !foreignLink) {
        return;
      }
      const readOnly = this.getMetadata().get(`entityDefs.${entityType}.fields.${foreignLink}.readOnly`);
      if (!readOnly) {
        return;
      }
      this.defs.create = false;
    }
    static #_ = _staticBlock = () => [_init_webSocketManager, _init_extra_webSocketManager] = _applyDecs(this, [], [[(0, _di.inject)(_webSocketManager.default), 0, "webSocketManager"]], 0, void 0, _bottom.default).e;
  }
  _staticBlock();
  var _default = _exports.default = RelationshipPanelView;
});
//# sourceMappingURL=relationship.js.map ;