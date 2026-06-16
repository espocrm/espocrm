/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import ModalView, {ModalViewOptions} from 'views/modal';
import Ajax from 'ajax';
import EditForModalRecordView from 'views/record/edit-for-modal';
import Model from 'model';
import DateTime from 'date-time';
import {inject} from 'di';
import DateFieldView from 'views/fields/date';
import Ui from 'ui';

export default class ResetFetchDataModalView extends ModalView<{
    model: Model,
    options: ModalViewOptions,
}> {

    templateContent = `
        <div
            class="alert alert-warning"
        >{{translate 'resetFetchData' category='messages' scope='EmailAccount'}}</div>
        <div class="record-container no-side-margin">{{{record}}}</div>
    `
    private recordView: EditForModalRecordView;

    private formModel: Model<{
        fetchSince: string | null;
    }>;

    @inject(DateTime)
    private dateTime: DateTime

    protected setup() {
        this.buttonList = [
            {
                name: 'reset',
                label: 'Reset',
                style: 'danger',
                onClick: () => this.processReset(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.close(),
            },
        ];

        this.formModel = new Model({
            fetchSince: this.dateTime.getToday(),
        });

        this.recordView = new EditForModalRecordView({
            model: this.formModel,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new DateFieldView({
                                    name: 'fetchSince',
                                    labelText: this.translate('fetchSince', 'fields', 'EmailAccount'),
                                    params: {
                                        required: true,
                                    },
                                })
                            },
                            false,
                        ]
                    ]
                }
            ],
        });

        this.assignView('record', this.recordView);
    }

    private async processReset() {
        if (this.recordView.validate()) {
            return;
        }

        await this.confirm({message: this.translate('confirmation', 'messages')});

        Ui.notifyWait();
        this.disableButton('reset');

        let response: Record<string, any>;

        try {
            response = await Ajax.postRequest(`${this.model.entityType!}/${this.model.id!}/resetFetchData`, {
                fetchSince: this.formModel.attributes.fetchSince,
            }) as Record<string, any>;
        } catch (e) {
            this.enableButton('reset');

            return;
        }

        this.model.setMultiple(response, {sync: true});

        Ui.success(this.translate('Done'));

        this.close();
    }
}
