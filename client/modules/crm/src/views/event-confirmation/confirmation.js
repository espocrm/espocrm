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

define('crm:views/event-confirmation/confirmation', ['view'], function (Dep) {

    return Dep.extend({

        template: 'crm:event-confirmation/confirmation',

        data: function () {
            let style = this.actionData.style || 'default';

            return {
                actionData: this.actionData,
                style: style,
                dateStart: this.actionData.dateStart ?
                    this.convertDateTime(this.actionData.dateStart) : null,
                sentDateStart: this.actionData.sentDateStart ?
                    this.convertDateTime(this.actionData.sentDateStart) : null,
                dateStartChanged: this.actionData.sentDateStart &&
                    this.actionData.dateStart !== this.actionData.sentDateStart,
                actionDataList: this.getActionDataList(),
            };
        },

        setup: function () {
            this.actionData = this.options.actionData;
        },

        getActionDataList: function () {
            let actionMap = {
                'Accepted': 'accept',
                'Declined': 'decline',
                'Tentative': 'tentative',
            };

            let statusList = ['Accepted', 'Tentative', 'Declined'];

            if (!statusList.includes(this.actionData.status)) {
                return null;
            }

            let url = window.location.href.replace('action=' + actionMap[this.actionData.status], 'action={action}');

            return statusList.map(item => {
                let active = item === this.actionData.status;

                return {
                    active: active,
                    link: active ? '' : url.replace('{action}', actionMap[item]),
                    label: this.actionData.statusTranslation[item],
                };
            });
        },

        convertDateTime: function (value) {
            let timezone = this.getConfig().get('timeZone');

            let m = this.getDateTime().toMoment(value)
                .tz(timezone);

            return m.format(this.getDateTime().getDateTimeFormat()) + ' ' +
                m.format('Z z');
        },
    });
});
