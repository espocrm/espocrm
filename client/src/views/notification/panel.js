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

define('views/notification/panel', ['view'], function (Dep) {

    return Dep.extend({

        template: 'notification/panel',

        events: {
            'click [data-action="markAllNotificationsRead"]': function () {
                Espo.Ajax
                    .postRequest('Notification/action/markAllRead')
                    .then(() => this.trigger('all-read'));
            },
            'click [data-action="openNotifications"]': function (e) {
                this.getRouter().navigate('#Notification', {trigger: true});
                this.close();
            },
            'click [data-action="closePanel"]': function () {
                this.close();
            },
            'keydown': function (e) {
                if (e.code === 'Escape') {
                    this.close();
                }
            }
        },

        setup: function () {
            this.wait(true);

            this.getCollectionFactory().create('Notification', (collection) => {
                this.collection = collection;
                collection.maxSize = this.getConfig().get('notificationsMaxSize') || 5;

                this.wait(false);

                this.listenTo(this.collection, 'sync', () => {
                    this.trigger('collection-fetched');
                });
            });

            this.navbarPanelHeightSpace = this.getThemeManager().getParam('navbarPanelHeightSpace') || 100;
            this.navbarPanelBodyMaxHeight = this.getThemeManager().getParam('navbarPanelBodyMaxHeight') || 600;

            this.once('remove', () => {
                $(window).off('resize.notifications-height');

                if (this.overflowWasHidden) {
                    $('body').css('overflow', 'unset');

                    this.overflowWasHidden = false;
                }
            });
        },

        afterRender: function () {
            this.listenToOnce(this.collection, 'sync', () => {
                var viewName = this.getMetadata()
                    .get(['clientDefs', 'Notification', 'recordViews', 'list']) ||
                    'views/notification/record/list';

                this.createView('list', viewName, {
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
                                        containerEl: this.options.el,
                                    }
                                }
                            ]
                        ],
                        right: {
                            name: 'read',
                            view: 'views/notification/fields/read',
                            width: '10px'
                        }
                    }
                }, (view) => {
                    view.render();
                });
            });

            this.collection.fetch();

            let $window = $(window);
            $window.off('resize.notifications-height');
            $window.on('resize.notifications-height', this.processSizing.bind(this));
            this.processSizing();

            $('#navbar li.notifications-badge-container').addClass('open');

            this.$el.find('> .panel').focus();
        },

        onRemove: function () {
            $('#navbar li.notifications-badge-container').removeClass('open');
        },

        processSizing: function () {
            let $window = $(window);
            let windowHeight = $window.height();
            let windowWidth = $window.width();

            let diffHeight = this.$el.find('.panel-heading').outerHeight();

            let cssParams = {};

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
                let maxHeight = windowHeight - this.navbarPanelHeightSpace;

                cssParams.maxHeight = maxHeight + 'px';
            }

            this.$el.find('.panel-body').css(cssParams);
        },

        close: function () {
            this.trigger('close');
        },
    });
});
