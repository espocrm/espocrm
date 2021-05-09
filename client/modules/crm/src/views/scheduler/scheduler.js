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

define('crm:views/scheduler/scheduler', ['view', 'lib!vis'], function (Dep, Vis) {

    return Dep.extend({

        templateContent: '<div class="timeline"></div>' +
            '<link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">',

        rangeMarginThreshold: 12 * 3600,

        leftMargin: 24 * 3600,

        rightMargin: 48 * 3600,

        rangeMultiplierLeft: 3,

        rangeMultiplierRight: 3,

        setup: function () {
            this.startField = this.options.startField || 'dateStart';
            this.endField = this.options.endField || 'dateEnd';
            this.assignedUserField = this.options.assignedUserField || 'assignedUser';

            var usersFieldDefault = 'users';

            if (!this.model.hasLink('users') && this.model.hasLink('assignedUsers')) {
                usersFieldDefault = 'assignedUsers';
            }

            this.eventAssignedUserIsAttendeeDisabled =
                this.getConfig().get('eventAssignedUserIsAttendeeDisabled') || false;

            this.usersField = this.options.usersField || usersFieldDefault;

            this.userIdList = [];

            this.Vis = Vis;

            this.listenTo(this.model, 'change', function (m, o) {
                var isChanged =
                    m.hasChanged('isAllDay') ||
                    m.hasChanged(this.startField) ||
                    m.hasChanged(this.endField) ||
                    m.hasChanged(this.usersField + 'Ids') ||
                    !this.eventAssignedUserIsAttendeeDisabled && m.hasChanged(this.assignedUserField + 'Id');

                if (!isChanged) {
                    return;
                }

                if (!m.hasChanged(this.assignedUserField + 'Id') && !m.hasChanged(this.usersField + 'Ids')) {
                    this.initDates(true);

                    if (!this.start || !this.end || !this.userIdList.length) {
                        if (!this.timeline) {
                            return;
                        }

                        this.showNoData();

                        this.trigger('no-data');

                        return;
                    }

                    this.trigger('has-data');

                    if (this.timeline) {
                        this.updateEvent();

                        this.timeline.setWindow(
                            this.start.toDate(),
                            this.end.toDate()
                        );
                    }

                    if (this.noDataShown) {
                        this.reRender();
                    }
                } else {
                    if (this.isRemoved()) {
                        return;
                    }

                    this.trigger('has-data');

                    this.reRender();
                }
            }, this);

            this.once('remove', function () {
                this.destroyTimeline();
            }, this);
        },

        destroyTimeline: function () {
            if (this.timeline) {
                this.timeline.destroy();
                this.timeline = null;
            }
        },

        showNoData: function () {
            this.noDataShown = true;

            this.destroyTimeline();

            this.$timeline.empty();
            this.$timeline.append(
                '<div class="revert-margin">'+this.translate('No Data')+'</div>'
            );
        },

        afterRender: function () {
            var $timeline = this.$timeline = this.$el.find('.timeline');

            this.noDataShown = false;

            this.$timeline.empty();

            this.initGroupsDataSet();
            this.initDates();

            if (!$timeline.get(0)) {
                return;
            }

            $timeline.get(0).innerHTML = '';

            if (!this.start || !this.end || !this.userIdList.length) {
                this.showNoData();

                this.trigger('no-data');

                return;
            }

            this.destroyTimeline();

            if (this.lastHeight) {
                $timeline.css('min-height', this.lastHeight + 'px');
            }

            this.fetch(this.start, this.end, function (eventList) {
                var itemsDataSet = new Vis.DataSet(eventList);

                var timeline = this.timeline =new Vis.Timeline($timeline.get(0), itemsDataSet, this.groupsDataSet, {
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
                    zoomable: false,
                    moveable: true,
                    orientation: 'top',
                    groupEditable: false,
                    editable: {
                        add: false,
                        updateTime: false,
                        updateGroup: false,
                        remove: false,
                    },
                    showCurrentTime: true,
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

                $timeline.css('min-height', '');

                timeline.on('rangechanged', function (e) {
                    e.skipClick = true;

                    this.blockClick = true;

                    setTimeout(function () {this.blockClick = false}.bind(this), 100);

                    this.start = moment(e.start);
                    this.end = moment(e.end);

                    this.updateRange();
                }.bind(this));

                setTimeout(function () {
                    this.lastHeight = $timeline.height();
                }.bind(this), 500);

            }.bind(this));
        },

        updateEvent: function () {
            var eventList = Espo.Utils.cloneDeep(this.busyEventList);

            var convertedEventList = this.convertEventList(eventList);
            this.addEvent(convertedEventList);

            var itemsDataSet = new this.Vis.DataSet(convertedEventList);

            this.timeline.setItems(itemsDataSet);
        },

        updateRange: function () {
            if (
                (this.start.unix() < this.fetchedStart.unix() + this.rangeMarginThreshold)
                ||
                (this.end.unix() > this.fetchedEnd.unix() - this.rangeMarginThreshold)
            ) {
                this.runFetch();
            }
        },

        initDates: function (update) {
            this.start = null;
            this.end = null;

            var startS = this.model.get(this.startField);
            var endS = this.model.get(this.endField);

            if (this.model.get('isAllDay')) {
                startS = this.model.get(this.startField + 'Date');
                endS = this.model.get(this.endField + 'Date');
            }

            if (!startS || !endS) {
                return;
            }

            if (this.model.get('isAllDay')) {
                this.eventStart = moment.tz(startS, this.getDateTime().getTimeZone());
                this.eventEnd = moment.tz(endS, this.getDateTime().getTimeZone());
                this.eventEnd.add(1, 'day');
            }else {
                this.eventStart = moment.utc(startS).tz(this.getDateTime().getTimeZone());
                this.eventEnd = moment.utc(endS).tz(this.getDateTime().getTimeZone());
            }

            var diff = this.eventEnd.diff(this.eventStart, 'hours');

            this.start = this.eventStart.clone();
            this.end = this.eventEnd.clone();

            if (diff < 0) {
                this.end = this.start.clone();
            }

            if (diff < 1) {
                diff = 1;
            }

            this.start.add(-diff * this.rangeMultiplierLeft, 'hours');
            this.end.add(diff * this.rangeMultiplierRight, 'hours');

            this.start.startOf('hour');
            this.end.endOf('hour');

            if (!update) {
                this.fetchedStart = null;
                this.fetchedEnd = null;
            }
        },

        runFetch: function () {
            this.fetch(this.start, this.end, function (eventList) {
                var itemsDataSet = new this.Vis.DataSet(eventList);

                this.timeline.setItems(itemsDataSet);
            }.bind(this));
        },

        fetch: function (from, to, callback) {
            from = from.clone().add((-1) * this.leftMargin, 'seconds');
            to = to.clone().add(this.rightMargin, 'seconds');

            var fromString = from.utc().format(this.getDateTime().internalDateTimeFormat);
            var toString = to.utc().format(this.getDateTime().internalDateTimeFormat);

            var url =
                'Activities/action/busyRanges?from=' + fromString + '&to=' + toString +
                '&userIdList=' + encodeURIComponent(this.userIdList.join(',')) +
                '&entityType=' + this.model.entityType;

            if (this.model.id) {
                url += '&entityId=' + this.model.id;
            }

            this.ajaxGetRequest(url).then(function (data) {
                this.fetchedStart = from.clone();
                this.fetchedEnd = to.clone();

                var eventList = [];

                for (var userId in data) {
                    data[userId].forEach(function (item) {
                        item.userId = userId;
                        item.isBusyRange = true;
                        eventList.push(item);
                    }, this);
                }

                this.busyEventList = Espo.Utils.cloneDeep(eventList);

                var convertedEventList = this.convertEventList(eventList);

                this.addEvent(convertedEventList);

                callback(convertedEventList);

            }.bind(this));
        },

        addEvent: function (list) {
            this.getCurrentItemList().forEach(function (item) {
                list.push(item);
            }, this);
        },

        getCurrentItemList: function () {
            var list = [];

            var o = {
                type: 'point',
                start: this.eventStart.clone(),
                end: this.eventEnd.clone(),
                type: 'background',
                style: 'z-index: 4; opacity: 0.6;',
                className: 'event-range',
            };

            var color = this.getColorFromScopeName(this.model.entityType);

            if (color) {
                o.style += '; border-color: ' + color;
                var rgb = this.hexToRgb(color);
                o.style += '; background-color: rgba('+rgb.r+', '+rgb.g+', '+rgb.b+', 0.05)';
            }

            this.userIdList.forEach(function (id) {
                var c = Espo.Utils.clone(o);
                c.group = id;
                c.id = 'event-' + id;
                list.push(c);
            }, this);

            return list;
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

        convertEvent: function (o) {
            var event;

            if (o.isBusyRange) {
                event = {
                    className: 'busy',
                    group: o.userId,
                    'date-start': o.dateStart,
                    'date-end': o.dateEnd,
                    type: 'background',
                };
            }

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

            if (o.isBusyRange) {
                return event;
            }
        },

        initGroupsDataSet: function () {
            var list = [];

            var userIdList = Espo.Utils.clone(this.model.get(this.usersField + 'Ids') || []);
            var assignedUserId = this.model.get(this.assignedUserField + 'Id');

            var names = this.model.get(this.usersField + 'Names') || {};

            if (!this.eventAssignedUserIsAttendeeDisabled && assignedUserId) {
                if (!~userIdList.indexOf(assignedUserId)) {
                    userIdList.unshift(assignedUserId);
                }

                names[assignedUserId] = this.model.get(this.assignedUserField + 'Name');
            }

            this.userIdList = userIdList;

            userIdList.forEach(function (id, i) {
                list.push({
                    id: id,
                    content: this.getGroupContent(id, names[id] || id),
                    order: i,
                });
            }, this);

            this.groupsDataSet = new this.Vis.DataSet(list);
        },

        getGroupContent: function (id, name) {
            if (name) {
                name = this.getHelper().escapeString(name);
            }
            if (this.calendarType === 'single') {
                return name;
            }

            var avatarHtml = this.getAvatarHtml(id);

            if (avatarHtml) {
                avatarHtml += ' ';
            }

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

            return '<img class="avatar avatar-link" width="14"'+
                ' src="'+this.getBasePath()+'?entryPoint=avatar&size=small&id=' + id + '&t='+t+'">';
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

        getColorFromScopeName: function (scope) {
            var color = this.getMetadata().get(['clientDefs', scope, 'color']) ||
                this.getMetadata().get(['clientDefs', 'Calendar', 'colors', scope]);

            return color;
        },

        hexToRgb: function (hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16),
            } : null;
        },

    });
});
