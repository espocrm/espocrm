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

import EditRecordView from 'views/record/edit';

export default class extends EditRecordView {

    scriptAreaHeight = 400

    bottomView = null

    sideView = null

    dropdownItemList = []

    isWide = true
    accessControlDisabled = true
    saveAndContinueEditingAction = false
    saveAndNewAction = false
    shortcutKeyCtrlEnterAction = 'run'

    setup() {
        this.scope = 'Formula';

        this.buttonList = [
            {
                name: 'run',
                label: 'Run',
                style: 'danger',
                title: 'Ctrl+Enter',
                onClick: () => this.actionRun(),
            },
        ];

        const additionalFunctionDataList = [
            {
                "name": "output\\print",
                "insertText": "output\\print(VALUE)"
            },
            {
                "name": "output\\printLine",
                "insertText": "output\\printLine(VALUE)"
            }
        ];

        this.detailLayout = [
            {
                rows: [
                    [
                        false,
                        {
                            name: 'targetType',
                            labelText: this.translate('targetType', 'fields', 'Formula'),
                        },
                        {
                            name: 'target',
                            labelText: this.translate('target', 'fields', 'Formula'),
                        },
                    ]
                ]
            },
            {
                rows: [
                    [
                        {
                            name: 'script',
                            noLabel: true,
                            options: {
                                targetEntityType: this.model.get('targetType'),
                                height: this.scriptAreaHeight,
                                additionalFunctionDataList: additionalFunctionDataList,
                            },
                        },
                    ]
                ]
            },
            {
                name: 'output',
                rows: [
                    [
                        {
                            name: 'errorMessage',
                            labelText: this.translate('error', 'fields', 'Formula'),
                        },
                    ],
                    [
                        {
                            name: 'output',
                            labelText: this.translate('output', 'fields', 'Formula'),
                        },
                    ]
                ]
            },
        ];

        super.setup();

        if (!this.model.get('targetType')) {
            this.hideField('target');
        }
        else {
            this.showField('target');
        }

        this.controlTargetTypeField();
        this.listenTo(this.model, 'change:targetId', () => this.controlTargetTypeField());

        this.controlOutputField();
        this.listenTo(this.model, 'change', () => this.controlOutputField());
    }

    controlTargetTypeField() {
        if (this.model.get('targetId')) {
            this.setFieldReadOnly('targetType');

            return;
        }

        this.setFieldNotReadOnly('targetType');
    }

    controlOutputField() {
        if (this.model.get('errorMessage')) {
            this.showField('errorMessage');
        }
        else {
            this.hideField('errorMessage');
        }
    }

    actionRun() {
        this.model.trigger('run');
    }
}
