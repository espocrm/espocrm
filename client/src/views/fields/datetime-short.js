/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

Espo.define('views/fields/datetime-short', 'views/fields/datetime', function (Dep) {

    return Dep.extend({

        listTemplate: 'fields/datetime-short/list',

        detailTemplate: 'fields/datetime-short/detail',

        data: function () {
            var data = Dep.prototype.data.call(this);
            if (this.mode == 'list' || this.mode == 'detail') {
                data.fullDateValue = Dep.prototype.getDateStringValue.call(this);
            }
            return data;
        },

        getDateStringValue: function () {
            if (this.mode == 'list' || this.mode == 'detail') {
                var value = this.model.get(this.name)
                if (value) {
                    var string;

                    var timeFormat = this.getDateTime().timeFormat;

                    if (this.params.hasSeconds) {
                        timeFormat = timeFormat.replace(/:mm/, ':mm:ss');
                    }

                    var d = this.getDateTime().toMoment(value);

                    var now = moment().tz(this.getDateTime().timeZone || 'UTC');

                    if (d.unix() > now.clone().startOf('day').unix() && d.unix() < now.clone().add(1, 'days').startOf('day').unix()) {
                        string = d.format(timeFormat);
                        return string;
                    }

                    var readableFormat = this.getDateTime().getReadableShortDateFormat();

                    if (d.format('YYYY') == now.format('YYYY')) {
                        string = d.format(readableFormat);
                    } else {
                        string = d.format(readableFormat + ', YY');
                    }

                    return string;
                }
            }

            return Dep.prototype.getDateStringValue.call(this);
        }

    });
});
