/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/detail', 'views/main', function (Dep) {

    return Dep.extend({

        template: 'detail',

        scope: null,

        name: 'Detail',

        optionsToPass: ['attributes', 'returnUrl', 'returnDispatchParams', 'rootUrl'],

        headerView: 'views/header',

        recordView: 'views/record/detail',

        rootLinkDisabled: false,

        addUnfollowButtonToMenu: function () {
            this.removeMenuItem('follow', true);

            this.addMenuItem('buttons', {
                name: 'unfollow',
                label: 'Followed',
                style: 'success',
                action: 'unfollow',
            }, true);
        },

        addFollowButtonToMenu: function () {
            this.removeMenuItem('unfollow', true);

            this.addMenuItem('buttons', {
                name: 'follow',
                label: 'Follow',
                style: 'default',
                html: '<span class="fas fa-rss fa-sm"></span> ' + this.translate('Follow'),
                action: 'follow',
            }, true);
        },

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

        setupRecord: function () {
            var o = {
                model: this.model,
                el: '#main > .record',
                scope: this.scope,
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

        getRecordViewName: function () {
            return this.getMetadata()
                .get('clientDefs.' + this.scope + '.recordViews.detail') || this.recordView;
        },

        handleFollowButton: function () {
            if (this.model.get('isFollowed')) {
                this.addUnfollowButtonToMenu();
            }
            else {
                if (this.getAcl().checkModel(this.model, 'stream')) {
                    this.addFollowButtonToMenu();
                }
            }
        },

        actionFollow: function () {
            this.disableMenuItem('follow');

            Espo.Ajax
                .putRequest(this.model.name + '/' + this.model.id + '/subscription')
                .then(() => {
                    this.removeMenuItem('follow', true);
                    this.model.set('isFollowed', true);
                })
                .catch(() => {
                    this.enableMenuItem('follow');
                });
        },

        actionUnfollow: function () {
            this.disableMenuItem('unfollow');

            Espo.Ajax
                .deleteRequest(this.model.name + '/' + this.model.id + '/subscription')
                .then(() => {
                    this.removeMenuItem('unfollow', true);
                    this.model.set('isFollowed', false);
                })
                .catch(() => {
                    this.enableMenuItem('unfollow');
                });
        },

        getHeader: function () {
            var name = Handlebars.Utils.escapeExpression(this.model.get('name'));

            if (name === '') {
                name = this.model.id;
            }

            name = '<span class="font-size-flexible title">' + name + '</span>';

            if (this.model.get('deleted')) {
                name = '<span style="text-decoration: line-through;">' + name + '</span>';
            }

            var rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

            var headerIconHtml = this.getHeaderIconHtml();

            var rootHtml = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

            if (!this.rootLinkDisabled) {
                rootHtml =
                    '<a href="' + rootUrl + '" class="action" data-action="navigateToRoot">' +
                    rootHtml +
                    '</a>';
            }

            return this.buildHeaderHtml([
                headerIconHtml + rootHtml,
                name,
            ]);
        },

        updatePageTitle: function () {
            if (this.model.has('name')) {
                this.setPageTitle(this.model.get('name'));
            }
            else {
                Dep.prototype.updatePageTitle.call(this);
            }
        },

        updateRelationshipPanel: function (name) {
            var bottom = this.getView('record').getView('bottom');

            if (bottom) {
                var rel = bottom.getView(name);
                if (rel) {
                    rel.collection.fetch();
                }
            }
        },

        relatedAttributeMap: {},

        relatedAttributeFunctions: {},

        selectRelatedFilters: {},

        selectPrimaryFilterNames: {},

        selectBoolFilterLists: [],

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

            Object.keys(this.relatedAttributeMap[link] || {}).forEach((attr) => {
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
                });
            });
        },

        actionSelectRelated: function (data) {
            var link = data.link;

            if (!data.foreignEntityType && !this.model.defs['links'][link]) {
                throw new Error('Link ' + link + ' does not exist.');
            }

            var scope = data.foreignEntityType || this.model.defs['links'][link].entity;

            var massRelateEnabled = data.massSelect;

            if (link in this.selectRelatedFilters) {
                var filters = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || {};

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
                        var filters = {};

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

            var boolFilterList = dataBoolFilterList || Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);

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
                        })
                        .catch(() => this.notify('Error occurred', 'error'));
                });
            });
        },

        actionDuplicate: function () {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this
                .ajaxPostRequest(this.scope + '/action/getDuplicateAttributes', {
                    id: this.model.id
                })
                .then((attributes) => {
                    Espo.Ui.notify(false);

                    var url = '#' + this.scope + '/create';

                    this.getRouter().dispatch(this.scope, 'create', {
                        attributes: attributes,
                        returnUrl: this.getRouter().getCurrentUrl(),
                    });

                    this.getRouter().navigate(url, {trigger: false});
                });
        },

    });
});
