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

import DetailRecordView from 'views/record/detail';

export default class extends DetailRecordView {

    setup() {
        super.setup();

        this.setupFieldsBehaviour();
        this.initSslFieldListening();
        this.initSmtpFieldsControl();

        if (this.getUser().isAdmin()) {
            this.setFieldNotReadOnly('assignedUser');
        } else {
            this.setFieldReadOnly('assignedUser');
        }
    }

    modifyDetailLayout(layout) {
        layout.filter(panel => panel.tabLabel === '$label:SMTP').forEach(panel => {
            panel.rows.forEach(row => {
                row.forEach(item => {
                    const labelText = this.translate(item.name, 'fields', 'EmailAccount');

                    if (labelText && labelText.indexOf('SMTP ') === 0) {
                        item.labelText = Espo.Utils.upperCaseFirst(labelText.substring(5));
                    }
                });
            })
        });
    }

    setupFieldsBehaviour() {
        this.controlStatusField();

        this.listenTo(this.model, 'change:status', (model, value, o) => {
            if (o.ui) {
                this.controlStatusField();
            }
        });

        this.listenTo(this.model, 'change:useImap', (model, value, o) => {
            if (o.ui) {
                this.controlStatusField();
            }
        });

        if (this.wasFetched()) {
            this.setFieldReadOnly('fetchSince');
        } else {
            this.setFieldNotReadOnly('fetchSince');
        }
    }

    controlStatusField() {
        const list = ['username', 'port', 'host', 'monitoredFolders'];

        if (this.model.get('status') === 'Active' && this.model.get('useImap')) {
            list.forEach(item => {
                this.setFieldRequired(item);
            });

            return;
        }

        list.forEach(item => {
            this.setFieldNotRequired(item);
        });
    }

    wasFetched() {
        if (!this.model.isNew()) {
            return !!((this.model.get('fetchData') || {}).lastUID);
        }

        return false;
    }

    initSslFieldListening() {
        this.listenTo(this.model, 'change:security', (model, value, o) => {
            if (!o.ui) {
                return;
            }

            if (value === 'SSL') {
                this.model.set('port', 993);
            } else {
                this.model.set('port', 143);
            }
        });

        this.listenTo(this.model, 'change:smtpSecurity', (model, value, o) => {
            if (o.ui) {
                if (value === 'SSL') {
                    this.model.set('smtpPort', 465);
                } else if (value === 'TLS') {
                    this.model.set('smtpPort', 587);
                } else {
                    this.model.set('smtpPort', 25);
                }
            }
        });
    }

    initSmtpFieldsControl() {
        this.controlSmtpFields();

        this.listenTo(this.model, 'change:useSmtp', this.controlSmtpFields, this);
        this.listenTo(this.model, 'change:smtpAuth', this.controlSmtpFields, this);
    }

    controlSmtpFields() {
        if (this.model.get('useSmtp')) {
            this.showField('smtpHost');
            this.showField('smtpPort');
            this.showField('smtpAuth');
            this.showField('smtpSecurity');
            this.showField('smtpTestSend');

            this.setFieldRequired('smtpHost');
            this.setFieldRequired('smtpPort');

            this.controlSmtpAuthField();

            return;
        }

        this.hideField('smtpHost');
        this.hideField('smtpPort');
        this.hideField('smtpAuth');
        this.hideField('smtpUsername');
        this.hideField('smtpPassword');
        this.hideField('smtpAuthMechanism');
        this.hideField('smtpSecurity');
        this.hideField('smtpTestSend');

        this.setFieldNotRequired('smtpHost');
        this.setFieldNotRequired('smtpPort');
        this.setFieldNotRequired('smtpUsername');
    }

    controlSmtpAuthField() {
        if (this.model.get('smtpAuth')) {
            this.showField('smtpUsername');
            this.showField('smtpPassword');
            this.showField('smtpAuthMechanism');
            this.setFieldRequired('smtpUsername');

            return;
        }

        this.hideField('smtpUsername');
        this.hideField('smtpPassword');
        this.hideField('smtpAuthMechanism');
        this.setFieldNotRequired('smtpUsername');
    }
}
