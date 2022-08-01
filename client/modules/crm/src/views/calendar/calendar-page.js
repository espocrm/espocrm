/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/calendar/calendar-page', ['view'], function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/calendar-page',

        el: '#main',

        fullCalendarModeList: [
            'month',
            'agendaWeek',
            'agendaDay',
            'basicWeek',
            'basicDay',
            'listWeek',
        ],

        events: {
            'click [data-action="createCustomView"]': function () {
                this.createCustomView();
            },
            'click [data-action="editCustomView"]': function () {
                this.editCustomView();
            }
        },

        /**
         * A shortcut-key => action map.
         *
         * @protected
         * @type {?Object.<string,function (JQueryKeyEventObject): void>}
         */
        shortcutKeys: {
            'Home': function (e) {
                this.handleShortcutKeyHome(e);
            },
            'Numpad7': function (e) {
                this.handleShortcutKeyHome(e);
            },
            'Numpad4': function (e) {
                this.handleShortcutKeyArrowLeft(e);
            },
            'Numpad6': function (e) {
                this.handleShortcutKeyArrowRight(e);
            },
            'ArrowLeft': function (e) {
                this.handleShortcutKeyArrowLeft(e);
            },
            'ArrowRight': function (e) {
                this.handleShortcutKeyArrowRight(e);
            },
            'Minus': function (e) {
                this.handleShortcutKeyMinus(e);
            },
            'Equal': function (e) {
                this.handleShortcutKeyPlus(e);
            },
            'NumpadSubtract': function (e) {
                this.handleShortcutKeyMinus(e);
            },
            'NumpadAdd': function (e) {
                this.handleShortcutKeyPlus(e);
            },
            'Digit1': function (e) {
                this.handleShortcutKeyDigit(e, 1);
            },
            'Digit2': function (e) {
                this.handleShortcutKeyDigit(e, 2);
            },
            'Digit3': function (e) {
                this.handleShortcutKeyDigit(e, 3);
            },
            'Digit4': function (e) {
                this.handleShortcutKeyDigit(e, 4);
            },
            'Digit5': function (e) {
                this.handleShortcutKeyDigit(e, 5);
            },
            'Digit6': function (e) {
                this.handleShortcutKeyDigit(e, 6);
            },
            'Control+Space': function (e) {
                this.handleShortcutKeyControlSpace(e);
            },
        },

        setup: function () {
            this.mode = this.mode || this.options.mode || null;
            this.date = this.date || this.options.date || null;

              if (!this.mode) {
                this.mode = this.getStorage().get('state', 'calendarMode') || null;

                if (this.mode && this.mode.indexOf('view-') === 0) {
                    let viewId = this.mode.substr(5);
                    let calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];
                    let isFound = false;

                    calendarViewDataList.forEach(item => {
                        if (item.id === viewId) {
                            isFound = true;
                        }
                    });

                    if (!isFound) {
                        this.mode = null;
                    }

                    if (this.options.userId) {
                        this.mode = null;
                    }
                }
            }

            this.events['keydown.main'] = e => {
                let key = Espo.Utils.getKeyFromKeyEvent(e);

                if (typeof this.shortcutKeys[key] === 'function') {
                    this.shortcutKeys[key].call(this, e);
                }
            }

            if (!this.mode || ~this.fullCalendarModeList.indexOf(this.mode) || this.mode.indexOf('view-') === 0) {
                this.setupCalendar();
            }
            else {
                if (this.mode === 'timeline') {
                    this.setupTimeline();
                }
            }
        },

        afterRender: function () {
            this.$el.focus();
        },

        updateUrl: function (trigger) {
            let url = '#Calendar/show';

            if (this.mode || this.date) {
                url += '/';
            }

            if (this.mode) {
                url += 'mode=' + this.mode;
            }

            if (this.date) {
                url += '&date=' + this.date;
            }

            if (this.options.userId) {
                url += '&userId=' + this.options.userId;

                if (this.options.userName) {
                    url += '&userName=' + this.getHelper().escapeString(this.options.userName).replace(/\\|\//g,'');
                }
            }

            this.getRouter().navigate(url, {trigger: trigger});
        },

        setupCalendar: function () {
            let viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) ||
                'crm:views/calendar/calendar';

            this.createView('calendar', viewName, {
                date: this.date,
                userId: this.options.userId,
                userName: this.options.userName,
                mode: this.mode,
                el: '#main > .calendar-container',
            }, view => {
                let initial = true;

                this.listenTo(view, 'view', (date, mode) => {
                    this.date = date;
                    this.mode = mode;

                    if (!initial) {
                        this.updateUrl();
                    }

                    initial = false;
                });

                this.listenTo(view, 'change:mode', (mode, refresh) => {
                    this.mode = mode;

                    if (!this.options.userId) {
                        this.getStorage().set('state', 'calendarMode', mode);
                    }

                    if (refresh) {
                        this.updateUrl(true);

                        return;
                    }

                    if (!~this.fullCalendarModeList.indexOf(mode)) {
                        this.updateUrl(true);
                    }

                    this.$el.focus();
                });
            });
        },

        setupTimeline: function () {
            var viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) ||
                'crm:views/calendar/timeline';

            this.createView('calendar', viewName, {
                date: this.date,
                userId: this.options.userId,
                userName: this.options.userName,
                el: '#main > .calendar-container',
            }, (view) => {
                this.listenTo(view, 'view', (date, mode) => {
                    this.date = date;
                    this.mode = mode;

                    this.updateUrl();
                });

                this.listenTo(view, 'change:mode', (mode) => {
                    this.mode = mode;

                    if (!this.options.userId) {
                        this.getStorage().set('state', 'calendarMode', mode);
                    }

                    this.updateUrl(true);
                });
            });
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('Calendar', 'scopeNames'));
        },

        createCustomView: function () {
            this.createView('createCustomView', 'crm:views/calendar/modals/edit-view', {}, (view) => {
                view.render();

                this.listenToOnce(view, 'after:save', (data) => {
                    view.close();
                    this.mode = 'view-' + data.id;
                    this.date = null;

                    this.updateUrl(true);
                });
            });
        },

        editCustomView: function () {
            let viewId = this.getView('calendar').viewId;

            if (!viewId) {
                return;
            }

            this.createView('createCustomView', 'crm:views/calendar/modals/edit-view', {
                id: viewId
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'after:save', (data) => {
                    view.close();

                    let calendarView = this.getView('calendar');
                    calendarView.setupMode();
                    calendarView.reRender();
                });

                this.listenToOnce(view, 'after:remove', (data) => {
                    view.close();

                    this.mode = null;
                    this.date = null;

                    this.updateUrl(true);
                });
            });
        },

        /**
         * @private
         * @return {module:view.Class}
         */
        getCalendarView: function () {
            return this.getView('calendar');
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyHome: function (e) {
            e.preventDefault();

            this.getCalendarView().actionToday();
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyArrowLeft: function (e) {
            e.preventDefault();

            this.getCalendarView().actionPrevious();
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyArrowRight: function (e) {
            e.preventDefault();

            this.getCalendarView().actionNext();
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyMinus: function (e) {
            if (!this.getCalendarView().actionZoomOut) {
                return;
            }

            e.preventDefault();

            this.getCalendarView().actionZoomOut();
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyPlus: function (e) {
            if (!this.getCalendarView().actionZoomIn) {
                return;
            }

            e.preventDefault();

            this.getCalendarView().actionZoomIn();
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         * @param {Number} digit
         */
        handleShortcutKeyDigit: function (e, digit) {
            let modeList = this.getCalendarView().hasView('modeButtons') ?
                this.getCalendarView()
                    .getView('modeButtons')
                    .getModeDataList(true)
                    .map(item => item.mode) :
                this.getCalendarView().modeList;

            let mode = modeList[digit - 1];

            if (!mode) {
                return;
            }

            e.preventDefault();

            if (mode === this.mode) {
                this.getCalendarView().actionRefresh();

                return;
            }

            this.getCalendarView().selectMode(mode);
        },

        /**
         * @private
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyControlSpace: function (e) {
            if (!this.getCalendarView().createEvent) {
                return;
            }

            e.preventDefault();

            this.getCalendarView().createEvent();
        },
    });
});
