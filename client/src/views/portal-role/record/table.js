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

import RoleRecordTableView from 'views/role/record/table';

export default class extends RoleRecordTableView {

    levelListMap = {
        'recordAllAccountContactOwnNo': ['all', 'account', 'contact', 'own', 'no'],
        'recordAllAccountOwnNo': ['all', 'account', 'own', 'no'],
        'recordAllContactOwnNo': ['all', 'contact', 'own', 'no'],
        'recordAllAccountNo': ['all', 'account', 'no'],
        'recordAllContactNo': ['all', 'contact', 'no'],
        'recordAllAccountContactNo': ['all', 'account', 'contact', 'no'],
        'recordAllOwnNo': ['all', 'own', 'no'],
        'recordAllNo': ['all', 'no'],
        'record': ['all', 'own', 'no']
    }

    levelList = [
        'all',
        'account',
        'contact',
        'own',
        'no',
    ]

    type = 'aclPortal'
    lowestLevelByDefault = true

    setupScopeList() {
        this.aclTypeMap = {};
        this.scopeList = [];

        const scopeListAll = this.getSortedScopeList();

        scopeListAll.forEach(scope => {
            if (
                this.getMetadata().get(`scopes.${scope}.disabled`) ||
                this.getMetadata().get(`scopes.${scope}.disabledPortal`)
            ) {
                return;
            }

            const acl = this.getMetadata().get(`scopes.${scope}.aclPortal`);

            if (acl) {
                this.scopeList.push(scope);
                this.aclTypeMap[scope] = acl;

                if (acl === true) {
                    this.aclTypeMap[scope] = 'record';
                }
            }
        });
    }

    isAclFieldLevelDisabledForScope(scope) {
        return !!this.getMetadata().get(`scopes.${scope}.aclPortalFieldLevelDisabled`);
    }
}
