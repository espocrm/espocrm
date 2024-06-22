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

import NoteStreamView from 'views/stream/note';

class AssignNoteStreamView extends NoteStreamView {

    template = 'stream/notes/assign'
    messageName = 'assign'

    init() {
        if (this.getUser().isAdmin()) {
            this.isRemovable = true;
        }

        super.init();
    }

    data() {
        return {
            ...super.data(),
            iconHtml: this.getIconHtml(),
        };
    }

    setup() {
        const data = this.model.get('data');

        this.assignedUserId = data.assignedUserId || null;
        this.assignedUserName = data.assignedUserName || data.assignedUserId || null;

        this.messageData['assignee'] =
            $('<span>')
                .addClass('nowrap name-avatar')
                .append(
                    this.getHelper().getAvatarHtml(data.assignedUserId, 'small', 16, 'avatar-link'),
                    $('<a>')
                        .attr('href', `#User/view/${data.assignedUserId}`)
                        .text(this.assignedUserName)
                        .attr('data-scope', 'User')
                        .attr('data-id', data.assignedUserId)
                );

        if (this.isUserStream) {
            if (this.assignedUserId) {
                if (this.assignedUserId === this.model.get('createdById')) {
                    this.messageName += 'Self';
                } else {
                    if (this.assignedUserId === this.getUser().id) {
                        this.messageName += 'You';
                    }
                }
            } else {
                this.messageName += 'Void';
            }
        } else {
            if (this.assignedUserId) {
                if (this.assignedUserId === this.model.get('createdById')) {
                    this.messageName += 'Self';
                }
            } else {
                this.messageName += 'Void';
            }
        }

        this.createMessage();
    }
}

export default AssignNoteStreamView;
