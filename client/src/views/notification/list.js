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

define('views/notification/list', ['view'], function (Dep) {

    return Dep.extend({

        template: 'notification/list',

        events: {
            'click [data-action="markAllNotificationsRead"]': function () {
                Espo.Ajax
                    .postRequest('Notification/action/markAllRead')
                    .then(() => {
                        this.trigger('all-read');
                        this.$el.find('.badge-circle-warning').remove();
                    });
            },
            'click [data-action="refresh"]': function () {
                this.getView('list').showNewRecords();
            },
        },

        setup: function () {
            this.wait(
                this.getCollectionFactory().create('Notification')
                    .then(collection => {
                        collection.maxSize = this.getConfig().get('recordsPerPage') || 20;
                        this.collection = collection;
                    })
            );
        },

        afterRender: function () {
            let viewName = this.getMetadata()
                .get(['clientDefs', 'Notification', 'recordViews', 'list']) ||
                'views/notification/record/list';

            let options = {
                el: this.options.el + ' .list-container',
                collection: this.collection,
                showCount: false,
                listLayout: {
                    rows: [
                        [
                            {
                                name: 'data',
                                view: 'views/notification/fields/container',
                                params: {
                                    containerEl: this.options.el
                                },
                            },
                        ],
                    ],
                    right: {
                        name: 'read',
                        view: 'views/notification/fields/read-with-menu',
                        width: '10px',
                    },
                },
            };

            this.collection
                .fetch()
                .then(() => this.createView('list', viewName, options))
                .then(view => view.render())
                .then(view => {
                    view.$el.find('> .list > .list-group');
                });
        },
    });
});
