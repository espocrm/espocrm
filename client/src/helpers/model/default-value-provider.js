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

import DateTime from 'date-time';
import {inject} from 'di';

const nowExpression = /return this\.dateTime\.getNow\(([0-9]+)\);/;
const shiftTodayExpression = /return this\.dateTime\.getDateShiftedFromToday\(([0-9]+), '([a-z]+)'\);/;
const shiftNowExpression = /return this\.dateTime\.getDateTimeShiftedFromNow\(([0-9]+), '([a-z]+)', ([0-9]+)\);/;

export default class DefaultValueProvider {

    /**
     * @type {DateTime}
     */
    @inject(DateTime)
    dateTime

    /**
     * Get a value.
     *
     * @param {string} key
     * @return {*}
     */
    get(key) {
        if (key === "return this.dateTime.getToday();") {
            return this.dateTime.getToday();
        }

        const matchNow = key.match(nowExpression);

        if (matchNow) {
            const multiplicity = parseInt(matchNow[1]);

            return this.dateTime.getNow(multiplicity);
        }

        const matchTodayShift = key.match(shiftTodayExpression);

        if (matchTodayShift) {
            const shift = parseInt(matchTodayShift[1]);
            const unit = matchTodayShift[2];

            return this.dateTime.getDateShiftedFromToday(shift, unit);
        }

        const matchNowShift = key.match(shiftNowExpression);

        if (matchNowShift) {
            const shift = parseInt(matchNowShift[1]);
            const unit = matchNowShift[2];
            const multiplicity = parseInt(matchNowShift[3]);

            return this.dateTime.getDateTimeShiftedFromNow(shift, unit, multiplicity);
        }

        return undefined;
    }
}
