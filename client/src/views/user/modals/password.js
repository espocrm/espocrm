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

export default class extends ModalView {

    templateContent = '<div class="record no-side-margin">{{{record}}}</div>'

    className = 'dialog dialog-record'

    shortcutKeys = {
        'Control+Enter': 'apply',
    }

    setup() {
        this.buttonList = [
            {
                name: 'apply',
                label: 'Apply',
                style: 'danger',
                onClick: () => this.actionApply(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        this.headerHtml = '&nbsp';

        this.userModel = this.options.userModel;

        const model = this.model = new Model();
        model.name = 'UserSecurity';

        model.setDefs({
            fields: {
                'password': {
                    type: 'password',
                    required: true,
                },
            }
        });

        this.createView('record', 'views/record/edit-for-modal', {
            scope: 'None',
            selector: '.record',
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                name: 'password',
                                labelText: this.translate('yourPassword', 'fields', 'User'),
                                params: {
                                    readyToChange: true,
                                }
                            },
                            false
                        ]
                    ]
                }
            ],
        });
    }

    /**
     * @return {import('views/record/edit').default}
     */
    getRecordView() {
        return this.getView('record');
    }

    actionApply() {
        const data = this.getRecordView().processFetch();

        if (!data) {
            return;
        }

        this.trigger('proceed', data);
    }
}
