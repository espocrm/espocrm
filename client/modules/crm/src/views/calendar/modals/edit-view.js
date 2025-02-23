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

export default class CalendarEditViewModal extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="panel panel-default no-side-margin">
            <div class="panel-body">
                <div class="record-container">{{{record}}}</div>
            </div>
        </div>
    `

    className ='dialog dialog-record'

    /**
     *
     * @param {{
     *     afterSave?: function({id: string}): void,
     *     afterRemove?: function(): void,
     *     id?: string,
     * }} options
     */
    constructor(options) {
        super();

        this.options = options;
    }

    setup() {
        const id = this.options.id;

        this.buttonList = [
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.actionCancel(),
            },
        ];

        this.isNew = !id;

        const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

        if (this.isNew) {
            this.buttonList.unshift({
                name: 'save',
                label: 'Create',
                style: 'danger',
                onClick: () => this.actionSave(),
            });
        } else {
            this.dropdownItemList.push({
                name: 'remove',
                label: 'Remove',
                onClick: () => this.actionRemove(),
            });

            this.buttonList.unshift({
                name: 'save',
                label: 'Save',
                style: 'primary',
                onClick: () => this.actionSave(),
            });
        }

        const model = new Model();

        model.name = 'CalendarView';

        const modelData = {};

        if (!this.isNew) {
            calendarViewDataList.forEach(item => {
                if (id === item.id) {
                    modelData.teamsIds = item.teamIdList || [];
                    modelData.teamsNames = item.teamNames || {};
                    modelData.id = item.id;
                    modelData.name = item.name;
                    modelData.mode = item.mode;
                }
            });
        } else {
            modelData.name = this.translate('Shared', 'labels', 'Calendar');

            let foundCount = 0;

            calendarViewDataList.forEach(item => {
                if (item.name.indexOf(modelData.name) === 0) {
                    foundCount++;
                }
            });

            if (foundCount) {
                modelData.name += ' ' + foundCount;
            }

            modelData.id = id;

            modelData.teamsIds = this.getUser().get('teamsIds') || [];
            modelData.teamsNames = this.getUser().get('teamsNames') || {};
        }

        model.set(modelData);

        this.createView('record', 'crm:views/calendar/record/edit-view', {
            selector: '.record-container',
            model: model
        });

        if (this.isNew) {
            this.headerText = this.translate('Create Shared View', 'labels', 'Calendar');
        } else {
            this.headerText = this.translate('Edit Shared View', 'labels', 'Calendar') + ' · ' +
                modelData.name;
        }
    }

    async actionSave() {
        const modelData = this.getView('record').fetch();

        this.getView('record').model.set(modelData);

        if (this.getView('record').validate()) {
            return;
        }

        this.disableButton('save');
        this.disableButton('remove');

        const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

        const data = {
            name: modelData.name,
            teamIdList: modelData.teamsIds,
            teamNames: modelData.teamsNames,
            mode: modelData.mode,
            id: undefined,
        };

        if (this.isNew) {
            data.id = Math.random().toString(36).substr(2, 10);

            calendarViewDataList.push(data);
        } else {
            data.id = this.getView('record').model.id;

            calendarViewDataList.forEach((item, i) => {
                if (item.id === data.id) {
                    calendarViewDataList[i] = data;
                }
            });
        }

        Espo.Ui.notify(this.translate('saving', 'messages'));

        try {
            await this.getPreferences().save(
                {
                    calendarViewDataList: calendarViewDataList,
                },
                {patch: true}
            )
        } catch (e) {
            this.enableButton('remove');
            this.enableButton('save');

            return;
        }

        Espo.Ui.notify();

        this.trigger('after:save', data);

        if (this.options.afterSave) {
            this.options.afterSave(data);
        }

        this.close();
    }

    async actionRemove() {
        await this.confirm(this.translate('confirmation', 'messages'));

        this.disableButton('save');
        this.disableButton('remove');

        const id = this.options.id;

        if (!id) {
            return;
        }

        const newCalendarViewDataList = [];

        const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

        calendarViewDataList.forEach(item => {
            if (item.id !== id) {
                newCalendarViewDataList.push(item);
            }
        });

        Espo.Ui.notifyWait();

        try {
            await this.getPreferences().save({
                calendarViewDataList: newCalendarViewDataList,
            }, {patch: true})
        } catch (e) {
            this.enableButton('remove');
            this.enableButton('save');

            return;
        }

        Espo.Ui.notify();

        this.trigger('after:remove');

        if (this.options.afterRemove) {
            this.options.afterRemove();
        }

        this.close();
    }
}
