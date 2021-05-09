/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/calendar/timeline', ['view', 'lib!vis'], function (Dep, Vis) {

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

        rangeMarginThreshold: 12 * 3600,

        leftMargin: 24 * 3600,

        rightMargin: 48 * 3600,

        calendarType: 'single',

        calendarTypeList: ['single', 'shared'],

        data: function () {
            var calendarTypeDataList = this.getCalendarTypeDataList();

            return {
                mode: this.mode,
                header: this.header,
                calendarType: this.calendarType,
                calendarTypeDataList: calendarTypeDataList,
                calendarTypeSelectEnabled: calendarTypeDataList.length > 1,
                calendarTypeLabel: this.getCalendarTypeLabel(this.calendarType),
                isCustomViewAvailable: this.isCustomViewAvailable,
            };
        },

        events: {
            'click button[data-action="today"]': function () {
                this.timeline.moveTo(moment());
                this.triggerView();
            },
            'click [data-action="mode"]': function (e) {
                var mode = $(e.currentTarget).data('mode');
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

                var $showSharedCalendarOptions = this.$el.find('> .button-container button[data-action="showSharedCalendarOptions"]');
                if (calendarType == 'shared') {
                    $showSharedCalendarOptions.removeClass('hidden');
                } else {
                    $showSharedCalendarOptions.addClass('hidden');
                }

                this.selectCalendarType(calendarType);
            },
            'click button[data-action="addUser"]': function () {
                this.actionAddUser();
            },
            'click button[data-action="showSharedCalendarOptions"]': function () {
                this.actionShowSharedCalendarOptions();
            }
        },

        setup: function () {
            this.date = this.options.date || this.getDateTime().getToday();
            this.mode = this.options.mode || this.defaultMode;
            this.header = ('header' in this.options) ? this.options.header : this.header;

            this.$container = this.options.$container;

            this.colors = Espo.Utils.clone(this.getMetadata().get('clientDefs.Calendar.colors') || this.colors || {});
            this.modeList = this.getMetadata().get('clientDefs.Calendar.modeList') || this.modeList || [];
            this.canceledStatusList = this.getMetadata().get('app.calendar.canceledStatusList') || this.canceledStatusList || [];
            this.completedStatusList = this.getMetadata().get('app.calendar.completedStatusList') || this.completedStatusList || [];
            this.scopeList = this.getConfig().get('calendarEntityList') || Espo.Utils.clone(this.scopeList) || [];
            this.allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') || this.allDayScopeList || [];

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

             this.enabledScopeList.forEach(function (item) {
                var color = this.getMetadata().get(['clientDefs', item, 'color']);
                if (color) {
                    this.colors[item] = color;
                }
            }, this);

            if (this.options.calendarType) {
                this.calendarType = this.options.calendarType;
            } else {
                if (this.options.userId) {
                    this.calendarType = 'single';
                } else {
                    this.calendarType = this.getStorage().get('calendar', 'timelineType') || 'shared';
                }
            }

            if (this.getAcl().get('userPermission' === 'no')) {
                if (this.calendarType === 'shared') {
                    this.calendarType = 'single';
                }
            }

            if (!~this.calendarTypeList.indexOf(this.calendarType)) {
                this.calendarType = 'single';
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

        getModeDataList: function () {
            var list = [];

            this.modeList.forEach(function (name, i) {
                var o = {
                    mode: name,
                    label: this.translate(name, 'modes', 'Calendar'),
                    labelShort: this.translate(name, 'modes', 'Calendar').substr(0, 2),
                };
                list.push(o);
            }, this);

            if (this.isCustomViewAvailable) {
                (this.getPreferences().get('calendarViewDataList') || []).forEach(function (item) {
                    var item = Espo.Utils.clone(item);
                    item.mode = 'view-' + item.id;
                    item.label = item.name;
                    item.labelShort = (item.name || '').substr(0, 2);
                    list.push(item);
                }, this);
            }

            var currentIndex = -1;
            list.forEach(function (item, i) {
                if (item.mode === this.mode) {
                    currentIndex = i;
                }
            }, this);

            if (currentIndex >= this.visibleModeListCount) {
                var tmp = list[this.visibleModeListCount - 1];
                list[this.visibleModeListCount - 1] = list[currentIndex];
                list[currentIndex] = tmp;
            }

            return list;
        },

        getVisibleModeDataList: function () {
            var fullList =  this.getModeDataList();

            var list = [];
            fullList.forEach(function (o, i) {
                if (i >= this.visibleModeListCount) return;
                list.push(o);
            }, this);

            return list;
        },

        getHiddenModeDataList: function () {
            var fullList =  this.getModeDataList();

            var list = [];
            fullList.forEach(function (o, i) {
                if (i < this.visibleModeListCount) return;
                list.push(o);
            }, this);

            return list;
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

            if (this.getAcl().get('userPermission') !== 'no') {
                list.push({
                    type: 'shared',
                    label: this.getCalendarTypeLabel('shared'),
                    disabled: this.calendarType !== 'shared'
                });
            }

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
                label = this.getHelper().escapeString(label);
                return label;
            }

            if (type === 'shared') {
                return this.translate('Shared', 'labels', 'Calendar');
            }
        },

        selectCalendarType: function (name) {
            this.calendarType = name;

            this.initUserList();
            this.initGroupsDataSet();
            this.timeline.setGroups(this.groupsDataSet);

            this.runFetch();

            this.getStorage().set('calendar', 'timelineType', name);
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

            var title = this.getTitle();
            this.$el.find('.date-title h4 span').text(title);
        },

        getTitle: function () {
            var title = '';

            if (this.options.userId && this.options.userName) {
                title += ' (' + this.options.userName + ')';
            }

            title = this.getHelper().escapeString(title);

            return title;
        },

        convertEvent: function (o) {
            var userId = o.userId || this.userList[0].id || this.getUser().id;

            var event;
            if (o.isBusyRange) {
                var event = {
                    className: 'busy',
                    group: userId,
                    'date-start': o.dateStart,
                    'date-end': o.dateEnd,
                    type: 'background'
                };
            } else {
                event = {
                    content: this.getHelper().escapeString(o.name),
                    title: this.getHelper().escapeString(o.name),
                    id: userId + '-' + o.scope + '-' + o.id,
                    group: userId,
                    'record-id': o.id,
                    scope: o.scope,
                    status: o.status,
                    'date-start': o.dateStart,
                    'date-end': o.dateEnd,
                    type: 'range',
                    className: 'clickable',
                    color: o.color,
                };
            }

            this.eventAttributes.forEach(function (attr) {
                event[attr] = o[attr];
            });
            if (o.dateStart) {
                if (!o.dateStartDate) {
                    event.start = this.getDateTime().toMoment(o.dateStart);
                } else {
                    event.start = moment.tz(o.dateStartDate, this.getDateTime().getTimeZone());
                }
            }
            if (o.dateEnd) {
                if (!o.dateEndDate) {
                    event.end = this.getDateTime().toMoment(o.dateEnd);
                } else {
                    event.end = moment.tz(o.dateEndDate, this.getDateTime().getTimeZone());
                }
            }

            if (o.dateStartDate && !~this.allDayScopeList.indexOf(o.scope)) {
                event.end = event.end.clone().add(1, 'days');
            }

            if (o.isBusyRange) {
                return event;
            }

            if (~this.allDayScopeList.indexOf(o.scope)) {
                event.type = 'box';
                if (event.end) {
                    if (o.dateEndDate) {
                        event.start = event.end.clone().add(1, 'days');
                    } else {
                        event.start = event.end.clone();
                    }
                }
            } else {
                if (!event.end || !event.start) return;
            }

            this.fillColor(event);

            this.handleStatus(event);

            return event;
        },

        fillColor: function (event) {
            var color = this.colors[event.scope];

            if (event.color) {
                color = event.color;
            }

            if (!color) {
                color = this.getColorFromScopeName(event.scope);
            }

            if (color) {
                color = this.shadeColor(color, 0.15);
            }

            if (~this.completedStatusList.indexOf(event.status) || ~this.canceledStatusList.indexOf(event.status)) {
            	color = this.shadeColor(color, 0.4);
            }

            event.style = event.style || '';
            event.style += 'background-color:' + color + ';';
            event.style += 'border-color:' + color + ';';
        },

        handleStatus: function (event) {
            if (~this.canceledStatusList.indexOf(event.status)) {
                event.className += ' event-canceled';
            }
        },

        shadeColor: function (color, percent) {
            var f = parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
            return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
        },


        convertEventList: function (list) {
            var resultList = [];

            list.forEach(function (item) {
                var event = this.convertEvent(item);
                if (!event) {
                    return;
                }

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
                    start: this.start.toDate(),
                    end: this.end.toDate(),
                    moment: function (date) {
                        var m = moment(date);
                        if (date && date.noTimeZone) {
                            return m;
                        }
                        return m.tz(this.getDateTime().getTimeZone());
                    }.bind(this),
                    format: this.getFormatObject(),
                    zoomMax: 24 * 3600 *  1000 * this.maxRange,
                    zoomMin: 1000 * 60 * 15,
                    orientation: 'top',
                    groupEditable: false,
                    editable: {
                        add: false,
                        updateTime: false,
                        updateGroup: false,
                        remove: false
                    },
                    locales: {
                        mylocale: {
                            current: this.translate('current', 'labels', 'Calendar'),
                            time: this.translate('time', 'labels', 'Calendar')
                        }
                    },
                    locale: 'mylocale',
                    margin: {
                        item: {
                            vertical: 12,
                        },
                        axis: 6,
                    },
                });

                timeline.on('click', function (e) {
                    if (this.blockClick) return;
                    if (e.item) {
                        var $item = this.$el.find('.timeline .vis-item[data-id="'+e.item+'"]');
                        var id = $item.attr('data-record-id');
                        var scope = $item.attr('data-scope');
                        if (id && scope) {
                            this.viewEvent(scope, id);
                        }
                    } else if (e.what == 'background' && e.group && e.time) {
                        var dateStart = moment(e.time).utc().format(this.getDateTime().internalDateTimeFormat);
                        this.createEvent(dateStart, e.group);
                    }
                }.bind(this));

                timeline.on('rangechanged', function (e) {
                    e.skipClick = true;

                    this.blockClick = true;
                    setTimeout(function () {this.blockClick = false}.bind(this), 100);

                    this.start = moment(e.start);
                    this.end = moment(e.end);

                    this.triggerView();

                    if (
                        (this.start.unix() < this.fetchedStart.unix() + this.rangeMarginThreshold)
                        ||
                        (this.end.unix() > this.fetchedEnd.unix() - this.rangeMarginThreshold)
                    ) {
                        this.runFetch();
                    }
                }.bind(this));

                this.once('remove', function () {
                    timeline.destroy();
                }, this);
            }.bind(this));
        },

        createEvent: function (dateStart, userId) {
            var attributes = {
                dateStart: dateStart
            };
            if (userId) {
                var userName;
                this.userList.forEach(function (item) {
                    if (item.id === userId) {
                        userName = item.name;
                    }
                }, this);
                attributes.assignedUserId = userId;
                attributes.assignedUserName = userName || userId;
            }

            this.notify('Loading...');
            this.createView('quickEdit', 'crm:views/calendar/modals/edit', {
                attributes: attributes,
                enabledScopeList: this.enabledScopeList,
                scopeList: this.scopeList
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.runFetch();
                }, this);
            }, this);
        },

        viewEvent: function (scope, id) {
            this.notify('Loading...');
            var viewName = this.getMetadata().get(['clientDefs', scope, 'modalViews', 'detail']) || 'views/modals/detail';
            this.createView('quickView', viewName, {
                scope: scope,
                id: id,
                removeDisabled: false
            }, function (view) {
                view.render();
                view.notify(false);

                this.listenToOnce(view, 'after:destroy', function (model) {
                    this.runFetch();
                }, this);

                this.listenToOnce(view, 'after:save', function (model) {
                    view.close();
                    this.runFetch();
                }, this);
            }, this);
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
            var m = this.start.clone().add(Math.round((this.end.unix() - this.start.unix()) / 2), 'seconds');
            this.trigger('view', m.format(this.getDateTime().internalDateFormat), this.mode);
        },

        initUserList: function () {
            if (this.options.userList) {
                this.userList = Espo.Utils.clone(this.options.userList);
                if (!this.userList.length) {
                    this.userList.push({
                        id: this.getUser().id,
                        name: this.getUser().get('name'),
                    });
                }
            } else {
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
                            name: this.getUser().get('name'),
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
            }
        },

        orderUserList: function (list) {
            var userList = [];

            list.forEach(function (id) {
                this.userList.forEach(function (item) {
                    if (id === item.id) {
                        userList.push(item);
                    }
                });
            }, this);

            this.userList = userList;

            this.storeUserList();
        },

        storeUserList: function () {
            this.getPreferences().save({
                'sharedCalendarUserList': Espo.Utils.clone(this.userList)
            }, {patch: true});
        },

        addSharedCalenderUser: function (id, name, skipStore) {
            var isMet = false;
            this.userList.forEach(function (item) {
                if (item.id === id) {
                    isMet = true;
                }
            });
            if (isMet) return;

            this.userList.push({
                id: id,
                name: name
            });
            if (!skipStore) {
                this.storeUserList();
            }
        },

        removeSharedCalendarUser: function (id) {
            var index = -1;
            this.userList.forEach(function (item, i) {
                if (item.id === id) {
                    index = i;
                }
            });
            if (~index) {
                this.userList.splice(index, 1);
                this.storeUserList();
            }
        },

        getSharedCalenderUserList: function () {
            var list = Espo.Utils.clone(this.getPreferences().get('sharedCalendarUserList'));

            if (list && list.length) {
                var isBad = false;

                list.forEach(function (item) {
                    if (typeof item !== 'object' || !item.id || !item.name) {
                        isBad = true;
                    }
                }, this);
                if (!isBad) {
                    return list;
                }
            }

            return [{
                id: this.getUser().id,
                name: this.getUser().get('name')
            }];
        },

        initDates: function () {
            if (this.date) {
                this.start = moment.tz(this.date, this.getDateTime().getTimeZone());
            } else {
                this.start = moment.tz(this.getDateTime().getTimeZone());
            }
            this.end = this.start.clone();
            this.end.add(1, 'day');

            this.fetchedStart = null;
            this.fetchedEnd = null;
        },

        initGroupsDataSet: function () {
            var list = [];
            this.userList.forEach(function (user, i) {
                list.push({
                    id: user.id,
                    content: this.getGroupContent(user.id, user.name),
                    order: i
                });
            }, this);
            this.groupsDataSet = new Vis.DataSet(list);
        },

        getGroupContent: function (id, name) {
            if (name) {
                name = this.getHelper().escapeString(name);
            }
            if (this.calendarType === 'single') {
                return name;
            }
            var avatarHtml = this.getAvatarHtml(id);
            if (avatarHtml) avatarHtml += ' ';
            var html = avatarHtml + '<span data-id="'+id+'" class="group-title">' + name + '</span>';

            return html;
        },

        getAvatarHtml: function (id) {
            if (this.getConfig().get('avatarsDisabled')) {
                return '';
            }
            var t;
            var cache = this.getCache();
            if (cache) {
                t = cache.get('app', 'timestamp');
            } else {
                t = Date.now();
            }
            return '<img class="avatar avatar-link" width="14" src="'+this.getBasePath()+'?entryPoint=avatar&size=small&id=' + id + '&t='+t+'">';
        },

        initItemsDataSet: function () {
            this.itemsDataSet = new Vis.DataSet(list);
        },

        fetchEvents: function (from, to, callback) {
            if (!this.options.noFetchLoadingMessage) {
                Espo.Ui.notify(this.translate('loading', 'messages'));
            }

            from = from.clone().add((-1) * this.leftMargin, 'seconds');
            to = to.clone().add(this.rightMargin, 'seconds');

            var fromString = from.utc().format(this.getDateTime().internalDateTimeFormat);
            var toString = to.utc().format(this.getDateTime().internalDateTimeFormat);

            var url = 'Timeline?from=' + fromString + '&to=' + toString;
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
                this.fetchedStart = from.clone();
                this.fetchedEnd = to.clone();
                var eventList = [];

                for (var userId in data) {
                    var userEventList = data[userId].eventList;
                    userEventList.forEach(function (item) {
                        item.userId = userId;
                        eventList.push(item);
                    }, this);

                    var userBusyRangeList = data[userId].busyRangeList;
                    userBusyRangeList.forEach(function (item) {
                        item.userId = userId;
                        item.isBusyRange = true;
                        eventList.push(item);
                    }, this);
                }

                var convertedEventList = this.convertEventList(eventList);
                callback(convertedEventList);
                this.notify(false);
            }.bind(this));
        },

        removeUser: function (id) {
            this.removeSharedCalendarUser(id);
            this.initGroupsDataSet();
            this.timeline.setGroups(this.groupsDataSet);
        },

        actionShowSharedCalendarOptions: function () {
            this.createView('dialog', 'crm:views/calendar/modals/shared-options', {
                userList: this.userList
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'save', function (data) {
                    this.userList = data.userList;
                    this.storeUserList();
                    this.initGroupsDataSet();
                    this.timeline.setGroups(this.groupsDataSet);
                    this.runFetch();
                }, this);
            }, this);
        },

        actionAddUser: function () {
            var boolFilterList = [];
            if (this.getAcl().get('userPermission') === 'team') {
                boolFilterList.push('onlyMyTeam');
            }

            var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select') || 'views/modals/select-records';

            this.notify('Loading...');
            this.createView('dialog', viewName, {
                scope: 'User',
                createButton: false,
                boolFilterList: boolFilterList,
                multiple: true
            }, function (view) {
                view.render();
                this.notify(false);
                this.listenToOnce(view, 'select', function (modelList) {
                    modelList.forEach(function (model) {
                        this.addSharedCalenderUser(model.id, model.get('name'));
                    }, this);
                    this.storeUserList();
                    this.initGroupsDataSet();
                    this.timeline.setGroups(this.groupsDataSet);
                    this.runFetch();
                }, this);
            }, this);
        },

        actionRefresh: function () {
            this.runFetch();
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
        },

    });
});
