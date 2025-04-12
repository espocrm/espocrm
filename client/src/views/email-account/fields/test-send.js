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

import BaseFieldView from 'views/fields/base';

export default class EmailAccountTestSendFieldView extends BaseFieldView {

    templateContent = `
        <button
            class="btn btn-default hidden"
            data-action="sendTestEmail"
        >{{translate 'Send Test Email' scope='Email'}}</button>
    `

    setup() {
        super.setup();

        this.addActionHandler('sendTestEmail', () => this.send());
    }

    fetch() {
        return {};
    }

    checkAvailability() {
        if (this.model.get('smtpHost')) {
            this.$el.find('button').removeClass('hidden');
        } else {
            this.$el.find('button').addClass('hidden');
        }
    }

    afterRender() {
        this.checkAvailability();

        this.stopListening(this.model, 'change:smtpHost');

        this.listenTo(this.model, 'change:smtpHost', () => {
            this.checkAvailability();
        });
    }

    /**
     * @protected
     */
    enableButton() {
        this.$el.find('button').removeClass('disabled').removeAttr('disabled');
    }

    /**
     * @protected
     */
    disabledButton() {
        this.$el.find('button').addClass('disabled').attr('disabled', 'disabled');
    }

    /**
     * @private
     */
    send() {
        const data = this.getSmtpData();

        this.createView('popup', 'views/outbound-email/modals/test-send', {
            emailAddress: this.getUser().get('emailAddress'),
        }).then(view => {
            view.render();

            this.listenToOnce(view, 'send', (emailAddress) => {
                this.disabledButton();

                data.emailAddress = emailAddress;

                Espo.Ui.notify(this.translate('Sending...'));

                view.close();

                Espo.Ajax.postRequest('Email/sendTest', data)
                    .then(() => {
                        this.enableButton();

                        Espo.Ui.success(this.translate('testEmailSent', 'messages', 'Email'));
                    })
                    .catch(xhr => {
                            let reason = xhr.getResponseHeader('X-Status-Reason') || '';

                            reason = reason
                                .replace(/ $/, '')
                                .replace(/,$/, '');

                            let msg = this.translate('Error');

                            if (xhr.status !== 200) {
                                msg += ' ' + xhr.status;
                            }

                            if (xhr.responseText) {
                                try {
                                    const data = /** @type {Record} */JSON.parse(xhr.responseText);

                                    if (data.messageTranslation) {
                                        this.enableButton();

                                        return;
                                    }

                                    reason = data.message || reason;
                                }
                                catch (e) {
                                    this.enableButton();

                                    console.error('Could not parse error response body.');

                                    return;
                                }
                            }

                            if (reason) {
                                msg += ': ' + reason;
                            }

                            Espo.Ui.error(msg, true);
                            console.error(msg);

                            xhr.errorIsHandled = true;

                            this.enableButton();
                        }
                    );
            });
        });
    }

    getSmtpData() {
        return {
            'server': this.model.get('smtpHost'),
            'port': this.model.get('smtpPort'),
            'auth': this.model.get('smtpAuth'),
            'security': this.model.get('smtpSecurity'),
            'username': this.model.get('smtpUsername'),
            'password': this.model.get('smtpPassword') || null,
            'authMechanism': this.model.get('smtpAuthMechanism'),
            'fromName': this.getUser().get('name'),
            'fromAddress': this.model.get('emailAddress'),
            'type': 'emailAccount',
            'id': this.model.id,
            'userId': this.model.get('assignedUserId'),
        };
    }
}
