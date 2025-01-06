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

import LinkFieldView from 'views/fields/link';

export default class extends LinkFieldView {

    createDisabled = true
    autocompleteDisabled = true

    getSelectFilters() {
        if (this.getUser().isAdmin() && this.model.get('assignedUserId')) {
            return {
                assignedUser: {
                    type: 'equals',
                    attribute: 'assignedUserId',
                    value: this.model.get('assignedUserId'),
                    data: {
                        type: 'is',
                        nameValue: this.model.get('assignedUserName'),
                    },
                }
            };
        }
    }

    setup() {
        super.setup();

        this.listenTo(this.model, 'change:assignedUserId', (model, e, o) => {
            if (!o.ui) {
                return;
            }

            this.model.set({
                emailFolderId: null,
                emailFolderName: null,
            });
        });
    }
}
