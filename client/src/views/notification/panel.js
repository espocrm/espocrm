/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

    setup() {
        this.addActionHandler('markAllNotificationsRead', () => this.actionMarkAllRead());
        this.addActionHandler('openNotifications', () => this.actionOpenNotifications());
        this.addActionHandler('closePanel', () => this.close());

        this.addHandler('keydown', '', /** KeyboardEvent */event => {
            if (event.code === 'Escape') {
                this.close();
            }
        })

        const promise =
            this.getCollectionFactory().create('Notification', collection => {
                this.collection = collection;
                this.collection.maxSize = this.getConfig().get('notificationsMaxSize') || 5;

                this.listenTo(this.collection, 'sync', () => {
                    this.trigger('collection-fetched');
                });
            });

        this.wait(promise);

        this.navbarPanelHeightSpace = this.getThemeManager().getParam('navbarPanelHeightSpace') || 100;
        this.navbarPanelBodyMaxHeight = this.getThemeManager().getParam('navbarPanelBodyMaxHeight') || 600;

        this.once('remove', () => {
            $(window).off('resize.notifications-height');

            if (this.overflowWasHidden) {
                $('body').css('overflow', 'unset');

                this.overflowWasHidden = false;
            }

            if (this.collection) {
                this.collection.abortLastFetch();
            }
        });
    }

    afterRender() {
        this.collection.fetch()
            .then(() => this.createRecordView())
            .then(view => view.render());

        const $window = $(window);

        $window.off('resize.notifications-height');
        $window.on('resize.notifications-height', this.processSizing.bind(this));

        this.processSizing();

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
                            },
                        }
                    ]
                ],
                right: {
                    name: 'read',
                    view: 'views/notification/fields/read',
                    width: 'var(--10px)',
                },
            },
        });
    }

    actionMarkAllRead() {
        Espo.Ajax.postRequest('Notification/action/markAllRead')
            .then(() => this.trigger('all-read'));
    }

    processSizing() {
        const $window = $(window);
        const windowHeight = $window.height();
        const windowWidth = $window.width();

        const diffHeight = this.$el.find('.panel-heading').outerHeight();

        const cssParams = {};

        if (windowWidth <= this.getThemeManager().getParam('screenWidthXs')) {
            cssParams.height = (windowHeight - diffHeight) + 'px';
            cssParams.overflow = 'auto';

            $('body').css('overflow', 'hidden');
            this.overflowWasHidden = true;

            this.$el.find('.panel-body').css(cssParams);

            return;
        }

        cssParams.height = 'unset';
        cssParams.overflow = 'none';

        if (this.overflowWasHidden) {
            $('body').css('overflow', 'unset');

            this.overflowWasHidden = false;
        }

        if (windowHeight - this.navbarPanelBodyMaxHeight < this.navbarPanelHeightSpace) {
            const maxHeight = windowHeight - this.navbarPanelHeightSpace;

            cssParams.maxHeight = maxHeight + 'px';
        }

        this.$el.find('.panel-body').css(cssParams);
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
