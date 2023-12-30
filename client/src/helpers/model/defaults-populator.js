/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/**
 * Defaults populator.
 */
class DefaultsPopulator {

    /**
     * @param {module:models/user} user
     * @param {module:models/preferences} preferences
     * @param {module:acl-manager} acl
     * @param {module:models/settings} config
     */
    constructor(user, preferences, acl, config) {
        this.user = user;
        this.preferences = preferences;
        this.acl = acl;
        this.config = config;
    }

    /**
     * Populate default values.
     *
     * @param {module:model} model A model.
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
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepare(model, defaultHash) {
        const hasAssignedUsers =
            model.hasField('assignedUsers') &&
            model.getLinkParam('assignedUsers', 'entity') === 'User';

        if (model.hasField('assignedUser') || hasAssignedUsers) {
            let assignedUserField = 'assignedUser';

            if (hasAssignedUsers) {
                assignedUserField = 'assignedUsers';
            }

            let fillAssignedUser = true;

            if (this.preferences.get('doNotFillAssignedUserIfNotRequired')) {
                fillAssignedUser = false;

                if (model.getFieldParam(assignedUserField, 'required')) {
                    fillAssignedUser = true;
                }
                else if (this.acl.getPermissionLevel('assignmentPermission') === 'no') {
                    fillAssignedUser = true;
                }
                else if (
                    this.acl.getPermissionLevel('assignmentPermission') === 'team' &&
                    !this.user.get('defaultTeamId')
                ) {
                    fillAssignedUser = true;
                }
                else if (
                    this.acl.getScopeForbiddenFieldList(model.entityType, 'edit').includes(assignedUserField)
                ) {
                    fillAssignedUser = true;
                }
            }

            if (fillAssignedUser) {
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
    }

    /**
     * @param {module:model} model
     * @param {Object.<string, *>} defaultHash
     * @private
     */
    prepareForPortal(model, defaultHash) {
        if (
            model.hasField('account') &&
            ['belongsTo', 'hasOne'].includes(model.getLinkType('account')) &&
            model.getLinkParam('account', 'entity') === 'Account'
        ) {
            if (this.user.get('accountId')) {
                defaultHash['accountId'] =  this.user.get('accountId');
                defaultHash['accountName'] = this.user.get('accountName');
            }
        }

        if (
            model.hasField('contact') &&
            ['belongsTo', 'hasOne'].includes(model.getLinkType('contact'))&&
            model.getLinkParam('contact', 'entity') === 'Contact'
        ) {
            if (this.user.get('contactId')) {
                defaultHash['contactId'] = this.user.get('contactId');
                defaultHash['contactName'] = this.user.get('contactName');
            }
        }

        if (model.hasField('parent') && model.getLinkType('parent') === 'belongsToParent') {
            if (!this.config.get('b2cMode')) {
                if (this.user.get('accountId')) {
                    if ((model.getFieldParam('parent', 'entityList') || []).includes('Account')) {
                        defaultHash['parentId'] = this.user.get('accountId');
                        defaultHash['parentName'] = this.user.get('accountName');
                        defaultHash['parentType'] = 'Account';
                    }
                }
            }
            else {
                if (this.user.get('contactId')) {
                    if ((model.getFieldParam('parent', 'entityList') || []).includes('Contact')) {
                        defaultHash['contactId'] = this.user.get('contactId');
                        defaultHash['parentName'] = this.user.get('contactName');
                        defaultHash['parentType'] = 'Contact';
                    }
                }
            }
        }

        if (
            model.hasField('accounts') &&
            model.getLinkType('accounts') === 'hasMany' &&
            model.getLinkParam('accounts', 'entity') === 'Account'
        ) {
            if (this.user.get('accountsIds')) {
                defaultHash['accountsIds'] = this.user.get('accountsIds');
                defaultHash['accountsNames'] = this.user.get('accountsNames');
            }
        }

        if (
            model.hasField('contacts') &&
            model.getLinkType('contacts') === 'hasMany'&&
            model.getLinkParam('contacts', 'entity') === 'Contact'
        ) {
            if (this.user.get('contactId')) {
                defaultHash['contactsIds'] = [this.user.get('contactId')];

                const names = {};

                names[this.user.get('contactId')] = this.user.get('contactName');
                defaultHash['contactsNames'] = names;
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
