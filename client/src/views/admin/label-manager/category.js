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

import View from 'view';

class LabelManagerCategoryView extends View {

    template = 'admin/label-manager/category'

    events = {}

    data() {
        return {
            categoryDataList: this.getCategoryDataList(),
        };
    }

    setup() {
        this.scope = this.options.scope;
        this.language = this.options.language;
        this.categoryData = this.options.categoryData;
    }

    getCategoryDataList() {
        const labelList = Object.keys(this.categoryData);

        labelList.sort((v1, v2) => {
            return v1.localeCompare(v2);
        });

        const categoryDataList = [];

        labelList.forEach(name => {
            let value = this.categoryData[name];

            if (value === null) {
                value = '';
            }

            if (value.replace) {
                value = value.replace(/\n/i, '\\n');
            }

            const o = {
                name: name,
                value: value,
            };

            const arr = name.split('[.]');

            o.label = arr.slice(1).join(' . ');

            categoryDataList.push(o);
        });

        return categoryDataList;
    }
}

export default LabelManagerCategoryView;
