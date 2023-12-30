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

/** @module modules/crm/views/calendar/mode-buttons */

define('crm:views/calendar/mode-buttons', ['view'], function (Dep) {

    return Dep.extend({

        template: 'crm:calendar/mode-buttons',

        visibleModeListCount: 3,

        data: function () {
            let scopeFilterList = Espo.Utils.clone(this.scopeList);
            scopeFilterList.unshift('all');

            var scopeFilterDataList = [];

            this.scopeList.forEach(scope => {
                let o = {scope: scope};

                if (!~this.getParentView().enabledScopeList.indexOf(scope)) {
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
        },

        setup: function () {
            this.isCustomViewAvailable = this.options.isCustomViewAvailable;
            this.modeList = this.options.modeList;
            this.scopeList = this.options.scopeList;
            this.mode = this.options.mode;
        },

        /**
         * @param {boolean} originalOrder
         * @return {Object.<string, *>[]}
         */
        getModeDataList: function (originalOrder) {
            var list = [];

            this.modeList.forEach(name => {
                var o = {
                    mode: name,
                    label: this.translate(name, 'modes', 'Calendar'),
                    labelShort: this.translate(name, 'modes', 'Calendar').substr(0, 2),
                };

                list.push(o);
            });

            if (this.isCustomViewAvailable) {
                (this.getPreferences().get('calendarViewDataList') || []).forEach(item => {
                    item = Espo.Utils.clone(item);

                    item.mode = 'view-' + item.id;
                    item.label = item.name;
                    item.labelShort = (item.name || '').substr(0, 2);
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

            if (currentIndex >= this.visibleModeListCount) {
                let tmp = list[this.visibleModeListCount - 1];

                list[this.visibleModeListCount - 1] = list[currentIndex];
                list[currentIndex] = tmp;
            }

            return list;
        },

        getVisibleModeDataList: function () {
            var fullList =  this.getModeDataList();

            var list = [];

            fullList.forEach((o, i) => {
                if (i >= this.visibleModeListCount) {
                    return;
                }

                list.push(o);
            });

            return list;
        },

        getHiddenModeDataList: function () {
            var fullList =  this.getModeDataList();

            var list = [];

            fullList.forEach((o, i) => {
                if (i < this.visibleModeListCount) {
                    return;
                }

                list.push(o);
            });

            return list;
        },
    });
});
