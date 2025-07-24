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

/** @module modules/crm/views/calendar/calendar */

import View from 'view';
import moment from 'moment';
import * as FullCalendar from 'fullcalendar';
import RecordModal from 'helpers/record-modal';

/**
 * @typedef {import('@fullcalendar/core/internal-common.js').EventImpl} EventImpl
 */

/**
 * @typedef {import('@fullcalendar/core/internal-common.d.ts').CalendarOptions} CalendarOptions
 */

/**
 * @typedef {import('@fullcalendar/interaction/index.d.ts').ExtraListenerRefiners} ExtraListenerRefiners
 */

class CalendarView extends View {

    template = 'crm:calendar/calendar'

    eventAttributes = []
    colors = {}
    allDayScopeList = ['Task']
    scopeList = ['Meeting', 'Call', 'Task']
    header = true
    modeList = []
    fullCalendarModeList = [
        'month',
        'agendaWeek',
        'agendaDay',
        'basicWeek',
        'basicDay',
        'listWeek',
    ]
    defaultMode = 'agendaWeek'
    slotDuration = 30
    scrollToNowSlots = 6;
    scrollHour = 6
    titleFormat = {
        month: 'MMMM YYYY',
        week: 'MMMM YYYY',
        day: 'dddd, MMMM D, YYYY',
    }
    rangeSeparator = ' – '

    /** @private */
    fetching = false

    modeViewMap = {
        month: 'dayGridMonth',
        agendaWeek: 'timeGridWeek',
        agendaDay: 'timeGridDay',
        basicWeek: 'dayGridWeek',
        basicDay: 'dayGridDay',
        listWeek: 'listWeek',
    }

    extendedProps = [
        'scope',
        'recordId',
        'dateStart',
        'dateEnd',
        'dateStartDate',
        'dateEndDate',
        'status',
        'originalColor',
        'duration',
        'allDayCopy',
    ]

    /** @type {FullCalendar.Calendar} */
    calendar

    events = {
        /** @this CalendarView */
        'click button[data-action="prev"]': function () {
            this.actionPrevious();
        },
        /** @this CalendarView */
        'click button[data-action="next"]': function () {
            this.actionNext();
        },
        /** @this CalendarView */
        'click button[data-action="today"]': function () {
            this.actionToday();
        },
        /** @this CalendarView */
        'click [data-action="mode"]': function (e) {
            const mode = $(e.currentTarget).data('mode');

            this.selectMode(mode);
        },
        /** @this CalendarView */
        'click [data-action="refresh"]': function () {
            this.actionRefresh();
        },
        /** @this CalendarView */
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
    }


    /**
     * @param {{
     *     userId?: string,
     *     userName?: string|null,
     *     mode?: string|null,
     *     date?: string|null,
     *     scrollToNowSlots?: boolean,
     *     $container?: JQuery,
     *     suppressLoadingAlert?: boolean,
     *     slotDuration?: number,
     *     scrollHour?: number,
     *     teamIdList?: string[],
     *     containerSelector?: string,
     *     height?: number,
     *     enabledScopeList?: string[],
     *     header?: boolean,
     *     onSave?: function(),
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;
    }

    data() {
        return {
            mode: this.mode,
            header: this.header,
            isCustomViewAvailable: this.isCustomViewAvailable,
            isCustomView: this.isCustomView,
            todayLabel: this.translate('Today', 'labels', 'Calendar'),
            todayLabelShort: this.translate('Today', 'labels', 'Calendar').slice(0, 2),
        };
    }

    setup() {
        this.wait(Espo.loader.requirePromise('lib!@fullcalendar/moment'));
        this.wait(Espo.loader.requirePromise('lib!@fullcalendar/moment-timezone'));

        this.suppressLoadingAlert = this.options.suppressLoadingAlert;

        this.date = this.options.date || null;
        this.mode = this.options.mode || this.defaultMode;
        this.header = ('header' in this.options) ? this.options.header : this.header;

        this.scrollToNowSlots = this.options.scrollToNowSlots !== undefined ?
            this.options.scrollToNowSlots : this.scrollToNowSlots;

        this.setupMode();

        this.$container = this.options.$container;

        this.colors = Espo.Utils
            .clone(this.getMetadata().get('clientDefs.Calendar.colors') || this.colors);

        this.modeList = this.getMetadata()
            .get('clientDefs.Calendar.modeList') || this.modeList;

        this.scopeList = this.getConfig()
            .get('calendarEntityList') || Espo.Utils.clone(this.scopeList);

        this.allDayScopeList = this.getMetadata()
            .get('clientDefs.Calendar.allDayScopeList') || this.allDayScopeList;

        this.slotDuration = this.options.slotDuration ||
            this.getPreferences().get('calendarSlotDuration') ||
            this.getMetadata().get('clientDefs.Calendar.slotDuration') ||
            this.slotDuration;

        this.setupScrollHour();

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

    /**
     * @private
     */
    setupScrollHour() {
        if (this.options.scrollHour !== undefined) {
            this.scrollHour = this.options.scrollHour;

            return;
        }

        const scrollHour = this.getPreferences().get('calendarScrollHour');

        if (scrollHour !== null) {
            this.scrollHour = scrollHour;

            return;
        }

        if (this.slotDuration < 30) {
            this.scrollHour = 8;
        }
    }

    setupMode() {
        this.viewMode = this.mode;

        this.isCustomView = false;
        this.teamIdList = this.options.teamIdList || null;

        if (this.teamIdList && !this.teamIdList.length) {
            this.teamIdList = null;
        }

        if (~this.mode.indexOf('view-')) {
            this.viewId = this.mode.slice(5);
            this.isCustomView = true;

            const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

            calendarViewDataList.forEach(item => {
                if (item.id === this.viewId) {
                    this.viewMode = item.mode;
                    this.teamIdList = item.teamIdList;
                    this.viewName = item.name;
                }
            });
        }
    }

    /**
     * @private
     * @return {boolean}
     */
    isAgendaMode() {
        return this.mode.indexOf('agenda') === 0;
    }

    /**
     * @param {string} mode
     */
    selectMode(mode) {
        if (this.fullCalendarModeList.includes(mode) || mode.indexOf('view-') === 0) {
            const previousMode = this.mode;

            if (
                mode.indexOf('view-') === 0 ||
                mode.indexOf('view-') !== 0 && previousMode.indexOf('view-') === 0
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

            this.calendar.changeView(this.modeViewMap[this.viewMode]);

            const toAgenda = previousMode.indexOf('agenda') !== 0 && mode.indexOf('agenda') === 0;
            const fromAgenda = previousMode.indexOf('agenda') === 0 && mode.indexOf('agenda') !== 0;

            if (
                toAgenda && !this.fetching ||
                fromAgenda && !this.fetching
            ) {
                this.calendar.refetchEvents();
            }

            this.updateDate();

            if (this.hasView('modeButtons')) {
                this.getModeButtonsView().mode = mode;
                this.getModeButtonsView().reRender();
            }
        }

        this.trigger('change:mode', mode);
    }

    /**
     * @return {import('./mode-buttons').default}
     */
    getModeButtonsView() {
        return this.getView('modeButtons');
    }

    /**
     * @private
     * @param {string} name
     */
    toggleScopeFilter(name) {
        const index = this.enabledScopeList.indexOf(name);

        if (!~index) {
            this.enabledScopeList.push(name);
        } else {
            this.enabledScopeList.splice(index, 1);
        }

        this.storeEnabledScopeList(this.enabledScopeList);

        this.calendar.refetchEvents();
    }

    /**
     * @private
     * @return {string[]|null}
     */
    getStoredEnabledScopeList() {
        const key = 'calendarEnabledScopeList';

        return this.getStorage().get('state', key) || null;
    }

    /**
     * @private
     * @param {string[]} enabledScopeList
     */
    storeEnabledScopeList(enabledScopeList) {
        const key = 'calendarEnabledScopeList';

        this.getStorage().set('state', key, enabledScopeList);
    }

    /**
     * @private
     */
    updateDate() {
        if (!this.header) {
            return;
        }

        if (this.isToday()) {
            this.$el.find('button[data-action="today"]').addClass('active');
        } else {
            this.$el.find('button[data-action="today"]').removeClass('active');
        }

        const title = this.getTitle();

        this.$el.find('.date-title h4 span').text(title);
    }

    /**
     * @private
     * @return {boolean}
     */
    isToday() {
        const view = this.calendar.view;

        const todayUnix = moment().unix();
        const startUnix = moment(view.activeStart).unix();
        const endUnix = moment(view.activeEnd).unix();

        return startUnix <= todayUnix && todayUnix < endUnix;
    }

    /**
     * @private
     * @return {string}
     */
    getTitle() {
        const view = this.calendar.view;

        const map = {
            timeGridWeek: 'week',
            timeGridDay: 'day',
            dayGridWeek: 'week',
            dayGridDay: 'day',
            dayGridMonth: 'month',
        };

        const viewName = map[view.type] || view.type;

        let title;

        const format = this.titleFormat[viewName];

        if (viewName === 'week') {
            const start = this.dateToMoment(view.currentStart).format(format);
            const end = this.dateToMoment(view.currentEnd).subtract(1, 'minute').format(format);

            title = start !== end ?
                start + this.rangeSeparator + end :
                start;
        } else {
            title = this.dateToMoment(view.currentStart).format(format);
        }

        if (this.options.userId && this.options.userName) {
            title += ' (' + this.options.userName + ')';
        }

        title = this.getHelper().escapeString(title);

        return title;
    }

    /**
     * @typedef {{
     *     recordId,
     *     dateStart?: string,
     *     originalColor?: string,
     *     scope?: string,
     *     display: string,
     *     id: string,
     *     dateEnd?: string,
     *     dateStartDate?: ?string,
     *     title: string,
     *     dateEndDate?: ?string,
     *     status?: string,
     *     allDay?: boolean,
     *     start?: Date,
     *     end?: Date,
     * }} module:modules/crm/views/calendar/calendar~FcEvent
     */

    /**
     * @private
     * @param {Object.<string, *>} o
     * @return {module:modules/crm/views/calendar/calendar~FcEvent}
     */
    convertToFcEvent(o) {
        const event = {
            title: o.name || '',
            scope: o.scope,
            id: o.scope + '-' + o.id,
            recordId: o.id,
            dateStart: o.dateStart,
            dateEnd: o.dateEnd,
            dateStartDate: o.dateStartDate,
            dateEndDate: o.dateEndDate,
            status: o.status,
            originalColor: o.color,
            display: 'block',
        };

        if (o.isWorkingRange) {
            event.display = 'inverse-background';
            event.groupId = 'nonWorking';
            event.color = this.colors['bg'];
        }

        if (this.teamIdList) {
            event.userIdList = o.userIdList || [];
            event.userNameMap = o.userNameMap || {};

            event.userIdList = event.userIdList.sort((v1, v2) => {
                return (event.userNameMap[v1] || '').localeCompare(event.userNameMap[v2] || '');
            });
        }

        this.eventAttributes.forEach(attr => {
            event[attr] = o[attr];
        });

        let start;
        let end;

        if (o.dateStart) {
            start = !o.dateStartDate ?
                this.getDateTime().toMoment(o.dateStart) :
                this.dateToMoment(o.dateStartDate);
        }

        if (o.dateEnd) {
            end = !o.dateEndDate ?
                this.getDateTime().toMoment(o.dateEnd) :
                this.dateToMoment(o.dateEndDate);
        }

        if (end && start) {
            event.duration = end.unix() - start.unix();
        }

        if (start) {
            event.start = start.toISOString(true);
        }

        if (end) {
            event.end = end.toISOString(true);
        }

        event.allDay = false;

        if (!o.isWorkingRange) {
            this.handleAllDay(event);
            this.fillColor(event);
            this.handleStatus(event);
        }

        if (o.isWorkingRange && !this.isAgendaMode()) {
            event.allDay = true;
        }

        return event;
    }

    /**
     * @private
     * @param {string|Date} date
     * @return {moment.Moment}
     */
    dateToMoment(date)  {
        return moment.tz(
            date,
            this.getDateTime().getTimeZone()
        );
    }

    /**
     * @private
     * @param {string} scope
     * @return {string[]}
     */
    getEventTypeCompletedStatusList(scope) {
        return this.getMetadata().get(['scopes', scope, 'completedStatusList']) || [];
    }

    /**
     * @private
     * @param {string} scope
     * @return {string[]}
     */
    getEventTypeCanceledStatusList(scope) {
        return this.getMetadata().get(['scopes', scope, 'canceledStatusList']) || [];
    }

    /**
     * @private
     * @param {Record} event
     */
    fillColor(event) {
        let color = this.colors[event.scope];

        if (event.originalColor) {
            color = event.originalColor;
        }

        if (!color) {
            color = this.getColorFromScopeName(event.scope);
        }

        if (
            color &&
            (
                this.getEventTypeCompletedStatusList(event.scope).includes(event.status) ||
                this.getEventTypeCanceledStatusList(event.scope).includes(event.status)
            )
        ) {
            color = this.shadeColor(color, 0.4);
        }

        event.color = color;
    }

    /**
     * @private
     * @param {Object} event
     */
    handleStatus(event) {
        if (this.getEventTypeCanceledStatusList(event.scope).includes(event.status)) {
            event.className = ['event-canceled'];
        } else {
            event.className = [];
        }
    }

    /**
     * @private
     * @param {string} color
     * @param {number} percent
     * @return {string}
     */
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

    /**
     * @private
     * @param {EventImpl} event
     * @param {boolean} [afterDrop]
     */
    handleAllDay(event, afterDrop) {
        let start = event.start ? this.dateToMoment(event.start) : null;
        const end = event.end ? this.dateToMoment(event.end) : null;

        if (this.allDayScopeList.includes(event.scope)) {
            event.allDay = event.allDayCopy = true;

            if (!afterDrop && end) {
                start = end.clone();

                if (
                    !event.dateEndDate &&
                    end.hours() === 0 &&
                    end.minutes() === 0
                ) {
                    start.add(-1, 'days');
                }
            }

            if (start.isSame(end)) {
                end.add(1, 'days');
            }

            if (start) {
                event.start = start.toDate();
            }

            if (end) {
                event.end = end.toDate();
            }

            return;
        }

        if (event.dateStartDate && event.dateEndDate) {
            event.allDay = true;
            event.allDayCopy = event.allDay;

            if (!afterDrop) {
                end.add(1, 'days')
            }

            if (start) {
                event.start = start.toDate();
            }

            if (end) {
                event.end = end.toDate();
            }

            return;
        }

        if (!start || !end) {
            event.allDay = true;

            if (end) {
                start = end;
            }
        } else if (
            start.format('YYYY-DD') !== end.format('YYYY-DD') &&
            end.unix() - start.unix() >= 86400
        ) {
            event.allDay = true;

            //if (!notInitial) {
            if (end.hours() !== 0 || end.minutes() !== 0) {
                end.add(1, 'days');
            }
            //}
        } else {
            event.allDay = false;
        }

        event.allDayCopy = event.allDay;

        if (start) {
            event.start = start.toDate();
        }

        if (end) {
            event.end = end.toDate();
        }
    }

    /**
     * @private
     * @param {Record[]} list
     * @return {Record[]}
     */
    convertToFcEvents(list) {
        this.now = moment.tz(this.getDateTime().getTimeZone());

        const events = [];

        list.forEach(o => {
            const event = this.convertToFcEvent(o);

            events.push(event);
        });

        return events;
    }

    /**
     * @private
     * @param {string} date
     * @return {string}
     */
    convertDateTime(date) {
        const format = this.getDateTime().internalDateTimeFormat;
        const timeZone = this.getDateTime().timeZone;

        const m = timeZone ?
            moment.tz(date, null, timeZone).utc() :
            moment.utc(date, null);

        return m.format(format) + ':00';
    }

    /**
     * @private
     * @return {number}
     */
    getCalculatedHeight() {
        if (this.$container && this.$container.length) {
            return this.$container.height();
        }

        return this.getHelper().calculateContentContainerHeight(this.$el.find('.calendar'));
    }

    /**
     * @private
     */
    adjustSize() {
        if (this.isRemoved()) {
            return;
        }

        const height = this.getCalculatedHeight();

        this.calendar.setOption('contentHeight', height);
        this.calendar.updateSize();
    }

    afterRender() {
        if (this.options.containerSelector) {
            this.$container = $(this.options.containerSelector);
        }

        this.$calendar = this.$el.find('div.calendar');

        const slotDuration = '00:' + this.slotDuration + ':00';
        const timeFormat = this.getDateTime().timeFormat;

        let slotLabelFormat = timeFormat;

        if (~timeFormat.indexOf('a')) {
            slotLabelFormat = 'h:mma';
        } else if (~timeFormat.indexOf('A')) {
            slotLabelFormat = 'h:mmA';
        }

        /** @type {CalendarOptions & Object.<string, *>} */
        const options = {
            scrollTime: this.scrollHour + ':00',
            headerToolbar: false,
            slotLabelFormat: slotLabelFormat,
            eventTimeFormat: timeFormat,
            initialView: this.modeViewMap[this.viewMode],
            defaultRangeSeparator: this.rangeSeparator,
            weekNumbers: true,
            weekNumberCalculation: 'ISO',
            editable: true,
            selectable: true,
            selectMirror: true,
            height: this.options.height || void 0,
            firstDay: this.getDateTime().weekStart,
            slotEventOverlap: true,
            slotDuration: slotDuration,
            slotLabelInterval: '01:00',
            snapDuration: this.slotDuration * 60 * 1000,
            timeZone: this.getDateTime().timeZone || undefined,
            longPressDelay: 300,
            eventColor: this.colors[''],
            nowIndicator: true,
            allDayText: '',
            weekText: '',
            views: {
                week: {
                    dayHeaderFormat: 'ddd DD',
                },
                day: {
                    dayHeaderFormat: 'ddd DD',
                },
                month: {
                    dayHeaderFormat: 'ddd',
                },
            },
            windowResize: () => {
                this.adjustSize();
            },
            select: info => {
                const start = info.startStr;
                const end = info.endStr;
                const allDay = info.allDay;

                let dateEndDate = null;
                let dateStartDate = null;

                const dateStart = this.convertDateTime(start);
                const dateEnd = this.convertDateTime(end);

                if (allDay) {
                    dateStartDate = moment(start).format('YYYY-MM-DD');
                    dateEndDate = moment(end).clone().add(-1, 'days').format('YYYY-MM-DD');
                }

                this.createEvent({
                    dateStart: dateStart,
                    dateEnd: dateEnd,
                    allDay: allDay,
                    dateStartDate: dateStartDate,
                    dateEndDate: dateEndDate,
                })

                this.calendar.unselect();
            },
            eventClick: async info => {
                const event = info.event;

                /** @type {string} */
                const scope = event.extendedProps.scope;
                /** @type {string} */
                const recordId = event.extendedProps.recordId;

                const helper = new RecordModal();

                /** @type {import('views/modals/detail').default} */
                let modalView;

                modalView = await helper.showDetail(this, {
                    entityType: scope,
                    id: recordId,
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

                        this.updateModel(model);
                    },
                    afterDestroy: model => {
                        this.removeModel(model);
                    },
                });
            },
            datesSet: () => {
                this.date = this.dateToMoment(this.calendar.getDate()).format('YYYY-MM-DD');

                this.trigger('view', this.date, this.mode);
            },
            events: (info, callback) => {
                const dateTimeFormat = this.getDateTime().internalDateTimeFormat;

                const from = moment.tz(info.startStr, info.timeZone);
                const to = moment.tz(info.endStr, info.timeZone);

                const fromStr = from.utc().format(dateTimeFormat);
                const toStr = to.utc().format(dateTimeFormat);

                this.fetchEvents(fromStr, toStr, callback);
            },
            eventDrop: async info => {
                const event = /** @type {EventImpl} */info.event;
                const delta = info.delta;

                const scope = event.extendedProps.scope;

                if (!event.allDay && event.extendedProps.allDayCopy) {
                    info.revert();

                    return;
                }

                if (event.allDay && !event.extendedProps.allDayCopy) {
                    info.revert();

                    return;
                }

                const start = event.start;
                const end = event.end;

                const dateStart = event.extendedProps.dateStart;
                const dateEnd = event.extendedProps.dateEnd;
                const dateStartDate = event.extendedProps.dateStartDate;
                const dateEndDate = event.extendedProps.dateEndDate;

                const attributes = {};

                if (dateStart) {
                    const dateString = this.getDateTime()
                        .toMoment(dateStart)
                        .add(delta)
                        .format(this.getDateTime().internalDateTimeFormat);

                    attributes.dateStart = this.convertDateTime(dateString);
                }

                if (dateEnd) {
                    const dateString = this.getDateTime()
                        .toMoment(dateEnd)
                        .add(delta)
                        .format(this.getDateTime().internalDateTimeFormat);

                    attributes.dateEnd = this.convertDateTime(dateString);
                }

                if (dateStartDate) {
                    const m = this.dateToMoment(dateStartDate).add(delta);

                    attributes.dateStartDate = m.format(this.getDateTime().internalDateFormat);
                }

                if (dateEndDate) {
                    const m = this.dateToMoment(dateEndDate).add(delta);

                    attributes.dateEndDate = m.format(this.getDateTime().internalDateFormat);
                }

                const props = this.obtainPropsFromEvent(event);

                if (!end && !this.allDayScopeList.includes(scope)) {
                    props.end = moment.tz(start.toISOString(), null, this.getDateTime().timeZone)
                        .clone()
                        .add(event.extendedProps.duration, 's')
                        .toDate();
                }

                props.allDay = false;

                props.dateStart = attributes.dateStart;
                props.dateEnd = attributes.dateEnd;
                props.dateStartDate = attributes.dateStartDate;
                props.dateEndDate = attributes.dateEndDate;

                this.handleAllDay(props, true);
                this.fillColor(props);

                Espo.Ui.notify(this.translate('saving', 'messages'));

                const model = await this.getModelFactory().create(scope);
                model.id = props.recordId;

                if (this.options.onSave) {
                    this.options.onSave();
                }

                try {
                    await model.save(attributes, {patch: true});
                } catch (e) {
                    info.revert();

                    return;
                }

                Espo.Ui.notify();

                this.applyPropsToEvent(event, props);
            },
            eventResize: async info => {
                const event = info.event;

                const attributes = {
                    dateEnd: this.convertDateTime(event.endStr),
                };

                const duration = moment(event.end).unix() - moment(event.start).unix();

                Espo.Ui.notify(this.translate('saving', 'messages'));

                const model = await this.getModelFactory().create(event.extendedProps.scope);
                model.id = event.extendedProps.recordId;

                if (this.options.onSave) {
                    this.options.onSave();
                }

                try {
                    await model.save(attributes, {patch: true})
                } catch (e) {
                    info.revert();

                    return;
                }

                Espo.Ui.notify();

                event.setExtendedProp('dateEnd', attributes.dateEnd);
                event.setExtendedProp('duration', duration);
            },
            eventAllow: (info, event) => {
                if (event.allDay && !info.allDay) {
                    return false;
                }

                if (!event.allDay && info.allDay) {
                    return false;
                }

                return true;
            }
        };

        if (this.teamIdList) {
            options.eventContent = arg => {
                const event = /** @type {EventImpl} */arg.event;

                const $content = $('<div>');

                $content.append(
                    $('<div>')
                        .append(
                            $('<div>')
                                .addClass('fc-event-main-frame')
                                .append(
                                    arg.timeText ?
                                        $('<div>').addClass('fc-event-time').text(arg.timeText) :
                                        undefined
                                )
                                .append(
                                    $('<div>').addClass('fc-event-title').text(event.title)
                                )
                        )
                );

                const userIdList = event.extendedProps.userIdList || [];

                userIdList.forEach(userId => {
                    const userName = event.extendedProps.userNameMap[userId] || '';
                    let avatarHtml = this.getHelper().getAvatarHtml(userId, 'small', 13);

                    if (avatarHtml) {
                        avatarHtml += ' ';
                    }

                    const $div = $('<div>')
                        .addClass('user')
                        .css({overflow: 'hidden'})
                        .append(avatarHtml)
                        .append(
                            $('<span>').text(userName)
                        );

                    $content.append($div);
                });

                return {html: $content.get(0).innerHTML};
            };
        }

        if (!this.options.height) {
            options.contentHeight = this.getCalculatedHeight();
        } else {
            options.aspectRatio = 1.62;
        }

        if (this.date) {
            options.initialDate = this.date;
        } else {
            this.$el.find('button[data-action="today"]').addClass('active');
        }

        setTimeout(() => {
            this.calendar = new FullCalendar.Calendar(this.$calendar.get(0), options);

            this.calendar.render();

            this.handleScrollToNow();
            this.updateDate();

            if (this.$container && this.$container.length) {
                this.adjustSize();
            }
        }, 150);
    }

    /**
     * @private
     */
    handleScrollToNow() {
        if (!(this.mode === 'agendaWeek' || this.mode === 'agendaDay')) {
            return;
        }

        if (!this.isToday()) {
            return;
        }

        const scrollHour = this.getDateTime().getNowMoment().hours() -
            Math.floor(this.slotDuration * this.scrollToNowSlots / 60);

        if (scrollHour < 0) {
            return;
        }

        this.calendar.scrollToTime(scrollHour + ':00');
    }

    /**
     * @param {{
     *   [allDay]: boolean,
     *   [dateStart]: string,
     *   [dateEnd]: string,
     *   [dateStartDate]: ?string,
     *   [dateEndDate]: ?string,
     * }} [values]
     */
    async createEvent(values) {
        values = values || {};

        if (
            !values.dateStart &&
            this.date !== this.getDateTime().getToday() &&
            (this.mode === 'day' || this.mode === 'agendaDay')
        ) {
            values.allDay = true;
            values.dateStartDate = this.date;
            values.dateEndDate = this.date;
        }

        const attributes = {};

        if (this.options.userId) {
            attributes.assignedUserId = this.options.userId;
            attributes.assignedUserName = this.options.userName || this.options.userId;
        }

        Espo.Ui.notifyWait();

        const view = await this.createView('dialog', 'crm:views/calendar/modals/edit', {
            attributes: attributes,
            enabledScopeList: this.enabledScopeList,
            scopeList: this.scopeList,
            allDay: values.allDay,
            dateStartDate: values.dateStartDate,
            dateEndDate: values.dateEndDate,
            dateStart: values.dateStart,
            dateEnd: values.dateEnd,
        });

        let added = false;

        this.listenTo(view, 'before:save', () => {
            if (this.options.onSave) {
                this.options.onSave();
            }
        });

        this.listenTo(view, 'after:save', model => {
            if (!added) {
                this.addModel(model);
                added = true;

                return;
            }

            this.updateModel(model);
        });

        await view.render();

        Espo.Ui.notify();
    }

    /**
     * @private
     * @param {string} from
     * @param {string} to
     * @param {function} callback
     */
    fetchEvents(from, to, callback) {
        let url = `Activities?from=${from}&to=${to}`;

        if (this.options.userId) {
            url += '&userId=' + this.options.userId;
        }

        url += '&scopeList=' + encodeURIComponent(this.enabledScopeList.join(','));

        if (this.teamIdList && this.teamIdList.length) {
            url += '&teamIdList=' + encodeURIComponent(this.teamIdList.join(','));
        }

        const agenda = this.mode === 'agendaWeek' || this.mode === 'agendaDay';

        url += '&agenda=' + encodeURIComponent(agenda);

        if (!this.suppressLoadingAlert) {
            Espo.Ui.notifyWait();
        }

        Espo.Ajax.getRequest(url).then(data => {
            const events = this.convertToFcEvents(data);

            callback(events);

            Espo.Ui.notify(false);
        });

        this.fetching = true;
        this.suppressLoadingAlert = false;

        setTimeout(() => this.fetching = false, 50)
    }

    /**
     * @private
     * @param {import('model').default} model
     */
    addModel(model) {
        const attributes = model.getClonedAttributes();

        attributes.scope = model.entityType;

        const event = this.convertToFcEvent(attributes);

        // true passed to prevent duplicates after re-fetch.
        this.calendar.addEvent(event, true);
    }

    /**
     * @private
     * @param {import('model').default} model
     */
    updateModel(model) {
        const eventId = model.entityType + '-' + model.id;

        const event = this.calendar.getEventById(eventId);

        if (!event) {
            return;
        }

        const attributes = model.getClonedAttributes();

        attributes.scope = model.entityType;

        const data = this.convertToFcEvent(attributes);

        this.applyPropsToEvent(event, data);
    }

    /**
     * @private
     * @param {EventImpl} event
     * @return {module:modules/crm/views/calendar/calendar~FcEvent}
     */
    obtainPropsFromEvent(event) {
        const props = {};

        for (const key in event.extendedProps) {
            props[key] = event.extendedProps[key];
        }

        props.allDay = event.allDay;
        props.start = event.start;
        props.end = event.end;
        props.title = event.title;
        props.id = event.id;
        props.color = event.color;

        return props;
    }

    /**
     * @private
     * @param {EventImpl} event
     * @param {{start?: Date, end?: Date, allDay: boolean} & Record} props
     */
    applyPropsToEvent(event, props) {
        if ('start' in props) {
            if (
                !props.allDay &&
                props.end &&
                props.end.getTime() === props.start.getTime()
            ) {
                // Otherwise, 0-duration event would disappear.
                props.end = moment(props.end).add(1, 'hour').toDate();
            }

            event.setDates(props.start, props.end, {allDay: props.allDay});
        }

        for (const key in props) {
            const value = props[key];

            if (
                key === 'start' ||
                key === 'end' ||
                key === 'allDay'
            ) {
                continue;
            }

            if (key === 'className') {
                event.setProp('classNames', value);

                continue;
            }

            if (this.extendedProps.includes(key)) {
                event.setExtendedProp(key, value);

                continue;
            }

            event.setProp(key, value);
        }
    }

    /**
     * @private
     * @param {import('model').default} model
     */
    removeModel(model) {
        const event = this.calendar.getEventById(model.entityType + '-' + model.id);

        if (!event) {
            return;
        }

        event.remove();
    }

    /**
     * @param {{suppressLoadingAlert: boolean}} [options]
     */
    actionRefresh(options) {
        if (options && options.suppressLoadingAlert) {
            this.suppressLoadingAlert = true;
        }

        this.calendar.refetchEvents();
    }

    actionPrevious() {
        this.calendar.prev();

        this.handleScrollToNow();
        this.updateDate();
    }

    actionNext() {
        this.calendar.next();

        this.handleScrollToNow();
        this.updateDate();
    }

    /**
     * @private
     * @param {string} scope
     * @return {string|undefined}
     */
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

    actionToday() {
        if (this.isToday()) {
            this.actionRefresh();

            return;
        }

        this.calendar.today();

        this.handleScrollToNow();
        this.updateDate();
    }
}

export default CalendarView;
