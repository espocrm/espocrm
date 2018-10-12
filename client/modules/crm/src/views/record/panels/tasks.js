/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/record/panels/tasks', 'views/record/panels/relationship', function (Dep) {

    return Dep.extend({

        name: 'tasks',

        scope: 'Task',

        filterList: ['all', 'actual', 'completed'],

        defaultTab: 'actual',

        sortBy: 'createdAt',

        asc: false,

        buttonList: [
            {
                action: 'createTask',
                title: 'Create Task',
                acl: 'create',
                aclScope: 'Task',
                html: '<span class="fas fa-plus"></span>',
            }
        ],

        actionList: [
            {
                label: 'View List',
                action: 'viewRelatedList'
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

        setup: function () {
            this.parentScope = this.model.name;
            this.link = 'tasks';

            this.panelName = 'tasksSide';

            this.defs.create = true;

            this.url = this.model.name + '/' + this.model.id + '/' + this.link;

            if (this.parentScope == 'Account') {
                this.link = 'tasksPrimary';
            }

            this.setupSorting();

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            this.setupFilterActions();

            this.setupTitle();
        },

        afterRender: function () {
            var url = this.url;

            if (!this.getAcl().check('Task', 'read')) {
                this.$el.find('.list-container').html(this.translate('No Access'));
                this.$el.find('.button-container').remove();
                return;
            };

            this.getCollectionFactory().create('Task', function (collection) {
                this.collection = collection;
                collection.seeds = this.seeds;
                collection.url = url;
                collection.sortBy = this.defaultSortBy;
                collection.asc = this.defaultAsc;
                collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

                this.setFilter(this.filter);

                var rowActionsView = 'crm:views/record/row-actions/tasks';

                this.createView('list', 'views/record/list-expanded', {
                    el: this.getSelector() + ' > .list-container',
                    pagination: false,
                    type: 'listRelationship',
                    rowActionsView: rowActionsView,
                    checkboxes: false,
                    collection: collection,
                    listLayout: this.listLayout,
                    skipBuildRows: true
                }, function (view) {
                    view.getSelectAttributeList(function (selectAttributeList) {
                        if (selectAttributeList) {
                            this.collection.data.select = selectAttributeList.join(',');
                        }
                        this.collection.fetch();
                    }.bind(this));
                });
            }, this);
        },

        actionCreateRelated: function () {
            this.actionCreateTask();
        },

        actionCreateTask: function (data) {
            var self = this;
            var link = this.link;
            if (this.parentScope === 'Account') {
                link = 'tasks';
            }
            var scope = 'Task';
            var foreignLink = this.model.defs['links'][link].foreign;

            this.notify('Loading...');

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';

            this.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: this.model,
                    link: foreignLink,
                }
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();
                    this.model.trigger('after:relate');
                }, this);
            });

        },

        actionRefresh: function () {
            this.collection.fetch();
        },

        actionComplete: function (data) {
            var id = data.id;
            if (!id) {
                return;
            }
            var model = this.collection.get(id);
            model.save({
                status: 'Completed'
            }, {
                patch: true,
                success: function () {
                    this.collection.fetch();
                }.bind(this)
            });
        }

    });
});
