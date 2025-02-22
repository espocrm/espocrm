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

import ListView from 'views/list';

class PortalUserListView extends ListView {

    defaultOrderBy = 'createdAt'
    defaultOrder = 'desc'

    async actionCreate(data) {
        /**
         * @type {
         *     module:views/modals/select-records~Options &
         *     {onSkip: function()}
         * }
         */
        const options = {
            entityType: 'Contact',
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
            ],
            onSelect: models => {
                const model = models[0];

                const attributes = {};

                attributes.contactId = model.id;
                attributes.contactName = model.attributes.name;

                if (model.attributes.accountId) {
                    const names = {};
                    names[model.attributes.accountId] = model.attributes.accountName;

                    attributes.accountsIds = [model.attributes.accountId];
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

                const url = `#${this.scope}/create`;

                this.getRouter().dispatch(this.scope, 'create', {attributes: attributes});
                this.getRouter().navigate(url, {trigger: false});
            },
            onSkip: () => {
                const attributes = {
                    type: 'portal',
                };

                const url = `#${this.scope}/create`;

                this.getRouter().dispatch(this.scope, 'create', {attributes: attributes});
                this.getRouter().navigate(url, {trigger: false});
            },
        };

        // As the file is supposed to bundled separately, resort to async module loading.
        /** @type {typeof import('modules/crm/views/contact/modals/select-for-portal-user').default} */
        const SelectForPortalUserModalView =
            await Espo.loader.requirePromise('modules/crm/views/contact/modals/select-for-portal-user');

        const view = new SelectForPortalUserModalView(options);

        await this.assignView('modal', view);

        await view.render();
    }
}

export default PortalUserListView;
