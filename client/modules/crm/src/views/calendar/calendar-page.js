/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

class CalendarPage extends View {

    template = 'crm:calendar/calendar-page'

    el = '#main'

    fullCalendarModeList = [
        'month',
        'agendaWeek',
        'agendaDay',
        'basicWeek',
        'basicDay',
        'listWeek',
    ]

    events = {
        /** @this CalendarPage */
        'click [data-action="createCustomView"]': function () {
            this.createCustomView();
        },
        /** @this CalendarPage */
        'click [data-action="editCustomView"]': function () {
            this.editCustomView();
        },
    }

    /**
     * A shortcut-key => action map.
     *
     * @protected
     * @type {?Object.<string,function (JQueryKeyEventObject): void>}
     */
    shortcutKeys = {
        /** @this CalendarPage */
        'Home': function (e) {
            this.handleShortcutKeyHome(e);
        },
        /** @this CalendarPage */
        'Numpad7': function (e) {
            this.handleShortcutKeyHome(e);
        },
        /** @this CalendarPage */
        'Numpad4': function (e) {
            this.handleShortcutKeyArrowLeft(e);
        },
        /** @this CalendarPage */
        'Numpad6': function (e) {
            this.handleShortcutKeyArrowRight(e);
        },
        /** @this CalendarPage */
        'ArrowLeft': function (e) {
            this.handleShortcutKeyArrowLeft(e);
        },
        /** @this CalendarPage */
        'ArrowRight': function (e) {
            this.handleShortcutKeyArrowRight(e);
        },
        /** @this CalendarPage */
        'Minus': function (e) {
            this.handleShortcutKeyMinus(e);
        },
        /** @this CalendarPage */
        'Equal': function (e) {
            this.handleShortcutKeyPlus(e);
        },
        /** @this CalendarPage */
        'NumpadSubtract': function (e) {
            this.handleShortcutKeyMinus(e);
        },
        /** @this CalendarPage */
        'NumpadAdd': function (e) {
            this.handleShortcutKeyPlus(e);
        },
        /** @this CalendarPage */
        'Digit1': function (e) {
            this.handleShortcutKeyDigit(e, 1);
        },
        /** @this CalendarPage */
        'Digit2': function (e) {
            this.handleShortcutKeyDigit(e, 2);
        },
        /** @this CalendarPage */
        'Digit3': function (e) {
            this.handleShortcutKeyDigit(e, 3);
        },
        /** @this CalendarPage */
        'Digit4': function (e) {
            this.handleShortcutKeyDigit(e, 4);
        },
        /** @this CalendarPage */
        'Digit5': function (e) {
            this.handleShortcutKeyDigit(e, 5);
        },
        /** @this CalendarPage */
        'Digit6': function (e) {
            this.handleShortcutKeyDigit(e, 6);
        },
        /** @this CalendarPage */
        'Control+Space': function (e) {
            this.handleShortcutKeyControlSpace(e);
        },
    }

    setup() {
        this.mode = this.mode || this.options.mode || null;
        this.date = this.date || this.options.date || null;

          if (!this.mode) {
            this.mode = this.getStorage().get('state', 'calendarMode') || null;

            if (this.mode && this.mode.indexOf('view-') === 0) {
                let viewId = this.mode.slice(5);
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
                this.shortcutKeys[key].call(this, e.originalEvent);
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
    }

    afterRender() {
        this.$el.focus();
    }

    updateUrl(trigger) {
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
                url += '&userName=' + encodeURIComponent(this.options.userName);
            }
        }

        this.getRouter().navigate(url, {trigger: trigger});
    }

    setupCalendar() {
        let viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'calendarView']) ||
            'crm:views/calendar/calendar';

        this.createView('calendar', viewName, {
            date: this.date,
            userId: this.options.userId,
            userName: this.options.userName,
            mode: this.mode,
            fullSelector: '#main > .calendar-container',
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
    }

    setupTimeline() {
        var viewName = this.getMetadata().get(['clientDefs', 'Calendar', 'timelineView']) ||
            'crm:views/calendar/timeline';

        this.createView('calendar', viewName, {
            date: this.date,
            userId: this.options.userId,
            userName: this.options.userName,
            fullSelector: '#main > .calendar-container',
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

            this.listenTo(view, 'change:mode', (mode) => {
                this.mode = mode;

                if (!this.options.userId) {
                    this.getStorage().set('state', 'calendarMode', mode);
                }

                this.updateUrl(true);
            });
        });
    }

    updatePageTitle() {
        this.setPageTitle(this.translate('Calendar', 'scopeNames'));
    }

    createCustomView() {
        this.createView('createCustomView', 'crm:views/calendar/modals/edit-view', {}, (view) => {
            view.render();

            this.listenToOnce(view, 'after:save', (data) => {
                view.close();
                this.mode = 'view-' + data.id;
                this.date = null;

                this.updateUrl(true);
            });
        });
    }

    editCustomView() {
        let viewId = this.getCalendarView().viewId;

        if (!viewId) {
            return;
        }

        this.createView('createCustomView', 'crm:views/calendar/modals/edit-view', {
            id: viewId
        }, (view) => {
            view.render();

            this.listenToOnce(view, 'after:save', () => {
                view.close();

                let calendarView = this.getCalendarView();

                calendarView.setupMode();
                calendarView.reRender();
            });

            this.listenToOnce(view, 'after:remove', () => {
                view.close();

                this.mode = null;
                this.date = null;

                this.updateUrl(true);
            });
        });
    }

    /**
     * @private
     * @return {module:modules/crm/views/calendar/calendar | module:modules/crm/views/calendar/timeline}
     */
    getCalendarView() {
        return this.getView('calendar');
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyHome(e) {
        e.preventDefault();

        this.getCalendarView().actionToday();
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyArrowLeft(e) {
        e.preventDefault();

        this.getCalendarView().actionPrevious();
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyArrowRight(e) {
        e.preventDefault();

        this.getCalendarView().actionNext();
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyMinus(e) {
        if (!this.getCalendarView().actionZoomOut) {
            return;
        }

        e.preventDefault();

        this.getCalendarView().actionZoomOut();
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyPlus(e) {
        if (!this.getCalendarView().actionZoomIn) {
            return;
        }

        e.preventDefault();

        this.getCalendarView().actionZoomIn();
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     * @param {Number} digit
     */
    handleShortcutKeyDigit(e, digit) {
        let modeList = this.getCalendarView().hasView('modeButtons') ?
            this.getCalendarView()
                .getModeButtonsView()
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
    }

    /**
     * @private
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyControlSpace(e) {
        if (!this.getCalendarView().createEvent) {
            return;
        }

        e.preventDefault();

        this.getCalendarView().createEvent();
    }
}

// noinspection JSUnusedGlobalSymbols
export default CalendarPage;
