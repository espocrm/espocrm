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

import ListView from 'views/list';

class PortalUserListView extends ListView {

    defaultOrderBy = 'createdAt'
    defaultOrder = 'desc'

    actionCreate() {
        const viewName = 'crm:views/contact/modals/select-for-portal-user';

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
                'phoneNumberData',
            ]
        }, view => {
            view.render();

            this.listenToOnce(view, 'select', model => {
                const attributes = {};

                attributes.contactId = model.id;
                attributes.contactName = model.get('name');

                if (model.get('accountId')) {
                    const names = {};
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

                if (attributes.userName) {
                    attributes.userName = attributes.userName.toLowerCase();
                }

                attributes.type = 'portal';

                const router = this.getRouter();

                const url = `#${this.scope}/create`;

                router.dispatch(this.scope, 'create', {
                    attributes: attributes
                });

                router.navigate(url, {trigger: false});
            });

            this.listenToOnce(view, 'skip', () => {
                const attributes = {
                    type: 'portal',
                };

                const router = this.getRouter();
                const url = `#${this.scope}/create`;

                router.dispatch(this.scope, 'create', {
                    attributes: attributes
                });

                router.navigate(url, {trigger: false});
            });
        });
    }
}

export default PortalUserListView;
