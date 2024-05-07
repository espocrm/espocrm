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

import DefaultRowActionsView from 'views/record/row-actions/default';

class StreamDefaultNoteRowActionsView extends DefaultRowActionsView {

    pinnedMaxCount

    setup() {
        super.setup();

        /** @type import('model').default */
        this.parentModel = this.options.parentModel;

        if (this.options.isThis && this.parentModel) {
            this.listenTo(this.model, 'change:isPinned', () => this.reRender());
            this.listenToOnce(this.parentModel, 'acl-edit-ready', () => this.reRender());

            this.pinnedMaxCount = this.getConfig().get('notePinnedMaxCount');
        }
    }

    getActionList() {
        const list = [];

        if (this.options.acl.edit && this.options.isEditable) {
            list.push({
                action: 'quickEdit',
                label: 'Edit',
                data: {
                    id: this.model.id,
                },
                groupIndex: 0,
            });
        }

        if (this.options.acl.edit && this.options.isRemovable) {
            list.push({
                action: 'quickRemove',
                label: 'Remove',
                data: {
                    id: this.model.id,
                },
                groupIndex: 0,
            });
        }

        if (
            this.options.isThis &&
            ['Post', 'EmailReceived', 'EmailSent'].includes(this.model.get('type')) &&
            this.parentModel &&
            this.getAcl().checkModel(this.parentModel, 'edit')
        ) {
            if (this.model.get('isPinned')) {
                list.push({
                    action: 'unpin',
                    label: 'Unpin',
                    data: {
                        id: this.model.id,
                    },
                    groupIndex: 1,
                });
            } else if (this.pinnedMaxCount > 0) {
                list.push({
                    action: 'pin',
                    label: 'Pin',
                    data: {
                        id: this.model.id,
                    },
                    groupIndex: 1,
                });
            }
        }

        return list;
    }
}

export default StreamDefaultNoteRowActionsView;
