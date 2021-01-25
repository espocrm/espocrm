/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/portal-user/list', 'views/list', function (Dep) {

    return Dep.extend({

        defaultOrderBy: 'createdAt',

        defaultOrder: 'desc',

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        actionCreate: function () {
            var viewName = this.getMetadata().get('clientDefs.Contact.modalViews.select') || 'views/modals/select-records';

            var viewName = 'crm:views/contact/modals/select-for-portal-user';

            this.createView('modal', viewName, {
                scope: 'Contact',
                primaryFilterName: 'notPortalUsers',
                createButton: false,
                mandatorySelectAttributeList: [
                    'salutationName',
                    'firstName',
                    'lastName',
                    'accountName',
                    'accountId',
                    'emailAddress',
                    'emailAddressData',
                    'phoneNumber',
                    'phoneNumberData'
                ]
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'select', function (model) {
                    var attributes = {};

                    attributes.contactId = model.id;
                    attributes.contactName = model.get('name');

                    if (model.get('accountId')) {
                        var names = {};
                        names[model.get('accountId')] = model.get('accountName');

                        attributes.accountsIds = [model.get('accountId')];
                        attributes.accountsNames = names;
                    }

                    attributes.firstName = model.get('firstName');
                    attributes.lastName = model.get('lastName');
                    attributes.salutationName = model.get('salutationName');

                    attributes.emailAddress = model.get('emailAddress');
                    attributes.emailAddressData = model.get('emailAddressData');

                    attributes.phoneNumber = model.get('phoneNumber');
                    attributes.phoneNumberData = model.get('phoneNumberData');

                    attributes.userName = attributes.emailAddress;

                    attributes.type = 'portal';

                    var router = this.getRouter();

                    var url = '#' + this.scope + '/create';

                    router.dispatch(this.scope, 'create', {
                        attributes: attributes
                    });
                    router.navigate(url, {trigger: false});
                }, this);

                this.listenToOnce(view, 'skip', function (model) {
                    var attributes = {
                        type: 'portal'
                    };

                    var router = this.getRouter();
                    var url = '#' + this.scope + '/create';
                    router.dispatch(this.scope, 'create', {
                        attributes: attributes
                    });
                    router.navigate(url, {trigger: false});
                }, this);
            }, this);
        }

    });
});
