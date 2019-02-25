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

Espo.define('views/admin/authentication/fields/test-connection', 'views/fields/base', function (Dep) {

    return Dep.extend({

        _template: '<button class="btn btn-default" data-action="testConnection">{{translate \'Test Connection\' scope=\'Settings\'}}</button>',

        events: {
            'click [data-action="testConnection"]': function () {
                this.testConnection();
            },
        },

        fetch: function () {
            return {};
        },

        getConnectionData: function () {
            var data = {
                'host': this.model.get('ldapHost'),
                'port': this.model.get('ldapPort'),
                'useSsl': this.model.get('ldapSecurity'),
                'useStartTls': this.model.get('ldapSecurity'),
                'username': this.model.get('ldapUsername'),
                'password': this.model.get('ldapPassword'),
                'bindRequiresDn': this.model.get('ldapBindRequiresDn'),
                'accountDomainName': this.model.get('ldapAccountDomainName'),
                'accountDomainNameShort': this.model.get('ldapAccountDomainNameShort'),
                'accountCanonicalForm': this.model.get('ldapAccountCanonicalForm')
            };
            return data;
        },

        testConnection: function () {
            var data = this.getConnectionData();

            this.$el.find('button').prop('disabled', true);

            this.notify('Connecting', null, null, 'Settings');

            $.ajax({
                url: 'Settings/action/testLdapConnection',
                type: 'POST',
                data: JSON.stringify(data),
                error: function (xhr, status) {
                    var statusReason = xhr.getResponseHeader('X-Status-Reason') || '';
                    statusReason = statusReason.replace(/ $/, '');
                    statusReason = statusReason.replace(/,$/, '');

                    var msg = this.translate('Error') + ' ' + xhr.status;
                    if (statusReason) {
                        msg += ': ' + statusReason;
                    }
                    Espo.Ui.error(msg);
                    console.error(msg);
                    xhr.errorIsHandled = true;

                    this.$el.find('button').prop('disabled', false);
                }.bind(this)
            }).done(function () {
                this.$el.find('button').prop('disabled', false);
                Espo.Ui.success(this.translate('ldapTestConnection', 'messages', 'Settings'));
            }.bind(this));

        },

    });

});

