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

import VarcharFieldView from 'views/fields/varchar';

class VarcharColumnFieldView extends VarcharFieldView {

    searchTypeList = [
        'startsWith',
        'contains',
        'equals',
        'endsWith',
        'like',
        'isEmpty',
        'isNotEmpty',
    ]

    fetchSearch() {
        const type = this.fetchSearchType() || 'startsWith';

        if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
            if (type === 'isEmpty') {
                return {
                    typeFront: type,
                    where: {
                        type: 'or',
                        value: [
                            {
                                type: 'columnIsNull',
                                field: this.name,
                            },
                            {
                                type: 'columnEquals',
                                field: this.name,
                                value: '',
                            },
                        ],
                    },
                };
            }

            return  {
                typeFront: type,
                where: {
                    type: 'and',
                    value: [
                        {
                            type: 'columnNotEquals',
                            field: this.name,
                            value: '',
                        },
                        {
                            type: 'columnIsNotNull',
                            field: this.name,
                            value: null,
                        },
                    ],
                },
            };
        }

        let value = this.$element.val().toString().trim();

        value = value.trim();

        if (value) {
            return {
                value: value,
                type: 'column' . Espo.Utils.upperCaseFirst(type),
                data: {
                    type: type,
                    value: value,
                },
            };
        }

        return null;
    }
}

export default VarcharColumnFieldView;

