/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/fields/datetime-short */

import Dep from 'views/fields/datetime';
import moment from 'moment';

/**
 * @class Class
 * @extends module:views/fields/datetime
 */
export default Dep.extend(/** @lends Class# */{

    listTemplate: 'fields/datetime-short/list',
    detailTemplate: 'fields/datetime-short/detail',

    data: function () {
        let data = Dep.prototype.data.call(this);

        if (this.mode === this.MODE_LIST || this.mode === this.MODE_DETAIL) {
            data.fullDateValue = Dep.prototype.getDateStringValue.call(this);
        }

        return data;
    },

    getDateStringValue: function () {
        if (!(this.mode === this.MODE_LIST || this.mode === this.MODE_DETAIL)) {
            return Dep.prototype.getDateStringValue.call(this);
        }

        let value = this.model.get(this.name)

        if (!value) {
            return Dep.prototype.getDateStringValue.call(this);
        }

        let timeFormat = this.getDateTime().timeFormat;

        if (this.params.hasSeconds) {
            timeFormat = timeFormat.replace(/:mm/, ':mm:ss');
        }

        let d = this.getDateTime().toMoment(value);
        let now = moment().tz(this.getDateTime().timeZone || 'UTC');

        if (
            d.unix() > now.clone().startOf('day').unix() &&
            d.unix() < now.clone().add(1, 'days').startOf('day').unix()
        ) {
            return  d.format(timeFormat);
        }

        let readableFormat = this.getDateTime().getReadableShortDateFormat();

        return d.format('YYYY') === now.format('YYYY') ?
            d.format(readableFormat) :
            d.format(readableFormat + ', YY');
    },
});
