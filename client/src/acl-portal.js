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

define('acl-portal', ['acl'], function (Dep) {

    return Dep.extend({

        user: null,

        getUser: function () {
            return this.user;
        },

        checkScope: function (data, action, precise, entityAccessData) {
            entityAccessData = entityAccessData || {};

            var inAccount = entityAccessData.inAccount;
            var isOwnContact = entityAccessData.isOwnContact;
            var isOwner = entityAccessData.isOwner;

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
                return true;
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
                    } else {
                        return true;
                    }
                } else if (inAccount) {
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
                } else if (isOwnContact) {
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
        },

        checkModel: function (model, data, action, precise) {
            if (this.getUser().isAdmin()) {
                return true;
            }
            var entityAccessData = {
                isOwner: this.checkIsOwner(model),
                inAccount: this.checkInAccount(model),
                isOwnContact: this.checkIsOwnContact(model),
            };
            return this.checkScope(data, action, precise, entityAccessData);
        },

        checkIsOwner: function (model) {
            if (model.hasField('createdBy')) {
                if (this.getUser().id === model.get('createdById')) {
                    return true;
                }
            }

            return false;
        },

        checkInAccount: function (model) {
            var accountIdList = this.getUser().getLinkMultipleIdList('accounts');

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
                (model.getLinkMultipleIdList('accounts')).forEach(function (id) {
                    if (~accountIdList.indexOf(id)) {
                        result = true;
                    }
                }, this);
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

        checkIsOwnContact: function (model) {
            var contactId = this.getUser().get('contactId');
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

            var result = false;

            if (model.hasField('contacts') && model.hasLink('contacts')) {
                if (!model.has('contactsIds')) {
                    result = null;
                }
                (model.getLinkMultipleIdList('contacts')).forEach(function (id) {
                    if (contactId === id) {
                        result = true;
                    }
                }, this);
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

    });

});

