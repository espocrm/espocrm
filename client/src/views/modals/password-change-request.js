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

define('views/modals/password-change-request', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'password-change-request',

        className: 'dialog dialog-centered',

        template: 'modals/password-change-request',

        noFullHeight: true,

        setup: function () {

            this.buttonList = [
                {
                    name: 'submit',
                    label: 'Submit',
                    style: 'danger'
                },
                {
                    name: 'cancel',
                    label: 'Close'
                }
            ];

            this.headerHtml = this.translate('Password Change Request', 'labels', 'User');

            this.once('close remove', function () {
                if (this.$userName) {
                    this.$userName.popover('destroy');
                }
                if (this.$emailAddress) {
                    this.$emailAddress.popover('destroy');
                }
            }, this);
        },

        afterRender: function () {
            this.$userName = this.$el.find('input[name="username"]');
            this.$emailAddress = this.$el.find('input[name="emailAddress"]');
        },

        actionSubmit: function () {
            var $userName = this.$userName;
            var $emailAddress = this.$emailAddress;

            var userName = $userName.val();
            var emailAddress = $emailAddress.val();

            var isValid = true;

            if (userName == '') {
                isValid = false;

                var message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');

                this.isPopoverUserNameDestroyed = false;

                $userName.popover({
                    container: 'body',
                    placement: 'bottom',
                    content: message,
                    trigger: 'manual',
                }).popover('show');

                var $cellUserName = $userName.closest('.form-group');
                $cellUserName.addClass('has-error');

                $userName.one('mousedown click', function () {
                    $cellUserName.removeClass('has-error');
                    if (this.isPopoverUserNameDestroyed) return;
                    $userName.popover('destroy');
                    this.isPopoverUserNameDestroyed = true;
                }.bind(this));
            }

            if (emailAddress == '') {
                isValid = false;

                var message = this.getLanguage().translate('emailAddressCantBeEmpty', 'messages', 'User');

                this.isPopoverEmailAddressDestroyed = false;

                $emailAddress.popover({
                    container: 'body',
                    placement: 'bottom',
                    content: message,
                    trigger: 'manual',
                }).popover('show');

                var $cellEmailAddress = $emailAddress.closest('.form-group');
                $cellEmailAddress.addClass('has-error');

                $emailAddress.one('mousedown click', function () {
                    $cellEmailAddress.removeClass('has-error');
                    if (this.isPopoverEmailAddressDestroyed) return;
                    $emailAddress.popover('destroy');
                    this.isPopoverEmailAddressDestroyed = true;
                }.bind(this));
            }

            if (!isValid) return;

            $submit = this.$el.find('button[data-name="submit"]');
            $submit.addClass('disabled');

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax.postRequest('User/passwordChangeRequest', {
                userName: userName,
                emailAddress: emailAddress,
                url: this.options.url,
            }).then(function () {
                Espo.Ui.notify(false);

                var msg = this.translate('uniqueLinkHasBeenSent', 'messages', 'User');

                this.$el.find('.cell-userName').addClass('hidden');
                this.$el.find('.cell-emailAddress').addClass('hidden');

                $submit.addClass('hidden');

                this.$el.find('.msg-box').removeClass('hidden');

                this.$el.find('.msg-box').html('<span class="text-success">' + msg + '</span>');
            }.bind(this)).fail(function (xhr) {
                if (xhr.status == 404) {
                    this.notify(this.translate('userNameEmailAddressNotFound', 'messages', 'User'), 'error');
                    xhr.errorIsHandled = true;
                }
                if (xhr.status == 403) {
                    var statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');
                    if (statusReasonHeader) {
                        try {
                            var response = JSON.parse(statusReasonHeader);
                            if (response.reason === 'Already-Sent') {
                                xhr.errorIsHandled = true;
                                Espo.Ui.error(this.translate('forbidden', 'messages', 'User'), 'error');
                            }
                        } catch (e) {}
                    }
                }
                $submit.removeClass('disabled');
            }.bind(this));
        }

    });
});
