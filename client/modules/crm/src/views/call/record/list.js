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

import ListRecordView from 'views/record/list';

export default class extends ListRecordView {

    rowActionsView = 'modules/crm/views/call/record/row-actions/default'

    setup() {
        super.setup();

        if (
            this.getAcl().checkScope(this.entityType, 'edit') &&
            this.getAcl().checkField(this.entityType, 'status', 'edit')
        ) {
            this.massActionList.push('setHeld');
            this.massActionList.push('setNotHeld');
        }
    }

    /**
     * @protected
     * @param {Record} data
     */
    async actionSetHeld(data) {
        const id = data.id;

        if (!id) {
            return;
        }

        const model = this.collection.get(id);

        if (!model) {
            return;
        }

        Espo.Ui.notify(this.translate('saving', 'messages'));

        await model.save({status: 'Held'}, {patch: true});

        Espo.Ui.success(this.translate('Saved'));
    }

    /**
     * @protected
     * @param {Record} data
     */
    async actionSetNotHeld(data) {
        const id = data.id;

        if (!id) {
            return;
        }

        const model = this.collection.get(id);

        if (!model) {
            return;
        }

        Espo.Ui.notify(this.translate('saving', 'messages'));

        await model.save({status: 'Not Held'}, {patch: true});

        Espo.Ui.success(this.translate('Saved'));
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionSetHeld() {
        const data = {};

        data.ids = this.checkedList;

        Espo.Ui.notify(this.translate('saving', 'messages'));

        await Espo.Ajax.postRequest(`${this.collection.entityType}/action/massSetHeld`, data);

        Espo.Ui.success(this.translate('Saved'));

        await this.collection.fetch();

        data.ids.forEach(id => {
            if (this.collection.get(id)) {
                this.checkRecord(id);
            }
        });
    }

    // noinspection JSUnusedGlobalSymbols
    async massActionSetNotHeld() {
        const data = {};

        data.ids = this.checkedList;

        Espo.Ui.notify(this.translate('saving', 'messages'));

        await Espo.Ajax.postRequest(`${this.collection.entityType}/action/massSetNotHeld`, data);

        Espo.Ui.success(this.translate('Saved'));

        await this.collection.fetch();

        data.ids.forEach(id => {
            if (this.collection.get(id)) {
                this.checkRecord(id);
            }
        });
    }
}
