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

Espo.define('Crm:Views.Record.Panels.History', 'Crm:Views.Record.Panels.Activities', function (Dep) {

    return Dep.extend({

        name: 'history',

        scopeList: ['Meeting', 'Call', 'Email'],

        sortBy: 'dateStart',

        asc: false,

        actions: [
            {
                action: 'createActivity',
                label: 'Log Meeting',
                data: {
                    link: 'meetings',
                    status: 'Held',
                },
                acl: 'edit',
                aclScope: 'Meeting',
            },
            {
                action: 'createActivity',
                label: 'Log Call',
                data: {
                    link: 'calls',
                    status: 'Held',
                },
                acl: 'edit',
                aclScope: 'Call',
            },
            {
                action: 'archiveEmail',
                label: 'Archive Email',
                acl: 'edit',
                aclScope: 'Email',
            },
        ],

        listLayout: {
            'Meeting': {
                rows: [
                    [
                        {name: 'ico', view: 'Crm:Fields.Ico'},
                        {
                            name: 'name',
                            link: true,
                        },
                        {name: 'status'},
                    ],
                    [
                        {name: 'assignedUser'},
                        {name: 'dateStart'},
                    ]
                ]
            },
            'Call': {
                rows: [
                    [
                        {name: 'ico', view: 'Crm:Fields.Ico'},
                        {
                            name: 'name',
                            link: true,
                        },
                        {name: 'status'},
                    ],
                    [
                        {name: 'assignedUser'},
                        {name: 'dateStart'},
                    ]
                ]
            },
            'Email': {
                rows: [
                    [
                        {name: 'ico', view: 'Crm:Fields.Ico'},
                        {
                            name: 'name',
                            link: true,
                        },
                    ],
                    [
                        {name: 'status'},
                        {name: 'dateSent'},
                    ]
                ]
            },
        },

        where: {
            scope: false,
        },

        getArchiveEmailAttributes: function (data, callback) {
            data = data || {};
            var attributes = {
                dateSent: this.getDateTime().getNow(15),
                status: 'Archived',
                from: this.model.get('emailAddress'),
                to: this.getUser().get('emailAddress')
            };

            if (this.model.name == 'Contact') {
                if (this.model.get('accountId')) {
                    attributes.parentType = 'Account',
                    attributes.parentId = this.model.get('accountId');
                    attributes.parentName = this.model.get('accountName');
                }
            } else if (this.model.name == 'Lead') {
                attributes.parentType = 'Lead',
                attributes.parentId = this.model.id
                attributes.parentName = this.model.get('name');
            }
            callback.call(this, attributes);
        },

        actionArchiveEmail: function (data) {
            var self = this;
            var link = 'emails';
            var scope = 'Email';

            var relate = null;
            if ('emails' in this.model.defs['links']) {
                relate = {
                    model: this.model,
                    link: this.model.defs['links']['emails'].foreign
                };
            }

            this.notify('Loading...');

            this.getArchiveEmailAttributes(data, function (attributes) {
                this.createView('quickCreate', 'Modals.Edit', {
                    scope: scope,
                    relate: relate,
                    attributes: attributes
                }, function (view) {
                    view.render();
                    view.notify(false);
                    view.once('after:save', function () {
                        self.collection.fetch();
                    });
                });
            });


        },
    });
});

