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
 ************************************************************************/

Espo.define('views/admin/outbound-emails', 'views/settings/record/edit', function (Dep) {

    return Dep.extend({

        layoutName: 'outboundEmails',

        dependencyDefs: {
            'smtpAuth': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['smtpUsername', 'smtpPassword']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['smtpUsername', 'smtpPassword']
                    }
                ]
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            var smtpSecurityField = this.getFieldView('smtpSecurity');
            this.listenTo(smtpSecurityField, 'change', function () {
                var smtpSecurity = smtpSecurityField.fetch()['smtpSecurity'];
                if (smtpSecurity == 'SSL') {
                    this.model.set('smtpPort', '465');
                } else if (smtpSecurity == 'TLS') {
                    this.model.set('smtpPort', '587');
                } else {
                    this.model.set('smtpPort', '25');
                }
            }.bind(this));
        },

    });

});

