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

    template = 'personal-data/modals/personal-data'

    className = 'dialog dialog-record'
    backdrop = true

    setup() {
        super.setup();

        this.buttonList = [
            {
                name: 'cancel',
                label: 'Close'
            },
        ];

        this.headerText = this.getLanguage().translate('Personal Data');
        this.headerText += ': ' + this.model.get('name');

        if (this.getAcl().check(this.model, 'edit')) {
            this.buttonList.unshift({
                name: 'erase',
                label: 'Erase',
                style: 'danger',
                disabled: true,
                onClick: () => this.actionErase(),
            });
        }

        this.fieldList = [];

        this.scope = this.model.entityType;

        this.createView('record', 'views/personal-data/record/record', {
            selector: '.record',
            model: this.model,
        }, (view) => {
            this.listenTo(view, 'check', (fieldList) => {
                this.fieldList = fieldList;

                if (fieldList.length) {
                    this.enableButton('erase');
                } else {
                    this.disableButton('erase');
                }
            });

            if (!view.fieldList.length) {
                this.disableButton('export');
            }
        });
    }

    actionErase() {
        this.confirm({
            message: this.translate('erasePersonalDataConfirmation', 'messages'),
            confirmText: this.translate('Erase')
        }, () => {
            this.disableButton('erase');

            Espo.Ajax.postRequest('DataPrivacy/action/erase', {
                fieldList: this.fieldList,
                entityType: this.scope,
                id: this.model.id,
            }).then(() => {
                Espo.Ui.success(this.translate('Done'));

                this.trigger('erase');
            })
            .catch(() => {
                this.enableButton('erase');
            });
        });
    }
}
