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

Espo.define('crm:views/calendar/timeline', ['view', 'lib!vis'], function (Dep, Vis) {

    return Dep.extend({

        template: 'crm:calendar/timeline',

        eventAttributes: [],

        colors: {},

        scopeList: [],

        canceledStatusList: [],

        completedStatusList: [],

        header: true,

        modeList: [],

        defaultMode: 'timeline',

        maxRange: 120,

        calendarType: 'single',

        data: function () {
            var scopeFilterList = Espo.Utils.clone(this.scopeList);
            scopeFilterList.unshift('all');

            var scopeFilterDataList = [];
            this.scopeList.forEach(function (scope) {
                var o = {scope: scope};
                if (!~this.enabledScopeList.indexOf(scope)) {
                    o.disabled = true;
                }
                scopeFilterDataList.push(o);
            }, this);

            var calendarTypeDataList = this.getCalendarTypeDataList();

            return {
                mode: this.mode,
                modeList: this.modeList,
                header: this.header,
                scopeFilterDataList: scopeFilterDataList,
                calendarTypeDataList: calendarTypeDataList,
                calendarTypeSelectEnabled: calendarTypeDataList.length > 1,
                calendarTypeLabel: this.getCalendarTypeLabel(this.calendarType)
            };
        },

        events: {
            'click button[data-action="today"]': function () {
                this.timeline.moveTo(moment());
                this.triggerView();
            },
            'click button[data-action="mode"]': function (e) {
                var mode = $(e.currentTarget).data('mode');
                this.trigger('change:mode', mode);
            },
            'click [data-action="refresh"]': function (e) {
            	this.runFetch();
            },
            'click [data-action="toggleScopeFilter"]': function (e) {
                var $target = $(e.currentTarget);
                var filterName = $target.data('name');

                var $check = $target.find('.filter-check-icon');
                if ($check.hasClass('hidden')) {
                    $check.removeClass('hidden');
                } else {
                    $check.addClass('hidden');
                }

                e.stopPropagation(e);

                this.toggleScopeFilter(filterName);
            },
            'click [data-action="toggleCalendarType"]': function (e) {
                var $target = $(e.currentTarget);
                var calendarType = $target.data('name');

                $target.parent().parent().find('.calendar-type-check-icon').addClass('hidden');

                var $check = $target.find('.calendar-type-check-icon');
                if ($check.hasClass('hidden')) {
                    $check.removeClass('hidden');
                }

                $target.closest('.calendar-type-button-group').find('.calendar-type-label').text(this.getCalendarTypeLabel(calendarType));

                this.selectCalendarType(calendarType);
            }
        },

        setup: function () {
            this.date = this.options.date || null;
            this.mode = this.options.mode || this.defaultMode;
            this.header = ('header' in this.options) ? this.options.header : this.header;

            this.$container = this.options.$container;

            this.colors = this.getMetadata().get('clientDefs.Calendar.colors') || this.colors;
            this.modeList = this.getMetadata().get('clientDefs.Calendar.modeList') || this.modeList;
            this.canceledStatusList = this.getMetadata().get('clientDefs.Calendar.canceledStatusList') || this.canceledStatusList;
            this.completedStatusList = this.getMetadata().get('clientDefs.Calendar.completedStatusList') || this.completedStatusList;
            this.scopeList = this.getMetadata().get('clientDefs.Calendar.scopeList') || Espo.Utils.clone(this.scopeList);
            this.allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDaySopeList') || this.allDayScopeList;

            this.scopeFilter = false;

            var scopeList = [];
            this.scopeList.forEach(function (scope) {
                if (this.getAcl().check(scope)) {
                    scopeList.push(scope);
                }
            }, this);
            this.scopeList = scopeList;

            if (this.header) {
                this.enabledScopeList = this.getStoredEnabledScopeList() || Espo.Utils.clone(this.scopeList);
            } else {
                this.enabledScopeList = this.options.enabledScopeList || Espo.Utils.clone(this.scopeList);
            }

            if (Object.prototype.toString.call(this.enabledScopeList) !== '[object Array]') {
                this.enabledScopeList = [];
            }
        },

        getCalendarTypeDataList: function () {
            var list = [];

            var o = {
                type: 'single',
                disabled: this.calendarType !== 'single',
                label: this.getCalendarTypeLabel('single')
            };


            list.push(o);

            if (this.options.userId) {
                return list;
            }

            list.push({
                type: 'shared',
                label: this.getCalendarTypeLabel('shared'),
                disabled: this.calendarType !== 'shared'
            });

            return list;
        },

        getCalendarTypeLabel: function (type) {
            var label;
            if (type === 'single') {
                if (this.options.userId) {
                    label = this.options.userName || this.options.userId;
                } else {
                    label = this.getUser().get('name');
                }
                return label;
            }

            if (type === 'shared') {
                return this.translate('Shared', 'labels', 'Calendar');
            }
        },

        selectCalendarType: function (name) {
            this.clendarType = name;
        },

        getSharedCalenderUserList: function () {
            this.getStorage().get('calendar', 'sharedUserList');
        },

        toggleScopeFilter: function (name) {
            var index = this.enabledScopeList.indexOf(name);
            if (!~index) {
                this.enabledScopeList.push(name);
            } else {
                this.enabledScopeList.splice(index, 1);
            }

            this.storeEnabledScopeList(this.enabledScopeList);

            this.runFetch();
        },

        getStoredEnabledScopeList: function () {
            var key = 'calendarEnabledScopeList';
            return this.getStorage().get('state', key) || null;
        },

        storeEnabledScopeList: function (enabledScopeList) {
            var key = 'calendarEnabledScopeList';
            this.getStorage().set('state', key, enabledScopeList);
        },

        updateDate: function () {
            if (!this.header) {
                return;
            }
            var title;

            if (this.options.userId && this.options.userName) {
                title += ' (' + this.options.userName + ')';
            }
            this.$el.find('.date-title h4 span').text(title);
        },

        convertEvent: function (o) {
            var userId = o.userId || this.userList[0].id || this.getUser().id;

            var event = {
                content: o.name,
                id: o.userId + '-' + o.scope + '-' + o.id,
                group: userId,
                recordId: o.id,
                scope: o.scope,
                status: o.status,
                dateStart: o.dateStart,
                dateEnd: o.dateEnd,
                type: 'range'
            };

            this.eventAttributes.forEach(function (attr) {
                event[attr] = o[attr];
            });
            if (o.dateStart) {
                if (!o.dateStartDate) {
                    event.start = this.getDateTime().toMoment(o.dateStart);
                } else {
                    event.start = this.getDateTime().toMoment(o.dateStartDate);
                }
            }
            if (o.dateEnd) {
                if (!o.dateEndDate) {
                    event.end = this.getDateTime().toMoment(o.dateEnd);
                } else {
                    event.end = this.getDateTime().toMomentDate(o.dateEndDate);
                }
            }

            if (~['Task'].indexOf(o.scope)) {
                event.type = 'box';
                if (event.end) {
                    event.start = event.end;
                }
            }

            this.fillColor(event);

            this.handleStatus(event);

            return event;
        },

        fillColor: function (event) {
            var color = this.colors[event.scope];
            var d = event.dateEnd;

            color = this.shadeColor(color, 0.15);

            if (~this.completedStatusList.indexOf(event.status) || ~this.canceledStatusList.indexOf(event.status)) {
            	color = this.shadeColor(color, 0.4);
            }

            event.style = event.style || '';
            event.style += 'background-color:' + color + ';';
            event.style += 'border-color:' + color + ';';
        },

        handleStatus: function (event) {
            if (~this.canceledStatusList.indexOf(event.status)) {
                event.className = 'event-canceled';
            } else {
                event.className = '';
            }
        },

        shadeColor: function (color, percent) {
            var f = parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
            return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
        },

        handleAllDay: function (event, notInitial) {
            if (~this.allDayScopeList.indexOf(event.scope)) {
                event.allDay = true;
                if (!notInitial) {
                    if (event.end) {
                        event.start = event.end;
                    }
                }
                return;
            }

            if (!event.start || !event.end) {
                event.allDay = true;
                if (event.end) {
                    event.start = event.end;
                }
            } else {
                if (
                    (event.start.format('d') != event.end.format('d') && (event.end.hours() != 0 || event.end.minutes() != 0))
                    ||
                    (event.end.unix() - event.start.unix() >= 86400)
                ) {
                    event.allDay = true;
                    if (!notInitial) {
                        if (event.end.hours() != 0 || event.end.minutes() != 0) {
                            event.end.add('days', 1);
                        }
                    }
                } else {
                    event.allDay = false;
                }
            }
        },

        convertEventList: function (list) {
            var resultList = [];
            list.forEach(function (o) {
                var event = this.convertEvent(o);
                resultList.push(event);
            }, this);
            return resultList;
        },

        convertTime: function (d) {
            var format = this.getDateTime().internalDateTimeFormat;
            var timeZone = this.getDateTime().timeZone;
            var string = d.format(format);

            var m;
            if (timeZone) {
                m = moment.tz(string, format, timeZone).utc();
            } else {
                m = moment.utc(string, format);
            }

            return m.format(format) + ':00';
        },

        afterRender: function () {
            if (this.options.containerSelector) {
                this.$container = $(this.options.containerSelector);
            }

            var $timeline = this.$timeline = this.$el.find('div.timeline');

            this.initUserList();
            this.initDates();
            this.initGroupsDataSet();

            this.fetchEvents(this.start, this.end, function (eventList) {
                var itemsDataSet = new Vis.DataSet(eventList);
                var timeline = this.timeline = new Vis.Timeline($timeline.get(0), itemsDataSet, this.groupsDataSet, {
                    dataAttributes: 'all',
                    start: this.start.format(this.getDateTime().internalDateTimeFormat),
                    end: this.end.format(this.getDateTime().internalDateTimeFormat),
                    moment: function (date) {
                        return moment(date).tz(this.getDateTime().timeZone);
                    }.bind(this),
                    format: this.getFormatObject(),
                    zoomMax: 24 * 3600 *  1000 * this.maxRange,
                    zoomMin: 1000 * 60 * 15
                });

                timeline.on('rangechanged', function (e) {
                    this.start = moment(e.start);
                    this.end = moment(e.end);
                    this.runFetch();
                }.bind(this));

                this.once('remove', function () {
                    timeline.destroy();
                }, this);
            }.bind(this));
        },

        runFetch: function () {
            this.fetchEvents(this.start, this.end, function (eventList) {
                var itemsDataSet = new Vis.DataSet(eventList);
                this.timeline.setItems(itemsDataSet);
                this.triggerView();
            }.bind(this));
        },

        getFormatObject: function () {
            var format = {
                minorLabels: {
                    millisecond: 'SSS',
                    second: 's',
                    minute: this.getDateTime().getTimeFormat(),
                    hour: this.getDateTime().getTimeFormat(),
                    weekday: 'ddd D',
                    day: 'D',
                    month: 'MMM',
                    year: 'YYYY'
                },
                majorLabels: {
                    millisecond: this.getDateTime().getTimeFormat() + ' ss',
                    second: this.getDateTime().getReadableDateFormat() + ' HH:mm',
                    minute: 'ddd D MMMM',
                    hour: 'ddd D MMMM',
                    weekday: 'MMMM YYYY',
                    day: 'MMMM YYYY',
                    month: 'YYYY',
                    year: ''
                }
            };
            return format;
        },

        triggerView: function () {
            var m = this.start.clone().add('seconds', Math.round((this.end.unix() - this.start.unix()) / 2));

            this.trigger('view', m.format(this.getDateTime().internalDateFormat), this.mode);
        },

        initUserList: function () {
            this.userList = [];

            if (this.calendarType === 'single') {
                if (this.options.userId) {
                    this.userList.push({
                        id: this.options.userId,
                        name: this.options.userName || this.options.userId
                    });
                } else {
                    this.userList.push({
                        id: this.getUser().id,
                        name: this.getUser().get('name')
                    });
                }
            }

            if (this.calendarType === 'shared') {
                this.getSharedCalenderUserList().forEach(function (item) {
                    this.userList.push({
                        id: item.id,
                        name: item.name
                    });
                }, this);
            }
        },

        initDates: function () {
            if (this.date) {
                this.start = moment.tz(this.date, this.getDateTime().timeZone);
            } else {
                this.start = moment.tz(this.getDateTime().timeZone);
            }
            this.end = this.start.clone();
            this.end.add('day', 1);

            this.fetchedStart = null;
            this.fetchedEnd = null;
        },

        initGroupsDataSet: function () {
            var list = [];
            this.userList.forEach(function (user) {
                list.push({
                    id: user.id,
                    content: user.name
                });
            }, this);
            this.groupsDataSet = new Vis.DataSet(list);
        },

        initItemsDataSet: function () {
            this.itemsDataSet = new Vis.DataSet(list);
        },

        fetchEvents: function (from, to, callback) {
            Espo.Ui.notify(this.translate('Loading...'));

            from = from.clone().add('days', -2);
            to = to.clone().add('hours', 12);

            var fromString = from.utc().format(this.getDateTime().internalDateTimeFormat);
            var toString = to.utc().format(this.getDateTime().internalDateTimeFormat);

            var url = 'Activities?from=' + fromString + '&to=' + toString;
            var userIdList = this.userList.map(function (user) {
                return user.id
            }, this);


            if (userIdList.length === 1) {
                url += '&userId=' + userIdList[0];
            } else {
                url += '&userIdList=' + encodeURIComponent(userIdList.join(','));
            }
            url += '&scopeList=' + encodeURIComponent(this.enabledScopeList.join(','));

            this.ajaxGetRequest(url).then(function (data) {
                if (!this.fetchedStart || from.unix() < this.fetchedStart.unix) {
                    this.fetchedStart = from.clone();
                }
                if (!this.fetchedEnd || to.unix() > this.fetchedEnd.unix) {
                    this.fetchedEnd = to.clone();
                }
                var eventList = this.convertEventList(data);
                callback(eventList);
                this.notify(false);
            }.bind(this));
        },

        addModel: function (model) {
            var d = model.attributes;
            d.scope = model.name;
            var event = this.convertToFcEvent(d);
            this.$calendar.fullCalendar('renderEvent', event);
        },

        updateModel: function (model) {
            var eventId = model.name + '-' + model.id;

            var events = this.$calendar.fullCalendar('clientEvents', eventId);
            if (!events.length) return;

            var event = events[0];

            var d = model.attributes;
            d.scope = model.name;
            var data = this.convertToFcEvent(d);
            for (var key in data) {
                event[key] = data[key];
            }

            this.$calendar.fullCalendar('updateEvent', event);
        },

        removeModel: function (model) {
            this.$calendar.fullCalendar('removeEvents', model.name + '-' + model.id);
        }

    });
});

