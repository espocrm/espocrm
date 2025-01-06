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

/** @module modules/crm/views/calendar/mode-buttons */

class CalendarModeButtons extends View {

    template = 'crm:calendar/mode-buttons'

    visibleModeListCount = 3

    data() {
        const scopeFilterList = Espo.Utils.clone(this.scopeList);
        scopeFilterList.unshift('all');

        const scopeFilterDataList = [];

        this.scopeList.forEach(scope => {
            const o = {scope: scope};

            if (!this.getCalendarParentView().enabledScopeList.includes(scope)) {
                o.disabled = true;
            }

            scopeFilterDataList.push(o);
        });

        return {
            mode: this.mode,
            visibleModeDataList: this.getVisibleModeDataList(),
            hiddenModeDataList: this.getHiddenModeDataList(),
            scopeFilterDataList: scopeFilterDataList,
            isCustomViewAvailable: this.isCustomViewAvailable,
            hasMoreItems: this.isCustomViewAvailable,
            hasWorkingTimeCalendarLink: this.getAcl().checkScope('WorkingTimeCalendar'),
        };
    }

    /**
     * @return {
     *     import('modules/crm/views/calendar/calendar').default|
     *     import('modules/crm/views/calendar/timeline').default
     * }
     */
    getCalendarParentView() {
        // noinspection JSValidateTypes
        return this.getParentView();
    }

    setup() {
        this.isCustomViewAvailable = this.options.isCustomViewAvailable;
        this.modeList = this.options.modeList;
        this.scopeList = this.options.scopeList;
        this.mode = this.options.mode;
    }

    /**
     * @param {boolean} [originalOrder]
     * @return {Object.<string, *>[]}
     */
    getModeDataList(originalOrder) {
        const list = [];

        this.modeList.forEach(name => {
            const o = {
                mode: name,
                label: this.translate(name, 'modes', 'Calendar'),
                labelShort: this.translate(name, 'modes', 'Calendar').substring(0, 2),
            };

            list.push(o);
        });

        if (this.isCustomViewAvailable) {
            (this.getPreferences().get('calendarViewDataList') || []).forEach(item => {
                item = Espo.Utils.clone(item);

                item.mode = 'view-' + item.id;
                item.label = item.name;
                item.labelShort = (item.name || '').substring(0, 2);
                list.push(item);
            });
        }

        if (originalOrder) {
            return list;
        }

        let currentIndex = -1;

        list.forEach((item, i) => {
            if (item.mode === this.mode) {
                currentIndex = i;
            }
        });

        return list;
    }

    getVisibleModeDataList() {
        const fullList = this.getModeDataList();

        const current = fullList.find(it => it.mode === this.mode);

        const list = fullList.slice(0, this.visibleModeListCount);

        if (current && !list.find(it => it.mode === this.mode)) {
            list.push(current);
        }

        return list;
    }

    getHiddenModeDataList() {
        const fullList = this.getModeDataList();

        const list = [];

        fullList.forEach((o, i) => {
            if (i < this.visibleModeListCount) {
                return;
            }

            list.push(o);
        });

        return list;
    }
}

export default CalendarModeButtons;
