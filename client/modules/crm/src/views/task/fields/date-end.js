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

define('crm:views/task/fields/date-end', ['views/fields/datetime-optional'], function (Dep) {

    return Dep.extend({

        detailTemplate: 'crm:task/fields/date-end/detail',
        listTemplate: 'crm:task/fields/date-end/detail',

        isEnd: true,

        data: function () {
            var data = Dep.prototype.data.call(this);

            if (this.model.get('status') && !~['Completed', 'Canceled'].indexOf(this.model.get('status'))) {
                if (this.mode === 'list' || this.mode === 'detail') {
                    if (!this.isDate()) {
                        let value = this.model.get(this.name);

                        if (value) {
                            let d = this.getDateTime().toMoment(value);
                            let now = moment().tz(this.getDateTime().timeZone || 'UTC');

                            if (d.unix() < now.unix()) {
                                data.isOverdue = true;
                            }
                        }
                    } else {
                        let value = this.model.get(this.nameDate);

                        if (value) {
                            let d = moment.utc(value + ' 23:59', this.getDateTime().internalDateTimeFormat);
                            let now = this.getDateTime().getNowMoment();

                            if (d.unix() < now.unix()) {
                                data.isOverdue = true;
                            }
                        }
                    }
                }
            }

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'change', () => {
                if (!this.model.get('dateEnd')) {
                    if (this.model.get('reminders')) {
                        this.model.set('reminders', []);
                    }
                }
            });
        },
    });
});
