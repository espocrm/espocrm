define("modules/crm/views/scheduler/scheduler", ["exports", "view", "vis-data", "vis-timeline", "moment", "jquery"], function (_exports, _view, _visData, _visTimeline, _moment, _jquery) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _moment = _interopRequireDefault(_moment);
  _jquery = _interopRequireDefault(_jquery);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class SchedulerView extends _view.default {
    // language=Handlebars
    templateContent = `
        <div class="timeline"></div>
        <link href="{{basePath}}client/modules/crm/css/vis.css" rel="stylesheet">
    `;
    rangeMarginThreshold = 12 * 3600;
    leftMargin = 24 * 3600;
    rightMargin = 48 * 3600;
    rangeMultiplierLeft = 3;
    rangeMultiplierRight = 3;
    setup() {
      this.startField = this.options.startField || 'dateStart';
      this.endField = this.options.endField || 'dateEnd';
      this.assignedUserField = this.options.assignedUserField || 'assignedUser';
      this.startDateField = this.startField + 'Date';
      this.endDateField = this.endField + 'Date';
      this.colors = Espo.Utils.clone(this.getMetadata().get('clientDefs.Calendar.colors') || {});
      this.colors = {
        ...this.colors,
        ...this.getHelper().themeManager.getParam('calendarColors')
      };
      let usersFieldDefault = 'users';
      if (!this.model.hasLink('users') && this.model.hasLink('assignedUsers')) {
        usersFieldDefault = 'assignedUsers';
      }
      this.eventAssignedUserIsAttendeeDisabled = this.getConfig().get('eventAssignedUserIsAttendeeDisabled') || false;
      this.usersField = this.options.usersField || usersFieldDefault;
      this.userIdList = [];
      this.listenTo(this.model, 'change', m => {
        let isChanged = m.hasChanged('isAllDay') || m.hasChanged(this.startField) || m.hasChanged(this.endField) || m.hasChanged(this.endDateField) || m.hasChanged(this.usersField + 'Ids') || !this.eventAssignedUserIsAttendeeDisabled && m.hasChanged(this.assignedUserField + 'Id');
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
            this.timeline.setWindow(this.start.toDate(), this.end.toDate());
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
      this.$timeline.append((0, _jquery.default)('<div>').addClass('revert-margin').text(this.translate('No Data')));
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
        let itemsDataSet = new _visData.DataSet(eventList);

        // noinspection SpellCheckingInspection
        this.timeline = new _visTimeline.Timeline($timeline.get(0), itemsDataSet, this.groupsDataSet, {
          dataAttributes: 'all',
          start: this.start.toDate(),
          end: this.end.toDate(),
          rollingMode: {
            follow: false // fixes slow render
          },
          moment: date => {
            let m = (0, _moment.default)(date);
            if (date && date.noTimeZone) {
              return m;
            }
            return m.tz(this.getDateTime().getTimeZone());
          },
          xss: {
            filterOptions: {
              onTag: (tag, html) => html
            }
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
            remove: false
          },
          showCurrentTime: true,
          locales: {
            myLocale: {
              current: this.translate('current', 'labels', 'Calendar'),
              time: this.translate('time', 'labels', 'Calendar')
            }
          },
          locale: 'myLocale',
          margin: {
            item: {
              vertical: 12
            },
            axis: 6
          }
        });
        $timeline.css('min-height', '');

        // noinspection SpellCheckingInspection
        this.timeline.on('rangechanged', e => {
          e.skipClick = true;
          this.blockClick = true;
          setTimeout(function () {
            this.blockClick = false;
          }.bind(this), 100);
          this.start = (0, _moment.default)(e.start);
          this.end = (0, _moment.default)(e.end);
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
      let itemsDataSet = new _visData.DataSet(convertedEventList);
      this.timeline.setItems(itemsDataSet);
    }
    updateRange() {
      if (this.start.unix() < this.fetchedStart.unix() + this.rangeMarginThreshold || this.end.unix() > this.fetchedEnd.unix() - this.rangeMarginThreshold) {
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
        this.eventStart = _moment.default.tz(startS, this.getDateTime().getTimeZone());
        this.eventEnd = _moment.default.tz(endS, this.getDateTime().getTimeZone());
        this.eventEnd.add(1, 'day');
      } else {
        this.eventStart = _moment.default.utc(startS).tz(this.getDateTime().getTimeZone());
        this.eventEnd = _moment.default.utc(endS).tz(this.getDateTime().getTimeZone());
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
        let itemsDataSet = new _visData.DataSet(eventList);
        this.timeline.setItems(itemsDataSet);
      });
    }
    fetch(from, to, callback) {
      from = from.clone().add(-1 * this.leftMargin, 'seconds');
      to = to.clone().add(this.rightMargin, 'seconds');
      let fromString = from.utc().format(this.getDateTime().internalDateTimeFormat);
      let toString = to.utc().format(this.getDateTime().internalDateTimeFormat);
      let url = 'Timeline/busyRanges?from=' + fromString + '&to=' + toString + '&userIdList=' + encodeURIComponent(this.userIdList.join(',')) + '&entityType=' + this.model.entityType;
      if (this.model.id) {
        url += '&entityId=' + this.model.id;
      }
      Espo.Ajax.getRequest(url).then(data => {
        this.fetchedStart = from.clone();
        this.fetchedEnd = to.clone();
        let eventList = [];
        for (let userId in data) {
          let itemList = /** @type {Object.<string, *>} */data[userId].filter(item => !item.isBusyRange).concat(data[userId].filter(item => item.isBusyRange));
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
        className: 'event-range'
      };
      let color = this.getColorFromScopeName(this.model.entityType);
      if (color) {
        o.style += '; border-color: ' + color;
        let rgb = this.hexToRgb(color);
        o.style += '; background-color: rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', 0.01)';
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
          type: 'background'
        };
      } else if (o.isWorkingRange) {
        event = {
          className: 'working',
          group: o.userId,
          'date-start': o.dateStart,
          'date-end': o.dateEnd,
          type: 'background'
        };
      } else if (o.isNonWorkingRange) {
        event = {
          className: 'non-working',
          group: o.userId,
          'date-start': o.dateStart,
          'date-end': o.dateEnd,
          type: 'background'
        };
        let color = this.colors['bg'];
        event.style = 'background-color:' + color + ';';
        event.style += 'border-color:' + color + ';';
      }
      if (o.dateStart) {
        if (!o.dateStartDate) {
          event.start = this.getDateTime().toMoment(o.dateStart);
        } else {
          event.start = _moment.default.tz(o.dateStartDate, this.getDateTime().getTimeZone());
        }
      }
      if (o.dateEnd) {
        if (!o.dateEndDate) {
          event.end = this.getDateTime().toMoment(o.dateEnd);
        } else {
          event.end = _moment.default.tz(o.dateEndDate, this.getDateTime().getTimeZone());
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
          order: i
        });
      });
      this.groupsDataSet = new _visData.DataSet(list);
    }
    getGroupContent(id, name) {
      if (this.calendarType === 'single') {
        return (0, _jquery.default)('<span>').text(name).get(0).outerHTML;
      }
      let avatarHtml = this.getAvatarHtml(id);
      if (avatarHtml) {
        avatarHtml += ' ';
      }
      return (0, _jquery.default)('<span>').append((0, _jquery.default)(avatarHtml), (0, _jquery.default)('<span>').attr('data-id', id).addClass('group-title').text(name)).get(0).innerHTML;
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
      return (0, _jquery.default)('<img>').addClass('avatar avatar-link').attr('width', '16').attr('src', this.getBasePath() + '?entryPoint=avatar&size=small&id=' + id + '&t=' + t).get(0).outerHTML;
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
      return this.getMetadata().get(['clientDefs', scope, 'color']) || this.getMetadata().get(['clientDefs', 'Calendar', 'colors', scope]);
    }
    hexToRgb(hex) {
      let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
      } : null;
    }
  }
  var _default = _exports.default = SchedulerView;
});

define("modules/crm/views/calendar/timeline", ["exports", "view", "vis-data", "vis-timeline", "moment", "jquery", "helpers/record-modal", "crm:views/calendar/modals/shared-options"], function (_exports, _view, _visData, _visTimeline, _moment, _jquery, _recordModal, _sharedOptions) {
  "use strict";

  Object.defineProperty(_exports, "__esModule", {
    value: true
  });
  _exports.default = void 0;
  _view = _interopRequireDefault(_view);
  _moment = _interopRequireDefault(_moment);
  _jquery = _interopRequireDefault(_jquery);
  _recordModal = _interopRequireDefault(_recordModal);
  _sharedOptions = _interopRequireDefault(_sharedOptions);
  function _interopRequireDefault(e) { return e && e.__esModule ? e : { default: e }; }
  /************************************************************************
   * This file is part of EspoCRM.
   *
   * EspoCRM – Open Source CRM application.
   * Copyright (C) 2014-2025 EspoCRM, Inc.
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

  class TimelineView extends _view.default {
    template = 'crm:calendar/timeline';
    eventAttributes = [];
    colors = {};

    /**
     * @private
     * @type {string[]}
     */
    allDayScopeList;

    /**
     * @private
     * @type {string[]}
     */
    scopeList = ['Meeting', 'Call', 'Task'];

    /**
     * @private
     * @type {string[]}
     */
    enabledScopeList;

    /**
     * @private
     * @type {string[]}
     */
    onlyDateScopeList;
    header = true;
    modeList = [];
    defaultMode = 'timeline';
    maxRange = 120;
    rangeMarginThreshold = 12 * 3600;
    leftMargin = 24 * 3600;
    rightMargin = 48 * 3600;
    calendarType = 'single';
    calendarTypeList = ['single', 'shared'];
    zoomPercentage = 1;

    /** @type {Timeline} */
    timeline;
    events = {
      /** @this TimelineView */
      'click button[data-action="today"]': function () {
        this.actionToday();
      },
      /** @this TimelineView */
      'click [data-action="mode"]': function (e) {
        const mode = (0, _jquery.default)(e.currentTarget).data('mode');
        this.selectMode(mode);
      },
      /** @this TimelineView */
      'click [data-action="refresh"]': function () {
        this.actionRefresh();
      },
      /** @this TimelineView */
      'click [data-action="toggleScopeFilter"]': function (e) {
        const $target = (0, _jquery.default)(e.currentTarget);
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
        const $target = (0, _jquery.default)(e.currentTarget);
        const calendarType = $target.data('name');
        $target.parent().parent().find('.calendar-type-check-icon').addClass('hidden');
        const $check = $target.find('.calendar-type-check-icon');
        if ($check.hasClass('hidden')) {
          $check.removeClass('hidden');
        }
        $target.closest('.calendar-type-button-group').find('.calendar-type-label').text(this.getCalendarTypeLabel(calendarType));
        const $showSharedCalendarOptions = this.$el.find('> .button-container button[data-action="showSharedCalendarOptions"]');
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
      }
    };

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
        isCustomViewAvailable: this.isCustomViewAvailable
      };
    }
    setup() {
      this.date = this.options.date || this.getDateTime().getToday();
      this.mode = this.options.mode || this.defaultMode;
      this.header = 'header' in this.options ? this.options.header : this.header;
      this.$container = this.options.$container;
      this.colors = Espo.Utils.clone(this.getMetadata().get('clientDefs.Calendar.colors') || this.colors || {});
      this.modeList = this.getMetadata().get('clientDefs.Calendar.modeList') || this.modeList || [];
      this.scopeList = this.getConfig().get('calendarEntityList') || Espo.Utils.clone(this.scopeList);
      this.allDayScopeList = this.getMetadata().get('clientDefs.Calendar.allDayScopeList') ?? [];
      this.scopeList.forEach(scope => {
        if (this.getMetadata().get(`scopes.${scope}.calendarOneDay`) && !this.allDayScopeList.includes(scope)) {
          this.allDayScopeList.push(scope);
        }
      });
      this.colors = {
        ...this.colors,
        ...this.getHelper().themeManager.getParam('calendarColors')
      };
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
      this.onlyDateScopeList = this.scopeList.filter(scope => {
        return this.getMetadata().get(`entityDefs.${scope}.fields.dateStart.type`) === 'date';
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
          mode: this.mode
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
        label: this.getCalendarTypeLabel('single')
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
          type: 'background'
        };
      } else if (o.isNonWorkingRange) {
        event = {
          className: 'non-working',
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
          color: o.color
        };
      }
      this.eventAttributes.forEach(attr => {
        event[attr] = o[attr];
      });
      if (o.dateStart || o.dateStartDate) {
        if (!o.dateStartDate) {
          event.start = this.getDateTime().toMoment(o.dateStart);
        } else {
          event.start = _moment.default.tz(o.dateStartDate, this.getDateTime().getTimeZone());
        }
      }
      if (o.dateEnd || o.dateEndDate) {
        if (!o.dateEndDate) {
          event.end = this.getDateTime().toMoment(o.dateEnd);
        } else {
          event.end = _moment.default.tz(o.dateEndDate, this.getDateTime().getTimeZone());
        }
      }
      if (o.dateStartDate && !this.allDayScopeList.includes(o.scope) && event.end) {
        event.end = event.end.clone().add(1, 'days');
      }
      if (o.isBusyRange) {
        return event;
      }
      if (this.allDayScopeList.includes(o.scope)) {
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
      if (event.status && (this.getEventTypeCompletedStatusList(event.scope).includes(event.status) || this.getEventTypeCanceledStatusList(event.scope).includes(event.status))) {
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
      return "#" + (0x1000000 + (Math.round((t - R) * p) + R) * 0x10000 + (Math.round((t - G) * p) + G) * 0x100 + (Math.round((t - B) * p) + B)).toString(16).slice(1) + alpha;
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
        this.$container = (0, _jquery.default)(this.options.containerSelector);
      }
      const $timeline = this.$timeline = this.$el.find('div.timeline');
      this.initUserList();
      this.initDates();
      this.initGroupsDataSet();
      this.fetchEvents(this.start, this.end, eventList => {
        const itemsDataSet = new _visData.DataSet(eventList);
        this.timeline = new _visTimeline.Timeline($timeline.get(0), itemsDataSet, this.groupsDataSet, {
          dataAttributes: 'all',
          start: this.start.toDate(),
          end: this.end.toDate(),
          rollingMode: {
            follow: false // fixes slow render
          },
          xss: {
            filterOptions: {
              onTag: (tag, html) => html
            }
          },
          moment: /** Record */date => {
            const m = (0, _moment.default)(date);
            if (date && date.noTimeZone) {
              return m;
            }

            // noinspection JSUnresolvedReference
            return m.tz(this.getDateTime().getTimeZone());
          },
          format: this.getFormatObject(),
          zoomMax: 24 * 3600 * 1000 * this.maxRange,
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
            myLocale: {
              current: this.translate('current', 'labels', 'Calendar'),
              time: this.translate('time', 'labels', 'Calendar')
            }
          },
          locale: 'myLocale',
          margin: {
            item: {
              vertical: 12
            },
            axis: 6
          }
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
            const dateStart = (0, _moment.default)(e.time).utc().format(this.getDateTime().internalDateTimeFormat);
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
          this.start = (0, _moment.default)(e.start);
          this.end = (0, _moment.default)(e.end);
          this.triggerView();
          if (this.start.unix() < this.fetchedStart.unix() + this.rangeMarginThreshold || this.end.unix() > this.fetchedEnd.unix() - this.rangeMarginThreshold) {
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
        const time = (this.timeline.getWindow().end - this.timeline.getWindow().start) / 2 + this.timeline.getWindow().start;
        dateStart = (0, _moment.default)(time).utc().format(this.getDateTime().internalDateTimeFormat);
        if (this.date === this.getDateTime().getToday()) {
          dateStart = (0, _moment.default)().utc().format(this.getDateTime().internalDateTimeFormat);
        }
      }
      const attributes = {
        dateStart: dateStart
      };
      if (userId) {
        let userName;
        this.userList.forEach(item => {
          if (item.id === userId) {
            userName = item.name;
          }
        });
        attributes.assignedUserId = userId;
        attributes.assignedUserName = userName || userId;
      }
      const scopeList = this.enabledScopeList.filter(it => !this.onlyDateScopeList.includes(it));
      Espo.Ui.notifyWait();
      const view = await this.createView('dialog', 'crm:views/calendar/modals/edit', {
        attributes: attributes,
        enabledScopeList: scopeList,
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
      const helper = new _recordModal.default();

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
        }
      });
    }
    runFetch() {
      this.fetchEvents(this.start, this.end, eventList => {
        const itemsDataSet = new _visData.DataSet(eventList);
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
            name: this.getUser().get('name')
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
          name: this.getUser().get('name')
        });
        return;
      }
      if (this.calendarType === 'shared') {
        this.getSharedCalenderUserList().forEach(item => {
          this.userList.push({
            id: item.id,
            name: item.name
          });
        });
      }
    }
    storeUserList() {
      this.getPreferences().save({
        'sharedCalendarUserList': Espo.Utils.clone(this.userList)
      }, {
        patch: true
      });
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
        name: this.getUser().get('name')
      }];
    }
    initDates() {
      if (this.date) {
        this.start = _moment.default.tz(this.date, this.getDateTime().getTimeZone());
      } else {
        this.start = _moment.default.tz(this.getDateTime().getTimeZone());
      }
      this.end = this.start.clone();
      this.end.add(1, 'day');
      this.fetchedStart = null;
      this.fetchedEnd = null;
    }
    initGroupsDataSet() {
      const list = [];
      this.userList.forEach((user, i) => {
        list.push({
          id: user.id,
          content: this.getGroupContent(user.id, user.name),
          order: i
        });
      });
      this.groupsDataSet = new _visData.DataSet(list);
    }
    getGroupContent(id, name) {
      if (this.calendarType === 'single') {
        return (0, _jquery.default)('<span>').text(name).get(0).outerHTML;
      }
      let avatarHtml = this.getAvatarHtml(id);
      if (avatarHtml) {
        avatarHtml += ' ';
      }
      return avatarHtml + (0, _jquery.default)('<span>').attr('data-id', id).addClass('group-title').text(name).get(0).outerHTML;
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
      return (0, _jquery.default)('<img>').addClass('avatar avatar-link').attr('width', '16').attr('src', this.getBasePath() + '?entryPoint=avatar&size=small&id=' + id + '&t=' + t).get(0).outerHTML;
    }
    fetchEvents(from, to, callback) {
      if (!this.options.suppressLoadingAlert) {
        Espo.Ui.notifyWait();
      }
      from = from.clone().add(-1 * this.leftMargin, 'seconds');
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
      const view = new _sharedOptions.default({
        users: this.userList,
        onApply: data => {
          this.userList = data.users;
          this.storeUserList();
          this.initGroupsDataSet();
          this.timeline.setGroups(this.groupsDataSet);
          this.runFetch();
        }
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
      this.timeline.moveTo((0, _moment.default)().toDate());
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
  var _default = _exports.default = TimelineView;
});

