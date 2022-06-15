/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('acl-portal', ['acl'], function (Dep) {

    /**
     * Internal class for portal access checking. Can be extended to customize access checking
     * for a specific scope.
     *
     * @class
     * @name Class
     * @extends module:acl.Class
     * @memberOf module:acl-portal
     */
    return Dep.extend(/** @lends module:acl-portal.Class# */{

        /**
         * @inheritDoc
         */
        user: null,

        /**
         * @inheritDoc
         */
        getUser: function () {
            return this.user;
        },

        /**
         * @inheritDoc
         */
        checkScope: function (data, action, precise, entityAccessData) {
            entityAccessData = entityAccessData || {};

            let inAccount = entityAccessData.inAccount;
            let isOwnContact = entityAccessData.isOwnContact;
            let isOwner = entityAccessData.isOwner;

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

            var value = data[action];

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

            var result = false;

            if (value === 'account') {
                result = inAccount;
                if (inAccount === null) {
                    if (precise) {
                        result = null;
                    }
                    else {
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
                    }
                    else {
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
                }
                else {
                    return true;
                }
            }

            return result;
        },

        /**
         * @inheritDoc
         */
        checkModel: function (model, data, action, precise) {
            if (this.getUser().isAdmin()) {
                return true;
            }

            let entityAccessData = {
                isOwner: this.checkIsOwner(model),
                inAccount: this.checkInAccount(model),
                isOwnContact: this.checkIsOwnContact(model),
            };

            return this.checkScope(data, action, precise, entityAccessData);
        },

        /**
         * @inheritDoc
         */
        checkIsOwner: function (model) {
            if (model.hasField('createdBy')) {
                if (this.getUser().id === model.get('createdById')) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Check if a user in an account of a model.
         *
         * @param {module:model.Class} model A model.
         * @returns {boolean|null} True if in an account, null if not clear.
         */
        checkInAccount: function (model) {
            let accountIdList = this.getUser().getLinkMultipleIdList('accounts');

            if (!accountIdList.length) {
                return false;
            }

            if (model.hasField('account')) {
                if (model.get('accountId')) {
                    if (~accountIdList.indexOf(model.get('accountId'))) {
                        return true;
                    }
                }
            }

            var result = false;

            if (model.hasField('accounts') && model.hasLink('accounts')) {
                if (!model.has('accountsIds')) {
                    result = null;
                }

                (model.getLinkMultipleIdList('accounts')).forEach(id => {
                    if (~accountIdList.indexOf(id)) {
                        result = true;
                    }
                });
            }

            if (model.hasField('parent') && model.hasLink('parent')) {
                if (model.get('parentType') === 'Account') {
                    if (!accountIdList.indexOf(model.get('parentId'))) {
                        return true;
                    }
                }
            }

            if (result === false) {
                if (!model.hasField('accounts') && model.hasLink('accounts')) {
                    return true;
                }
            }

            return result;
        },

        /**
         * Check if a user is a contact-owner to a model.
         *
         * @param {module:model.Class} model A model.
         * @returns {boolean|null} True if in a contact-owner, null if not clear.
         */
        checkIsOwnContact: function (model) {
            let contactId = this.getUser().get('contactId');

            if (!contactId) {
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
        },
    });
});
