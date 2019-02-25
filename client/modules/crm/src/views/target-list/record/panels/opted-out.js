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

Espo.define('crm:views/target-list/record/panels/opted-out', ['views/record/panels/relationship', 'multi-collection'], function (Dep, MultiCollection) {

    return Dep.extend({

        name: 'optedOut',

        template: 'crm:target-list/record/panels/opted-out',

        scopeList: ['Contact', 'Lead', 'User', 'Account'],

        listLayout: {
            'Account': {
                rows: [
                    [
                        {
                            name: 'name',
                            link: true
                        }
                    ]
                ]
            },
            'Contact': {
                rows: [
                    [
                        {
                            name: 'name',
                            link: true
                        }
                    ]
                ]
            },
            'Lead': {
                rows: [
                    [
                        {
                            name: 'name',
                            link: true
                        }
                    ]
                ]
            },
            'User': {
                rows: [
                    [
                        {
                            name: 'name',
                            link: true
                        }
                    ]
                ]
            }
        },

        data: function () {
            return {
                currentTab: this.currentTab,
                scopeList: this.scopeList
            };
        },

        getStorageKey: function () {
            return 'target-list-opted-out-' + this.model.name + '-' + this.name;
        },

        setup: function () {
            this.seeds = {};

            this.wait(true);
            var i = 0;
            this.scopeList.forEach(function (scope) {
                this.getModelFactory().getSeed(scope, function (seed) {
                    this.seeds[scope] = seed;
                    i++;
                    if (i == this.scopeList.length) {
                        this.wait(false);
                    }
                }.bind(this));
            }.bind(this));

            this.listenTo(this.model, 'opt-out', function () {
                this.actionRefresh();
            }, this);

            this.listenTo(this.model, 'cancel-opt-out', function () {
                this.actionRefresh();
            }, this);
        },

        afterRender: function () {
            var url = 'TargetList/' + this.model.id + '/' + this.name;

            this.collection = new MultiCollection();
            this.collection.seeds = this.seeds;
            this.collection.url = url;

            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', 'views/record/list-expanded', {
                    el: this.getSelector() + ' > .list-container',
                    pagination: false,
                    type: 'listRelationship',
                    rowActionsView: 'crm:views/target-list/record/row-actions/opted-out',
                    checkboxes: false,
                    collection: this.collection,
                    listLayout: this.listLayout,
                }, function (view) {
                    view.render();
                });
            }.bind(this));
            this.collection.fetch();
        },

        actionRefresh: function () {
            this.collection.fetch();
        },

        actionCancelOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                $.ajax({
                    url: 'TargetList/action/cancelOptOut',
                    type: 'POST',
                    data: JSON.stringify({
                        id: this.model.id,
                        targetId: data.id,
                        targetType: data.type
                    })
                }).done(function () {
                    this.collection.fetch();
                }.bind(this));
            }, this);
        }

    });
});

