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

define('crm:views/contact/fields/account-role', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        detailTemplate: 'crm:contact/fields/account-role/detail',
        listTemplate: 'crm:contact/fields/account-role/detail',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:title', () => {
                this.model.set('accountRole', this.model.get('title'));
            });
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);

            list.push('title');
            list.push('accountIsInactive');

            return list;
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            if (this.model.has('accountIsInactive')) {
                data.accountIsInactive = this.model.get('accountIsInactive');
            }

            return data;
        }
    });
});
