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

import ModalView from 'views/modal';

export default class extends ModalView {

    template = 'email-folder/modals/select-folder'

    cssName = 'select-folder'
    backdrop = true

    /** @const */
    FOLDER_ALL = 'all'
    /** @const */
    FOLDER_INBOX = 'inbox'
    /** @const */
    FOLDER_IMPORTANT = 'important'
    /** @const */
    FOLDER_SENT = 'sent'
    /** @const */
    FOLDER_DRAFTS = 'drafts'
    /** @const */
    FOLDER_TRASH = 'trash'
    /** @const */
    FOLDER_ARCHIVE = 'archive'

    data() {
        return {
            folderDataList: this.folderDataList,
        };
    }

    /**
     * @private
     * @type {string|undefined}
     */
    currentFolderId

    setup() {
        this.addActionHandler('selectFolder', (e, target) => {
            const id = target.dataset.id;
            const name = target.dataset.name;

            this.trigger('select', id, name);
            this.close();
        });

        this.headerText = this.options.headerText || '';
        this.isGroup = this.options.isGroup || false;
        this.noArchive = this.options.noArchive || false;
        this.currentFolderId = this.options.currentFolderId;

        if (this.headerText === '') {
            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel',
            });
        }

        Espo.Ui.notifyWait();

        this.wait(
            Espo.Ajax.getRequest('EmailFolder/action/listAll')
                .then(/** {list: {id: string, name: string}[]} */data => {
                    Espo.Ui.notify(false);

                    const builtInFolders = [
                        this.FOLDER_INBOX,
                        this.FOLDER_IMPORTANT,
                        this.FOLDER_SENT,
                        this.FOLDER_DRAFTS,
                        this.FOLDER_TRASH,
                        this.FOLDER_ARCHIVE,
                    ];

                    const iconMap = {
                        [this.FOLDER_ALL]: 'far fa-hdd',
                        [this.FOLDER_TRASH]: 'far fa-trash-alt',
                        [this.FOLDER_SENT]: 'far fa-paper-plane',
                        [this.FOLDER_INBOX]: 'fas fa-inbox',
                        [this.FOLDER_ARCHIVE]: 'far fa-caret-square-down',
                    };

                    this.folderDataList = data.list
                        .filter(item => {
                            if (this.isGroup && !item.id.startsWith('group:')) {
                                return false;
                            }

                            return !builtInFolders.includes(item.id);
                        })
                        .map(item => {
                            const isGroup = item.id.startsWith('group:');

                            return {
                                disabled: item.id === this.currentFolderId,
                                id: item.id,
                                name: item.name,
                                isGroup: isGroup,
                                iconClass: isGroup ? 'far fa-circle' : 'far fa-folder',
                            };
                        });

                    this.folderDataList.unshift({
                        id: 'inbox',
                        name: this.isGroup ?
                            this.translate('all', 'presetFilters', 'Email') :
                            this.translate('inbox', 'presetFilters', 'Email'),
                        iconClass: this.isGroup ?
                            iconMap[this.FOLDER_ALL] :
                            iconMap[this.FOLDER_INBOX],
                    });

                    if (!this.noArchive) {
                        this.folderDataList.push({
                            id: this.FOLDER_ARCHIVE,
                            name: this.translate('archive', 'presetFilters', 'Email'),
                            iconClass: iconMap[this.FOLDER_ARCHIVE],
                            disabled: this.currentFolderId === this.FOLDER_ARCHIVE,
                        });
                    }
                })
        );
    }
}
