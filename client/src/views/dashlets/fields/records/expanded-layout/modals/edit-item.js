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
import EditForModalRecordView from 'views/record/edit-for-modal';
import Model from 'model';
import BoolFieldView from 'views/fields/bool';

export default class ExpandedLayoutEditItemModalFieldView extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `

    /**
     * @private
     * @type {EditForModalRecordView}
     */
    recordView

    /**
     * @private
     * @type {Model}
     */
    formModel

    /**
     * @param {{
     *     onApply: function({soft: boolean, small: boolean}),
     *     label: string,
     *     data: {
     *         soft: boolean,
     *         small: boolean,
     *     },
     * }} options
     */
    constructor(options) {
        super();

        this.options = options;
    }

    setup() {
        this.headerText = this.translate('Edit') + ' · ' + this.options.label;

        this.formModel = new Model();
        this.formModel.setMultiple({...this.options.data});

        this.recordView = new EditForModalRecordView({
            model: this.formModel,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new BoolFieldView({
                                    name: 'soft',
                                    labelText: this.translate('soft', 'otherFields', 'DashletOptions'),
                                }),
                            },
                            {
                                view: new BoolFieldView({
                                    name: 'small',
                                    labelText: this.translate('small', 'otherFields', 'DashletOptions'),
                                }),
                            },
                        ],
                    ]
                }
            ]
        });

        this.assignView('record', this.recordView);

        this.buttonList = [
            {
                name: 'apply',
                style: 'danger',
                label: 'Apply',
                onClick: () => this.actionApply(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.actionClose(),
            },
        ]
    }

    /**
     * @private
     */
    actionApply() {
        if (this.recordView.validate()) {
            return;
        }

        this.options.onApply({
            soft: this.formModel.attributes.soft,
            small: this.formModel.attributes.small,
        });

        this.close();
    }
}
