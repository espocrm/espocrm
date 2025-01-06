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

import BaseFieldView from 'views/fields/base';
import moment from 'moment';

// noinspection JSUnusedGlobalSymbols
export default class extends BaseFieldView {

    readOnly = true

    templateContent = `
        {{~#if isOverdue}}
        <span class="label label-danger">{{translate "overdue" scope="Task"}}</span>
        {{/if~}}
    `

    data() {
        let isOverdue = false;

        if (['Completed', 'Canceled'].indexOf(this.model.get('status')) === -1) {
            if (this.model.has('dateEnd')) {
                if (!this.isDate()) {
                    const value = this.model.get('dateEnd');

                    if (value) {
                        const d = this.getDateTime().toMoment(value);
                        const now = moment.tz(this.getDateTime().timeZone || 'UTC');

                        if (d.unix() < now.unix()) {
                            isOverdue = true;
                        }
                    }
                } else {
                    const value = this.model.get('dateEndDate');

                    if (value) {
                        const d = moment.utc(value + ' 23:59', this.getDateTime().internalDateTimeFormat);
                        const now = this.getDateTime().getNowMoment();

                        if (d.unix() < now.unix()) {
                            isOverdue = true;
                        }
                    }
                }
            }
        }

        return {
            isOverdue: isOverdue,
        };
    }

    setup() {
        this.mode = 'detail';
    }

    isDate() {
        const dateValue = this.model.get('dateEnd');

        if (dateValue) {
            return true;
        }

        return false;
    }
}
