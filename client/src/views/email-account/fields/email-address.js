/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import EmailAddressFieldView from 'views/fields/email-address';

export default class extends EmailAddressFieldView {

    setup() {
        super.setup();

        this.on('change', () => {
            const emailAddress = this.model.get('emailAddress');

            this.model.set('name', emailAddress);
        });

        const userId = this.model.get('assignedUserId');

        if (this.getUser().isAdmin() && userId !== this.getUser().id) {
            Espo.Ajax.getRequest(`User/${userId}`).then((data) => {
                const list = [];

                if (data.emailAddress) {
                    list.push(data.emailAddress);

                    this.params.options = list;

                    if (data.emailAddressData) {
                        data.emailAddressData.forEach(item => {
                            if (item.emailAddress === data.emailAddress) {
                                return;
                            }

                            list.push(item.emailAddress);
                        });
                    }

                    this.reRender();
                }
            });
        }
    }

    setupOptions() {
        if (this.model.get('assignedUserId') === this.getUser().id) {
            this.params.options = this.getUser().get('userEmailAddressList');
        }
    }
}
