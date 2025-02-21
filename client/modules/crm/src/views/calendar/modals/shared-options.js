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

export default class TimelineSharedOptionsModalView extends ModalView {

    className = 'dialog dialog-record'

    templateContent = `
        <div class="panel panel-default no-side-margin">
            <div class="panel-body">
                <div class="record-container">{{{record}}}</div>
            </div>
        </div>
    `

    /**
     *
     * @param {{
     *     users: {id: string, name: string}[],
     *     onApply: function({
     *         users: {id: string, name: string}[],
     *     }),
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;
    }

    setup() {
        this.buttonList = [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                onClick: () => this.actionSave(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.actionClose(),
            },
        ];

        this.headerText = this.translate('timeline', 'modes', 'Calendar') + ' · ' +
            this.translate('Shared Mode Options', 'labels', 'Calendar')

        const users = this.options.users;

        const userIdList = [];
        const userNames = {};

        users.forEach(item => {
            userIdList.push(item.id);

            userNames[item.id] = item.name;
        });

        this.model = new Model({
            usersIds: userIdList,
            usersNames: userNames,
        });

        this.createView('record', 'crm:views/calendar/record/shared-options', {
            model: this.model,
            selector: '.record-container',
        });

    }

    /**
     * @private
     * @return {import('views/record/edit').default}
     */
    getRecordView() {
        return this.getView('record');
    }

    /**
     * @private
     */
    actionSave() {
        const data = this.getRecordView().processFetch();

        if (this.getRecordView().validate()) {
            return;
        }

        /** @type {{id: string, name: string}[]} */
        const users = [];

        const userIds = this.model.attributes.usersIds || [];

        userIds.forEach(id => {
            users.push({
                id: id,
                name: (data.usersNames || {})[id] || id
            });
        });

        this.options.onApply({users: users})

        this.close();
    }
}
