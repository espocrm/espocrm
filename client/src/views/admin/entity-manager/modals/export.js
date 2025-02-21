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
import Model from 'model';
import EditForModalRecordView from 'views/record/edit-for-modal';
import VarcharFieldView from 'views/fields/varchar';

class EntityManagerExportModalView extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `

    setup() {
        this.headerText = this.translate('Export');

        this.buttonList = [
            {
                name: 'export',
                label: 'Export',
                style: 'danger',
                onClick: () => this.export(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        /** @type {Record} */
        const manifest = this.getConfig().get('customExportManifest') || {};

        this.model = new Model({
            name: manifest.name ?? null,
            module: manifest.module ?? null,
            version: manifest.version ?? '0.0.1',
            author: manifest.author ?? null,
            description: manifest.description ?? null,
        });

        this.recordView = new EditForModalRecordView({
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'name',
                                    labelText: this.translate('name', 'fields'),
                                    params: {
                                        pattern: '$latinLettersDigitsWhitespace',
                                        required: true,
                                    },

                                }),
                            },
                            {
                                view: new VarcharFieldView({
                                    name: 'module',
                                    labelText: this.translate('module', 'fields', 'EntityManager'),
                                    params: {
                                        pattern: '[A-Z][a-z][A-Za-z]+',
                                        required: true,
                                    },
                                }),
                            },
                        ],
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'version',
                                    labelText: this.translate('version', 'fields', 'EntityManager'),
                                    params: {
                                        pattern: '[0-9]+\\.[0-9]+\\.[0-9]+',
                                        required: true,
                                    },
                                }),
                            },
                            false
                        ],
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'author',
                                    labelText: this.translate('author', 'fields', 'EntityManager'),
                                    params: {
                                        required: true,
                                    },
                                }),

                            },
                            {
                                view: new VarcharFieldView({
                                    name: 'description',
                                    labelText: this.translate('description', 'fields'),
                                    params: {},

                                }),
                            },
                        ],
                    ]
                }
            ]
        });

        this.assignView('record', this.recordView);
    }

    export() {
        const data = this.recordView.fetch();

        if (this.recordView.validate()) {
            return;
        }

        this.disableButton('export');

        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest('EntityManager/action/exportCustom', data)
            .then(response => {
                this.close();

                this.getConfig().set('customExportManifest', data);

                Espo.Ui.success(this.translate('Done'));

                window.location = this.getBasePath() + '?entryPoint=download&id=' + response.id;
            })
            .catch(() => this.enableButton('create'));
    }
}

export default EntityManagerExportModalView;
