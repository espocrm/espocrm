/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Crm:Views.Record.Panels.Tasks', 'Views.Record.Panels.Relationship', function (Dep) {

    return Dep.extend({

        name: 'tasks',

        template: 'crm:record.panels.tasks',

        tabList: ['Active', 'Inactive'],

        sortBy: 'createdAt',

        asc: false,

        actions: [
            {
                action: 'createTask',
                label: 'Create Task',
                acl: 'edit',
                aclScope: 'Task',
            }
        ],

        listLayout: {
            rows: [
                [
                    {
                        name: 'name',
                        link: true,
                    },
                    {
                        name: 'isOverdue'
                    }
                ],
                [
                    {name: 'assignedUser'},
                    {name: 'status'},
                    {name: 'dateEnd'},
                ]
            ]
        },


        events: _.extend({
            'click button.tab-switcher': function (e) {
                var $target = $(e.currentTarget);
                this.$el.find('button.tab-switcher').removeClass('active');
                $target.addClass('active');

                this.currentTab = $target.data('tab');

                this.collection.where = this.where = [
                    {
                        type: 'boolFilters',
                        value: [this.currentTab]
                    }
                ];

                this.listenToOnce(this.collection, 'sync', function () {
                    this.notify(false);
                }.bind(this));
                this.notify('Loading...');
                this.collection.fetch();

                this.getStorage().set('state', this.getStorageKey(), this.currentTab);
            }
        }, Dep.prototype.events),

        data: function () {
            return {
                currentTab: this.currentTab,
                tabList: this.tabList
            };
        },

        getStorageKey: function () {
            return 'tasks-' + this.model.name + '-' + this.name;
        },

        setup: function () {
            this.currentTab = this.getStorage().get('state', this.getStorageKey()) || 'Active';

            this.where = [
                {
                    type: 'boolFilters',
                    value: [this.currentTab]
                }
            ];
        },

        afterRender: function () {
            var url = this.model.name + '/' + this.model.id + '/tasks';

            if (!this.getAcl().check('Task', 'read')) {
                this.$el.find('.list-container').html(this.translate('No Access'));
                this.$el.find('.button-container').remove();
                return;
            };                  

            this.getCollectionFactory().create('Task', function (collection) {
                this.collection = collection;
                collection.seeds = this.seeds;
                collection.url = url;
                collection.where = this.where;
                collection.sortBy = this.sortBy;
                collection.asc = this.asc;
                collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

                this.listenToOnce(this.collection, 'sync', function () {
                    this.createView('list', 'Record.ListExpanded', {
                        el: this.$el.selector + ' > .list-container',
                        pagination: false,
                        type: 'listRelationship',
                        rowActionsView: 'Record.RowActions.RelationshipNoUnlink',
                        checkboxes: false,
                        collection: collection,
                        listLayout: this.listLayout,
                    }, function (view) {
                        view.render();
                    });
                }.bind(this));            
                this.collection.fetch();            
            }, this);
        },
        
        actionCreateTask: function (data) {
            var self = this;
            var link = 'tasks';
            var scope = 'Task';
            var foreignLink = this.model.defs['links'][link].foreign;

            this.notify('Loading...');
            this.getModelFactory().create(scope, function (model) {
                this.createView('quickCreate', 'Modals.Edit', {
                    scope: scope,
                    relate: {
                        model: this.model,
                        link: foreignLink,
                    }
                }, function (view) {
                    view.render();
                    view.notify(false);
                    view.once('after:save', function () {
                        self.collection.fetch();
                    });
                });
            }.bind(this));
        },    

    });
});

