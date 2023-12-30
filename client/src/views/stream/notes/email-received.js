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

class EmailReceivedNoteStreamView extends NoteStreamView {

    template = 'stream/notes/email-received'
    isRemovable = false
    isSystemAvatar = true

    data() {
        return {
            ...super.data(),
            emailId: this.emailId,
            emailName: this.emailName,
            hasPost: this.hasPost,
            hasAttachments: this.hasAttachments,
            emailIconClassName: this.getMetadata().get(['clientDefs', 'Email', 'iconClass']) || '',
        };
    }

    setup() {
        let data = /** @type Object.<string, *> */this.model.get('data') || {};

        this.emailId = data.emailId;
        this.emailName = data.emailName;

        if (
            this.parentModel &&
            (
                this.model.get('parentType') === this.parentModel.entityType &&
                this.model.get('parentId') === this.parentModel.id
            )
        ) {
            if (this.model.get('post')) {
                this.createField('post', null, null, 'views/stream/fields/post');
                this.hasPost = true;
            }

            if ((this.model.get('attachmentsIds') || []).length) {
                this.createField(
                    'attachments',
                    'attachmentMultiple',
                    {},
                    'views/stream/fields/attachment-multiple'
                );

                this.hasAttachments = true;
            }
        }

        this.messageData['email'] =
            $('<a>')
                .attr('href', '#Email/view/' + data.emailId)
                .text(data.emailName);

        this.messageName = 'emailReceived';

        if (data.isInitial) {
            this.messageName += 'Initial';
        }

        if (data.personEntityId) {
            this.messageName += 'From';

            this.messageData['from'] =
                $('<a>')
                    .attr('href', '#' + data.personEntityType + '/view/' + data.personEntityId)
                    .text(data.personEntityName);
        }

        if (
            this.model.get('parentType') === data.personEntityType &&
            this.model.get('parentId') === data.personEntityId
        ) {
            this.isThis = true;
        }

        if (this.isThis) {
            this.messageName += 'This';
        }

        this.createMessage();
    }
}

export default EmailReceivedNoteStreamView;
