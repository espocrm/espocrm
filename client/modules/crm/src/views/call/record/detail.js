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

import DetailRecordView from 'views/record/detail';

export default class extends DetailRecordView {

    duplicateAction = true

    setupActionItems() {
        super.setupActionItems();

        if (
            this.getAcl().checkModel(this.model, 'edit') &&
            this.getAcl().checkField(this.entityType, 'status', 'edit')
        ) {
            if (!['Held', 'Not Held'].includes(this.model.attributes.status)) {
                this.dropdownItemList.push({
                    label: 'Set Held',
                    name: 'setHeld',
                    onClick: () => this.actionSetHeld(),
                });

                this.dropdownItemList.push({
                    label: 'Set Not Held',
                    name: 'setNotHeld',
                    onClick: () => this.actionSetNotHeld(),
                });
            }
        }
    }

    manageAccessEdit(second) {
        super.manageAccessEdit(second);

        if (second) {
            if (!this.getAcl().checkModel(this.model, 'edit', true)) {
                this.hideActionItem('setHeld');
                this.hideActionItem('setNotHeld');
            }
        }
    }

    actionSetHeld() {
        this.model.save({status: 'Held'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.translate('Saved'));

                this.removeActionItem('setHeld');
                this.removeActionItem('setNotHeld');
            });
    }

    actionSetNotHeld() {
        this.model.save({status: 'Not Held'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.translate('Saved'));

                this.removeActionItem('setHeld');
                this.removeActionItem('setNotHeld');
            });
    }
}
