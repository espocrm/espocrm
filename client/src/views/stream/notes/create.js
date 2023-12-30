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

class CreateNoteStreamView extends NoteStreamView {

    template = 'stream/notes/create'
    assigned = false
    messageName = 'create'
    isRemovable = false

    data() {
        return {
            ...super.data(),
            statusText: this.statusText,
            statusStyle: this.statusStyle,
        };
    }

    setup() {
        if (this.model.get('data')) {
            this.setupData();
        }

        this.createMessage();
    }

    setupData() {
        let data = /** @type Object.<string, *> */this.model.get('data');

        this.assignedUserId = data.assignedUserId || null;
        this.assignedUserName = data.assignedUserName || null;

        this.messageData['assignee'] =
            $('<a>')
                .attr('href', '#User/view/' + this.assignedUserId)
                .text(this.assignedUserName);

        let isYou = false;

        if (this.isUserStream) {
            if (this.assignedUserId === this.getUser().id) {
                isYou = true;
            }
        }

        if (this.assignedUserId) {
            this.messageName = 'createAssigned';

            if (this.isThis) {
                this.messageName += 'This';

                if (this.assignedUserId === this.model.get('createdById')) {
                    this.messageName += 'Self';
                }
            } else {
                if (this.assignedUserId === this.model.get('createdById')) {
                    this.messageName += 'Self';
                }
                else if (isYou) {
                    this.messageName += 'You';
                }
            }
        }

        if (data.statusField) {
            let statusField = this.statusField = data.statusField;
            let statusValue = data.statusValue;

            this.statusStyle = data.statusStyle || 'default';
            this.statusText = this.getLanguage()
                .translateOption(statusValue, statusField, this.model.get('parentType'));
        }
    }
}

export default CreateNoteStreamView;

