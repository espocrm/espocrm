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

define('views/detail', ['views/main'], function (Dep) {

    /**
     * A detail view page.
     *
     * @class
     * @name Class
     * @extends module:views/main.Class
     * @memberOf module:views/detail
     */
    return Dep.extend(/** @lends module:views/detail.Class# */{

        /**
         * @inheritDoc
         */
        template: 'detail',

        /**
         * @inheritDoc
         */
        name: 'Detail',

        /**
         * @inheritDoc
         */
        optionsToPass: [
            'attributes',
            'returnUrl',
            'returnDispatchParams',
            'rootUrl',
        ],

        /**
         * A header view name.
         *
         * @type {string}
         */
        headerView: 'views/header',

        /**
         * A record view name.
         *
         * @type {string}
         */
        recordView: 'views/record/detail',

        /**
         * A root breadcrumb item not to be a link.
         *
         * @type {boolean}
         */
        rootLinkDisabled: false,

        /**
         * Add an un-follow button.
         */
        addUnfollowButtonToMenu: function () {
            this.removeMenuItem('follow', true);

            this.addMenuItem('buttons', {
                name: 'unfollow',
                label: 'Followed',
                style: 'success',
                action: 'unfollow',
            }, true);
        },

        /**
         * Add a follow button.
         */
        addFollowButtonToMenu: function () {
            this.removeMenuItem('unfollow', true);

            this.addMenuItem('buttons', {
                name: 'follow',
                label: 'Follow',
                style: 'default',
                iconHtml: '<span class="fas fa-rss fa-sm"></span>',
                text: this.translate('Follow'),
                action: 'follow',
            }, true);
        },

        /**
         * @inheritDoc
         */
        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerView = this.options.headerView || this.headerView;
            this.recordView = this.options.recordView || this.recordView;

            this.setupHeader();
            this.setupRecord();

            this.setupPageTitle();

            if (this.getMetadata().get('scopes.' + this.scope + '.stream')) {
                if (this.model.has('isFollowed')) {
                    this.handleFollowButton();
                }

                this.listenTo(this.model, 'change:isFollowed', () => {
                    this.handleFollowButton();
                });
            }

            this.getHelper().processSetupHandlers(this, 'detail');
        },

        /**
         * Set up a page title.
         */
        setupPageTitle: function () {
            this.listenTo(this.model, 'after:save', () => {
                this.updatePageTitle();
            });

            this.listenTo(this.model, 'sync', (model) => {
                if (model && model.hasChanged('name')) {
                    this.updatePageTitle();
                }
            });
        },

        /**
         * Set up a header.
         */
        setupHeader: function () {
            this.createView('header', this.headerView, {
                model: this.model,
                el: '#main > .header',
                scope: this.scope,
                fontSizeFlexible: true,
            });

            this.listenTo(this.model, 'sync', (model) => {
                if (model && model.hasChanged('name')) {
                    if (this.getView('header')) {
                        this.getView('header').reRender();
                    }
                }
            });
        },

        /**
         * Set up a record.
         */
        setupRecord: function () {
            let o = {
                model: this.model,
                el: '#main > .record',
                scope: this.scope,
                shortcutKeysEnabled: true,
            };

            this.optionsToPass.forEach((option) => {
                o[option] = this.options[option];
            });

            if (this.options.params && this.options.params.rootUrl) {
                o.rootUrl = this.options.params.rootUrl;
            }

            if (this.model.get('deleted')) {
                o.readOnly = true;
            }

            return this.createView('record', this.getRecordViewName(), o);
        },

        /**
         * Get a record view name.
         *
         * @returns {string}
         */
        getRecordViewName: function () {
            return this.getMetadata()
                .get('clientDefs.' + this.scope + '.recordViews.detail') || this.recordView;
        },

        /**
         * Control follow/unfollow buttons visibility.
         */
        handleFollowButton: function () {
            if (this.model.get('isFollowed')) {
                this.addUnfollowButtonToMenu();

                return;
            }

            if (this.getAcl().checkModel(this.model, 'stream')) {
                this.addFollowButtonToMenu();
            }
        },

        /**
         * Action 'follow'.
         */
        actionFollow: function () {
            this.disableMenuItem('follow');

            Espo.Ajax
                .putRequest(this.model.name + '/' + this.model.id + '/subscription')
                .then(() => {
                    this.removeMenuItem('follow', true);

                    this.model.set('isFollowed', true, {sync: true});
                })
                .catch(() => {
                    this.enableMenuItem('follow');
                });
        },

        /**
         * Action 'unfollow'.
         */
        actionUnfollow: function () {
            this.disableMenuItem('unfollow');

            Espo.Ajax
                .deleteRequest(this.model.name + '/' + this.model.id + '/subscription')
                .then(() => {
                    this.removeMenuItem('unfollow', true);

                    this.model.set('isFollowed', false, {sync: true});
                })
                .catch(() => {
                    this.enableMenuItem('unfollow');
                });
        },

        /**
         * @inheritDoc
         */
        getHeader: function () {
            let name = this.model.get('name') || this.model.id;

            let $name =
                $('<span>')
                    .addClass('font-size-flexible title')
                    .text(name);

            if (this.model.get('deleted')) {
                $name.css('text-decoration', 'line-through');
            }

            let rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
            let headerIconHtml = this.getHeaderIconHtml();
            let scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

            let $root = $('<span>').text(scopeLabel);

            if (!this.rootLinkDisabled) {
                $root = $('<span>')
                    .append(
                        $('<a>')
                            .attr('href', rootUrl)
                            .addClass('action')
                            .attr('data-action', 'navigateToRoot')
                            .text(scopeLabel)
                    );
            }

            if (headerIconHtml) {
                $root.prepend(headerIconHtml);
            }

            return this.buildHeaderHtml([
                $root,
                $name,
            ]);
        },

        /**
         * @inheritDoc
         */
        updatePageTitle: function () {
            if (this.model.has('name')) {
                this.setPageTitle(this.model.get('name'));
            }
            else {
                Dep.prototype.updatePageTitle.call(this);
            }
        },

        /**
         * Update a relationship panel (fetch data).
         *
         * @param {string} name A relationship name.
         */
        updateRelationshipPanel: function (name) {
            var bottom = this.getView('record').getView('bottom');

            if (bottom) {
                var rel = bottom.getView(name);

                if (rel) {
                    rel.collection.fetch();
                }
            }
        },

        /**
         * When a related record created, attributes will be copied from a current entity.
         *
         * Example:
         * ```
         * {
         *     'linkName': {
         *         'attributeNameOfCurrentEntity': 'attributeNameOfCreatedRelatedEntity',
         *     }
         * }
         * ```
         *
         * @type {Object}
         */
        relatedAttributeMap: {},

        /**
         * When a related record created, use a function to obtain some attributes for a created entity.
         *
         * Example:
         * ```
         * {
         *     'linkName': function () {
         *         return {
         *            'someAttribute': this.model.get('attribute1') + ' ' +
         *                 this.model.get('attribute2')
         *         };
         *     },
         * }
         * ```
         *
         * @type {Object}
         */
        relatedAttributeFunctions: {},

        /**
         * When selecting a related record, field filters can be automatically applied.
         *
         * Example:
         * ```
         * {
         *     'linkName': {
         *         'field1': function () {
         *             return {
         *                 attribute: 'field1',
         *                 type: 'equals',
         *                 value: this.model.get('someField'),
         *                 data: {}, // Additional filter data specific for a field type.
         *             };
         *         },
         *     },
         * }
         * ```
         *
         * @type {Object}
         */
        selectRelatedFilters: {},

        /**
         * When selecting a related record, a primary filter can be automatically applied.
         *
         * Example:
         * ```
         * {
         *     'linkName1': 'primaryFilterName',
         *     'linkName2': function () {
         *         return 'primaryFilterName';
         *     },
         * }
         * ```
         *
         * @type {Object}
         */
        selectPrimaryFilterNames: {},

        /**
         * When selecting a related record, bool filters can be automatically applied.
         *
         * Example:
         * ```
         * {
         *     'linkName1': ['onlyMy', 'followed'],
         *     'linkName2': function () {
         *         return ['someBoolFilterName];
         *     },
         * }
         * ```
         *
         * @type {Object}
         */
        selectBoolFilterLists: [],

        /**
         * Action 'createRelated'.
         *
         * @param {Object} data
         */
        actionCreateRelated: function (data) {
            data = data || {};

            var link = data.link;
            var scope = this.model.defs['links'][link].entity;
            var foreignLink = this.model.defs['links'][link].foreign;

            var attributes = {};

            if (
                this.relatedAttributeFunctions[link] &&
                typeof this.relatedAttributeFunctions[link] === 'function'
            ) {
                attributes = _.extend(this.relatedAttributeFunctions[link].call(this), attributes);
            }

            Object.keys(this.relatedAttributeMap[link] || {})
                .forEach(attr => {
                    attributes[this.relatedAttributeMap[link][attr]] = this.model.get(attr);
                });

            this.notify('Loading...');

            var viewName = this.getMetadata()
                .get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';

            this.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: this.model,
                    link: foreignLink,
                },
                attributes: attributes,
            }, (view) => {
                view.render();
                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    if (data.fromSelectRelated) {
                        setTimeout(() => {
                            this.clearView('dialogSelectRelated');
                        }, 25);
                    }

                    this.updateRelationshipPanel(link);

                    this.model.trigger('after:relate');
                    this.model.trigger('after:relate:' + link);
                });
            });
        },

        /**
         * Action 'selectRelated'.
         *
         * @param {Object} data
         */
        actionSelectRelated: function (data) {
            var link = data.link;

            if (!data.foreignEntityType && !this.model.defs['links'][link]) {
                throw new Error('Link ' + link + ' does not exist.');
            }

            var scope = data.foreignEntityType || this.model.defs['links'][link].entity;

            var massRelateEnabled = data.massSelect;

            let filters;

            if (link in this.selectRelatedFilters) {
                filters = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || {};

                for (var filterName in filters) {
                    if (typeof filters[filterName] === 'function') {
                        var filtersData = filters[filterName].call(this);

                        if (filtersData) {
                            filters[filterName] = filtersData;
                        } else {
                            delete filters[filterName];
                        }
                    }
                }
            }
            else {
                var foreignLink = (this.model.defs['links'][link] || {}).foreign;

                if (foreignLink && scope) {
                    var foreignLinkType = this.getMetadata()
                        .get(['entityDefs', scope, 'links', foreignLink, 'type']);

                    var foreignLinkFieldType = this.getMetadata()
                        .get(['entityDefs', scope, 'fields', foreignLink, 'type']);

                    if (
                        ~['belongsTo', 'belongsToParent'].indexOf(foreignLinkType) &&
                        foreignLinkFieldType
                    ) {
                        filters = {};

                        if (foreignLinkFieldType === 'link' || foreignLinkFieldType === 'linkParent') {
                            filters[foreignLink] = {
                                type: 'isNull',
                                attribute: foreignLink + 'Id',
                                data: {
                                    type: 'isEmpty',
                                },
                            };
                        }
                    }
                }
            }

            var primaryFilterName = data.primaryFilterName || this.selectPrimaryFilterNames[link] || null;

            if (typeof primaryFilterName === 'function') {
                primaryFilterName = primaryFilterName.call(this);
            }

            var dataBoolFilterList = data.boolFilterList;

            if (typeof data.boolFilterList === 'string') {
                dataBoolFilterList = data.boolFilterList.split(',');
            }

            var boolFilterList = dataBoolFilterList ||
                Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);

            if (typeof boolFilterList === 'function') {
                boolFilterList = boolFilterList.call(this);
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.selectFollowers') ||
                this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') ||
                'views/modals/select-records';

            this.notify('Loading...');

            this.createView('dialogSelectRelated', viewName, {
                scope: scope,
                multiple: true,
                createButton: data.createButton || false,
                triggerCreateEvent: true,
                filters: filters,
                massRelateEnabled: massRelateEnabled,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList,
            }, (dialog) => {
                dialog.render();

                Espo.Ui.notify(false);

                this.listenTo(dialog, 'create', () => {
                    this.actionCreateRelated({
                        link: data.link,
                        fromSelectRelated: true,
                    });
                });

                this.listenToOnce(dialog, 'select', (selectObj) => {
                    var data = {};

                    if (Object.prototype.toString.call(selectObj) === '[object Array]') {
                        var ids = [];

                        selectObj.forEach((model) => {
                            ids.push(model.id);
                        });

                        data.ids = ids;
                    }
                    else {
                        if (selectObj.massRelate) {
                            data.massRelate = true;
                            data.where = selectObj.where;
                            data.searchParams = selectObj.searchParams;
                        }
                        else {
                            data.id = selectObj.id;
                        }
                    }

                    Espo.Ajax.postRequest(this.scope + '/' + this.model.id + '/' + link, data)
                        .then(() => {
                            this.notify('Linked', 'success');
                            this.updateRelationshipPanel(link);

                            this.model.trigger('after:relate');
                            this.model.trigger('after:relate:' + link);
                        });
                });
            });
        },

        /**
         * Action 'duplicate'.
         */
        actionDuplicate: function () {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax.postRequest(this.scope + '/action/getDuplicateAttributes', {
                    id: this.model.id
                })
                .then((attributes) => {
                    Espo.Ui.notify(false);

                    var url = '#' + this.scope + '/create';

                    this.getRouter().dispatch(this.scope, 'create', {
                        attributes: attributes,
                        returnUrl: this.getRouter().getCurrentUrl(),
                        options: {
                            duplicateSourceId: this.model.id,
                        },
                    });

                    this.getRouter().navigate(url, {trigger: false});
                });
        },
    });
});
