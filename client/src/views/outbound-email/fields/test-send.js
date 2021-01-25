/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/outbound-email/fields/test-send', 'views/fields/base', function (Dep) {

    return Dep.extend({

        templateContent:
            '<button class="btn btn-default hidden" data-action="sendTestEmail">'+
            '{{translate \'Send Test Email\' scope=\'Email\'}}</button>',

        events: {
            'click [data-action="sendTestEmail"]': function () {
                this.send();
            },
        },

        fetch: function () {
            return {};
        },

        checkAvailability: function () {
            if (this.model.get('smtpServer')) {
                this.$el.find('button').removeClass('hidden');
            } else {
                this.$el.find('button').addClass('hidden');
            }
        },

        afterRender: function () {
            this.checkAvailability();

            this.stopListening(this.model, 'change:smtpServer');

            this.listenTo(this.model, 'change:smtpServer', function () {
                this.checkAvailability();
            }, this);
        },

        getSmtpData: function () {
            var data = {
                'server': this.model.get('smtpServer'),
                'port': this.model.get('smtpPort'),
                'auth': this.model.get('smtpAuth'),
                'security': this.model.get('smtpSecurity'),
                'username': this.model.get('smtpUsername'),
                'password': this.model.get('smtpPassword') || null,
                'fromName': this.model.get('outboundEmailFromName'),
                'fromAddress': this.model.get('outboundEmailFromAddress'),
                'type': 'outboundEmail',
            };
            return data;
        },


        send: function () {
            var data = this.getSmtpData();

            this.createView('popup', 'views/outbound-email/modals/test-send', {
                emailAddress: this.getUser().get('emailAddress')
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'send', function (emailAddress) {
                    this.$el.find('button').addClass('disabled');
                    data.emailAddress = emailAddress;

                    this.notify('Sending...');

                    view.close();

                    Espo.Ajax.postRequest('Email/action/sendTestEmail', data)
                        .then(
                            function () {
                                this.$el.find('button').removeClass('disabled');

                                Espo.Ui.success(this.translate('testEmailSent', 'messages', 'Email'));
                            }
                            .bind(this)
                        )
                        .fail(
                            function (xhr) {
                                var reason = xhr.getResponseHeader('X-Status-Reason') || '';

                                reason = reason
                                    .replace(/ $/, '')
                                    .replace(/,$/, '');

                                var msg = this.translate('Error');

                                if (xhr.status !== 200) {
                                    msg += ' ' + xhr.status;
                                }

                                if (xhr.responseText) {
                                    try {
                                        var data = JSON.parse(xhr.responseText);

                                        reason = data.message || reason;
                                    }
                                    catch (e) {
                                        console.error('Could not parse error response body.');

                                        return;
                                    }
                                }

                                if (reason) {
                                    msg += ': ' + reason;
                                }

                                Espo.Ui.error(msg);

                                console.error(msg);

                                xhr.errorIsHandled = true;

                                this.$el.find('button').removeClass('disabled');
                            }
                            .bind(this)
                        );

                }, this);
            }.bind(this));

        },

    });
});

