/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Views.Modals.PasswordChangeRequest', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'password-change-request',

        template: 'modals.password-change-request',

        setup: function () {
        
            this.buttons = [
                {
                    name: 'submit',
                    label: 'Submit',
                    style: 'danger',
                    onClick: function (dialog) {
                        this.submit();
                    }.bind(this)
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];
            
            this.header = this.translate('Password Change Request', 'labels', 'User');
        },

        submit: function () {
            var $userName = this.$el.find('input[name="userName"]');
            var $emailAddress = this.$el.find('input[name="emailAddress"]');

            $userName.popover('destroy');
            $emailAddress.popover('destroy');

            var userName = $userName.val();
            var emailAddress = $emailAddress.val();

            var isValid = true;
            if (userName == '') {
                isValid = false;
                
                var message = this.getLanguage().translate('Username can not be empty', 'labels', 'User');

                $userName.popover({
                    placement: 'bottom',
                    content: message,
                    trigger: 'manual',
                }).popover('show');

                var $cellUserName = $userName.closest('.form-group');
                $cellUserName.addClass('has-error');
                

                $userName.one('mousedown click', function () {
                    $cellUserName.removeClass('has-error');
                    $userName.popover('destroy');
                });

            }

            var isValid = true;
            if (emailAddress == '') {
                isValid = false;
                
                var message = this.getLanguage().translate('Email Address can not be empty', 'labels', 'User');

                $emailAddress.popover({
                    placement: 'bottom',
                    content: message,
                    trigger: 'manual',
                }).popover('show');

                var $cellEmailAddress = $emailAddress.closest('.form-group');
                $cellEmailAddress.addClass('has-error');
                
                $emailAddress.one('mousedown click', function () {
                    $cellEmailAddress.removeClass('has-error');
                    $emailAddress.popover('destroy');
                });
            }

            if (!isValid) return;

            $submit = this.$el.find('.msg-box');
            $submit.addClass('disabled');
            this.notify('Please wait...');

            $.ajax({
                url: 'PasswordChangeRequest',
                type: 'POST',
                data: JSON.stringify({
                    userName: userName,
                    emailAddress: emailAddress
                }),
                error: function (xhr) {
                    if (xhr.status == 404) {
                        this.notify(this.translate('Username/Email Address not found', 'labels', 'User'), 'error');
                        xhr.errorIsHandled = true;
                    }
                    $submit.removeClass('disabled');
                }.bind(this)
            }).done(function () {
                var msg = this.translate('The unique link has been sent to the specified email address.', 'labels', 'User');

                this.$el.find('.cell-userName').addClass('hidden');
                this.$el.find('.cell-emailAddress').addClass('hidden');
                $submit.addClass('hidden');
                this.$el.find('.msg-box').removeClass('hidden');

                this.$el.find('.msg-box').html('<span class="text-success">' + msg + '</span>');
            }.bind(this));
        }

    });
});

