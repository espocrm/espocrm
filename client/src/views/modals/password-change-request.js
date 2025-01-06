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

import ModalView from 'views/modal';

class PasswordChangeRequestModalView extends ModalView {

    template = 'modals/password-change-request'

    cssName = 'password-change-request'
    className = 'dialog dialog-centered'
    noFullHeight = true
    footerAtTheTop = false

    setup() {
        this.buttonList = [
            {
                name: 'submit',
                label: 'Submit',
                style: 'danger',
                className: 'btn-s-wide',
            },
            {
                name: 'cancel',
                label: 'Close',
                pullLeft: true,
                className: 'btn-s-wide',
            }
        ];

        this.headerText = this.translate('Password Change Request', 'labels', 'User');

        this.once('close remove', () => {
            if (this.$userName) {
                this.$userName.popover('destroy');
            }

            if (this.$emailAddress) {
                this.$emailAddress.popover('destroy');
            }
        });
    }

    afterRender() {
        this.$userName = this.$el.find('input[name="username"]');
        this.$emailAddress = this.$el.find('input[name="emailAddress"]');
    }

    // noinspection JSUnusedGlobalSymbols
    actionSubmit() {
        const $userName = this.$userName;
        const $emailAddress = this.$emailAddress;

        const userName = $userName.val();
        const emailAddress = $emailAddress.val();

        let isValid = true;

        if (userName === '') {
            isValid = false;

            const message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');

            this.isPopoverUserNameDestroyed = false;

            $userName.popover({
                container: 'body',
                placement: 'bottom',
                content: message,
                trigger: 'manual',
            }).popover('show');

            const $cellUserName = $userName.closest('.form-group');

            $cellUserName.addClass('has-error');

            $userName.one('mousedown click', () => {
                $cellUserName.removeClass('has-error');

                if (this.isPopoverUserNameDestroyed) {
                    return;
                }

                $userName.popover('destroy');
                this.isPopoverUserNameDestroyed = true;
            });
        }

        if (emailAddress === '') {
            isValid = false;

            const message = this.getLanguage().translate('emailAddressCantBeEmpty', 'messages', 'User');

            this.isPopoverEmailAddressDestroyed = false;

            $emailAddress.popover({
                container: 'body',
                placement: 'bottom',
                content: message,
                trigger: 'manual',
            }).popover('show');

            const $cellEmailAddress = $emailAddress.closest('.form-group');

            $cellEmailAddress.addClass('has-error');

            $emailAddress.one('mousedown click', () => {
                $cellEmailAddress.removeClass('has-error');

                if (this.isPopoverEmailAddressDestroyed) {
                    return;
                }

                $emailAddress.popover('destroy');

                this.isPopoverEmailAddressDestroyed = true;
            });
        }

        if (!isValid) {
            return;
        }

        const $submit = this.$el.find('button[data-name="submit"]');

        $submit.addClass('disabled');

        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

        Espo.Ajax
            .postRequest('User/passwordChangeRequest', {
                userName: userName,
                emailAddress: emailAddress,
                url: this.options.url,
            })
            .then(() => {
                Espo.Ui.notify(false);

                let msg = this.translate('uniqueLinkHasBeenSent', 'messages', 'User');

                msg += ' ' + this.translate('passwordRecoverySentIfMatched', 'messages', 'User');

                this.$el.find('.cell-userName').addClass('hidden');
                this.$el.find('.cell-emailAddress').addClass('hidden');

                $submit.addClass('hidden');

                this.$el.find('.msg-box').removeClass('hidden');
                this.$el.find('.msg-box').html('<span class="text-success">' + msg + '</span>');
            })
            .catch(xhr => {
                if (xhr.status === 404) {
                    Espo.Ui.error(this.translate('userNameEmailAddressNotFound', 'messages', 'User'));

                    xhr.errorIsHandled = true;
                }

                if (xhr.status === 403 && xhr.getResponseHeader('X-Status-Reason') === 'Already-Sent') {
                    Espo.Ui.error(this.translate('forbidden', 'messages', 'User'), true);

                    xhr.errorIsHandled = true;
                }

                $submit.removeClass('disabled');
            });
    }
}

export default PasswordChangeRequestModalView;
