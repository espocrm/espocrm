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

    rowActionsView = 'views/admin/auth-token/record/row-actions/default'
    massActionList = ['remove', 'setInactive']
    checkAllResultMassActionList = ['remove', 'setInactive']

    // noinspection JSUnusedGlobalSymbols
    massActionSetInactive() {
        let ids = null;
        const allResultIsChecked = this.allResultIsChecked;

        if (!allResultIsChecked) {
            ids = this.checkedList;
        }

        const attributes = {
            isActive: false,
        };

        Espo.Ajax
            .postRequest('MassAction', {
                action: 'update',
                entityType: this.entityType,
                params: {
                    ids: ids || null,
                    where: (!ids || ids.length === 0) ? this.getWhereForAllResult() : null,
                    searchParams: (!ids || ids.length === 0) ? this.collection.data : null,
                },
                data: attributes,
            })
            .then(() => {
                this.collection
                    .fetch()
                    .then(() => {
                        Espo.Ui.success(this.translate('Done'));

                        if (ids) {
                            ids.forEach(id => {
                                this.checkRecord(id);
                            });
                        }
                    });
            });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @param {Record} data
     */
    actionSetInactive(data) {
        if (!data.id) {
            return;
        }

        const model = this.collection.get(data.id);

        if (!model) {
            return;
        }

        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

        model
            .save({'isActive': false}, {patch: true})
            .then(() => {
                Espo.Ui.notify(false);
            });
    }
}
