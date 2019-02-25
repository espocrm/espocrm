/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/email-account/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupFieldsBehaviour();
            this.initSslFieldListening();
            this.initSmtpFieldsControl();

            if (this.getUser().isAdmin()) {
                this.setFieldNotReadOnly('assignedUser');
            } else {
                this.setFieldReadOnly('assignedUser');
            }
        },

        setupFieldsBehaviour: function () {
            this.controlStatusField();
            this.listenTo(this.model, 'change:status', function (model, value, o) {
                if (o.ui) {
                    this.controlStatusField();
                }
            }, this);
            this.listenTo(this.model, 'change:useImap', function (model, value, o) {
                if (o.ui) {
                    this.controlStatusField();
                }
            }, this);

            if (this.wasFetched()) {
                this.setFieldReadOnly('fetchSince');
            } else {
                this.setFieldNotReadOnly('fetchSince');
            }
        },

        controlStatusField: function () {
            var list = ['username', 'port', 'host', 'monitoredFolders'];
            if (this.model.get('status') === 'Active' && this.model.get('useImap')) {
                list.forEach(function (item) {
                    this.setFieldRequired(item);
                }, this);
            } else {
                list.forEach(function (item) {
                    this.setFieldNotRequired(item);
                }, this);
            }
        },

        wasFetched: function () {
            if (!this.model.isNew()) {
                return !!((this.model.get('fetchData') || {}).lastUID);
            }
            return false;
        },

        initSslFieldListening: function () {
            this.listenTo(this.model, 'change:ssl', function (model, value, o) {
                if (o.ui) {
                    if (value) {
                        this.model.set('port', 993);
                    } else {
                        this.model.set('port', 143);
                    }
                }
            }, this);

            this.listenTo(this.model, 'change:smtpSecurity', function (model, value, o) {
                if (o.ui) {
                    if (value === 'SSL') {
                        this.model.set('smtpPort', 465);
                    } else if (value === 'TLS') {
                        this.model.set('smtpPort', 587);
                    } else {
                        this.model.set('smtpPort', 25);
                    }
                }
            }, this);
        },

        initSmtpFieldsControl: function () {
            this.controlSmtpFields();
            this.listenTo(this.model, 'change:useSmtp', this.controlSmtpFields, this);
            this.listenTo(this.model, 'change:smtpAuth', this.controlSmtpAuthField, this);
        },

        controlSmtpFields: function () {
            if (this.model.get('useSmtp')) {
                this.showField('smtpHost');
                this.showField('smtpPort');
                this.showField('smtpAuth');
                this.showField('smtpSecurity');
                this.showField('smtpTestSend');

                this.setFieldRequired('smtpHost');
                this.setFieldRequired('smtpPort');

                this.controlSmtpAuthField();
            } else {
                this.hideField('smtpHost');
                this.hideField('smtpPort');
                this.hideField('smtpAuth');
                this.hideField('smtpUsername');
                this.hideField('smtpPassword');
                this.hideField('smtpSecurity');
                this.hideField('smtpTestSend');

                this.setFieldNotRequired('smtpHost');
                this.setFieldNotRequired('smtpPort');
                this.setFieldNotRequired('smtpUsername');
            }
        },

        controlSmtpAuthField: function () {
            if (this.model.get('smtpAuth')) {
                this.showField('smtpUsername');
                this.showField('smtpPassword');
                this.setFieldRequired('smtpUsername');
            } else {
                this.hideField('smtpUsername');
                this.hideField('smtpPassword');
                this.setFieldNotRequired('smtpUsername');
            }
        },

    });

});

