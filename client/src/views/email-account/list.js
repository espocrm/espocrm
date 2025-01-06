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

class EmailAccountListView extends ListView {

    keepCurrentRootUrl = true

    setup() {
        this.options.params = this.options.params || {};
        const params = this.options.params || {};

        this.userId = params.userId;

        super.setup();

        if (this.userId) {
            this.collection.where = [{
                type: 'equals',
                field: 'assignedUserId',
                value: params.userId,
            }];
        }
    }

    setupSearchPanel() {
        if (this.userId || !this.getUser().isAdmin()) {
            this.searchPanel = false;

            this.searchManager.reset();

            return;
        }

        super.setupSearchPanel();
    }

    getCreateAttributes() {
        const attributes = {};

        if (this.options.params.userId) {
            attributes.assignedUserId = this.options.params.userId;
            attributes.assignedUserName = this.options.params.userName || this.options.params.userId;
        }

        return attributes;
    }
}

export default EmailAccountListView;
