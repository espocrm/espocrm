/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('crm:views/task/fields/is-overdue', 'views/fields/base', function (Dep) {

    return Dep.extend({

        readOnly: true,

        _template: '{{#if isOverdue}}<span class="label label-danger">{{translate "overdue"}}</span>{{/if}}',

        data: function () {
            var isOverdue = false;
            if (['Completed', 'Canceled'].indexOf(this.model.get('status')) == -1) {
                if (this.model.has('dateEnd')) {
                    if (!this.isDate()) {
                        var value = this.model.get('dateEnd');
                        if (value) {
                            var d = this.getDateTime().toMoment(value);
                            var now = moment().tz(this.getDateTime().timeZone || 'UTC');
                            if (d.unix() < now.unix()) {
                                isOverdue = true;
                            }
                        }
                    } else {
                        var value = this.model.get('dateEndDate');
                        if (value) {
                            var d = moment.utc(value + ' 23:59', this.getDateTime().internalDateTimeFormat);
                            var now = this.getDateTime().getNowMoment();
                            if (d.unix() < now.unix()) {
                                isOverdue = true;
                            }
                        }
                    }

                }
            }
            return {
                isOverdue: isOverdue
            };
        },

        setup: function () {
            this.mode = 'detail';
        },

        isDate: function () {
            var dateValue = this.model.get('dateEnd');
            if (dateValue && dateValue != '') {
                return true;
            }
            return false;
        },

    });

});
