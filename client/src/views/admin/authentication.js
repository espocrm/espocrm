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

Espo.define('views/admin/authentication', 'views/settings/record/edit', function (Dep) {

    return Dep.extend({

        layoutName: 'authentication',

        dependencyDefs: {
            'ldapAuth': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['ldapUsername', 'ldapPassword', 'testConnection']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['ldapUsername', 'ldapPassword', 'testConnection']
                    }
                ]
            },
            'ldapAccountCanonicalForm': {
                map: {
                    'Backslash': [
                        {
                            action: 'show',
                            fields: ['ldapAccountDomainName', 'ldapAccountDomainNameShort']
                        }
                    ],
                    'Principal': [
                        {
                            action: 'show',
                            fields: ['ldapAccountDomainName', 'ldapAccountDomainNameShort']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['ldapAccountDomainName', 'ldapAccountDomainNameShort']
                    }
                ]
            },
            'ldapCreateEspoUser': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['ldapUserTitleAttribute', 'ldapUserFirstNameAttribute', 'ldapUserLastNameAttribute', 'ldapUserEmailAddressAttribute', 'ldapUserPhoneNumberAttribute', 'ldapUserTeams', 'ldapUserDefaultTeam']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['ldapUserTitleAttribute', 'ldapUserFirstNameAttribute', 'ldapUserLastNameAttribute', 'ldapUserEmailAddressAttribute', 'ldapUserPhoneNumberAttribute', 'ldapUserTeams', 'ldapUserDefaultTeam']
                    }
                ]
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.methodList = this.getMetadata().get('entityDefs.Settings.fields.authenticationMethod.options') || [];

            this.authFields = {
                'LDAP': [
                    'ldapHost', 'ldapPort', 'ldapAuth', 'ldapSecurity',
                    'ldapUsername', 'ldapPassword', 'ldapBindRequiresDn',
                    'ldapUserLoginFilter', 'ldapBaseDn', 'ldapAccountCanonicalForm',
                    'ldapAccountDomainName', 'ldapAccountDomainNameShort', 'ldapAccountDomainName',
                    'ldapAccountDomainNameShort', 'ldapTryUsernameSplit', 'ldapOptReferrals',
                    'ldapCreateEspoUser'
                ]
            };

            this.handlePanelsVisibility();
        },


        afterRender: function () {
            this.listenTo(this.model, 'change:authenticationMethod', function () {
                this.handlePanelsVisibility();
            }, this);
        },

        handlePanelsVisibility: function () {
            var authenticationMethod = this.model.get('authenticationMethod');

            this.methodList.forEach(function (method) {
                var list = (this.authFields[method] || []);
                if (method != authenticationMethod) {
                    this.hidePanel(method);
                    list.forEach(function (field) {
                        this.hideField(field);
                    }, this);
                } else {
                    this.showPanel(method);

                    list.forEach(function (field) {
                        this.showField(field);
                    }, this);
                    Object.keys(this.dependencyDefs || {}).forEach(function (attr) {
                        if (~list.indexOf(attr)) {
                            this._handleDependencyAttribute(attr);
                        }
                    }, this);
                }
            }, this);
        },

    });

});

