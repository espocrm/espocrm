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

import DatetimeOptionalFieldView from 'views/fields/datetime-optional';
import moment from 'moment';

class TaskDateEndFieldView extends DatetimeOptionalFieldView {

    isEnd = true

    getAttributeList() {
        return [
            ...super.getAttributeList(),
            'status',
        ];
    }

    data() {
        const data = super.data();

        const status = this.model.attributes.status;

        if (!status || this.notActualStatusList.includes(status)) {
            return data;
        }

        if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
            if (this.isDateInPast()) {
                data.isOverdue = true;
            } else if (this.isDateToday()) {
                data.style = 'warning';
            }
        }

        if (data.isOverdue) {
            data.style = 'danger';
        }

        return data;
    }

    setup() {
        super.setup();

        this.notActualStatusList = [
            ...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []),
            ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || []),
        ];

        if (this.isEditMode() || this.isDetailMode()) {
            this.on('change', () => {
                if (!this.model.get('dateEnd') && this.model.get('reminders')) {
                    this.model.set('reminders', []);
                }
            });
        }
    }

    /**
     * @private
     * @return {boolean}
     */
    isDateInPast() {
        if (this.isDate()) {
            const value = this.model.get(this.nameDate);

            if (value) {
                const d = moment.tz(value + ' 23:59', this.getDateTime().getTimeZone());
                const now = this.getDateTime().getNowMoment();

                if (d.unix() < now.unix()) {
                    return true;
                }
            }
        }

        const value = this.model.get(this.name);

        if (value) {
            const d = this.getDateTime().toMoment(value);
            const now = moment().tz(this.getDateTime().timeZone || 'UTC');

            if (d.unix() < now.unix()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @private
     * @return {boolean}
     */
    isDateToday() {
        if (!this.isDate()) {
            return false;
        }

        return this.getDateTime().getToday() === this.model.attributes[this.nameDate];
    }
}

export default TaskDateEndFieldView;
