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

import DefaultRowActionsView from 'views/record/row-actions/default';

class EmailDefaultRowActionView extends DefaultRowActionsView {

    setup() {
        super.setup();

        this.listenTo(this.model, 'change:isImportant change:inTrash change:groupStatusFolder', () => {
            setTimeout(() => this.reRender(), 10);
        });
    }

    getActionList() {
        /** @type {module:views/record/list~rowAction[]} */
        let list = [{
            action: 'quickView',
            label: 'View',
            data: {
                id: this.model.id
            },
            groupIndex: 0,
        }];

        if (
            this.model.get('createdById') === this.getUser().id && this.model.get('status') === 'Draft' &&
            !this.model.attributes.inTrash
        ) {
            list.push({
                action: 'send',
                label: 'Send',
                data: {
                    id: this.model.id,
                },
            });
        }

        if (this.options.acl.edit) {
            list = list.concat([
                {
                    action: 'quickEdit',
                    label: 'Edit',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 0,
                },
            ]);
        }

        if (this.model.get('isUsers')) {
            if (!this.model.get('isImportant')) {
                if (!this.model.get('inTrash')) {
                    list.push({
                        action: 'markAsImportant',
                        label: 'Mark as Important',
                        data: {
                            id: this.model.id
                        },
                        groupIndex: 1,
                    });
                }
            } else {
                list.push({
                    action: 'markAsNotImportant',
                    label: 'Unmark Importance',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 1,
                });
            }
        }

        if (this.model.attributes.isUsers && !this.model.attributes.isRead) {
            list.push({
                action: 'markAsRead',
                label: 'Mark Read',
                data: {
                    id: this.model.id
                },
                groupIndex: 1,
            });
        }

        if (
            (this.model.attributes.isUsers && this.model.attributes.status !== 'Draft') ||
            this.model.attributes.groupFolderId
        ) {
            const inTrash = this.model.attributes.groupFolderId ?
                this.model.attributes.groupStatusFolder === 'Trash' :
                this.model.attributes.inTrash;

            const inArchive = this.model.attributes.groupFolderId ?
                this.model.attributes.groupStatusFolder === 'Archive' :
                this.model.attributes.inArchive;

            if (!inTrash) {
                list.push({
                    action: 'moveToTrash',
                    label: 'Move to Trash',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 2,
                });
            } else {
                list.push({
                    action: 'retrieveFromTrash',
                    label: 'Retrieve from Trash',
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 2,
                });
            }

            if (!inArchive) {
                list.push({
                    action: 'moveToArchive',
                    text: this.getLanguage().translatePath('Email.actions.moveToArchive'),
                    data: {
                        id: this.model.id
                    },
                    groupIndex: 2,
                });
            }

            list.push({
                action: 'moveToFolder',
                label: 'Move to Folder',
                data: {
                    id: this.model.id
                },
                groupIndex: 2,
            });
        } else if (
            !this.model.attributes.isUsers &&
            !this.model.attributes.groupFolderId &&
            this.model.attributes.status === 'Archived'
        ) {
            list.push({
                action: 'moveToFolder',
                label: 'Move to Folder',
                data: {
                    id: this.model.id
                },
                groupIndex: 2,
            });
        }

        if (this.options.acl.delete) {
            list.push({
                action: 'quickRemove',
                label: 'Remove',
                data: {
                    id: this.model.id
                },
                groupIndex: 0,
            });
        }


        return list;
    }
}

export default EmailDefaultRowActionView;
