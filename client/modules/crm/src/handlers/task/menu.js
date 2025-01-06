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

import ActionHandler from 'action-handler';

class TaskMenuHandler extends ActionHandler {

    complete() {
        const model = this.view.model;

        model
            .save({status: 'Completed'}, {patch: true})
            .then(() => {
                Espo.Ui.success(this.view.getLanguage().translateOption('Completed', 'status', 'Task'));
            });
    }

    // noinspection JSUnusedGlobalSymbols
    isCompleteAvailable() {
        const status = this.view.model.get('status');

        const view = /** @type {module:views/detail} */this.view;

        if (view.getRecordView().isEditMode()) {
            return false;
        }

        /** @type {string[]} */
        const notActualStatuses = this.view.getMetadata().get('entityDefs.Task.fields.status.notActualOptions') || [];

        return !notActualStatuses.includes(status);
    }
}

export default TaskMenuHandler;
