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

import ActionHandler from 'action-handler';
import {inject} from 'di';
import Metadata from 'metadata';
import Ui from 'ui';

export default class TaskMenuHandler extends ActionHandler {

    /**
     * @type {string[]}
     * @private
     */
    historyStatusList

    /**
     * @private
     * @type {string|null}
     */
    completedStatusValue

    @inject(Metadata)
    metadata

    constructor(view) {
        super(view);

        /** @var string[]*/
        const completedStatusList = this.metadata.get(`scopes.Task.completedStatusList`, []);

        this.historyStatusList = [
            ...completedStatusList,
            ...this.metadata.get(`scopes.Task.canceledStatusList`, []),
        ];

        this.completedStatusValue = completedStatusList[0] ?? null;
    }

    async complete() {
        const model = this.view.model;

        Ui.notifyWait();

        await model.save({status: this.completedStatusValue}, {patch: true});

        Ui.success(this.view.getLanguage().translateOption('Completed', 'status', 'Task'));
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @return {boolean}
     */
    isCompleteAvailable() {
        const status = this.view.model.attributes.status;

        const view = /** @type {import('views/detail').default} */this.view;

        if (view.getRecordView().isEditMode()) {
            return false;
        }

        return !this.historyStatusList.includes(status);
    }
}
