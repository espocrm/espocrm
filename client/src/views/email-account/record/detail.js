/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.initSslFieldListening();
            this.initSmtpFieldsControl();

            if (this.wasFetched()) {
                this.setFieldReadOnly('fetchSince');
            }

            if (this.getUser().isAdmin()) {
                var fieldView = this.getFieldView('assignedUser');
                if (fieldView) {
                    fieldView.readOnly = false;
                    fieldView.render();
                }
            }
        },

        wasFetched: function () {
            if (!this.model.isNew()) {
                return !!((this.model.get('fetchData') || {}).lastUID);
            }
            return false;
        },

        initSslFieldListening: function () {
            var sslField = this.getFieldView('ssl');
            this.listenTo(sslField, 'change', function () {
                var ssl = sslField.fetch()['ssl'];
                if (ssl) {
                    this.model.set('port', '993');
                } else {
                    this.model.set('port', '143');
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

