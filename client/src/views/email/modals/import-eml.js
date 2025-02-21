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
import EditForModalRecordView from 'views/record/edit-for-modal';
import Model from 'model';
import FileFieldView from 'views/fields/file';

class ImportEmlModal extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `

    setup() {
        this.headerText = this.translate('Import EML', 'labels', 'Email');

        this.addButton({
            name: 'import',
            label: 'Proceed',
            style: 'danger',
            onClick: () => this.actionImport(),
        });

        this.addButton({
            name: 'cancel',
            label: 'Cancel',
            onClick: () => this.close(),
        });

        this.model = new Model({}, {entityType: 'ImportEml'});

        this.recordView = new EditForModalRecordView({
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new FileFieldView({
                                    name: 'file',
                                    params: {
                                        required: true,
                                        accept: ['.eml'],
                                    },
                                    labelText: this.translate('file', 'otherFields', 'Email'),
                                })
                            }
                        ]
                    ]
                }
            ]
        });

        this.assignView('record', this.recordView, '.record');
    }

    actionImport() {
        if (this.recordView.validate()) {
            return;
        }

        this.disableButton('import');
        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest('Email/importEml', {fileId: this.model.attributes.fileId})
            .then(/** {id: string} */response => {
                Espo.Ui.notify(false);

                this.getRouter().navigate(`Email/view/${response.id}`, {trigger: true});
            })
            .catch(() => this.enableButton('import'));
    }
}

export default ImportEmlModal;
