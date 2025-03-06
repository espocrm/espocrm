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

import {inject} from 'di';
import Metadata from 'metadata';
import ViewHelper from 'view-helper';
import Settings from 'models/settings';
import User from 'models/user';
import AclManager from 'acl-manager';
import Preferences from 'models/preferences';

/**
 * Defaults populator.
 */
class DefaultsPopulator {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @private
     * @type {ViewHelper}
     */
    @inject(ViewHelper)
    viewHelper

    /**
     * @private
     * @type {Settings}
     */
    @inject(Settings)
    config

    /**
     * @private
     * @type {User}
     */
    @inject(User)
    user

    /**
     * @private
     * @type {Preferences}
     */
    @inject(Preferences)
    preferences

    /**
     * @private
     * @type {AclManager}
     */
    @inject(AclManager)
    acl

    /**
     * Populate default values.
     *
     * @param {module:model} model A model.
     * @return {Promise|undefined}
     */
    populate(model) {
        model.populateDefaults();

        const defaultHash = {};

        if (!this.user.isPortal()) {
            this.prepare(model, defaultHash);
        }

        if (this.user.isPortal()) {
            this.prepareForPortal(model, defaultHash);
        }

        this.prepareFields(model, defaultHash);

        for (const attr in defaultHash) {
            if (model.has(attr)) {
                delete defaultHash[attr];
            }
        }

        model.set(defaultHash, {silent: true});

        const preparatorClass = this.metadata.get(`clientDefs.${model.entityType}.modelDefaultsPreparator`);

        if (!preparatorClass) {
            return undefined;
        }

        return Espo.loader.requirePromise(preparatorClass)
            .then(Class => {
                /** @type {import('handlers/model/defaults-preparator').default} */
                const preparator = new Class(this.viewHelper);

                return preparator.prepare(model)
            })
            .then(attributes => {
                model.set(attributes, {silent: true});
            });
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepare(model, defaultHash) {
        const hasAssignedUsers = model.hasField('assignedUsers') &&
            model.getLinkParam('assignedUsers', 'entity') === 'User';

        if (model.hasField('assignedUser') || hasAssignedUsers) {
            let assignedUserField = 'assignedUser';

            if (hasAssignedUsers) {
                assignedUserField = 'assignedUsers';
            }

            if (this.toFillAssignedUser(model, assignedUserField)) {
                if (hasAssignedUsers) {
                    defaultHash['assignedUsersIds'] = [this.user.id];
                    defaultHash['assignedUsersNames'] = {};
                    defaultHash['assignedUsersNames'][this.user.id] = this.user.get('name');
                }
                else {
                    defaultHash['assignedUserId'] = this.user.id;
                    defaultHash['assignedUserName'] = this.user.get('name');
                }
            }
        }

        const defaultTeamId = this.user.get('defaultTeamId');

        if (defaultTeamId) {
            if (
                model.hasField('teams') &&
                !model.getFieldParam('teams', 'default') &&
                Espo.Utils.lowerCaseFirst(model.getLinkParam('teams', 'relationName') || '') === 'entityTeam'
            ) {
                defaultHash['teamsIds'] = [defaultTeamId];
                defaultHash['teamsNames'] = {};
                defaultHash['teamsNames'][defaultTeamId] = this.user.get('defaultTeamName');
            }
        }

        const hasCollaborators = model.hasField('collaborators') &&
            model.getLinkParam('collaborators', 'entity') === 'User' &&
            this.metadata.get(`scopes.${model.entityType}.collaborators`);

        if (hasCollaborators) {
            defaultHash.collaboratorsIds = [this.user.id];
            defaultHash.collaboratorsNames = {[this.user.id]: this.user.attributes.name};
        }
    }

    /**
     *
     * @param {import('model').default} model
     * @param {string} assignedUserField
     */
    toFillAssignedUser(model, assignedUserField) {
        if (!this.preferences.get('doNotFillAssignedUserIfNotRequired')) {
            return true;
        }

        if (model.getFieldParam(assignedUserField, 'required')) {
            return true;
        }

        if (this.acl.getPermissionLevel('assignmentPermission') === 'no') {
            return true;
        }

        if (
            this.acl.getPermissionLevel('assignmentPermission') === 'team' &&
            !this.user.get('defaultTeamId')
        ) {
            return true;
        }

        if (this.acl.getLevel(model.entityType, 'read') === 'own') {
            return true;
        }

        if (!this.acl.checkField(model.entityType, assignedUserField, 'edit')) {
            return true;
        }

        return false;
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepareForPortal(model, defaultHash) {
        const accountLink = this.metadata.get(`aclDefs.${model.entityType}.accountLink`);
        const contactLink = this.metadata.get(`aclDefs.${model.entityType}.contactLink`);

        if (
            accountLink &&
            model.hasField(accountLink) &&
            ['belongsTo', 'hasOne'].includes(model.getLinkType(accountLink)) &&
            model.getLinkParam(accountLink, 'entity') === 'Account'
        ) {
            if (this.user.attributes.accountId) {
                defaultHash[accountLink + 'Id'] = this.user.attributes.accountId;
                defaultHash[accountLink + 'Name'] = this.user.attributes.accuntName;
            }
        }

        if (
            contactLink &&
            model.hasField(contactLink) &&
            ['belongsTo', 'hasOne'].includes(model.getLinkType(contactLink))&&
            model.getLinkParam(contactLink, 'entity') === 'Contact'
        ) {
            if (this.user.attributes.contactId) {
                defaultHash[contactLink + 'Id'] = this.user.attributes.contactId;
                defaultHash[contactLink + 'Name'] = this.user.attributes.contactName;
            }
        }

        if (
            accountLink &&
            model.hasField(accountLink) &&
            model.getLinkType(accountLink) === 'hasMany' &&
            model.getLinkParam(accountLink, 'entity') === 'Account'
        ) {
            if (this.user.attributes.accountsIds) {
                defaultHash['accountsIds'] = [...this.user.attributes.accountsIds];
                defaultHash['accountsNames'] = {...this.user.attributes.accountsNames};
            }
        }

        if (
            contactLink &&
            model.hasField(contactLink) &&
            model.getLinkType(contactLink) === 'hasMany'&&
            model.getLinkParam(contactLink, 'entity') === 'Contact'
        ) {
            if (this.user.attributes.contactId) {
                defaultHash['contactsIds'] = [this.user.attributes.contactId];
                defaultHash['contactsNames'] = {[this.user.attributes.contactId]: this.user.attributes.contactName};
            }
        }

        if (
            model.hasField('parent') &&
            model.getLinkType('parent') === 'belongsToParent'
        ) {
            if (!this.config.get('b2cMode')) {
                if (
                    this.user.attributes.accountId &&
                    (model.getFieldParam('parent', 'entityList') || []).includes('Account')
                ) {
                    defaultHash['parentId'] = this.user.attributes.accountId;
                    defaultHash['parentName'] = this.user.attributes.accountName;
                    defaultHash['parentType'] = 'Account';
                }
            } else {
                if (
                    this.user.attributes.contactId &&
                    (model.getFieldParam('parent', 'entityList') || []).includes('Contact')
                ) {
                    defaultHash['parentId'] = this.user.attributes.contactId;
                    defaultHash['parentName'] = this.user.attributes.contactName;
                    defaultHash['parentType'] = 'Contact';
                }
            }
        }
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepareFields(model, defaultHash) {
        const set = (attribute, value) => {
            if (
                attribute in defaultHash ||
                model.has(attribute)
            ) {
                return;
            }

            defaultHash[attribute] = value;
        };

        model.getFieldList().forEach(field => {
            const type = model.getFieldType(field);

            if (!type) {
                return;
            }

            if (
                model.getFieldParam(field, 'disabled') ||
                model.getFieldParam(field, 'utility')
            ) {
                return;
            }

            if (type === 'enum') {
                /** @type {string[]} */
                const options = model.getFieldParam(field, 'options') || [];
                let value = options[0] || '';
                value = value !== '' ? value : null;

                if (value) {
                    set(field, value);
                }
            }
        });
    }
}

export default DefaultsPopulator;
