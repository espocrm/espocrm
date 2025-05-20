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

import LinkFieldView from 'views/fields/link';

class LinkOneFieldView extends LinkFieldView {

    searchTypeList = ['is', 'isEmpty', 'isNotEmpty', 'isOneOf']

    fetchSearch() {
        const type = this.$el.find('select.search-type').val();
        const value = this.$el.find('[data-name="' + this.idName + '"]').val();

        if (['isOneOf'].includes(type) && !this.searchData.oneOfIdList.length) {
            return {
                type: 'isNotNull',
                attribute: 'id',
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isOneOf') {
            if (!value) {
                return false;
            }

            return  {
                type: 'linkedWith',
                field: this.name,
                value: this.searchData.oneOfIdList,
                data: {
                    type: type,
                    oneOfIdList: this.searchData.oneOfIdList,
                    oneOfNameHash: this.searchData.oneOfNameHash,
                },
            };
        }

        if (type === 'is' || !type) {
            if (!value) {
                return false;
            }

            return  {
                type: 'linkedWith',
                field: this.name,
                value: value,
                data: {
                    type: type,
                    nameValue: this.$el.find('[data-name="' + this.nameName + '"]').val(),
                },
            };
        }

        if (type === 'isEmpty') {
            return  {
                type: 'isNotLinked',
                data: {
                    type: type,
                },
            };
        }

        if (type === 'isNotEmpty') {
            return  {
                type: 'isLinked',
                data: {
                    type: type,
                },
            };
        }
    }
}

export default LinkOneFieldView;
