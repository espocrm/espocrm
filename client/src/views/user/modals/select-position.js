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

export default class UserSelectPositionModalView extends ModalView {

    templateContent = '<div class="record no-side-margin">{{{record}}}</div>'

    className = 'dialog dialog-record'

    shortcutKeys = {
        'Control+Enter': 'apply',
    }

    /**
     * @param {{
     *     positionList: string[],
     *     position: string|null,
     *     name: string,
     *     onApply: function(string|null),
     * }} options
     */
    constructor(options) {
        super(options);

        /** @private */
        this.props = options;
    }

    setup() {
        this.headerText =
            this.translate('changePosition', 'actions', 'User') + ' · ' +
            this.props.name;

        this.buttonList = [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                onClick: () => this.apply(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
            }
        ];

        this.model = new Model();
        this.model.setMultiple({position: this.props.position});

        this.recordView = new EditForModalRecordView({
            model: this.model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new EnumFieldView({
                                    name: 'position',
                                    params: {
                                        options: ['', ...this.props.positionList],
                                    },
                                    labelText: this.translate('teamRole', 'fields', 'User'),
                                }),
                            },
                            false
                        ]
                    ]
                }
            ]
        });

        this.assignView('record', this.recordView, '.record');
    }

    /**
     * @private
     */
    apply() {
        if (this.recordView.validate()) {
            return;
        }

        this.props.onApply(this.model.attributes.position);
        this.close();
    }

    onBackdropClick() {
        if (this.recordView.isChanged) {
            return;
        }

        this.close();
    }
}
