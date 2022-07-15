/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/record/panels/relationship', ['views/record/panels/bottom', 'search-manager'],
function (Dep, SearchManager) {

    /**
     * A relationship panel.
     *
     * @class
     * @name Class
     * @extends module:views/record/panels/bottom.Class
     * @memberOf module:views/record/panels/relationship
     */
    return Dep.extend(/** @lends module:views/record/panels/relationship.Class# */{

        /**
         * @inheritDoc
         */
        template: 'record/panels/relationship',

        /**
         * A row-actions view.
         *
         * @protected
         */
        rowActionsView: 'views/record/row-actions/relationship',

        /**
         * An API URL.
         *
         * @protected
         */
        url: null,

        /**
         * A scope.
         */
        scope: null,

        /**
         * Read-only.
         */
        readOnly: false,

        /**
         * Fetch a collection on a model 'after:relate' event.
         *
         * @protected
         */
        fetchOnModelAfterRelate: false,

        /**
         * @protected
         */
        noCreateScopeList: ['User', 'Team', 'Role', 'Portal'],

        /**
         * @private
         */
        recordsPerPage: null,

        /**
         * @protected
         */
        viewModalView: null,

        init: function () {
            Dep.prototype.init.call(this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.link = this.link || this.defs.link || this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }

            this.scope = this.scope || this.model.defs.links[this.link].entity;

            let linkReadOnly = this.getMetadata()
                .get(['entityDefs', this.model.entityType, 'links', this.link, 'readOnly']) || false;

            var url = this.url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

            if (!('create' in this.defs)) {
                this.defs.create = true;
            }
            if (!('select' in this.defs)) {
                this.defs.select = true;
            }

            if (!('view' in this.defs)) {
                this.defs.view = true;
            }

            if (linkReadOnly) {
                this.defs.create = false;
                this.defs.select = false;
            }

            this.filterList = this.defs.filterList || this.filterList || null;

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

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

            var hasCreate = false;

            if (this.defs.create) {
                if (this.getAcl().check(this.scope, 'create') && !~this.noCreateScopeList.indexOf(this.scope)) {
                    this.buttonList.push({
                        title: 'Create',
                        action: this.defs.createAction || 'createRelated',
                        link: this.link,
                        acl: 'edit',
                        html: '<span class="fas fa-plus"></span>',
                        data: {
                            link: this.link,
                        },
                    });

                    hasCreate = true;
                }
            }

            if (this.defs.select) {
                var data = {link: this.link};

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
                    acl: 'edit'
                });
            }

            if (this.defs.view) {
                this.actionList.unshift({
                    label: 'View List',
                    action: this.defs.viewAction || 'viewRelatedList'
                });
            }

            this.setupActions();

            var layoutName = 'listSmall';
            this.setupListLayout();

            if (this.listLayoutName) {
                layoutName = this.listLayoutName;
            }

            var listLayout = null;

            var layout = this.defs.layout || null;

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

            this.getCollectionFactory().create(this.scope, (collection) => {
                collection.maxSize = this.recordsPerPage || this.getConfig().get('recordsPerPageSmall') || 5;

                if (this.defs.filters) {
                    var searchManager = new SearchManager(collection, 'listRelationship', false, this.getDateTime());
                    searchManager.setAdvanced(this.defs.filters);
                    collection.where = searchManager.getWhere();
                }

                collection.url = collection.urlRoot = url;

                if (this.defaultOrderBy) {
                    collection.setOrder(this.defaultOrderBy, this.defaultOrder || false, true);
                }

                this.collection = collection;

                collection.parentModel = this.model;

                this.setFilter(this.filter);

                if (this.fetchOnModelAfterRelate) {
                    this.listenTo(this.model, 'after:relate', () => {
                        collection.fetch();
                    });
                }

                this.listenTo(this.model, 'update-all', () => {
                    collection.fetch();
                });

                var viewName =
                    this.defs.recordListView ||
                    this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'listRelated']) ||
                    this.getMetadata().get(['clientDefs', this.scope, 'recordViews', 'list']) ||
                    'views/record/list';

                this.listViewName = viewName;
                this.rowActionsView = this.defs.readOnly ? false : (this.defs.rowActionsView || this.rowActionsView);

                this.once('after:render', () => {
                    this.createView('list', viewName, {
                        collection: collection,
                        layoutName: layoutName,
                        listLayout: listLayout,
                        checkboxes: false,
                        rowActionsView: this.rowActionsView,
                        buttonsDisabled: true,
                        el: this.options.el + ' .list-container',
                        skipBuildRows: true,
                        rowActionsOptions: {
                            unlinkDisabled: this.defs.unlinkDisabled,
                        },
                    }, (view) =>{
                        view.getSelectAttributeList((selectAttributeList) => {
                            if (selectAttributeList) {
                                collection.data.select = selectAttributeList.join(',');
                            }

                            if (!this.defs.hidden) {
                                collection.fetch();
                            } else {
                                this.once('show', () => {
                                    collection.fetch();
                                });
                            }
                        });
                    });
                });

                this.wait(false);
            });

            this.setupFilterActions();
            this.setupLast();
        },

        /**
         * Set up lastly.
         *
         * @protected
         */
        setupLast: function () {},

        /**
         * Set up title.
         *
         * @protected
         */
        setupTitle: function () {
            this.title = this.title || this.translate(this.link, 'links', this.model.name);

            var iconHtml = '';
            if (!this.getConfig().get('scopeColorsDisabled')) {
                iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
            }

            this.titleHtml = this.title;

            if (this.defs.label) {
                this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.scope);
            } else {
                this.titleHtml = iconHtml + this.title;
            }

            if (this.filter && this.filter !== 'all') {
                this.titleHtml += ' &middot; ' + this.translateFilter(this.filter);
            }
        },

        /**
         * Set up sorting.
         *
         * @protected
         */
        setupSorting: function () {
            var orderBy = this.defs.orderBy || this.defs.sortBy || this.orderBy;
            var order = this.defs.orderDirection || this.orderDirection || this.order;

            if ('asc' in this.defs) { // TODO remove in 5.8
                order = this.defs.asc ? 'asc' : 'desc';
            }

            if (!orderBy) {
                orderBy = this.getMetadata().get(['entityDefs', this.scope, 'collection', 'orderBy']);
                order = this.getMetadata().get(['entityDefs', this.scope, 'collection', 'order'])
            }

            if (orderBy && !order) {
                order = 'asc';
            }

            this.defaultOrderBy = orderBy;
            this.defaultOrder = order;
        },

        /**
         * Set up a list layout.
         *
         * @protected
         */
        setupListLayout: function () {},

        /**
         * Set up actions.
         *
         * @protected
         */
        setupActions: function () {},

        /**
         * Set up filter actions.
         *
         * @protected
         */
        setupFilterActions: function () {
            if (this.filterList && this.filterList.length) {
                this.actionList.push(false);

                this.filterList.slice(0).forEach((item) => {
                    let selected;

                    if (item === 'all') {
                        selected = !this.filter;
                    }
                    else {
                        selected = item === this.filter;
                    }

                    let label = this.translateFilter(item);

                    let $item =
                        $('<div>')
                            .append(
                                $('<span>')
                                    .addClass('check-icon fas fa-check pull-right')
                                    .addClass(!selected ? 'hidden' : '')
                            )
                            .append(
                                $('<div>').text(label)
                            );

                    this.actionList.push({
                        action: 'selectFilter',
                        html: $item.get(0).innerHTML,
                        data: {
                            name: item,
                        },
                    });
                });
            }
        },

        /**
         * Translate a filter.
         *
         * @param {string} name A name.
         * @return {string}
         */
        translateFilter: function (name) {
            return this.translate(name, 'presetFilters', this.scope);
        },

        /**
         * @private
         */
        getStoredFilter: function () {
            var key = 'panelFilter' + this.model.name + '-' + (this.panelName || this.name);

            return this.getStorage().get('state', key) || null;
        },

        /**
         * @private
         */
        storeFilter: function (filter) {
            var key = 'panelFilter' + this.model.name + '-' + (this.panelName || this.name);

            if (filter) {
                this.getStorage().set('state', key, filter);
            } else {
                this.getStorage().clear('state', key);
            }
        },

        /**
         * Set a filter.
         *
         * @param {string} filter A filter.
         */
        setFilter: function (filter) {
            this.filter = filter;
            this.collection.data.primaryFilter = null;

            if (filter && filter !== 'all') {
                this.collection.data.primaryFilter = filter;
            }
        },

        /**
         * A `select-filter` action.
         *
         * @protected
         */
        actionSelectFilter: function (data) {
            var filter = data.name;
            var filterInternal = filter;

            if (filter === 'all') {
                filterInternal = false;
            }

            this.storeFilter(filterInternal);
            this.setFilter(filterInternal);

            this.filterList.forEach(item => {
                var $el = this.$el.closest('.panel').find('[data-name="'+item+'"] span');

                if (item === filter) {
                    $el.removeClass('hidden');
                } else {
                    $el.addClass('hidden');
                }
            });

            this.collection.reset();

            var listView = this.getView('list');

            if (listView && listView.$el) {
                var height = listView.$el.parent().get(0).clientHeight;

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
                this.$el.closest('.panel')
                    .find('> .panel-heading > .panel-title > span')
                    .html(this.titleHtml);
            }
        },

        /**
         * A `refresh` action.
         *
         * @protected
         */
        actionRefresh: function () {
            this.collection.fetch();
        },

        /**
         * A `view-related-list` action.
         *
         * @protected
         */
        actionViewRelatedList: function (data) {
            var viewName =
                this.getMetadata().get(
                    ['clientDefs', this.model.name, 'relationshipPanels', this.name, 'viewModalView']
                ) ||
                this.getMetadata().get(['clientDefs', this.scope, 'modalViews', 'relatedList']) ||
                this.viewModalView ||
                'views/modals/related-list';

            var scope = data.scope || this.scope;

            var filter = this.filter;

            if (this.relatedListFiltersDisabled) {
                filter = null;
            }

            var options = {
                model: this.model,
                panelName: this.panelName,
                link: this.link,
                scope: scope,
                defs: this.defs,
                title: data.title || this.title,
                filterList: this.filterList,
                filter: filter,
                layoutName: this.layoutName,
                defaultOrder: this.defaultOrder,
                defaultOrderBy: this.defaultOrderBy,
                url: data.url || this.url,
                listViewName: this.listViewName,
                createDisabled: !this.isCreateAvailable(scope),
                selectDisabled: !this.isSelectAvailable(scope),
                rowActionsView: this.rowActionsView,
                panelCollection: this.collection,
                filtersDisabled: this.relatedListFiltersDisabled,
            };

            if (data.viewOptions) {
                for (var item in data.viewOptions) {
                    options[item] = data.viewOptions[item];
                }
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));

            this.createView('modalRelatedList', viewName, options, (view) => {
                Espo.Ui.notify(false);

                view.render();

                this.listenTo(view, 'action', (action, data, e) => {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);

                    if (typeof this[method] === 'function') {
                        this[method](data, e);

                        e.preventDefault();
                    }
                });

                this.listenToOnce(view, 'close', () => {
                    this.clearView('modalRelatedList');
                });
            });
        },

        /**
         * Is create available.
         *
         * @protected
         * @param {string} scope A scope (entity type).
         * @return {boolean};
         */
        isCreateAvailable: function (scope) {
            return !!this.defs.create;
        },

        /**
         * Is select available.
         *
         * @protected
         * @param {string} scope A scope (entity type).
         * @return {boolean};
         */
        isSelectAvailable: function (scope) {
            return !!this.defs.select;
        },

        /**
         * A `view-related` action.
         *
         * @protected
         */
        actionViewRelated: function (data) {
            var id = data.id;
            var scope = this.collection.get(id).name;

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.detail') ||
                'views/modals/detail';

            this.notify('Loading...');

            this.createView('quickDetail', viewName, {
                scope: scope,
                id: id,
                model: this.collection.get(id),
            }, (view) => {
                view.once('after:render', () => {
                    Espo.Ui.notify(false);
                });

                view.render();

                view.once('after:save', () => {
                    this.collection.fetch();
                });
            });
        },

        /**
         * An `edit-related` action.
         *
         * @protected
         */
        actionEditRelated: function (data) {
            var id = data.id;
            var scope = this.collection.get(id).name;

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') ||
                'views/modals/edit';

            this.notify('Loading...');

            this.createView('quickEdit', viewName, {
                scope: scope,
                id: id,
            }, (view) => {
                view.once('after:render', () => {
                    Espo.Ui.notify(false);
                });

                view.render();

                view.once('after:save', () => {
                    this.collection.fetch();
                });
            });
        },

        /**
         * An `unlink-related` action.
         *
         * @protected
         */
        actionUnlinkRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink'),
            }, () => {
                this.notify('Unlinking...');

                Espo.Ajax
                    .deleteRequest(this.collection.url, {
                        id: id,
                    }).then(() => {
                        this.notify('Unlinked', 'success');

                        this.collection.fetch();

                        this.model.trigger('after:unrelate');
                        this.model.trigger('after:unrelate:' + this.link);
                    });
            });
        },

        /**
         * A `remove-related` action.
         *
         * @protected
         */
        actionRemoveRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove'),
            }, () => {
                var model = this.collection.get(id);

                this.notify('Removing...');

                model
                    .destroy()
                    .then(() => {
                        this.notify('Removed', 'success');

                        this.collection.fetch();

                        this.model.trigger('after:unrelate');
                        this.model.trigger('after:unrelate:' + this.link);
                    });
            });
        },

        /**
         * An `unlink-all-related` action.
         *
         * @protected
         */
        actionUnlinkAllRelated: function (data) {
            this.confirm(this.translate('unlinkAllConfirmation', 'messages'), () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                Espo.Ajax
                    .postRequest(this.model.name + '/action/unlinkAll', {
                        link: data.link,
                        id: this.model.id,
                    })
                    .then(() => {
                        this.notify('Unlinked', 'success');

                        this.collection.fetch();

                        this.model.trigger('after:unrelate');
                        this.model.trigger('after:unrelate:' + this.link);
                    });
            });
        },
    });
});
