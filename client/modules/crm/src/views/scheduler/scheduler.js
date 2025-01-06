/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import View from 'view';
import {DataSet} from 'vis-data';
import {Timeline} from 'vis-timeline';
import moment from 'moment';
import $ from 'jquery';

class SchedulerView extends View {

    // language=Handlebars
    templateContent = `
        <div class="timeline"></div>
        <link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">
    `

    rangeMarginThreshold = 12 * 3600
    leftMargin = 24 * 3600
    rightMargin = 48 * 3600
    rangeMultiplierLeft = 3
    rangeMultiplierRight = 3

    setup() {
        this.startField = this.options.startField || 'dateStart';
        this.endField = this.options.endField || 'dateEnd';
        this.assignedUserField = this.options.assignedUserField || 'assignedUser';

        this.startDateField = this.startField + 'Date';
        this.endDateField = this.endField + 'Date';

        this.colors = Espo.Utils.clone(this.getMetadata().get('clientDefs.Calendar.colors') || {});

        this.colors = {...this.colors, ...this.getHelper().themeManager.getParam('calendarColors')};

        let usersFieldDefault = 'users';

        if (!this.model.hasLink('users') && this.model.hasLink('assignedUsers')) {
            usersFieldDefault = 'assignedUsers';
        }

        this.eventAssignedUserIsAttendeeDisabled =
            this.getConfig().get('eventAssignedUserIsAttendeeDisabled') || false;

        this.usersField = this.options.usersField || usersFieldDefault;
        this.userIdList = [];

        this.listenTo(this.model, 'change', (m) => {
            let isChanged =
                m.hasChanged('isAllDay') ||
                m.hasChanged(this.startField) ||
                m.hasChanged(this.endField) ||
                m.hasChanged(this.endDateField) ||
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

                return;
            }

            if (this.isRemoved()) {
                return;
            }

            this.trigger('has-data');
            this.reRender();
        });

        this.once('remove', () => {
            this.destroyTimeline();
        });
    }

    destroyTimeline() {
        if (this.timeline) {
            this.timeline.destroy();
            this.timeline = null;
        }
    }

    showNoData() {
        this.noDataShown = true;

        this.destroyTimeline();

        this.$timeline.empty();
        this.$timeline.append(
            $('<div>')
                .addClass('revert-margin')
                .text(this.translate('No Data'))
        );
    }

    afterRender() {
        let $timeline = this.$timeline = this.$el.find('.timeline');

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

        this.fetch(this.start, this.end, eventList => {
            let itemsDataSet = new DataSet(eventList);

            // noinspection SpellCheckingInspection
            this.timeline = new Timeline($timeline.get(0), itemsDataSet, this.groupsDataSet, {
                dataAttributes: 'all',
                start: this.start.toDate(),
                end: this.end.toDate(),
                rollingMode: {
                    follow: false, // fixes slow render
                },
                moment: date => {
                    let m = moment(date);

                    if (date && date.noTimeZone) {
                        return m;
                    }

                    return m.tz(this.getDateTime().getTimeZone());
                },
                xss: {
                    filterOptions: {
                        onTag: (tag, html) => html,
                    },
                },
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
                    myLocale: {
                        current: this.translate('current', 'labels', 'Calendar'),
                        time: this.translate('time', 'labels', 'Calendar'),
                    }
                },
                locale: 'myLocale',
                margin: {
                    item: {
                        vertical: 12,
                    },
                    axis: 6,
                },
            });

            $timeline.css('min-height', '');

            // noinspection SpellCheckingInspection
            this.timeline.on('rangechanged', (e) => {
                e.skipClick = true;

                this.blockClick = true;

                setTimeout(function () {this.blockClick = false}.bind(this), 100);

                this.start = moment(e.start);
                this.end = moment(e.end);

                this.updateRange();
            });

            setTimeout(() => {
                this.lastHeight = $timeline.height();
            }, 500);
        });
    }

    updateEvent() {
        let eventList = Espo.Utils.cloneDeep(this.busyEventList);

        let convertedEventList = this.convertEventList(eventList);
        this.addEvent(convertedEventList);

        let itemsDataSet = new DataSet(convertedEventList);

        this.timeline.setItems(itemsDataSet);
    }

    updateRange() {
        if (
            (this.start.unix() < this.fetchedStart.unix() + this.rangeMarginThreshold) ||
            (this.end.unix() > this.fetchedEnd.unix() - this.rangeMarginThreshold)
        ) {
            this.runFetch();
        }
    }

    initDates(update) {
        this.start = null;
        this.end = null;

        let startS = this.model.get(this.startField);
        let endS = this.model.get(this.endField);

        if (this.model.get('isAllDay')) {
            startS = this.model.get(this.startDateField);
            endS = this.model.get(this.endDateField);
        }

        if (!startS || !endS) {
            return;
        }

        if (this.model.get('isAllDay')) {
            this.eventStart = moment.tz(startS, this.getDateTime().getTimeZone());
            this.eventEnd = moment.tz(endS, this.getDateTime().getTimeZone());
            this.eventEnd.add(1, 'day');
        } else {
            this.eventStart = moment.utc(startS).tz(this.getDateTime().getTimeZone());
            this.eventEnd = moment.utc(endS).tz(this.getDateTime().getTimeZone());
        }

        let diff = this.eventEnd.diff(this.eventStart, 'hours');

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
    }

    runFetch() {
        this.fetch(this.start, this.end, eventList => {
            let itemsDataSet = new DataSet(eventList);

            this.timeline.setItems(itemsDataSet);
        });
    }

    fetch(from, to, callback) {
        from = from.clone().add((-1) * this.leftMargin, 'seconds');
        to = to.clone().add(this.rightMargin, 'seconds');

        let fromString = from.utc().format(this.getDateTime().internalDateTimeFormat);
        let toString = to.utc().format(this.getDateTime().internalDateTimeFormat);

        let url =
            'Timeline/busyRanges?from=' + fromString + '&to=' + toString +
            '&userIdList=' + encodeURIComponent(this.userIdList.join(',')) +
            '&entityType=' + this.model.entityType;

        if (this.model.id) {
            url += '&entityId=' + this.model.id;
        }

        Espo.Ajax.getRequest(url).then(data => {
            this.fetchedStart = from.clone();
            this.fetchedEnd = to.clone();

            let eventList = [];

            for (let userId in data) {
                let itemList = /** @type {Object.<string, *>} */data[userId]
                    .filter(item => !item.isBusyRange)
                    .concat(
                        data[userId].filter(item => item.isBusyRange)
                    );

                itemList.forEach(item => {
                    item.userId = userId;

                    eventList.push(item);
                });
            }

            this.busyEventList = Espo.Utils.cloneDeep(eventList);

            let convertedEventList = this.convertEventList(eventList);

            this.addEvent(convertedEventList);

            callback(convertedEventList);
        });
    }

    addEvent(list) {
        this.getCurrentItemList().forEach(item => {
            list.push(item);
        });
    }

    getCurrentItemList() {
        let list = [];

        let o = {
            start: this.eventStart.clone(),
            end: this.eventEnd.clone(),
            type: 'background',
            style: 'z-index: 4; opacity: 0.6;',
            className: 'event-range',
        };

        let color = this.getColorFromScopeName(this.model.entityType);

        if (color) {
            o.style += '; border-color: ' + color;

            let rgb = this.hexToRgb(color);

            o.style += '; background-color: rgba('+rgb.r+', '+rgb.g+', '+rgb.b+', 0.01)';
        }

        this.userIdList.forEach(id => {
            let c = Espo.Utils.clone(o);
            c.group = id;
            c.id = 'event-' + id;

            list.push(c);
        });

        return list;
    }

    convertEventList(list) {
        let resultList = [];

        list.forEach(item => {
            let event = this.convertEvent(item);

            if (!event) {
                return;
            }

            resultList.push(event);
        });

        return resultList;
    }

    /**
     * @param {Object.<string, *>} o
     * @return {Object}
     */
    convertEvent(o) {
        let event;

        if (o.isBusyRange) {
            event = {
                className: 'busy',
                group: o.userId,
                'date-start': o.dateStart,
                'date-end': o.dateEnd,
                type: 'background',
            };
        }
        else if (o.isWorkingRange) {
            event = {
                className: 'working',
                group: o.userId,
                'date-start': o.dateStart,
                'date-end': o.dateEnd,
                type: 'background',
            };
        }
        else if (o.isNonWorkingRange) {
            event = {
                className: 'non-working',
                group: o.userId,
                'date-start': o.dateStart,
                'date-end': o.dateEnd,
                type: 'background',
            };

            let color = this.colors['bg'];

            event.style = 'background-color:' + color + ';';
            event.style += 'border-color:' + color + ';';
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

        if (o.isBusyRange || o.isNonWorkingRange) {
            return event;
        }
    }

    initGroupsDataSet() {
        let list = [];

        let userIdList = Espo.Utils.clone(this.model.get(this.usersField + 'Ids') || []);
        let assignedUserId = this.model.get(this.assignedUserField + 'Id');

        let names = this.model.get(this.usersField + 'Names') || {};

        if (!this.eventAssignedUserIsAttendeeDisabled && assignedUserId) {
            if (!~userIdList.indexOf(assignedUserId)) {
                userIdList.unshift(assignedUserId);
            }

            names[assignedUserId] = this.model.get(this.assignedUserField + 'Name');
        }

        this.userIdList = userIdList;

        userIdList.forEach((id, i) => {
            list.push({
                id: id,
                content: this.getGroupContent(id, names[id] || id),
                order: i,
            });
        });

        this.groupsDataSet = new DataSet(list);
    }

    getGroupContent(id, name) {
        if (this.calendarType === 'single') {
            return $('<span>')
                .text(name)
                .get(0).outerHTML;
        }

        let avatarHtml = this.getAvatarHtml(id);

        if (avatarHtml) {
            avatarHtml += ' ';
        }

        return $('<span>')
            .append(
                $(avatarHtml),
                $('<span>')
                    .attr('data-id', id)
                    .addClass('group-title')
                    .text(name)
            )
            .get(0).innerHTML;
    }

    getAvatarHtml(id) {
        if (this.getConfig().get('avatarsDisabled')) {
            return '';
        }

        let t;
        let cache = this.getCache();

        if (cache) {
            t = cache.get('app', 'timestamp');
        } else {
            t = Date.now();
        }

        // noinspection HtmlRequiredAltAttribute,RequiredAttributes
        return $('<img>')
            .addClass('avatar avatar-link')
            .attr('width', '16')
            .attr('src', this.getBasePath() + '?entryPoint=avatar&size=small&id=' + id + '&t=' + t)
            .get(0).outerHTML;
    }

    getFormatObject() {
        return {
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
    }

    getColorFromScopeName(scope) {
        return this.getMetadata().get(['clientDefs', scope, 'color']) ||
            this.getMetadata().get(['clientDefs', 'Calendar', 'colors', scope]);
    }

    hexToRgb(hex) {
        let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
        } : null;
    }
}

export default SchedulerView;
