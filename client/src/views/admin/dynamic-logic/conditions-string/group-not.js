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

import DynamicLogicConditionsStringGroupBaseView from 'views/admin/dynamic-logic/conditions-string/group-base';

export default class DynamicLogicConditionsStringGroupNotView extends DynamicLogicConditionsStringGroupBaseView {

    template = 'admin/dynamic-logic/conditions-string/group-not'

    data() {
        return {
            viewKey: this.viewKey,
            operator: this.operator,
        };
    }

    setup() {
        this.level = this.options.level || 0;
        this.number = this.options.number || 0;
        this.scope = this.options.scope;
        this.operator = this.options.operator || this.operator;
        this.itemData = this.options.itemData || {};
        this.viewList = [];

        const i = 0;
        const key = `view-${this.level.toString()}-${this.number.toString()}-${i.toString()}`;

        this.createItemView(i, key, this.itemData.value);
        this.viewKey = key;
    }
}
