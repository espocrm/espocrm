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

Espo.define('views/user/modals/access', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'user-access',

        multiple: false,

        template: 'user/modals/access',

        header: false,

        backdrop: true,

        data: function () {
            return {
                valuePermissionDataList: this.getValuePermissionList(),
                levelListTranslation: this.getLanguage().get('Role', 'options', 'levelList') || {}
            };
        },

        getValuePermissionList: function () {
            var list = this.getMetadata().get(['app', 'acl', 'valuePermissionList'], []);
            var dataList = [];
            list.forEach(function (item) {
                var o = {};
                o.name = item;
                o.value = this.options.aclData[item];
                dataList.push(o);
            }, this);
            return dataList;
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            var fieldTable = Espo.Utils.cloneDeep(this.options.aclData.fieldTable || {});
            for (var scope in fieldTable) {
                var scopeData = fieldTable[scope] || {};
                for (var field in scopeData) {
                    if (this.getMetadata().get(['app', 'acl', 'mandatory', 'scopeFieldLevel', scope, field]) !== null) {
                        delete scopeData[field];
                    }

                    if (scopeData[field] && this.getMetadata().get(['entityDefs', scope, 'fields', field, 'readOnly'])) {
                        if (scopeData[field].edit === 'no' && scopeData[field].read === 'yes') {
                            delete scopeData[field];
                        }
                    }
                }
            }

            this.createView('table', 'views/role/record/table', {
                acl: {
                    data: this.options.aclData.table,
                    fieldData: fieldTable,
                },
                final: true
            });

            this.headerHtml = this.translate('Access');
        }

    });
});
