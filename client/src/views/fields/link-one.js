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

define('views/fields/link-one', 'views/fields/link', function (Dep) {

    return Dep.extend({

        readOnly: true,

        searchTypeList: ['is', 'isEmpty', 'isNotEmpty', 'isOneOf'],

        fetchSearch: function () {
            var type = this.$el.find('select.search-type').val();
            var value = this.$el.find('[data-name="' + this.idName + '"]').val();

            if (type == 'isOneOf') {
                var data = {
                    type: 'linkedWith',
                    field: this.name,
                    value: this.searchData.oneOfIdList,
                    data: {
                        type: type,
                        oneOfIdList: this.searchData.oneOfIdList,
                        oneOfNameHash: this.searchData.oneOfNameHash,
                    },
                };
                return data;

            } else if (type == 'is' || !type) {
                if (!value) {
                    return false;
                }
                var data = {
                    type: 'linkedWith',
                    field: this.name,
                    value: value,
                    data: {
                        type: type,
                        nameValue: this.$el.find('[data-name="' + this.nameName + '"]').val(),
                    },
                };
                return data;

            } else if (type === 'isEmpty') {
                var data = {
                    type: 'isNotLinked',
                    data: {
                        type: type,
                    },
                };
                return data;

            } else if (type === 'isNotEmpty') {
                var data = {
                    type: 'isLinked',
                    data: {
                        type: type,
                    },
                };
                return data;
            }
        },

    });
});
