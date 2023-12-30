/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import View from 'view';

class NotificationListView extends View {

    template = 'notification/list'

    setup() {
        this.addActionHandler('refresh', () => this.getRecordView().showNewRecords());
        this.addActionHandler('markAllNotificationsRead', () => this.actionMarkAllRead());

        const promise =
            this.getCollectionFactory().create('Notification')
                .then(collection => {
                    this.collection = collection;
                    this.collection.maxSize = this.getConfig().get('recordsPerPage') || 20;
                })

        this.wait(promise);
    }

    afterRender() {
        const viewName = this.getMetadata()
                .get(['clientDefs', 'Notification', 'recordViews', 'list']) ||
            'views/notification/record/list';

        const options = {
            selector: '.list-container',
            collection: this.collection,
            showCount: false,
            listLayout: {
                rows: [
                    [
                        {
                            name: 'data',
                            view: 'views/notification/fields/container',
                            options: {
                                containerSelector: this.getSelector(),
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
    }

    actionMarkAllRead() {
        Espo.Ajax.postRequest('Notification/action/markAllRead')
            .then(() => {
                this.trigger('all-read');

                this.$el.find('.badge-circle-warning').remove();
            });
    }

    /**
     * @return {module:views/notification/record/list}
     */
    getRecordView() {
        return this.getView('list');
    }
}

export default NotificationListView;
