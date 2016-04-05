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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/calendar/calendar-page', 'view', function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/calendar-page',

        el: '#main',

        fullCalendarModeList: ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay'],

        setup: function () {
            this.mode = this.mode || this.options.mode || null;
            this.date = this.date || this.options.date || null;

            if (!this.mode) {
                this.mode = this.getStorage().get('state', 'calendarMode') || null;
            }

            if (!this.mode || ~this.fullCalendarModeList.indexOf(this.mode)) {
                this.setupCalendar();
            } else {
                if (this.mode === 'timeline') {
                    this.setupTimeline();
                }
            }
        },

        updateUrl: function (trigger) {
            var url = '#Calendar/show/mode=' + this.mode;
            if (this.date) {
                url += '&date=' + this.date;
            }
            if (this.options.userId) {
                url += '&userId=' + this.options.userId;
                if (this.options.userName) {
                    url += '&userName=' + this.options.userName;
                }
            }
            this.getRouter().navigate(url, {trigger: trigger});
        },

        setupCalendar: function () {
            this.createView('calendar', 'crm:views/calendar/calendar', {
                date: this.date,
                userId: this.options.userId,
                userName: this.options.userName,
                mode: this.mode,
                el: '#main > .calendar-container',
            }, function (view) {
                var initial = true;
                this.listenTo(view, 'view', function (date, mode) {
                    this.date = date;
                    this.mode = mode;
                    if (!initial) {
                        this.updateUrl();
                    }
                    initial = false;
                }, this);
                this.listenTo(view, 'change:mode', function (mode) {
                    this.mode = mode;
                    this.getStorage().set('state', 'calendarMode', mode);
                    if (!~this.fullCalendarModeList.indexOf(mode)) {
                        this.updateUrl(true);
                    }
                }, this);
            }, this);
        },

        setupTimeline: function () {
            this.createView('calendar', 'crm:views/calendar/timeline', {
                date: this.date,
                userId: this.options.userId,
                userName: this.options.userName,
                el: '#main > .calendar-container',
            }, function (view) {
                var first = true;
                this.listenTo(view, 'view', function (date, mode) {
                    this.date = date;
                    this.mode = mode;
                    this.updateUrl();
                }, this);
                this.listenTo(view, 'change:mode', function (mode) {
                    this.mode = mode;
                    this.getStorage().set('state', 'calendarMode', mode);
                    this.updateUrl(true);
                }, this);
            }, this);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('Calendar', 'scopeNames'));
        },
    });
});


