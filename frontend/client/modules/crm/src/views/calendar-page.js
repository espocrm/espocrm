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

Espo.define('Crm:Views.CalendarPage', 'View', function (Dep) {

    return Dep.extend({

        template: 'crm:calendar-page',

        el: '#main',

        setup: function () {
            this.createView('calendar', 'Crm:Calendar.Calendar', {
                date: this.options.date,
                mode: this.options.mode,
                el: '#main > .calendar-container',
            }, function (view) {
                var first = true;
                this.listenTo(view, 'view', function (date, mode) {
                    if (!first) {
                        this.getRouter().navigate('#Calendar/show/date=' + date + '&mode=' + mode);
                    }
                    first = false;
                }.bind(this));
            }.bind(this));
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('Calendar', 'scopeNames'));
        },
    });
});


