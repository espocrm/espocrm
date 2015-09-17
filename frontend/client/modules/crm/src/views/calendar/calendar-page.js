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

Espo.define('crm:views/calendar/calendar-page', 'view', function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/calendar-page',

        el: '#main',

        setup: function () {
            var mode = this.options.mode || null;
            if (!mode) {
                var mode = this.getStorage().get('state', 'calendarMode') || null;
            }
            this.createView('calendar', 'crm:views/calendar/calendar', {
                date: this.options.date,
                userId: this.options.userId,
                userName: this.options.userName,
                mode: mode,
                el: '#main > .calendar-container',
            }, function (view) {
                var first = true;
                this.listenTo(view, 'view', function (date, mode) {
                    var url = '#Calendar/show/date=' + date + '&mode=' + mode;
                    if (this.options.userId) {
                        url += '&userId=' + this.options.userId;
                        if (this.options.userName) {
                            url += '&userName=' + this.options.userName;
                        }
                    }
                    if (!first) {
                        this.getRouter().navigate(url);
                    }
                    first = false;
                }, this);
                this.listenTo(view, 'change:mode', function (mode) {
                    this.getStorage().set('state', 'calendarMode', mode);
                }, this);
            }.bind(this));
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('Calendar', 'scopeNames'));
        },
    });
});


