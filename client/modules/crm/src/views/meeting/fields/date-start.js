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

class DateStartMeetingFieldView extends DatetimeOptionalFieldView {

    emptyTimeInInlineEditDisabled = true

    setup() {
        super.setup();

        this.noneOption = this.translate('All-Day', 'labels', 'Meeting');

        this.notActualStatusList = [
            ...(this.getMetadata().get(`scopes.${this.entityType}.completedStatusList`) || []),
            ...(this.getMetadata().get(`scopes.${this.entityType}.canceledStatusList`) || []),
        ];
    }

    getAttributeList() {
        return [
            ...super.getAttributeList(),
            'dateEnd',
            'dateEndDate',
            'status',
        ];
    }

    data() {
        let style;

        const status = this.model.get('status');

        if (
            status &&
            !this.notActualStatusList.includes(status) &&
            (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST)
        ) {
            if (this.isDateInPast('dateEnd')) {
                style = 'danger';
            } else if (this.isDateInPast('dateStart', true)) {
                style = 'warning';
            }
        }

        // noinspection JSValidateTypes
        return {
            ...super.data(),
            style: style,
        };
    }

    /**
     * @private
     * @param {string} field
     * @param {boolean} [isFrom]
     * @return {boolean}
     */
    isDateInPast(field, isFrom) {
        if (this.isDate()) {
            const value = this.model.get(field + 'Date');

            if (value) {
                const timeValue = isFrom ? value + ' 00:00' : value + ' 23:59';

                const d = moment.tz(timeValue, this.getDateTime().getTimeZone());
                const now = this.getDateTime().getNowMoment();

                if (d.unix() < now.unix()) {
                    return true;
                }
            }

            return false;
        }

        const value = this.model.get(field);

        if (value) {
            const d = this.getDateTime().toMoment(value);
            const now = moment().tz(this.getDateTime().timeZone || 'UTC');

            if (d.unix() < now.unix()) {
                return true;
            }
        }

        return false;
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            this.controlTimePartVisibility();
        }
    }

    fetch() {
        const data = super.fetch();

        if (data[this.nameDate]) {
            data.isAllDay = true;
        } else {
            data.isAllDay = false;
        }

        return data;
    }

    controlTimePartVisibility() {
        if (!this.isEditMode()) {
            return;
        }

        if (!this.isInlineEditMode()) {
            return;
        }

        if (this.model.get('isAllDay')) {
            this.$time.addClass('hidden');
            this.$el.find('.time-picker-btn').addClass('hidden');
        } else {
            this.$time.removeClass('hidden');
            this.$el.find('.time-picker-btn').removeClass('hidden');
        }
    }
}

export default DateStartMeetingFieldView;
