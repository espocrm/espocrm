/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import EditRecordView from 'views/record/edit';

class RoleEditRecordView extends EditRecordView {

    tableView = 'views/role/record/table'

    sideView = false
    isWide = true
    stickButtonsContainerAllTheWay = true

    fetch() {
        const data = super.fetch();

        data['data'] = {};

        const scopeList = this.getTableView().scopeList;
        const actionList = this.getTableView().actionList;
        const aclTypeMap = this.getTableView().aclTypeMap;

        for (const i in scopeList) {
            const scope = scopeList[i];

            if (this.$el.find('select[name="' + scope + '"]').val() === 'not-set') {
                continue;
            }

            if (this.$el.find('select[name="' + scope + '"]').val() === 'disabled') {
                data['data'][scope] = false;

                continue;
            }

            let o = true;

            if (aclTypeMap[scope] !== 'boolean') {
                o = {};

                for (const j in actionList) {
                    const action = actionList[j];

                    o[action] = this.$el.find('select[name="' + scope + '-' + action + '"]').val();
                }
            }

            data['data'][scope] = o;
        }

        data['data'] = this.getTableView().fetchScopeData();
        data['fieldData'] = this.getTableView().fetchFieldData();

        return data;
    }

    setup() {
        super.setup();

        this.createView('extra', this.tableView, {
            mode: 'edit',
            selector: '.extra',
            model: this.model,
        }, view => {
            this.listenTo(view, 'change', () => {
                const data = this.fetch();

                this.model.set(data);
            });
        });
    }

    /**
     * @return {import('./table').default}
     */
    getTableView() {
        return this.getView('extra');
    }
}

export default RoleEditRecordView;
