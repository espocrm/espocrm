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

class DuplicateModalView extends ModalView {

    template = 'modals/duplicate'

    cssName = 'duplicate-modal'

    data() {
        return {
            scope: this.scope,
            duplicates: this.duplicates,
        };
    }

    setup() {
        let saveLabel = 'Save';

        if (this.model && this.model.isNew()) {
            saveLabel = 'Create';
        }

        this.buttonList = [
            {
                name: 'save',
                label: saveLabel,
                style: 'danger',
                onClick: dialog => {
                    this.trigger('save');

                    dialog.close();
                },
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        this.scope = this.options.scope;
        this.duplicates = this.options.duplicates;

        if (this.scope) {
            this.setupRecord();
        }
    }

    setupRecord() {
        let promise = new Promise(resolve => {
            this.getHelper().layoutManager.get(this.scope, 'listSmall', layout => {
                layout = Espo.Utils.cloneDeep(layout);
                layout.forEach(item => item.notSortable = true);

                this.getCollectionFactory().create(this.scope)
                    .then(collection => {
                        collection.add(this.duplicates);

                        this.createView('record', 'views/record/list', {
                            selector: '.list-container',
                            collection: collection,
                            listLayout: layout,
                            buttonsDisabled: true,
                            massActionsDisabled: true,
                            rowActionsDisabled: true,
                        });

                        resolve();
                    });
            });
        })

        this.wait(promise);
    }
}

export default DuplicateModalView;
