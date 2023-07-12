/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

/** @module views/admin/layouts/modals/create */

import ModalView from 'views/modal';
import EditForModalRecordView from 'views/record/edit-for-modal';
import Model from 'model';

class LayoutCreateModalView extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="complex-text-container">{{complexText info}}</div>
        <div class="record no-side-margin">{{{record}}}</div>    `

    className = 'dialog dialog-record'

    /**
     * @typedef {Object} module:views/admin/layouts/modals/create~data
     * @property {string} type
     * @property {string} name
     * @property {string} label
     */

    /**
     * @param {{scope: string}} options
     */
    constructor(options) {
        super();

        this.scope = options.scope;
    }

    data() {
        return {
            info: this.translate('createInfo', 'messages', 'LayoutManager'),
        }
    }

    setup() {
        this.headerText = this.translate('Create');

        this.buttonList = [
            {
                name: 'create',
                style: 'danger',
                label: 'Create',
                onClick: () => this.actionCreate(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        this.model = new Model({
            type: 'list',
            name: 'listForMyEntityType',
            label: 'List (for MyEntityType)',
        });

        this.recordView = new EditForModalRecordView({
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                name: 'type',
                                type: 'enum',
                                params: {
                                    readOnly: true,
                                    translation: 'Admin.layouts',
                                    options: ['list'],
                                },
                                labelText: this.translate('type', 'fields', 'Admin'),
                            },
                            false
                        ],
                        [
                            {
                                name: 'name',
                                type: 'varchar',
                                params: {
                                    required: true,
                                    noSpellCheck: true,
                                    pattern: '$latinLetters',
                                },
                                labelText: this.translate('name', 'fields'),
                            },
                            false
                        ],
                        [
                            {
                                name: 'label',
                                type: 'varchar',
                                params: {
                                    required: true,
                                    pattern: '$noBadCharacters',
                                },
                                labelText: this.translate('label', 'fields', 'Admin'),
                            },
                            false
                        ],
                    ]
                }
            ]
        });

        this.assignView('record', this.recordView, '.record');
    }

    actionCreate() {
        this.recordView.fetch();

        if (this.recordView.validate()) {
            return;
        }

        this.disableButton('create');

        Espo.Ui.notify(' ... ');

        Espo.Ajax
            .postRequest('Layout/action/create', {
                scope: this.scope,
                type: this.model.get('type'),
                name: this.model.get('name'),
                label: this.model.get('label'),
            })
            .then(() => {
                this.reRender();

                Espo.Ui.success('Created', {suppress: true});

                this.trigger('done');

                this.close();
            })
            .catch(() => {
                this.enableButton('create');
            });
    }
}

export default LayoutCreateModalView;
