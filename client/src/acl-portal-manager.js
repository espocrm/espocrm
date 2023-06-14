/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module acl-portal-manager */

import AclManager from 'acl-manager';
import AclPortal from 'acl-portal';

/**
 * An access checking class for a specific scope for portals.
 */
class AclPortalManager extends AclManager {

    /**
     * Check if a user in an account of a model.
     *
     * @param {module:model} model A model.
     * @returns {boolean|null} True if in an account, null if not clear.
     */
    checkInAccount(model) {
        return this.getImplementation(model.name).checkInAccount(model);
    }

    /**
     * Check if a user is a contact-owner to a model.
     *
     * @param {module:model} model A model.
     * @returns {boolean|null} True if in a contact-owner, null if not clear.
     */
    checkIsOwnContact(model) {
        return this.getImplementation(model.name).checkIsOwnContact(model);
    }

    /**
     * @param {string} scope A scope.
     * @returns {module:acl-portal}
     */
    getImplementation(scope) {
        if (!(scope in this.implementationHash)) {
            let implementationClass = AclPortal;

            if (scope in this.implementationClassMap) {
                implementationClass = this.implementationClassMap[scope];
            }

            this.implementationHash[scope] =
                new implementationClass(this.getUser(), scope, this.aclAllowDeleteCreated);
        }

        return this.implementationHash[scope];
    }
}

export default AclPortalManager;
