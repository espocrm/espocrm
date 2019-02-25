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

Espo.define('views/admin/layouts/record/edit-attributes', 'views/record/base', function (Dep) {

    return Dep.extend({

        template: 'admin/layouts/record/edit-attributes',

        data: function () {
            return {
                attributeDataList: this.getAttributeDataList()
            };
        },

        getAttributeDataList: function () {
            var list = [];
            this.attributeList.forEach(function (item) {
                list.push({
                    name: item,
                    viewKey: item + 'Field'
                });
            }, this);
            return list;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.attributeList = this.options.attributeList || [];
            this.attributeDefs = this.options.attributeDefs || {};

            this.attributeList.forEach(function (field) {
                var params = this.attributeDefs[field] || {};
                var type = params.type || 'base';

                var viewName = params.view || this.getFieldManager().getViewName(type);
                this.createField(field, viewName, params);
            }, this);
        }

    });
});
