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
import DatetimeFieldView from 'views/fields/datetime';
import moment from 'moment';

// noinspection JSUnusedGlobalSymbols
export default class EmailScheduleSendModalView extends ModalView {

    // language=Handlebars
    templateContent = `<div class="record no-side-margin">{{{record}}}</div>`

    /**
     * @type {Model}
     */
    formModel

    /**
     * @type {EditForModalRecordView}
     */
    recordView

    /**
     * @param {{
     *     model: import('model').default,
     *     onSave: function(): void,
     * }} options
     */
    constructor(options) {
        super(options);

        this.onSave = options.onSave;
    }

    setup() {
        this.headerText = this.translate('Schedule Send', 'labels', 'Email');

        this.buttonList.push({
            name: 'schedule',
            label: 'Schedule',
            style: 'danger',
            onClick: () => this.actionSchedule(),
        });

        this.buttonList.push({
            name: 'cancel',
            label: 'Cancel',
            onClick: () => this.close(),
        });

        this.formModel = new Model(
            {
                now: this.getDateTime().getNow(),
                sendAt: this.getSendAt(),
            }
        );

        this.recordView = new EditForModalRecordView({
            model: this.formModel,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new DatetimeFieldView({
                                    name: 'sendAt',
                                    labelText: this.translate('sendAt', 'fields', 'Email'),
                                    params: {
                                        required: true,
                                        after: 'now',
                                    },
                                    otherFieldLabelText: this.translate('Now'),
                                })
                            },
                            false
                        ]
                    ]
                }
            ],
        });

        this.assignView('record', this.recordView, '.record');
    }

    /**
     * @private
     * @return {string}
     */
    getSendAt() {
        const sendAtMoment = moment.utc(this.getDateTime().getNow(10));

        if (sendAtMoment.isBefore(moment().add(1, 'minutes'))) {
            sendAtMoment.add(10, 'minutes');
        }

        return sendAtMoment.format(this.getDateTime().internalDateTimeFormat);
    }

    async actionSchedule() {
        if (this.recordView.validate()) {
            return;
        }

        this.disableButton('schedule');
        Espo.Ui.notifyWait();

        this.model.set({
            status: 'Draft',
            sendAt: this.formModel.attributes.sendAt,
        });

        try {
            await this.model.save();
        } catch (e) {
            this.enableButton('schedule');

            return;
        }

        const name = this.model.attributes.subject;
        const url = `#Email/view/${this.model.id}`;

        const message = this.translate('Scheduled') + '\n' + `[${name}](${url})`;

        Espo.Ui.notify(message, 'success', 4000);

        this.onSave();
    }
}
