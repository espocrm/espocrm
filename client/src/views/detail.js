/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

        addUnfollowButtonToMenu: function () {
            this.removeMenuItem('follow', true);

            this.addMenuItem('buttons', {
                name: 'unfollow',
                label: 'Followed',
                style: 'success',
                action: 'unfollow'
            }, true);
        },

        addFollowButtonToMenu: function () {
            this.removeMenuItem('unfollow', true);

            this.addMenuItem('buttons', {
                name: 'follow',
                label: 'Follow',
                style: 'default',
                html: '<span class="fas fa-rss fa-sm"></span> ' + this.translate('Follow'),
                action: 'follow'
            }, true);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerView = this.options.headerView || this.headerView;
            this.recordView = this.options.recordView || this.recordView;

            this.setupHeader();
            this.setupRecord();

            if (this.getMetadata().get('scopes.' + this.scope + '.stream')) {
                if (this.model.has('isFollowed')) {
                    this.handleFollowButton();
                }

                this.listenTo(this.model, 'change:isFollowed', function () {
                    this.handleFollowButton();
                }, this);
            }
        },

        setupHeader: function () {
            this.createView('header', this.headerView, {
                model: this.model,
                el: '#main > .header',
                scope: this.scope,
                fontSizeFlexible: true,
            });

            this.listenTo(this.model, 'sync', function (model) {
                if (model && model.hasChanged('name')) {
                    if (this.getView('header')) {
                        this.getView('header').reRender();
                    }
                    this.updatePageTitle();
                }
            }, this);
        },

        setupRecord: function () {
            var o = {
                model: this.model,
                el: '#main > .record',
                scope: this.scope
            };
            this.optionsToPass.forEach(function (option) {
                o[option] = this.options[option];
            }, this);
            if (this.options.params && this.options.params.rootUrl) {
                o.rootUrl = this.options.params.rootUrl;
            }
            if (this.model.get('deleted')) {
                o.readOnly = true;
            }
            return this.createView('record', this.getRecordViewName(), o);
        },

        getRecordViewName: function () {
            return this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.detail') || this.recordView;
        },

        handleFollowButton: function () {
            if (this.model.get('isFollowed')) {
                this.addUnfollowButtonToMenu();
            } else {
                if (this.getAcl().checkModel(this.model, 'stream')) {
                    this.addFollowButtonToMenu();
                }
            }
        },

        actionFollow: function () {
            $el = this.$el.find('[data-action="follow"]');
            $el.addClass('disabled');
            $.ajax({
                url: this.model.name + '/' + this.model.id + '/subscription',
                type: 'PUT',
                success: function () {
                    $el.remove();
                    this.model.set('isFollowed', true);
                }.bind(this),
                error: function () {
                    $el.removeClass('disabled');
                }.bind(this)
            });
        },

        actionUnfollow: function () {
            $el = this.$el.find('[data-action="unfollow"]');
            $el.addClass('disabled');
            $.ajax({
                url: this.model.name + '/' + this.model.id + '/subscription',
                type: 'DELETE',
                success: function () {
                    $el.remove();
                    this.model.set('isFollowed', false);
                }.bind(this),
                error: function () {
                    $el.removeClass('disabled');
                }.bind(this)
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

            return this.buildHeaderHtml([
                headerIconHtml + '<a href="' + rootUrl + '" class="action" data-action="navigateToRoot">' + this.getLanguage().translate(this.scope, 'scopeNamesPlural') + '</a>',
                name
            ]);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.model.get('name'));
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

            if (this.relatedAttributeFunctions[link] && typeof this.relatedAttributeFunctions[link] == 'function') {
                attributes = _.extend(this.relatedAttributeFunctions[link].call(this), attributes);
            }

            Object.keys(this.relatedAttributeMap[link] || {}).forEach(function (attr) {
                attributes[this.relatedAttributeMap[link][attr]] = this.model.get(attr);
            }, this);

            this.notify('Loading...');

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';
            this.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: this.model,
                    link: foreignLink,
                },
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.updateRelationshipPanel(link);
                    this.model.trigger('after:relate');
                }, this);
            }.bind(this));
        },

        actionSelectRelated: function (data) {
            var link = data.link;

            if (!data.foreignEntityType && !this.model.defs['links'][link]) {
                throw new Error('Link ' + link + ' does not exist.');
            }

            var scope = data.foreignEntityType || this.model.defs['links'][link].entity;

            var massRelateEnabled = data.massSelect;

            var self = this;
            var attributes = {};

            var filters = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || {};
            for (var filterName in filters) {
                if (typeof filters[filterName] == 'function') {
                    var filtersData = filters[filterName].call(this);
                    if (filtersData) {
                        filters[filterName] = filtersData;
                    } else {
                        delete filters[filterName];
                    }
                }
            }

            var primaryFilterName = data.primaryFilterName || this.selectPrimaryFilterNames[link] || null;
            if (typeof primaryFilterName == 'function') {
                primaryFilterName = primaryFilterName.call(this);
            }


            var dataBoolFilterList = data.boolFilterList;
            if (typeof data.boolFilterList == 'string') {
                dataBoolFilterList = data.boolFilterList.split(',');
            }

            var boolFilterList = dataBoolFilterList || Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);

            if (typeof boolFilterList == 'function') {
                boolFilterList = boolFilterList.call(this);
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: scope,
                multiple: true,
                createButton: false,
                filters: filters,
                massRelateEnabled: massRelateEnabled,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList
            }, function (dialog) {
                dialog.render();
                this.notify(false);
                dialog.once('select', function (selectObj) {
                    var data = {};
                    if (Object.prototype.toString.call(selectObj) === '[object Array]') {
                        var ids = [];
                        selectObj.forEach(function (model) {
                            ids.push(model.id);
                        });
                        data.ids = ids;
                    } else {
                        if (selectObj.massRelate) {
                            data.massRelate = true;
                            data.where = selectObj.where;
                        } else {
                            data.id = selectObj.id;
                        }
                    }
                    $.ajax({
                        url: self.scope + '/' + self.model.id + '/' + link,
                        type: 'POST',
                        data: JSON.stringify(data),
                        success: function () {
                            this.notify('Linked', 'success');
                            this.updateRelationshipPanel(link);
                            this.model.trigger('after:relate');
                        }.bind(this),
                        error: function () {
                            this.notify('Error occurred', 'error');
                        }.bind(this)
                    });
                }.bind(this));
            }.bind(this));
        },

        actionDuplicate: function () {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
            this.ajaxPostRequest(this.scope + '/action/getDuplicateAttributes', {
                id: this.model.id
            }).then(function (attributes) {
                Espo.Ui.notify(false);
                var url = '#' + this.scope + '/create';

                this.getRouter().dispatch(this.scope, 'create', {
                    attributes: attributes,
                });
                this.getRouter().navigate(url, {trigger: false});
            }.bind(this));


        },

    });
});
