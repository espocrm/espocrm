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

/** @module modules/crm/views/calendar/timeline */

import View from 'view';
import {DataSet} from 'vis-data';
import {Timeline} from 'vis-timeline';
import moment from 'moment';
import $ from 'jquery';
import RecordModal from 'helpers/record-modal';
import TimelineSharedOptionsModalView from 'crm:views/calendar/modals/shared-options';

class TimelineView extends View {

    template = 'crm:calendar/timeline'

    eventAttributes = []
    colors = {}
    scopeList = []
    header = true
    modeList = []
    defaultMode = 'timeline'
    maxRange = 120
    rangeMarginThreshold = 12 * 3600
    leftMargin = 24 * 3600
    rightMargin = 48 * 3600
    calendarType = 'single'
    calendarTypeList = ['single', 'shared']
    zoomPercentage = 1

    /** @type {Timeline} */
    timeline

    events = {
        /** @this TimelineView */
        'click button[data-action="today"]': function () {
            this.actionToday();
        },
        /** @this TimelineView */
        'click [data-action="mode"]': function (e) {
            const mode = $(e.currentTarget).data('mode');

            this.selectMode(mode)
        },
        /** @this TimelineView */
        'click [data-action="refresh"]': function () {
            this.actionRefresh();
        },
        /** @this TimelineView */
        'click [data-action="toggleScopeFilter"]': function (e) {
            const $target = $(e.currentTarget);
            const filterName = $target.data('name');

            const $check = $target.find('.filter-check-icon');

            if ($check.hasClass('hidden')) {
                $check.removeClass('hidden');
            } else {
                $check.addClass('hidden');
            }

            e.stopPropagation(e);

            this.toggleScopeFilter(filterName);
        },
        /** @this TimelineView */
        'click [data-action="toggleCalendarType"]': function (e) {
            const $target = $(e.currentTarget);
            const calendarType = $target.data('name');

            $target.parent().parent().find('.calendar-type-check-icon').addClass('hidden');

            const $check = $target.find('.calendar-type-check-icon');

            if ($check.hasClass('hidden')) {
                $check.removeClass('hidden');
            }

            $target.closest('.calendar-type-button-group')
                .find('.calendar-type-label')
                .text(this.getCalendarTypeLabel(calendarType));

            const $showSharedCalendarOptions = this.$el
                .find('> .button-container button[data-action="showSharedCalendarOptions"]');

            if (calendarType === 'shared') {
                $showSharedCalendarOptions.removeClass('hidden');
            } else {
                $showSharedCalendarOptions.addClass('hidden');
            }

            this.selectCalendarType(calendarType);
        },
        /** @this TimelineView */
        'click button[data-action="showSharedCalendarOptions"]': function () {
            this.actionShowSharedCalendarOptions();
        },
    }

    /**
     * @param {{
     *     userId?: string,
     *     userName?: string|null,
     *     mode?: string|null,
     *     date?: string|null,
     *     $container?: JQuery,
     *     suppressLoadingAlert?: boolean,
     *     containerSelector?: string,
     *     enabledScopeList?: string[],
     *     calendarType?: string,
     *     userList?: string[],
     *     header?: boolean,
     *     onSave?: function(),
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;
    }

    data() {
        const calendarTypeDataList = this.getCalendarTypeDataList();

        return {
            mode: this.mode,
            header: this.header,
            calendarType: this.calendarType,
            calendarTypeDataList: calendarTypeDataList,
            calendarTypeSelectEnabled: calendarTypeDataList.length > 1,
            calendarTypeLabel: this.getCalendarTypeLabel(this.calendarType),
            isCustomViewAvailable: this.isCustomViewAvailable,
        };
    }

    setup() {
        this.date = this.options.date || this.getDateTime().getToday();
        this.mode = this.options.mode || this.defaultMode;
        this.header = ('header' in this.options) ? this.options.header : this.header;

        this.$container = this.options.$container;

        this.colors = Espo.Utils
            .clone(this.getMetadata().get('clientDefs.Calendar.colors') || this.colors || {});
        this.modeList = this.getMetadata()
            .get('clientDefs.Calendar.modeList') || this.modeList || [];
        this.scopeList = this.getConfig()
            .get('calendarEntityList') || Espo.Utils.clone(this.scopeList) || [];
        this.allDayScopeList = this.getMetadata()
            .get('clientDefs.Calendar.allDayScopeList') || this.allDayScopeList || [];

        this.colors = {...this.colors, ...this.getHelper().themeManager.getParam('calendarColors')};

        this.isCustomViewAvailable = this.getAcl().getPermissionLevel('userCalendar') !== 'no';

        if (this.options.userId) {
            this.isCustomViewAvailable = false;
        }

        const scopeList = [];

        this.scopeList.forEach(scope => {
            if (this.getAcl().check(scope)) {
                scopeList.push(scope);
            }
        });

        this.scopeList = scopeList;

        if (this.header) {
            this.enabledScopeList = this.getStoredEnabledScopeList() || Espo.Utils.clone(this.scopeList);
        } else {
            this.enabledScopeList = this.options.enabledScopeList || Espo.Utils.clone(this.scopeList);
        }

        if (Object.prototype.toString.call(this.enabledScopeList) !== '[object Array]') {
            this.enabledScopeList = [];
        }

         this.enabledScopeList.forEach(item => {
             const color = this.getMetadata().get(['clientDefs', item, 'color']);

             if (color) {
                this.colors[item] = color;
            }
        });

        if (this.options.calendarType) {
            this.calendarType = this.options.calendarType;
        } else {
            if (this.options.userId) {
                this.calendarType = 'single';
            } else {
                this.calendarType = this.getStorage().get('calendar', 'timelineType') || 'shared';
            }
        }

        if (this.getAcl().getPermissionLevel('userCalendar') === 'no') {
            if (this.calendarType === 'shared') {
                this.calendarType = 'single';
            }
        }

        if (!~this.calendarTypeList.indexOf(this.calendarType)) {
            this.calendarType = 'single';
        }

        if (this.header) {
            this.createView('modeButtons', 'crm:views/calendar/mode-buttons', {
                selector: '.mode-buttons',
                isCustomViewAvailable: this.isCustomViewAvailable,
                modeList: this.modeList,
                scopeList: this.scopeList,
                mode: this.mode,
            });
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @return {import('./mode-buttons').default}
     */
    getModeButtonsView() {
        return this.getView('modeButtons');
    }

    selectMode(mode) {
        this.trigger('change:mode', mode);
    }

    getCalendarTypeDataList() {
        const list = [];

        const o = {
            type: 'single',
            disabled: this.calendarType !== 'single',
            label: this.getCalendarTypeLabel('single'),
        };

        list.push(o);

        if (this.options.userId) {
            return list;
        }

        if (this.getAcl().getPermissionLevel('userCalendar') !== 'no') {
            list.push({
                type: 'shared',
                label: this.getCalendarTypeLabel('shared'),
                disabled: this.calendarType !== 'shared'
            });
        }

        return list;
    }

    getCalendarTypeLabel(type) {
        let label;

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
    }

    selectCalendarType(name) {
        this.calendarType = name;

        this.initUserList();
        this.initGroupsDataSet();
        this.timeline.setGroups(this.groupsDataSet);

        this.runFetch();

        this.getStorage().set('calendar', 'timelineType', name);
    }

    toggleScopeFilter(name) {
        const index = this.enabledScopeList.indexOf(name);

        if (!~index) {
            this.enabledScopeList.push(name);
        } else {
            this.enabledScopeList.splice(index, 1);
        }

        this.storeEnabledScopeList(this.enabledScopeList);
        this.runFetch();
    }

    getStoredEnabledScopeList() {
        const key = 'calendarEnabledScopeList';

        return this.getStorage().get('state', key) || null;
    }

    storeEnabledScopeList(enabledScopeList) {
        const key = 'calendarEnabledScopeList';

        this.getStorage().set('state', key, enabledScopeList);
    }

    getTitle() {
        let title = '';

        if (this.options.userId && this.options.userName) {
            title += ' (' + this.options.userName + ')';
        }

        title = this.getHelper().escapeString(title);

        return title;
    }

    /**
     * @param {Object.<string, *>} o
     * @return {Object}
     */
    convertEvent(o) {
        const userId = o.userId || this.userList[0].id || this.getUser().id;

        let event;

        if (o.isBusyRange) {
            event = {
                className: 'busy',
                group: userId,
                'date-start': o.dateStart,
                'date-end': o.dateEnd,
                type: 'background'
            };
        } else if (o.isWorkingRange) {
            event = {
                className: 'working',
                group: userId,
                'date-start': o.dateStart,
                'date-end': o.dateEnd,
                type: 'background',
            };
        } else if (o.isNonWorkingRange) {
            event = {
                className: 'non-working',
                group: userId,
                'date-start': o.dateStart,
                'date-end': o.dateEnd,
                type: 'background',
            };
        }
        else {
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

        this.eventAttributes.forEach(attr => {
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

        if (!o.isNonWorkingRange) {
            this.handleStatus(event);
        }

        return event;
    }

    /**
     * @param {string} scope
     * @return {string[]}
     */
    getEventTypeCompletedStatusList(scope) {
        return this.getMetadata().get(['scopes', scope, 'completedStatusList']) || [];
    }

    /**
     * @param {string} scope
     * @return {string[]}
     */
    getEventTypeCanceledStatusList(scope) {
        return this.getMetadata().get(['scopes', scope, 'canceledStatusList']) || [];
    }

    fillColor(event) {
        let key = event.scope;

        if (event.className === 'non-working') {
            key = 'bg';
        }

        let color = this.colors[key];

        if (event.color) {
            color = event.color;
        }

        if (!color) {
            color = this.getColorFromScopeName(event.scope);
        }

        if (
            event.status &&
            (
                this.getEventTypeCompletedStatusList(event.scope).includes(event.status) ||
                this.getEventTypeCanceledStatusList(event.scope).includes(event.status)
            )
        ) {
            color = this.shadeColor(color, 0.4);
        }

        event.style = event.style || '';
        event.style += 'background-color:' + color + ';';
        event.style += 'border-color:' + color + ';';
    }

    handleStatus(event) {
        if (this.getEventTypeCanceledStatusList(event.scope).includes(event.status)) {
            event.className += ' event-canceled';
        }
    }

    shadeColor(color, percent) {
        if (color === 'transparent') {
            return color;
        }

        if (this.getThemeManager().getParam('isDark')) {
            percent *= -1;
        }

        const alpha = color.substring(7);

        const f = parseInt(color.slice(1, 7), 16),
            t = percent < 0 ? 0 : 255,
            p = percent < 0 ? percent * -1 : percent,
            R = f >> 16,
            G = f >> 8 & 0x00FF,
            B = f & 0x0000FF;

        return "#" + (
            0x1000000 + (
                Math.round((t - R) * p) + R) * 0x10000 +
                (Math.round((t - G) * p) + G) * 0x100 +
                (Math.round((t - B) * p) + B)
            ).toString(16).slice(1) + alpha;
    }

    convertEventList(list) {
        const resultList = [];

        list.forEach(item => {
            const event = this.convertEvent(item);

            if (!event) {
                return;
            }

            resultList.push(event);
        });

        return resultList;
    }

    afterRender() {
        if (this.options.containerSelector) {
            this.$container = $(this.options.containerSelector);
        }

        const $timeline = this.$timeline = this.$el.find('div.timeline');

        this.initUserList();
        this.initDates();
        this.initGroupsDataSet();

        this.fetchEvents(this.start, this.end, eventList => {
            const itemsDataSet = new DataSet(eventList);

            this.timeline = new Timeline($timeline.get(0), itemsDataSet, this.groupsDataSet, {
                dataAttributes: 'all',
                start: this.start.toDate(),
                end: this.end.toDate(),
                rollingMode: {
                    follow: false, // fixes slow render
                },
                xss: {
                    filterOptions: {
                        onTag: (tag, html) => html,
                    },
                },
                moment: /** Record */date => {
                    const m = moment(date);

                    if (date && date.noTimeZone) {
                        return m;
                    }

                    // noinspection JSUnresolvedReference
                    return m.tz(this.getDateTime().getTimeZone());
                },
                format: this.getFormatObject(),
                zoomMax: 24 * 3600 *  1000 * this.maxRange,
                zoomMin: 1000 * 60 * 15,
                orientation: 'top',
                groupEditable: false,
                editable: {
                    add: false,
                    updateTime: false,
                    updateGroup: false,
                    remove: false,
                },
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

            this.timeline.on('click', e => {
                if (this.blockClick) {
                    return;
                }

                if (e.item) {
                    const $item = this.$el.find('.timeline .vis-item[data-id="' + e.item + '"]');
                    const id = $item.attr('data-record-id');
                    const scope = $item.attr('data-scope');

                    if (id && scope) {
                        this.viewEvent(scope, id);
                    }

                    return;
                }

                if (e.what === 'background' && e.group && e.time) {
                    const dateStart = moment(e.time).utc().format(this.getDateTime().internalDateTimeFormat);

                    this.createEvent(dateStart, e.group);
                }
            });

            // noinspection SpellCheckingInspection
            this.timeline.on('rangechanged', e => {
                e.skipClick = true;

                this.blockClick = true;

                setTimeout(() => {
                    this.blockClick = false;
                }, 100);

                this.start = moment(e.start);
                this.end = moment(e.end);

                this.triggerView();

                if (
                    (this.start.unix() < this.fetchedStart.unix() + this.rangeMarginThreshold) ||
                    (this.end.unix() > this.fetchedEnd.unix() - this.rangeMarginThreshold)
                ) {
                    this.runFetch();
                }
            });

            this.once('remove', () => {
                this.timeline.destroy();
            });
        });
    }

    /**
     * @private
     * @param {string} dateStart
     * @param {string} [userId]
     */
    async createEvent(dateStart, userId) {
        if (!dateStart) {
            const time = (this.timeline.getWindow().end - this.timeline.getWindow().start) / 2 +
                this.timeline.getWindow().start;

            dateStart = moment(time)
                .utc()
                .format(this.getDateTime().internalDateTimeFormat);

            if (this.date === this.getDateTime().getToday()) {
                dateStart = moment()
                    .utc()
                    .format(this.getDateTime().internalDateTimeFormat);
            }
        }

        const attributes = {dateStart: dateStart};

        if (userId) {
            let userName;

            this.userList.forEach((item) => {
                if (item.id === userId) {
                    userName = item.name;
                }
            });

            attributes.assignedUserId = userId;
            attributes.assignedUserName = userName || userId;
        }

        Espo.Ui.notifyWait();

        const view = await this.createView('dialog', 'crm:views/calendar/modals/edit', {
            attributes: attributes,
            enabledScopeList: this.enabledScopeList,
            scopeList: this.scopeList
        });

        this.listenTo(view, 'before:save', () => {
            if (this.options.onSave) {
                this.options.onSave();
            }
        });

        this.listenTo(view, 'after:save', () => this.runFetch());

        await view.render();

        Espo.Ui.notify();
    }

    /**
     *
     * @param {string} scope
     * @param {string} id
     */
    async viewEvent(scope, id) {
        const helper = new RecordModal();

        /** @type {import('views/modals/detail').default} */
        let modalView;

        modalView = await helper.showDetail(this, {
            entityType: scope,
            id: id,
            removeDisabled: false,
            beforeSave: () => {
                if (this.options.onSave) {
                    this.options.onSave();
                }
            },
            beforeDestroy: () => {
                if (this.options.onSave) {
                    this.options.onSave();
                }
            },
            afterSave: (model, o) => {
                if (!o.bypassClose) {
                    modalView.close();
                }

                this.runFetch();
            },
            afterDestroy: () => {
                this.runFetch();
            },
        });
    }

    runFetch() {
        this.fetchEvents(this.start, this.end, eventList => {
            const itemsDataSet = new DataSet(eventList);

            this.timeline.setItems(itemsDataSet);
            this.triggerView();
        });
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
                year: 'YYYY',
            },
            majorLabels: {
                millisecond: this.getDateTime().getTimeFormat() + ' ss',
                second: this.getDateTime().getReadableDateFormat() + ' HH:mm',
                minute: 'ddd D MMMM',
                hour: 'ddd D MMMM',
                weekday: 'MMMM YYYY',
                day: 'MMMM YYYY',
                month: 'YYYY',
                year: '',
            }
        };
    }

    triggerView() {
        const m = this.start.clone().add(Math.round((this.end.unix() - this.start.unix()) / 2), 'seconds');

        const date = m.format(this.getDateTime().internalDateFormat);

        this.date = date;

        this.trigger('view', date, this.mode);
    }

    initUserList() {
        if (this.options.userList) {
            this.userList = Espo.Utils.clone(this.options.userList);

            if (!this.userList.length) {
                this.userList.push({
                    id: this.getUser().id,
                    name: this.getUser().get('name'),
                });
            }

            return;
        }

        this.userList = [];

        if (this.calendarType === 'single') {
            if (this.options.userId) {
                this.userList.push({
                    id: this.options.userId,
                    name: this.options.userName || this.options.userId
                });

                return;
            }

            this.userList.push({
                id: this.getUser().id,
                name: this.getUser().get('name'),
            });


            return;
        }

        if (this.calendarType === 'shared') {
            this.getSharedCalenderUserList().forEach(item => {
                this.userList.push({
                    id: item.id,
                    name: item.name,
                });
            });
        }
    }

    storeUserList() {
        this.getPreferences().save({
            'sharedCalendarUserList': Espo.Utils.clone(this.userList),
        }, {patch: true});
    }

    getSharedCalenderUserList() {
        const list = Espo.Utils.clone(this.getPreferences().get('sharedCalendarUserList'));

        if (list && list.length) {
            let isBad = false;

            list.forEach(item => {
                if (typeof item !== 'object' || !item.id || !item.name) {
                    isBad = true;
                }
            });

            if (!isBad) {
                return list;
            }
        }

        return [{
            id: this.getUser().id,
            name: this.getUser().get('name'),
        }];
    }

    initDates() {
        if (this.date) {
            this.start = moment.tz(this.date, this.getDateTime().getTimeZone());
        } else {
            this.start = moment.tz(this.getDateTime().getTimeZone());
        }

        this.end = this.start.clone();
        this.end.add(1, 'day');

        this.fetchedStart = null;
        this.fetchedEnd = null;
    }

    initGroupsDataSet() {
        const list = [];

        this.userList.forEach((user, i)  =>{
            list.push({
                id: user.id,
                content: this.getGroupContent(user.id, user.name),
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

        return avatarHtml +
            $('<span>')
                .attr('data-id', id)
                .addClass('group-title')
                .text(name)
                .get(0).outerHTML;
    }

    getAvatarHtml(id) {
        if (this.getConfig().get('avatarsDisabled')) {
            return '';
        }

        let t;
        const cache = this.getCache();

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

    fetchEvents(from, to, callback) {
        if (!this.options.suppressLoadingAlert) {
            Espo.Ui.notifyWait();
        }

        from = from.clone().add((-1) * this.leftMargin, 'seconds');
        to = to.clone().add(this.rightMargin, 'seconds');

        const fromString = from.utc().format(this.getDateTime().internalDateTimeFormat);
        const toString = to.utc().format(this.getDateTime().internalDateTimeFormat);

        let url = 'Timeline?from=' + fromString + '&to=' + toString;

        const userIdList = this.userList.map(user => {
            return user.id;
        });

        if (userIdList.length === 1) {
            url += '&userId=' + userIdList[0];
        } else {
            url += '&userIdList=' + encodeURIComponent(userIdList.join(','));
        }

        url += '&scopeList=' + encodeURIComponent(this.enabledScopeList.join(','));

        Espo.Ajax.getRequest(url).then(data => {
            this.fetchedStart = from.clone();
            this.fetchedEnd = to.clone();

            const eventList = [];

            for (const userId in data) {
                const userEventList = data[userId];

                userEventList.forEach(item => {
                    item.userId = userId;

                    eventList.push(item);
                });
            }

            const convertedEventList = this.convertEventList(eventList);

            callback(convertedEventList);

            Espo.Ui.notify(false);
        });
    }

    async actionShowSharedCalendarOptions() {
        const view = new TimelineSharedOptionsModalView({
            users: this.userList,
            onApply: data => {
                this.userList = data.users;

                this.storeUserList();
                this.initGroupsDataSet();

                this.timeline.setGroups(this.groupsDataSet);

                this.runFetch();
            },
        });

        await this.assignView('modal', view);

        await view.render();
    }

    actionRefresh() {
        this.runFetch();

        const iconEl = this.element.querySelector('button[data-action="refresh"] > span');

        if (iconEl) {
            iconEl.classList.add('animation-spin-fast');

            setTimeout(() => iconEl.classList.remove('animation-spin-fast'), 500);
        }
    }

    getColorFromScopeName(scope) {
        const additionalColorList = this.getMetadata().get('clientDefs.Calendar.additionalColorList') || [];

        if (!additionalColorList.length) {
            return;
        }

        const colors = this.getMetadata().get('clientDefs.Calendar.colors') || {};
        const scopeList = this.getConfig().get('calendarEntityList') || [];

        let index = 0;
        let j = 0;

        for (let i = 0; i < scopeList.length; i++) {
            if (scopeList[i] in colors) {
                continue;
            }

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

    // noinspection JSUnusedGlobalSymbols
    actionPrevious() {
        const start = this.timeline.getWindow().start;

        this.timeline.moveTo(start);

        this.triggerView();
    }

    // noinspection JSUnusedGlobalSymbols
    actionNext() {
        const end = this.timeline.getWindow().end;

        this.timeline.moveTo(end);

        this.triggerView();
    }

    actionToday() {
        this.timeline.moveTo(moment().toDate());

        this.triggerView();
    }

    // noinspection JSUnusedGlobalSymbols
    actionZoomOut() {
        this.timeline.zoomOut(this.zoomPercentage);

        this.triggerView();
    }

    // noinspection JSUnusedGlobalSymbols
    actionZoomIn() {
        this.timeline.zoomIn(this.zoomPercentage);
    }
}

export default TimelineView;
