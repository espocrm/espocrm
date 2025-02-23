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
import EditForModalRecordView from 'views/record/edit-for-modal';
import EnumFieldView from 'views/fields/enum';
import VarcharFieldView from 'views/fields/varchar';
import CalendarSharedViewTeamsFieldView from 'crm:views/calendar/fields/teams';

export default class CalendarEditViewModal extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `

    className = 'dialog dialog-record'

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView

    /**
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

        this.recordView = new EditForModalRecordView({
            model: model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'name',
                                    labelText: this.translate('name', 'fields'),
                                    params: {
                                        required: true,
                                    },
                                })
                            },
                            {
                                view: new EnumFieldView({
                                    name: 'mode',
                                    labelText: this.translate('mode', 'fields', 'DashletOptions'),
                                    params: {
                                        translation: 'DashletOptions.options.mode',
                                        options: this.getMetadata().get('clientDefs.Calendar.sharedViewModeList') || [],
                                    },
                                })
                            }
                        ],
                        [
                            {
                                view: new CalendarSharedViewTeamsFieldView({
                                    name: 'teams',
                                    labelText: this.translate('teams', 'fields'),
                                    params: {
                                        required: true
                                    },
                                })
                            },
                            false
                        ]
                    ]
                }
            ]
        });

        this.assignView('record', this.recordView);

        if (this.isNew) {
            this.headerText = this.translate('Create Shared View', 'labels', 'Calendar');
        } else {
            this.headerText = this.translate('Edit Shared View', 'labels', 'Calendar') + ' · ' +
                modelData.name;
        }
    }

    async actionSave() {
        if (this.recordView.validate()) {
            return;
        }

        const modelData = this.recordView.fetch();

        const calendarViewDataList = this.getPreferences().get('calendarViewDataList') || [];

        const data = {
            name: modelData.name,
            teamIdList: modelData.teamsIds,
            teamNames: modelData.teamsNames,
            mode: modelData.mode,
            id: undefined,
        };

        if (this.isNew) {
            data.id = Math.random().toString(36).substring(2, 12);

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

        this.disableButton('save');
        this.disableButton('remove');

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
