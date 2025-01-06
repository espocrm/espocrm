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

import NoteStreamView from 'views/stream/note';

/** @module views/stream/notes/assign */

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

    /**
     * @typedef {{
     *    assignedUserId?: string,
     *     assignedUserName?: string,
     *     addedAssignedUsers?: {id: string, name: string|null}[],
     *     removedAssignedUsers?: {id: string, name: string|null}[],
     * }} module:views/stream/notes/assign~data
     */

    setup() {
        this.setupData();

        this.createMessage();
    }

    setupData() {
        /** @type {module:views/stream/notes/assign~data} */
        const data = this.model.get('data') || {};

        this.assignedUserId = data.assignedUserId || null;
        this.assignedUserName = data.assignedUserName || data.assignedUserId || null;

        if (data.addedAssignedUsers) {
            this.setupDataMulti(data);

            if (this.isThis) {
                this.messageName += 'This';
            }

            return;
        }

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

            return;
        }

        if (this.assignedUserId) {
            if (this.assignedUserId === this.model.get('createdById')) {
                this.messageName += 'Self';
            }

            return;
        }

        this.messageName += 'Void';
    }

    /**
     * @private
     * @param {module:views/stream/notes/assign~data} data
     */
    setupDataMulti(data) {
        this.messageName = 'assignMultiAdd';

        const added = data.addedAssignedUsers;
        const removed = data.removedAssignedUsers;

        if (!added || !removed) {
            return;
        }

        if (added.length && removed.length) {
            this.messageName = 'assignMultiAddRemove';
        } else if (removed.length) {
            this.messageName = 'assignMultiRemove';
        }

        if (added.length) {
            this.messageData['assignee'] = this.createUsersElement(added);
        }

        if (removed.length) {
            this.messageData['removedAssignee'] = this.createUsersElement(removed);
        }
    }

    /**
     * @private
     * @param {{id: string, name: ?string}[]} users
     * @return {HTMLElement}
     */
    createUsersElement(users) {
        const wrapper = document.createElement('span');

        users.forEach((it, i) => {
            const a = document.createElement('a');
            a.href = `#User/view/${it.id}`;
            a.text = it.name || it.id;
            a.dataset.id = it.id;
            a.dataset.scope = 'User';

            const span = document.createElement('span');
            span.className = 'nowrap name-avatar';
            span.innerHTML = this.getHelper().getAvatarHtml(it.id, 'small', 16, 'avatar-link');
            span.appendChild(a);

            wrapper.appendChild(span);

            if (i < users.length - 1) {
                wrapper.appendChild(document.createTextNode(', '));
            }
        });

        return wrapper;
    }
}

export default AssignNoteStreamView;
