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

import RowActionHandler from 'handlers/row-action';
import UserSelectPositionModalView from 'views/user/modals/select-position';

// noinspection JSUnusedGlobalSymbols
export default class ChangeUserTeamPositionRowActionHandler extends RowActionHandler {

    async process(model, action) {
        if (!model.collection || !model.collection.parentModel) {
            console.error(`Team model cannot be obtained.`);

            return;
        }

        const team = model.collection.parentModel;

        /** @type {string[]} */
        const positionList = team.attributes.positionList || [];
        const position = model.attributes.teamRole;

        const view = new UserSelectPositionModalView({
            position: position,
            positionList: positionList,
            name: model.attributes.name,
            onApply: position => {
                this.savePosition(team.id, model, position);
            },
        });

        await this.view.assignView('dialog', view);
        await view.render();
    }

    isAvailable(model, action) {
        if (!model.collection || !model.collection.parentModel) {
            return false;
        }

        if (!this.view.getAcl().checkModel(model, 'edit')) {
            return false;
        }

        if (!this.view.getUser().isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * @private
     * @param {string} teamId
     * @param {import('model').default} model
     * @param {string|null} position
     */
    async savePosition(teamId, model, position) {
        Espo.Ui.notifyWait();

        await Espo.Ajax.putRequest(`Team/${teamId}/userPosition`, {
            id: model.id,
            position: position,
        });

        model.setMultiple({teamRole: position});

        Espo.Ui.success(this.view.translate('Saved'));
    }
}
