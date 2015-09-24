/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('views/detail', 'views/main', function (Dep) {

    return Dep.extend({

        template: 'detail',

        el: '#main',

        scope: null,

        name: 'Detail',

        optionsToPass: ['attributes', 'returnUrl', 'returnDispatchParams'],

        views: {
            header: {
                el: '#main > .page-header',
                view: 'Header'
            },
            body: {
                view: 'Record.Detail',
                el: '#main > .body',
            }
        },

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
                icon: 'glyphicon glyphicon-share-alt',
                html: '<span class="glyphicon glyphicon-share-alt"></span> ' + this.translate('Follow'),
                action: 'follow'
            }, true);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getMetadata().get('scopes.' + this.scope + '.stream')) {
                if (this.model.has('isFollowed')) {
                    this.handleFollowButton();
                }

                this.listenTo(this.model, 'change:isFollowed', function () {
                    this.handleFollowButton();
                }, this);
            }
        },

        handleFollowButton: function () {
            if (this.model.get('isFollowed')) {
                this.addUnfollowButtonToMenu();
            } else {
                this.addFollowButtonToMenu();
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

            return this.buildHeaderHtml([
                '<a href="#' + this.model.name + '" class="action" data-action="navigateToRoot">' + this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>',
                name
            ]);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.model.get('name'));
        },

        updateRelationshipPanel: function (name) {
            var bottom = this.getView('body').getView('bottom');
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

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'Modals.Edit';
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
                }, this);
            }.bind(this));
        },

        actionSelectRelated: function (data) {
            var link = data.link;

            if (!this.model.defs['links'][link]) {
                throw new Error('Link ' + link + ' does not exist.');
            }
            var scope = this.model.defs['links'][link].entity;
            var foreign = this.model.defs['links'][link].foreign;

            var massRelateEnabled = false;
            if (foreign) {
                var foreignType = this.getMetadata().get('entityDefs.' + scope + '.links.' + foreign + '.type');
                if (foreignType == 'hasMany') {
                    massRelateEnabled = true;
                }
            }

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

            var primaryFilterName = this.selectPrimaryFilterNames[link] || null;
            if (typeof primaryFilterName == 'function') {
                primaryFilterName = primaryFilterName.call(this);
            }

            var boolFilterList = Espo.Utils.cloneDeep(this.selectBoolFilterLists[link] || []);
            if (typeof boolFilterList == 'function') {
                boolFilterList = boolFilterList.call(this);
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') || 'Modals.SelectRecords';

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
                            self.notify('Linked', 'success');
                            self.updateRelationshipPanel(link);
                        },
                        error: function () {
                            self.notify('Error occurred', 'error');
                        },
                    });
                }.bind(this));
            }.bind(this));
        },

        actionDuplicate: function () {
            var attributes = Espo.Utils.cloneDeep(this.model.attributes);
            delete attributes.id;

            var url = '#' + this.scope + '/create';

            this.getRouter().dispatch(this.scope, 'create', {
                attributes: attributes,
            });
            this.getRouter().navigate(url, {trigger: false});
        },

    });
});

