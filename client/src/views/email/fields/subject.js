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

import VarcharFieldView from 'views/fields/varchar';

class EmailSubjectFieldView extends VarcharFieldView {

    listLinkTemplate = 'email/fields/subject/list-link'

    data() {
        const data = super.data();

        data.isRead = (this.model.get('sentById') === this.getUser().id) || this.model.get('isRead');
        data.isImportant = this.model.has('isImportant') && this.model.get('isImportant');
        data.hasAttachment = this.model.has('hasAttachment') && this.model.get('hasAttachment');
        data.isReplied = this.model.has('isReplied') && this.model.get('isReplied');

        data.inTrash = this.model.attributes.groupFolderId ?
            this.model.attributes.groupStatusFolder === 'Trash' :
            this.model.attributes.inTrash;

        data.inArchive = this.model.attributes.groupFolderId ?
            this.model.attributes.groupStatusFolder === 'Archive' :
            this.model.attributes.inArchive;

        data.style = null;

        if (data.isImportant) {
            data.style = 'warning';
        } else if (data.inTrash) {
            data.style = 'muted';
        } else if (data.inArchive) {
            data.style = 'info';
        }

        if (!data.isRead && !this.model.has('isRead')) {
            data.isRead = true;
        }

        if (!data.isNotEmpty) {
            if (
                this.model.get('name') !== null &&
                this.model.get('name') !== '' &&
                this.model.has('name')
            ) {
                data.isNotEmpty = true;
            }
        }

        return data;
    }

    getValueForDisplay() {
        return this.model.get('name');
    }

    getAttributeList() {
        return [
            'name',
            'subject',
            'isRead',
            'isImportant',
            'hasAttachment',
            'inTrash',
            'groupStatusFolder',
        ];
    }

    setup() {
        super.setup();

        this.events['click [data-action="showAttachments"]'] = e => {
            e.stopPropagation();

            this.showAttachments();
        }

        this.listenTo(this.model, 'change:isRead change:isImportant change:groupStatusFolder', () => {
            if (this.mode === this.MODE_LIST || this.mode === this.MODE_LIST_LINK) {
                this.reRender();
            }
        });
    }

    fetch() {
        const data = super.fetch();

        data.name = data.subject;

        return data;
    }

    showAttachments() {
        Espo.Ui.notifyWait();

        this.createView('dialog', 'views/email/modals/attachments', {model: this.model})
            .then(view => {
                view.render();

                Espo.Ui.notify(false);
            });
    }
}

export default EmailSubjectFieldView;
