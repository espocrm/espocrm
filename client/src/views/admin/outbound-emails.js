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

import SettingsEditRecordView from 'views/settings/record/edit';

export default class extends SettingsEditRecordView {

    layoutName = 'outboundEmails'

    saveAndContinueEditingAction = false

    dynamicLogicDefs = {
        fields: {
            smtpUsername: {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                        {
                            type: 'isTrue',
                            attribute: 'smtpAuth',
                        }
                    ]
                },
                required: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                        {
                            type: 'isTrue',
                            attribute: 'smtpAuth',
                        }
                    ]
                }
            },
            smtpPassword: {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                        {
                            type: 'isTrue',
                            attribute: 'smtpAuth',
                        }
                    ]
                }
            },
            smtpPort: {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                    ]
                },
                required: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                    ]
                }
            },
            smtpSecurity: {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                    ]
                }
            },
            smtpAuth: {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isNotEmpty',
                            attribute: 'smtpServer',
                        },
                    ]
                }
            },
        },
    }

    afterRender() {
        super.afterRender();

        const smtpSecurityField = this.getFieldView('smtpSecurity');

        this.listenTo(smtpSecurityField, 'change', () => {
            const smtpSecurity = smtpSecurityField.fetch()['smtpSecurity'];

            if (smtpSecurity === 'SSL') {
                this.model.set('smtpPort', 465);
            } else if (smtpSecurity === 'TLS') {
                this.model.set('smtpPort', 587);
            } else {
                this.model.set('smtpPort', 25);
            }
        });
    }
}

