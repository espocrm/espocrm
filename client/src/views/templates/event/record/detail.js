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

import DetailRecordView from 'views/record/detail';

export default class extends DetailRecordView {

    setup() {
        super.setup();

        /** @type string[] */
        const options = this.model.getFieldParam('status', 'options') ?? [];

        if (options.includes('Held')) {
            this.dropdownItemList.push({
                labelTranslation: `${this.scope}.labels.Set Held`,
                name: 'setHeld',
                onClick: () => this.actionSetHeld(),
            });
        }

        if (options.includes('Not Held')) {
            this.dropdownItemList.push({
                labelTranslation: `${this.scope}.labels.Set Not Held`,
                name: 'setNotHeld',
                onClick: () => this.actionSetNotHeld(),
            });
        }

        this.controlHeldButtons();

        this.model.onSync({
            owner: this,
            callback: () => this.controlHeldButtons(),
        });
    }

    actionSetHeld() {
        this.model
            .save({status: 'Held'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.translate('Saved', 'labels', 'Meeting'));

                this.controlHeldButtons();
            });
    }

    actionSetNotHeld() {
        this.model
            .save({status: 'Not Held'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.translate('Saved', 'labels', 'Meeting'));
            });
    }

    /**
     * @private
     */
    controlHeldButtons() {
        if (
            this.getAcl().checkModel(this.model, 'edit') &&
            ['Held', 'Not Held'].includes(this.model.attributes.status)
        ) {
            this.hideActionItem('setHeld');
            this.hideActionItem('setNotHeld');
        } else {
            this.showActionItem('setHeld');
            this.showActionItem('setNotHeld');
        }
    }
}
