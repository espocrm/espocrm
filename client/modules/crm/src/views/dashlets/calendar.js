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

define('crm:views/dashlets/calendar', 'views/dashlets/abstract/base', function (Dep) {

    return Dep.extend({

        name: 'Calendar',

        noPadding: true,

        _template: '<div class="calendar-container">{{{calendar}}} </div>',

        init: function () {
            Dep.prototype.init.call(this);
        },

        afterRender: function () {
            var mode = this.getOption('mode');

            if (mode === 'timeline') {
                var userList = [];
                var userIdList = this.getOption('usersIds') || [];
                var userNames = this.getOption('usersNames') || {};

                userIdList.forEach(id => {
                    userList.push({
                        id: id,
                        name: userNames[id] || id,
                    });
                });

                let viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) ||
                    'crm:views/calendar/timeline';

                this.createView('calendar', viewName, {
                    el: this.options.el + ' > .calendar-container',
                    header: false,
                    calendarType: 'shared',
                    userList: userList,
                    enabledScopeList: this.getOption('enabledScopeList'),
                    noFetchLoadingMessage: true,
                }, (view) => {
                    view.render();
                });

                return;
            }

            var teamIdList = null;

            if (~['basicWeek', 'month', 'basicDay'].indexOf(mode)) {
                teamIdList = this.getOption('teamsIds');
            }

            let viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) ||
                'crm:views/calendar/calendar';

            this.createView('calendar', viewName, {
                mode: mode,
                el: this.options.el + ' > .calendar-container',
                header: false,
                enabledScopeList: this.getOption('enabledScopeList'),
                containerSelector: this.options.el,
                teamIdList: teamIdList,
            }, (view) => {
                this.listenTo(view, 'view', () => {
                    if (this.getOption('mode') === 'month') {
                        let title = this.getOption('title');

                        let $container = $('<span>')
                            .append(
                                $('<span>').text(title),
                                ' <span class="chevron-right"></span> ',
                                $('<span>').text(view.getTitle())
                            );

                        let $headerSpan = this.$el.closest('.panel').find('.panel-heading > .panel-title > span');

                        $headerSpan.html($container.get(0).innerHTML);
                    }
                });

                view.render();

                this.on('resize', () => {
                    setTimeout(() => view.adjustSize(), 50);
                });
            });
        },

        setupActionList: function () {
            this.actionList.unshift({
                name: 'viewCalendar',
                text: this.translate('View Calendar', 'labels', 'Calendar'),
                url: '#Calendar',
                iconHtml: '<span class="far fa-calendar-alt"></span>',
            });
        },

        setupButtonList: function () {
            if (this.getOption('mode') !== 'timeline') {
                this.buttonList.push({
                    name: 'previous',
                    html: '<span class="fas fa-chevron-left"></span>',
                });

                this.buttonList.push({
                    name: 'next',
                    html: '<span class="fas fa-chevron-right"></span>',
                });
            }
        },

        actionRefresh: function () {
            var view = this.getView('calendar');

            if (!view) {
                return;
            }

            view.actionRefresh();
        },

        actionNext: function () {
            var view = this.getView('calendar');

            if (!view) {
                return;
            }

            view.actionNext();
        },

        actionPrevious: function () {
            var view = this.getView('calendar');

            if (!view) {
                return;
            }

            view.actionPrevious();
        },

        actionViewCalendar: function () {
            this.getRouter().navigate('#Calendar', {trigger: true});
        },
    });
});
