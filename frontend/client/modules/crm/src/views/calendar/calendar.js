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

Espo.define('Crm:Views.Calendar.Calendar', ['View', 'lib!FullCalendar'], function (Dep, FullCalendar) {

    return Dep.extend({

        template: 'crm:calendar.calendar',

        eventAttributes: [],

        colors: {
            'Meeting': '#558BBD',
            'Call': '#cf605d',
            'Task': '#76BA4E',
        },

        header: true,

        modeList: ['month', 'agendaWeek', 'agendaDay'],

        defaultMode: 'agendaWeek',

        slotDuration: 30,

        titleFormat: {
            month: 'MMMM YYYY',
            week: 'MMMM D, YYYY',
            day: 'dddd, MMMM D, YYYY'
        },

        data: function () {
            return {
                mode: this.mode,
                modeList: this.modeList,
                header: this.header,
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
                this.$el.find('button[data-action="mode"]').removeClass('active');
                this.$el.find('button[data-mode="' + mode + '"]').addClass('active');
                this.$calendar.fullCalendar('changeView', mode);
                this.updateDate();
            },
            'click [data-action="refresh"]': function (e) {
            	this.$calendar.fullCalendar('refetchEvents');
            }
        },

        setup: function () {
            this.date = this.options.date || null;
            this.mode = this.options.mode || this.defaultMode;
            this.header = ('header' in this.options) ? this.options.header : this.header;
            this.slotDuration = this.options.slotDuration || this.slotDuration;
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
            };
            this.eventAttributes.forEach(function (attr) {
                event[attr] = o[attr];
            });
            if (o.dateStart) {
                event.start = this.getDateTime().toMoment(o.dateStart);
            }
            if (o.dateEnd) {
                event.end = this.getDateTime().toMoment(o.dateEnd);
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

            return event;
        },

        fillColor: function (event) {
            var color = this.colors[event.scope];
            var d = event.dateEnd;

            if (d && this.getDateTime().toMoment(d).unix() < this.now.unix()) {
                color = this.shadeColor(color, 0.4);
            }
            event.color = color;
        },

        shadeColor: function (color, percent) {
            var f = parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
            return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
        },

        handleAllDay: function (event) {
            if (event.scope == 'Task') {
                event.allDay = true;
                if (!event.start || !event.end) {
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

        getCalculatedHeight: function () {
            var height = $(window).height();
            var width = $(window).width();
            var spaceHeight = 150;
            if (width < 768) {
                spaceHeight = 170;
            }
            return $(window).height() - spaceHeight;
        },

        afterRender: function () {
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
                firstDay: this.getPreferences().get('weekStart'),
                slotEventOverlap: true,
                slotDuration: slotDuration,
                snapDuration: this.slotDuration * 60 * 1000,
                timezone: this.getDateTime().timeZone,
                windowResize: function () {
                    var height = this.getCalculatedHeight();
                    $calendar.fullCalendar('option', 'contentHeight', height);


                }.bind(this),
                select: function (start, end, allDay) {
                    var dateStart = this.convertTime(start);
                    var dateEnd = this.convertTime(end);

                    this.notify('Loading...');
                    this.createView('quickEdit', 'Crm:Calendar.Modals.Edit', {
                        attributes: {
                            dateStart: dateStart,
                            dateEnd: dateEnd,
                        },
                    }, function (view) {
                        view.render();
                        view.notify(false);
                    });
                    $calendar.fullCalendar('unselect');
                }.bind(this),
                eventClick: function (event) {
                    this.notify('Loading...');
                    this.createView('quickEdit', 'Crm:Calendar.Modals.Edit', {
                        scope: event.scope,
                        id: event.recordId
                    }, function (view) {
                        view.render();
                        view.notify(false);
                    });
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
                    if (!event.dateStart && event.dateEnd) {
                        attributes.dateEnd = dateStart;
                        event.dateEnd = attributes.dateEnd;
                    } else {
                        if (event.dateStart) {
                            attributes.dateStart = dateStart;
                            event.dateStart = dateStart;
                        }
                        if (event.dateEnd) {
                            attributes.dateEnd = dateEnd;
                            event.dateEnd = dateEnd;
                        }
                    }

                    if (!event.end) {
                        if (event.scope != 'Task') {
                            event.end = event.start.clone().add(event.duration, 's');
                        }
                    }

                    event.allDay = false;

                    this.handleAllDay(event);

                    this.fillColor(event);

                    this.notify('Saving...');
                    this.getModelFactory().create(event.scope, function (model) {
                        model.once('sync', function () {
                            this.notify(false);
                        }.bind(this));
                        model.id = event.recordId;
                        model.save(attributes, {patch: true});
                    }, this);
                }.bind(this),
                eventResize: function (event, delta, revertFunc) {
                    var attributes = {
                        dateEnd: this.convertTime(event.end)
                    };
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
            }.bind(this), 150);
        },

        fetchEvents: function (from, to, callback) {
            $.ajax({
                url: 'Activities?from=' + from + '&to=' + to,
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

