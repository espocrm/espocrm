/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('crm:views/calendar/calendar-page', 'view', function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/calendar-page',

        el: '#main',

        fullCalendarModeList: ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay', 'listWeek'],

        events: {
            'click [data-action="createCustomView"]': function () {
                this.createCustomView();
            },
            'click [data-action="editCustomView"]': function () {
                this.editCustomView();
            }
        },

        setup: function () {
            this.mode = this.mode || this.options.mode || null;
            this.date = this.date || this.options.date || null;

              if (!this.mode) {
                this.mode = this.getStorage().get('state', 'calendarMode') || null;

                if (this.mode && this.mode.indexOf('view-') === 0) {
                    var viewId = this.mode.substr(5);
                    var calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
                    var isFound = false;
                    calendarViewDataList.forEach(function (item) {
                        if (item.id === viewId) {
                            isFound = true;
                        }
                    });

                    if (!isFound) {
                        this.mode = null;
                    }

                    if (this.options.userId) {
                        this.mode = null;
                    }
                }
            }

            if (!this.mode || ~this.fullCalendarModeList.indexOf(this.mode) || this.mode.indexOf('view-') === 0) {
                this.setupCalendar();
            } else {
                if (this.mode === 'timeline') {
                    this.setupTimeline();
                }
            }
        },

        updateUrl: function (trigger) {
            var url = '#Calendar/show';

            if (this.mode || this.date) {
                url += '/';
            }
            if (this.mode) {
                url += 'mode=' + this.mode;
            }
            if (this.date) {
                url += '&date=' + this.date;
            }
            if (this.options.userId) {
                url += '&userId=' + this.options.userId;
                if (this.options.userName) {
                    url += '&userName=' + this.getHelper().escapeString(this.options.userName).replace(/\\|\//g,'');
                }
            }
            this.getRouter().navigate(url, {trigger: trigger});
        },

        setupCalendar: function () {
            var viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) || 'crm:views/calendar/calendar';

            this.createView('calendar', viewName, {
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
                this.listenTo(view, 'change:mode', function (mode, refresh) {
                    this.mode = mode;
                    if (!this.options.userId) {
                        this.getStorage().set('state', 'calendarMode', mode);
                    }
                    if (refresh) {
                        this.updateUrl(true);
                        return;
                    }
                    if (!~this.fullCalendarModeList.indexOf(mode)) {
                        this.updateUrl(true);
                    }
                }, this);
            }, this);
        },

        setupTimeline: function () {
            var viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) || 'crm:views/calendar/timeline';

            this.createView('calendar', viewName, {
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
                    if (!this.options.userId) {
                        this.getStorage().set('state', 'calendarMode', mode);
                    }
                    this.updateUrl(true);
                }, this);
            }, this);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('Calendar', 'scopeNames'));
        },

        createCustomView: function () {
            this.createView('createCustomView', 'crm:views/calendar/modals/edit-view', {}, function (view) {
                view.render();

                this.listenToOnce(view, 'after:save', function (data) {
                    view.close();
                    this.mode = 'view-' + data.id;
                    this.date = null;
                    this.updateUrl(true);
                }, this);
            });
        },

        editCustomView: function () {
            var viewId = this.getView('calendar').viewId;
            if (!viewId) return;

            this.createView('createCustomView', 'crm:views/calendar/modals/edit-view', {
                id: viewId
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'after:save', function (data) {
                    view.close();
                    var calendarView = this.getView('calendar');
                    calendarView.setupMode();

                    calendarView.reRender();
                }, this);

                this.listenToOnce(view, 'after:remove', function (data) {
                    view.close();
                    this.mode = null;
                    this.date = null;
                    this.updateUrl(true);
                }, this);
            });
        }
    });
});
