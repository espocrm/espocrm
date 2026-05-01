/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

class NotificationPanelView extends View {

    template = 'notification/panel'

    /**
     * @private
     * @type {boolean}
     */
    groupingEnabled

    setup() {
        this.addActionHandler('markAllNotificationsRead', () => this.actionMarkAllRead());
        this.addActionHandler('openNotifications', () => this.actionOpenNotifications());
        this.addActionHandler('closePanel', () => this.close());

        this.addHandler('keydown', '', /** KeyboardEvent */event => {
            if (event.code === 'Escape') {
                this.close();
            }
        })

        this.groupingEnabled = this.getPreferences().get('notificationGrouping') === true;

        const promise =
            this.getCollectionFactory().create('Notification', collection => {
                this.collection = collection;
                this.collection.maxSize = this.getConfig().get('notificationsMaxSize') || 5;

                this.listenTo(this.collection, 'sync', () => {
                    this.trigger('collection-fetched');
                });
            });

        this.wait(promise);

        this.once('remove', () => {
            if (this.collection) {
                this.collection.abortLastFetch();
            }
        });
    }

    afterRender() {
        this.collection.fetch()
            .then(() => this.createRecordView())
            .then(view => view.render());

        $('#navbar li.notifications-badge-container').addClass('open');

        this.$el.find('> .panel').focus();
    }

    onRemove() {
        $('#navbar li.notifications-badge-container').removeClass('open');
    }

    /**
     * @return {Promise<module:views/record/list-expanded>}
     */
    createRecordView() {
        const viewName = this.getMetadata().get(['clientDefs', 'Notification', 'recordViews', 'list']) ||
            'views/notification/record/list';

        return /** @type {Promise<module:views/record/list-expanded>} */this.createView('list', viewName, {
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
                                groupingEnabled: this.groupingEnabled,
                            },
                        }
                    ]
                ],
            },
        });
    }

    async actionMarkAllRead() {
        this.collection.trigger('all-read');

        await Espo.Ajax.postRequest('Notification/action/markAllRead');

        this.trigger('all-read')
    }

    close() {
        this.trigger('close');
    }

    actionOpenNotifications() {
        this.getRouter().navigate('#Notification', {trigger: true});

        this.close();
    }
}

export default NotificationPanelView;
