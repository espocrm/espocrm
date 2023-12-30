/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/authentication/fields/test-connection', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        templateContent: `
            <button
                class="btn btn-default"
                data-action="testConnection"
            >{{translate \'Test Connection\' scope=\'Settings\'}}</button>
        `,

        events: {
            'click [data-action="testConnection"]': function () {
                this.testConnection();
            },
        },

        fetch: function () {
            return {};
        },

        getConnectionData: function () {
            return {
                'host': this.model.get('ldapHost'),
                'port': this.model.get('ldapPort'),
                'useSsl': this.model.get('ldapSecurity'),
                'useStartTls': this.model.get('ldapSecurity'),
                'username': this.model.get('ldapUsername'),
                'password': this.model.get('ldapPassword'),
                'bindRequiresDn': this.model.get('ldapBindRequiresDn'),
                'accountDomainName': this.model.get('ldapAccountDomainName'),
                'accountDomainNameShort': this.model.get('ldapAccountDomainNameShort'),
                'accountCanonicalForm': this.model.get('ldapAccountCanonicalForm'),
            };
        },

        testConnection: function () {
            let data = this.getConnectionData();

            this.$el.find('button').prop('disabled', true);

            this.notify('Connecting', null, null, 'Settings');

            Espo.Ajax
                .postRequest('Ldap/action/testConnection', data)
                .then(() => {
                    this.$el.find('button').prop('disabled', false);

                    Espo.Ui.success(this.translate('ldapTestConnection', 'messages', 'Settings'));
                })
                .catch(xhr => {
                    let statusReason = xhr.getResponseHeader('X-Status-Reason') || '';
                    statusReason = statusReason.replace(/ $/, '');
                    statusReason = statusReason.replace(/,$/, '');

                    let msg = this.translate('Error') + ' ' + xhr.status;

                    if (statusReason) {
                        msg += ': ' + statusReason;
                    }

                    Espo.Ui.error(msg, true);

                    console.error(msg);

                    xhr.errorIsHandled = true;

                    this.$el.find('button').prop('disabled', false);
                });
        },
    });
});
