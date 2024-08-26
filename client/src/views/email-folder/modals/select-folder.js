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

import ModalView from 'views/modal';

export default class extends ModalView {

    template = 'email-folder/modals/select-folder'

    cssName = 'select-folder'
    fitHeight = true
    backdrop = true

    data() {
        return {
            folderDataList: this.folderDataList,
        };
    }

    setup() {
        this.addActionHandler('selectFolder', (e, target) => {
            const id = target.dataset.id;
            const name = target.dataset.name;

            this.trigger('select', id, name);
            this.close();
        });

        this.headerText = this.options.headerText || '';

        if (this.headerText === '') {
            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel',
            });
        }

        Espo.Ui.notify(' ... ');

        this.wait(
            Espo.Ajax.getRequest('EmailFolder/action/listAll')
                .then(data => {
                    Espo.Ui.notify(false);

                    this.folderDataList = data.list
                        .filter(item => {
                            return [
                                'inbox',
                                'important',
                                'sent',
                                'drafts',
                                'trash',
                                'archive',
                            ].indexOf(item.id) === -1;
                        })
                        .map(item => {
                            return {
                                id: item.id,
                                name: item.name,
                                isGroup: item.id.indexOf('group:') === 0,
                            };
                        });

                    this.folderDataList.unshift({
                        id: 'inbox',
                        name: this.translate('inbox', 'presetFilters', 'Email'),
                    });

                    this.folderDataList.push({
                        id: 'archive',
                        name: this.translate('archive', 'presetFilters', 'Email'),
                    });
                })
        );
    }
}
