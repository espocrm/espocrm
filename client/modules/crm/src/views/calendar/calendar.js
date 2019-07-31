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

        fullCalendarModeList: ['month', 'agendaWeek', 'agendaDay', 'basicWeek', 'basicDay', 'listWeek'],

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
                header: this.header,
                isCustomViewAvailable: this.isCustomViewAvailable,
                isCustomView: this.isCustomView,
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
            'click [data-action="mode"]': function (e) {
                var mode = $(e.currentTarget).data('mode');

                this.selectMode(mode);
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

            this.setupMode();

            this.$container = this.options.$container;

            this.colors = Espo.Utils.clone(this.getMetadata().get('clientDefs.Calendar.colors') || this.colors);
            this.modeList = this.getMetadata().get('clientDefs.Calendar.modeList') || this.modeList;
            this.canceledStatusList = this.getMetadata().get('clientDefs.Calendar.canceledStatusList') || this.canceledStatusList;
            this.completedStatusList = this.getMetadata().get('clientDefs.Calendar.completedStatusList') || this.completedStatusList;
            this.scopeList = this.getConfig().get('calendarEntityList') || Espo.Utils.clone(this.scopeList);
            this.allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') || this.allDayScopeList;

            this.scopeFilter = false;

            this.isCustomViewAvailable = this.getAcl().get('userPermission') !== 'no';
            if (this.options.userId) {
                this.isCustomViewAvailable = false;
            }

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

            if (this.header) {
                this.createView('modeButtons', 'crm:views/calendar/mode-buttons', {
                    el: this.getSelector() + ' .mode-buttons',
                    isCustomViewAvailable: this.isCustomViewAvailable,
                    modeList: this.modeList,
                    scopeList: this.scopeList,
                    mode: this.mode,
                });
            }
        },

        setupMode: function () {
            this.viewMode = this.mode;

            this.isCustomView = false;
            this.teamIdList = this.options.teamIdList || null;

            if (this.teamIdList && !this.teamIdList.length) {
                this.teamIdList = null;
            }

            if (~this.mode.indexOf('view-')) {
                this.viewId = this.mode.substr(5);
                this.isCustomView = true;

                var calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
                calendarViewDataList.forEach(function (item) {
                    if (item.id === this.viewId) {
                        this.viewMode = item.mode;
                        this.teamIdList = item.teamIdList;
                        this.viewName = item.name;
                    }
                }, this);
            }
        },

        selectMode: function (mode) {
            if (~this.fullCalendarModeList.indexOf(mode) || mode.indexOf('view-') === 0) {
                var previosMode = this.mode;

                if (
                    mode.indexOf('view-') === 0
                    ||
                    mode.indexOf('view-') !== 0 && previosMode.indexOf('view-') === 0
                ) {
                    this.trigger('change:mode', mode, true);
                    return;
                }

                this.mode = mode;

                this.setupMode();

                if (this.isCustomView) {
                    this.$el.find('button[data-action="editCustomView"]').removeClass('hidden');
                } else {
                    this.$el.find('button[data-action="editCustomView"]').addClass('hidden');
                }
                this.$el.find('[data-action="mode"]').removeClass('active');
                this.$el.find('[data-mode="' + mode + '"]').addClass('active');


                this.$calendar.fullCalendar('changeView', this.viewMode);

                this.updateDate();

                if (this.hasView('modeButtons')) {
                    this.getView('modeButtons').mode = mode;
                    this.getView('modeButtons').reRender();
                }
            }
            this.trigger('change:mode', mode);
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
            var today = moment();

            if (view.intervalStart.unix() <= today.unix() && today.unix() < view.intervalEnd.unix()) {
                this.$el.find('button[data-action="today"]').addClass('active');
            } else {
                this.$el.find('button[data-action="today"]').removeClass('active');
            }

            var title = this.getTitle();

            this.$el.find('.date-title h4 span').text(title);
        },

        getTitle: function () {
            var view = this.$calendar.fullCalendar('getView');

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

            title = this.getHelper().escapeString(title);

            return title;
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

            if (this.teamIdList && o.userIdList) {
                event.userIdList = o.userIdList;
                event.userNameMap = o.userNameMap || {};

                event.userIdList = event.userIdList.sort(function (v1, v2) {
                    return (event.userNameMap[v1] || '').localeCompare(event.userNameMap[v2] || '');
                });
            }

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

            if (!color) {
                color = this.getColorFromScopeName(event.scope);
            }

            if (color && ~this.completedStatusList.indexOf(event.status) || ~this.canceledStatusList.indexOf(event.status)) {
            	color = this.shadeColor(color, 0.4);
            }
            event.color = color;
        },

        handleStatus: function (event) {
        	if (~this.canceledStatusList.indexOf(event.status)) {
                event.className = ['event-canceled'];
        	} else {
                event.className = [];
            }
        },

        shadeColor: function (color, percent) {
            var f = parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
            return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
        },

        handleAllDay: function (event, notInitial) {
            if (~this.allDayScopeList.indexOf(event.scope)) {
                event.allDay = event.allDayCopy = true;
                if (!notInitial) {
                    if (event.end) {
                        event.start = event.end;
                        if (!event.dateEndDate && event.end.hours() === 0 && event.end.minutes() === 0) {
                            event.start.add(-1, 'days');
                        }
                    }
                }
                return;
            }

            if (event.dateStartDate && event.dateEndDate) {
                event.allDay = true;
                event.allDayCopy = event.allDay;
                if (!notInitial) {
                    event.end.add(1, 'days')
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

            event.allDayCopy = event.allDay;
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
            var smallScreenWidth = this.smallScreenWidth = this.smallScreenWidth || this.getThemeManager().getParam('screenWidthXs');
            var $window = $(window);

            if (this.$container && this.$container.length) {
                return this.$container.height();
            }

            var footerHeight = this.footerHeight = this.footerHeight || $('#footer').height() || 26;

            var top = 0;

            var colendarElement = this.$el.find('.calendar').get(0);

            if (colendarElement) {
                top = colendarElement.getBoundingClientRect().top;

                if ($window.width() < smallScreenWidth) {
                    var $navbarCollapse = $('#navbar .navbar-body');
                    if ($navbarCollapse.hasClass('in') || $navbarCollapse.hasClass('collapsing')) {
                        top -= $navbarCollapse.height();
                    }
                }
            }

            var spaceHeight = top + footerHeight;

            return $window.height() - spaceHeight - 20;
        },

        adjustSize: function () {
            if (this.isRemoved()) return;
            var height = this.getCalculatedHeight();
            this.$calendar.fullCalendar('option', 'contentHeight', height);
        },

        afterRender: function () {
            if (this.options.containerSelector) {
                this.$container = $(this.options.containerSelector);
            }

            var $calendar = this.$calendar = this.$el.find('div.calendar');

            var slotDuration = '00:' + this.slotDuration + ':00';

            var timeFormat = this.getDateTime().timeFormat;

            var slotLabelFormat;
            if (~timeFormat.indexOf('a')) {
                slotLabelFormat = 'h(:mm)a';
            } else if (~timeFormat.indexOf('A')) {
                slotLabelFormat = 'h(:mm)A';
            } else {
                slotLabelFormat = timeFormat;
            }

            var options = {
                header: false,
                slotLabelFormat: slotLabelFormat,
                timeFormat: timeFormat,
                defaultView: this.viewMode,
                weekNumbers: true,
                weekNumberCalculation: 'ISO',
                editable: true,
                selectable: true,
                selectHelper: true,
                height: this.options.height || null,
                firstDay: this.getDateTime().weekStart,
                slotEventOverlap: true,
                slotDuration: slotDuration,
                snapDuration: this.slotDuration * 60 * 1000,
                timezone: this.getDateTime().timeZone,
                longPressDelay: 300,
                windowResize: function () {
                    this.adjustSize();
                }.bind(this),
                select: function (start, end) {
                    var dateStart = this.convertTime(start);
                    var dateEnd = this.convertTime(end);

                    var allDay = !start.hasTime();

                    var dateEndDate = null;
                    var dateStartDate = null;
                    if (allDay) {
                        dateStartDate = start.format('YYYY-MM-DD');
                        dateEndDate = end.clone().add(-1, 'days').format('YYYY-MM-DD');
                    }

                    var attributes = {};
                    if (this.options.userId) {
                        attributes.assignedUserId = this.options.userId;
                        attributes.assignedUserName = this.options.userName || this.options.userId;
                    }

                    this.notify('Loading...');
                    this.createView('quickEdit', 'crm:views/calendar/modals/edit', {
                        attributes: attributes,
                        enabledScopeList: this.enabledScopeList,
                        scopeList: this.scopeList,
                        allDay: allDay,
                        dateStartDate: dateStartDate,
                        dateEndDate: dateEndDate,
                        dateStart: dateStart,
                        dateEnd: dateEnd
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
                    var date = this.getDateTime().fromIso(this.$calendar.fullCalendar('getDate'));

                    var m = moment(this.$calendar.fullCalendar('getDate'));
                    this.trigger('view', m.format('YYYY-MM-DD'), this.mode);
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
                eventDrop: function (event, delta, revertFunc) {
                    if (event.start.hasTime()) {
                        if (event.allDayCopy) {
                            revertFunc();
                            return;
                        }
                    } else {
                        if (!event.allDayCopy) {
                            revertFunc();
                            return;
                        }
                    }
                    var eventCloned = Espo.Utils.clone(event);

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
                            this.$calendar.fullCalendar('updateEvent', event);
                        }, this);
                        model.id = event.recordId;
                        model.save(attributes, {patch: true}).fail(function () {
                            revertFunc();
                        }.bind(this));
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
                            this.$calendar.fullCalendar('updateEvent', event);
                        }.bind(this));
                        model.id = event.recordId;
                        model.save(attributes, {patch: true}).fail(function () {
                            revertFunc();
                        }.bind(this));
                    }.bind(this));
                }.bind(this),
                allDayText: '',
                firstHour: 8,
                weekNumberTitle: '',
                views: {
                    week: {
                        columnFormat: 'ddd DD',
                    },
                    day: {
                        columnFormat: 'ddd DD',
                    }
                }
            };

            if (this.teamIdList) {
                options.eventRender = function (event, element, view) {
                    var $el = $(element);
                    var $content = $el.find('.fc-content');

                    if (event.userIdList) {
                        event.userIdList.forEach(function (userId) {
                            var userName = this.getHelper().escapeString(event.userNameMap[userId] || '');
                            var avatarHtml = this.getHelper().getAvatarHtml(userId, 'small', 13);
                            if (avatarHtml) avatarHtml += ' ';

                            var $div = $('<div class="user">').html(avatarHtml + userName);
                            $content.append($div);
                        }, this);
                    }
                }.bind(this);
            }

            if (!this.options.height) {
                options.contentHeight = this.getCalculatedHeight();
            } else {
                options.aspectRatio = 1.62;
            }

            if (this.date) {
                options.defaultDate = moment.utc(this.date);
            } else {
                this.$el.find('button[data-action="today"]').addClass('active');
            }

            setTimeout(function () {
                $calendar.fullCalendar(options);
                this.updateDate();
                if (this.$container && this.$container.length) {
                    this.adjustSize();
                }
            }.bind(this), 150);
        },

        fetchEvents: function (from, to, callback) {
            var url = 'Activities?from=' + from + '&to=' + to;
            if (this.options.userId) {
                url += '&userId=' + this.options.userId;
            }

            url += '&scopeList=' + encodeURIComponent(this.enabledScopeList.join(','));

            if (this.teamIdList && this.teamIdList.length) {
                url += '&teamIdList=' + encodeURIComponent(this.teamIdList.join(','));
            }

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

        actionNext: function () {
            this.$calendar.fullCalendar('next');
            this.updateDate();
        },

        actionPrevious: function () {
            this.$calendar.fullCalendar('prev');
            this.updateDate();
        },

        getColorFromScopeName: function (scope) {
            var additionalColorList = this.getMetadata().get('clientDefs.Calendar.additionalColorList') || [];

            if (!additionalColorList.length) return;

            var colors = this.getMetadata().get('clientDefs.Calendar.colors') || {};

            var scopeList = this.getConfig().get('calendarEntityList') || [];

            var index = 0;
            var j = 0;
            for (var i = 0; i < scopeList.length; i++) {
                if (scopeList[i] in colors) continue;
                if (scopeList[i] === scope) {
                    index = j;
                    break;
                }
                j++;
            }

            index = index % additionalColorList.length;
            this.colors[scope] = additionalColorList[index];

            return this.colors[scope];
        }

    });
});
