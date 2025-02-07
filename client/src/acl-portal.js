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

/** @module acl-portal */

import Acl from 'acl';
import {inject} from 'di';
import Metadata from 'metadata';

/**
 * Internal class for portal access checking. Can be extended to customize access checking
 * for a specific scope.
 */
class AclPortal extends Acl {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /** @inheritDoc */
    checkScope(data, action, precise, entityAccessData) {
        entityAccessData = entityAccessData || {};

        const inAccount = entityAccessData.inAccount;
        const isOwnContact = entityAccessData.isOwnContact;
        const isOwner = entityAccessData.isOwner;

        if (this.getUser().isAdmin()) {
            return true;
        }

        if (data === false) {
            return false;
        }

        if (data === true) {
            return true;
        }

        if (typeof data === 'string') {
            return true;
        }

        if (data === null) {
            return false;
        }

        action = action || null;

        if (action === null) {
            return true;
        }

        if (!(action in data)) {
            return false;
        }

        const value = data[action];

        if (value === 'all') {
            return true;
        }

        if (value === 'yes') {
            return true;
        }

        if (value === 'no') {
            return false;
        }

        if (typeof isOwner === 'undefined') {
            return true;
        }

        if (isOwner) {
            if (value === 'own' || value === 'account' || value === 'contact') {
                return true;
            }
        }

        let result = false;

        if (value === 'account') {
            result = inAccount;

            if (inAccount === null) {
                if (precise) {
                    result = null;
                } else {
                    return true;
                }
            }
            else if (inAccount) {
                return true;
            }
        }

        if (value === 'contact') {
            result = isOwnContact;

            if (isOwnContact === null) {
                if (precise) {
                    result = null;
                } else {
                    return true;
                }
            }
            else if (isOwnContact) {
                return true;
            }
        }

        if (isOwner === null) {
            if (precise) {
                result = null;
            } else {
                return true;
            }
        }

        return result;
    }

    /** @inheritDoc */
    checkModel(model, data, action, precise) {
        if (this.getUser().isAdmin()) {
            return true;
        }

        const entityAccessData = {
            isOwner: this.checkIsOwner(model),
            inAccount: this.checkInAccount(model),
            isOwnContact: this.checkIsOwnContact(model),
        };

        return this.checkScope(data, action, precise, entityAccessData);
    }

    /** @inheritDoc */
    checkIsOwner(model) {
        if (
            model.hasField('createdBy') &&
            this.getUser().id === model.get('createdById')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user in an account of a model.
     *
     * @param {import('model').default} model A model.
     * @returns {boolean|null} True if in an account, null if not clear.
     */
    checkInAccount(model) {
        const accountsIds = this.getUser().getLinkMultipleIdList('accounts');

        if (!accountsIds.length) {
            return false;
        }

        const link = this.metadata.get(`aclDefs.${model.entityType}.accountLink`);

        if (link) {
            const linkType = model.getLinkType(link);

            if (linkType === 'belongsTo' || linkType === 'hasOne') {
                const idAttribute = link + 'Id';

                if (!model.has(idAttribute)) {
                    return null;
                }

                const id = model.get(idAttribute);

                if (!id) {
                    return false;
                }

                return accountsIds.includes(id);
            }

            if (linkType === 'belongsToParent') {
                const idAttribute = link + 'Id';
                const typeAttribute = link + 'Type';

                if (!model.has(idAttribute) || !model.has(typeAttribute)) {
                    return null;
                }

                const id = model.get(idAttribute);

                if (model.get(typeAttribute) !== 'Account' || !id) {
                    return false;
                }

                return accountsIds.includes(id);
            }

            if (linkType === 'hasMany') {
                if (!model.hasField(link) || model.getFieldType(link) !== 'linkMultiple') {
                    return true;
                }

                if (!model.has(link + 'Ids')) {
                    return null;
                }

                const ids = model.getLinkMultipleIdList(link);

                for (const id of ids) {
                    if (accountsIds.includes(id)) {
                        return true;
                    }
                }

                return false;
            }

            return false;
        }

        if (
            model.hasField('account') &&
            model.get('accountId') &&
            accountsIds.includes(model.get('accountId'))
        ) {
            return true;
        }

        let result = false;

        if (model.hasField('accounts') && model.hasLink('accounts')) {
            if (!model.has('accountsIds')) {
                result = null;
            }

            (model.getLinkMultipleIdList('accounts')).forEach(id => {
                if (accountsIds.includes(id)) {
                    result = true;
                }
            });
        }

        if (
            model.hasField('parent') &&
            model.hasLink('parent') &&
            model.get('parentType') === 'Account' &&
            accountsIds.includes(model.get('parentId'))
        ) {
            return true;
        }

        if (result === false) {
            if (!model.hasField('accounts') && model.hasLink('accounts')) {
                return true;
            }
        }

        return result;
    }

    /**
     * Check if a user is a contact-owner to a model.
     *
     * @param {module:model} model A model.
     * @returns {boolean|null} True if in a contact-owner, null if not clear.
     */
    checkIsOwnContact(model) {
        const contactId = this.getUser().get('contactId');

        if (!contactId) {
            return false;
        }

        const link = this.metadata.get(`aclDefs.${model.entityType}.contactLink`);

        if (link) {
            const linkType = model.getLinkType(link);

            if (linkType === 'belongsTo' || linkType === 'hasOne') {
                const idAttribute = link + 'Id';

                if (!model.has(idAttribute)) {
                    return null;
                }

                return model.get(idAttribute) === contactId;
            }

            if (linkType === 'belongsToParent') {
                const idAttribute = link + 'Id';
                const typeAttribute = link + 'Type';

                if (!model.has(idAttribute) || !model.has(typeAttribute)) {
                    return null;
                }

                if (model.get(typeAttribute) !== 'Contact') {
                    return false;
                }

                return model.get(idAttribute) === contactId;
            }

            if (linkType === 'hasMany') {
                if (!model.hasField(link) || model.getFieldType(link) !== 'linkMultiple') {
                    return true;
                }

                if (!model.has(link + 'Ids')) {
                    return null;
                }

                const ids = model.getLinkMultipleIdList(link);

                return ids.includes(contactId);
            }

            return false;
        }

        if (model.hasField('contact')) {
            if (model.get('contactId')) {
                if (contactId === model.get('contactId')) {
                    return true;
                }
            }
        }

        let result = false;

        if (model.hasField('contacts') && model.hasLink('contacts')) {
            if (!model.has('contactsIds')) {
                result = null;
            }

            (model.getLinkMultipleIdList('contacts')).forEach(id => {
                if (contactId === id) {
                    result = true;
                }
            });
        }

        if (model.hasField('parent') && model.hasLink('parent')) {
            if (model.get('parentType') === 'Contact' && model.get('parentId') === contactId) {
                return true;
            }
        }

        if (result === false) {
            if (!model.hasField('contacts') && model.hasLink('contacts')) {
                return true;
            }
        }

        return result;
    }
}

export default AclPortal;
