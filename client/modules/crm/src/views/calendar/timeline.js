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
            'click button[data-action="today"]': function () {
                this.$calendar.fullCalendar('today');
                this.updateDate();
            },
            'click button[data-action="mode"]': function (e) {
                var mode = $(e.currentTarget).data('mode');
                this.trigger('change:mode', mode);
            },
            'click [data-action="refresh"]': function (e) {
            	this.$calendar.fullCalendar('refetchEvents');
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

            this.$container = this.options.$container;

            this.colors = this.getMetadata().get('clientDefs.Calendar.colors') || this.colors;
            this.modeList = this.getMetadata().get('clientDefs.Calendar.modeList') || this.modeList;
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

        toggleScopeFilter: function (name) {
            var index = this.enabledScopeList.indexOf(name);
            if (!~index) {
                this.enabledScopeList.push(name);
            } else {
                this.enabledScopeList.splice(index, 1);
            }

            this.storeEnabledScopeList(this.enabledScopeList);

            //.this.$calendar.fullCalendar('refetchEvents');
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
            /*var view = this.$calendar.fullCalendar('getView');
            var today = new Date();

            if (view.start <= today && today < view.end) {
                this.$el.find('button[data-action="today"]').addClass('active');
            } else {
                this.$el.find('button[data-action="today"]').removeClass('active');
            }*/


            var title;

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
            this.now = moment.tz(this.getDateTime().timeZone);

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

        afterRender: function () {
            if (this.options.containerSelector) {
                this.$container = $(this.options.containerSelector);
            }

            var $timeline = this.$timeline = this.$el.find('div.timeline');

            this.initGroupsDataSet();
            this.initItemsDataSet();

            //this.updateDate();
        },

        initGroupsDataSet: function () {
            var list = [
                {
                    id: this.getUser().id,
                    content: this.getUser().get('name'),
                }
            ];
            this.groupsDataSet = new Vis.DataSet(list);
        },

        initItemsDataSet: function () {
            var list = [];
            this.getUserList
            this.itemsDataSet = new Vis.DataSet(list);
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
        }

    });
});

