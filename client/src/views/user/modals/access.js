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

import ModalView from 'views/modal';

export default class extends ModalView {

    cssName = 'user-access'
    multiple = false
    template = 'user/modals/access'
    backdrop = true

    styleMap = {
        yes: 'success',
        all: 'success',
        account: 'info',
        contact: 'info',
        team: 'info',
        own: 'warning',
        no: 'danger',
        enabled: 'success',
        disabled: 'danger',
        'not-set': 'default',
    }

    data() {
        return {
            valuePermissionDataList: this.getValuePermissionList(),
            levelListTranslation: this.getLanguage().get('Role', 'options', 'levelList') || {},
            styleMap: this.styleMap,
        };
    }

    getValuePermissionList() {
        const list = this.getMetadata().get(['app', 'acl', 'valuePermissionList'], []);
        const dataList = [];

        list.forEach(item => {
            const o = {};
            o.name = item;
            o.value = this.options.aclData[item];

            dataList.push(o);
        });

        return dataList;
    }

    setup() {
        this.buttonList = [
            {
                name: 'cancel',
                label: 'Cancel',
            }
        ];

        const fieldTable = Espo.Utils.cloneDeep(this.options.aclData.fieldTable || {});

        for (const scope in fieldTable) {
            const scopeData = fieldTable[scope] || {};

            for (const field in scopeData) {
                if (
                    this.getMetadata()
                        .get(['app', 'acl', 'mandatory', 'scopeFieldLevel', scope, field]) !== null
                ) {
                    delete scopeData[field];
                }

                if (
                    scopeData[field] &&
                    this.getMetadata().get(['entityDefs', scope, 'fields', field, 'readOnly'])
                ) {
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
            final: true,
            selector: '.user-access-table',
        });

        this.headerText = this.translate('Access');
    }
}
