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

import BaseNotificationItemView from 'views/notification/items/base';

class AssignNotificationItemView extends BaseNotificationItemView {

    messageName = 'assign'

    template = 'notification/items/assign'

    setup() {
        const data = this.model.get('data') || {};

        this.userId = data.userId;

        this.messageData['entityType'] = this.translateEntityType(data.entityType);

        this.messageData['entity'] =
            $('<a>')
                .attr('href', '#' + data.entityType + '/view/' + data.entityId)
                .attr('data-id', data.entityId)
                .attr('data-scope', data.entityType)
                .text(data.entityName);

        this.messageData['user'] =
            $('<a>')
                .attr('href', '#User/view/' + data.userId)
                .attr('data-id', data.userId)
                .attr('data-scope', 'User')
                .text(data.userName);

        this.createMessage();
    }
}

export default AssignNotificationItemView;
