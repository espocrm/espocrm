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

Espo.define('crm:views/calendar/calendar', ['view', 'lib!full-calendar'], function (Dep, FullCalendar) {

    return Dep.extend({

        template: 'crm:calendar/calendar',

        eventAttributes: [],

        colors: {},

        allDayScopeList: ['Task'],

        scopeList: ['Meeting', 'Call', 'Task'],

        canceledStatusList: [],

        completedStatusList: [],

        header: true,

        modeList: [],

        fullCalendarModeList: ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay'],

        defaultMode: 'agendaWeek',

        slotDuration: 30,

        titleFormat: {
            month: 'MMMM YYYY',
            week: 'MMMM D, YYYY',
            day: 'dddd, MMMM D, YYYY'
        },

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

            return {
                mode: this.mode,
                modeList: this.modeList,
                header: this.header,
                scopeFilterDataList: scopeFilterDataList
            };
        },

        events: {
            'click button[data-action="prev"]': function () {
                this.$calendar.fullCalendar('prev');
                this.updateDate();
            },
            'click button[data-action="next"]': function () {
                this.$calendar.fullCalendar('next');
                this.updateDate();
            },
            'click button[data-action="today"]': function () {
                this.$calendar.fullCalendar('today');
                this.updateDate();
            },
            'click button[data-action="mode"]': function (e) {
                var mode = $(e.currentTarget).data('mode');
                if (~this.fullCalendarModeList.indexOf(mode)) {
                    this.$el.find('button[data-action="mode"]').removeClass('active');
                    this.$el.find('button[data-mode="' + mode + '"]').addClass('active');
                    this.$calendar.fullCalendar('changeView', mode);
                    this.updateDate();
                }
                this.trigger('change:mode', mode);
            },
            'click [data-action="refresh"]': function (e) {
                this.actionRefresh();
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
            }
        },

        setup: function () {
            this.date = this.options.date || null;
            this.mode = this.options.mode || this.defaultMode;
            this.header = ('header' in this.options) ? this.options.header : this.header;
            this.slotDuration = this.options.slotDuration || this.slotDuration;

            this.$container = this.options.$container;

            this.colors = this.getMetadata().get('clientDefs.Calendar.colors') || this.colors;
            this.modeList = this.getMetadata().get('clientDefs.Calendar.modeList') || this.modeList;
            this.canceledStatusList = this.getMetadata().get('clientDefs.Calendar.canceledStatusList') || this.canceledStatusList;
            this.completedStatusList = this.getMetadata().get('clientDefs.Calendar.completedStatusList') || this.completedStatusList;
            this.scopeList = this.getMetadata().get('clientDefs.Calendar.scopeList') || Espo.Utils.clone(this.scopeList);
            this.allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') || this.allDayScopeList;

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

        toggleScopeFilter: function (name) {
            var index = this.enabledScopeList.indexOf(name);
            if (!~index) {
                this.enabledScopeList.push(name);
            } else {
                this.enabledScopeList.splice(index, 1);
            }

            this.storeEnabledScopeList(this.enabledScopeList);

            this.$calendar.fullCalendar('refetchEvents');
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
            var view = this.$calendar.fullCalendar('getView');
            var today = new Date();

            if (view.start <= today && today < view.end) {
                this.$el.find('button[data-action="today"]').addClass('active');
            } else {
                this.$el.find('button[data-action="today"]').removeClass('active');
            }

            var map = {
                'agendaWeek': 'week',
                'agendaDay': 'day',
                'basicWeek': 'week',
                'basicDay': 'day',
            };

            var viewName = map[view.name] || view.name

            var title;

            if (viewName == 'week') {
                title = $.fullCalendar.formatRange(view.start, view.end, this.titleFormat[viewName], ' - ');
            } else {
                title = view.intervalStart.format(this.titleFormat[viewName]);
            }
            if (this.options.userId && this.options.userName) {
                title += ' (' + this.options.userName + ')';
            }
            this.$el.find('.date-title h4 span').text(title);
        },

        convertToFcEvent: function (o) {
            var event = {
                title: o.name,
                scope: o.scope,
                id: o.scope + '-' + o.id,
                recordId: o.id,
                dateStart: o.dateStart,
                dateEnd: o.dateEnd,
                dateStartDate: o.dateStartDate,
                dateEndDate: o.dateEndDate,
                status: o.status
            };

            this.eventAttributes.forEach(function (attr) {
                event[attr] = o[attr];
            });
            if (o.dateStart) {
                if (!o.dateStartDate) {
                    event.start = this.getDateTime().toMoment(o.dateStart);
                } else {
                    event.start = this.getDateTime().toMomentDate(o.dateStartDate);
                }
            }
            if (o.dateEnd) {
                if (!o.dateEndDate) {
                    event.end = this.getDateTime().toMoment(o.dateEnd);
                } else {
                    event.end = this.getDateTime().toMomentDate(o.dateEndDate);
                }
            }
            if (event.end && event.start) {
                event.duration = event.end.unix() - event.start.unix();
                if (event.duration < 1800) {
                    event.end = event.start.clone().add(30, 'm');
                }
            }

            event.allDay = false;

            this.handleAllDay(event);

            this.fillColor(event);

            this.handleStatus(event);

            return event;
        },

        fillColor: function (event) {
            var color = this.colors[event.scope];
            var d = event.dateEnd;

            if (~this.completedStatusList.indexOf(event.status) || ~this.canceledStatusList.indexOf(event.status)) {
            	color = this.shadeColor(color, 0.4);
            }
            event.color = color;
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
                            event.end.add(1, 'days');
                        }
                    }
                } else {
                    event.allDay = false;
                }
            }
        },

        convertToFcEvents: function (list) {
            this.now = moment.tz(this.getDateTime().getTimeZone());

            var events = [];
            list.forEach(function (o) {
                var event = this.convertToFcEvent(o);
                events.push(event);
            }.bind(this));
            return events;
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

        getCalculatedHeight: function () {
            if (this.$container && this.$container.size()) {
                return this.$container.height();
            }
            var height = $(window).height();
            var width = $(window).width();
            var spaceHeight = 150;
            if (width < 768) {
                spaceHeight = 164;
            }
            return $(window).height() - spaceHeight;
        },

        adjustSize: function () {
            var height = this.getCalculatedHeight();
            this.$calendar.fullCalendar('option', 'contentHeight', height);
        },

        afterRender: function () {
            if (this.options.containerSelector) {
                this.$container = $(this.options.containerSelector);
            }

            var $calendar = this.$calendar = this.$el.find('div.calendar');

            var slotDuration = '00:' + this.slotDuration + ':00';

            var options = {
                header: false,
                axisFormat: this.getDateTime().timeFormat,
                timeFormat: this.getDateTime().timeFormat,
                defaultView: this.mode,
                weekNumbers: true,
                editable: true,
                selectable: true,
                selectHelper: true,
                height: this.options.height || null,
                firstDay: this.getDateTime().weekStart,
                slotEventOverlap: true,
                slotDuration: slotDuration,
                snapDuration: this.slotDuration * 60 * 1000,
                timezone: this.getDateTime().timeZone,
                windowResize: function () {
                    this.adjustSize();
                }.bind(this),
                select: function (start, end, allDay) {
                    var dateStart = this.convertTime(start);
                    var dateEnd = this.convertTime(end);

                    var attributes = {
                        dateStart: dateStart,
                        dateEnd: dateEnd
                    };
                    if (this.options.userId) {
                        attributes.assignedUserId = this.options.userId;
                        attributes.assignedUserName = this.options.userName || this.options.userId;
                    }

                    this.notify('Loading...');
                    this.createView('quickEdit', 'crm:views/calendar/modals/edit', {
                        attributes: attributes,
                        enabledScopeList: this.enabledScopeList,
                        scopeList: this.scopeList
                    }, function (view) {
                        view.render();
                        view.notify(false);
                        this.listenToOnce(view, 'after:save', function (model) {
                            this.addModel(model);
                        }, this);
                    }, this);
                    $calendar.fullCalendar('unselect');
                }.bind(this),
                eventClick: function (event) {
                    this.notify('Loading...');
                    var viewName = this.getMetadata().get(['clientDefs', event.scope, 'modalViews', 'detail']) || 'views/modals/detail';
                    this.createView('quickView', viewName, {
                        scope: event.scope,
                        id: event.recordId,
                        removeDisabled: false
                    }, function (view) {
                        view.render();
                        view.notify(false);

                        this.listenToOnce(view, 'after:destroy', function (model) {
                            this.removeModel(model);
                        }, this);

                        this.listenToOnce(view, 'after:save', function (model) {
                            view.close();
                            this.updateModel(model);
                        }, this);
                    }, this);
                }.bind(this),
                viewRender: function (view, el) {
                    var mode = view.name;
                    var date = this.getDateTime().fromIso(this.$calendar.fullCalendar('getDate'));

                    var m = moment(this.$calendar.fullCalendar('getDate'));
                    this.trigger('view', m.format('YYYY-MM-DD'), mode);
                }.bind(this),
                events: function (from, to, timezone, callback) {
                    var dateTimeFormat = this.getDateTime().internalDateTimeFormat;

                    var fromStr = from.format(dateTimeFormat);
                    var toStr = to.format(dateTimeFormat);

                    from = moment.tz(fromStr, timezone);
                    to = moment.tz(toStr, timezone);

                    fromStr = from.utc().format(dateTimeFormat);
                    toStr = to.utc().format(dateTimeFormat);

                    this.fetchEvents(fromStr, toStr, callback);
                }.bind(this),
                eventDrop: function (event, delta, callback) {
                    var dateStart = this.convertTime(event.start) || null;

                    var dateEnd = null;
                    if (event.duration) {
                        dateEnd = this.convertTime(event.start.clone().add(event.duration, 's')) || null;
                    }

                    var attributes = {};

                    if (event.dateStart) {
                        event.dateStart = this.convertTime(this.getDateTime().toMoment(event.dateStart).add(delta));
                        attributes.dateStart = event.dateStart;
                    }
                    if (event.dateEnd) {
                        event.dateEnd = this.convertTime(this.getDateTime().toMoment(event.dateEnd).add(delta));
                        attributes.dateEnd = event.dateEnd;
                    }
                    if (event.dateStartDate) {
                        var d = this.getDateTime().toMomentDate(event.dateStartDate).add(delta);
                        event.dateStartDate = d.format(this.getDateTime().internalDateFormat);
                        attributes.dateStartDate = event.dateStartDate;
                    }
                    if (event.dateEndDate) {
                        var d = this.getDateTime().toMomentDate(event.dateEndDate).add(delta);
                        event.dateEndDate = d.format(this.getDateTime().internalDateFormat);
                        attributes.dateEndDate = event.dateEndDate;
                    }

                    if (!event.end) {
                        if (!~this.allDayScopeList.indexOf(event.scope)) {
                            event.end = event.start.clone().add(event.duration, 's');
                        }
                    }

                    event.allDay = false;

                    this.handleAllDay(event, true);

                    this.fillColor(event);

                    this.notify('Saving...');
                    this.getModelFactory().create(event.scope, function (model) {
                        model.once('sync', function () {
                            this.notify(false);
                        }, this);
                        model.id = event.recordId;
                        model.save(attributes, {patch: true});
                    }, this);
                }.bind(this),
                eventResize: function (event, delta, revertFunc) {
                    var attributes = {
                        dateEnd: this.convertTime(event.end)
                    };
                    event.dateEnd = attributes.dateEnd;
                    event.duration = event.end.unix() - event.start.unix();

                    this.fillColor(event);

                    this.notify('Saving...');
                    this.getModelFactory().create(event.scope, function (model) {
                        model.once('sync', function () {
                            this.notify(false);
                        }.bind(this));
                        model.id = event.recordId;
                        model.save(attributes, {patch: true});
                    }.bind(this));
                }.bind(this),
                allDayText: '',
                firstHour: 8,
                columnFormat: {
                    week: 'ddd DD',
                    day: 'ddd DD',
                },
                weekNumberTitle: '',
            };

            if (!this.options.height) {
                options.contentHeight = this.getCalculatedHeight();
            } else {
                options.aspectRatio = 1.62;
            }

            if (this.date) {
                options.defaultDate = moment.utc(this.date);
            }

            setTimeout(function () {
                $calendar.fullCalendar(options);
                this.updateDate();
                if (this.$container && this.$container.size()) {
                    this.adjustSize();
                }
            }.bind(this), 150);
        },

        fetchEvents: function (from, to, callback) {
            var url = 'Activities?from=' + from + '&to=' + to;
            if (this.options.userId) {
                url += '&userId=' + this.options.userId;
                if (this.options.userName) {
                    url += '&userName=' + this.options.userName;
                }
            }

            url += '&scopeList=' + encodeURIComponent(this.enabledScopeList.join(','));

            $.ajax({
                url: url,
                success: function (data) {
                    var events = this.convertToFcEvents(data);
                    callback(events);
                    this.notify(false);
                }.bind(this)
            });
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
        },

        actionRefresh: function () {
            this.$calendar.fullCalendar('refetchEvents');
        },

    });
});

