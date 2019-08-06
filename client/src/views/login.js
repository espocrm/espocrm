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

define('views/login', 'view', function (Dep) {

    return Dep.extend({

        template: 'login',

        views: {
            footer: {
                el: 'body > footer',
                view: 'views/site/footer'
            },
        },

        events: {
            'submit #login-form': function (e) {
                this.login();
                return false;
            },
            'click a[data-action="passwordChangeRequest"]': function (e) {
                this.showPasswordChangeRequest();
            }
        },

        data: function () {
            return {
                logoSrc: this.getLogoSrc()
            };
        },

        getLogoSrc: function () {
            var companyLogoId = this.getConfig().get('companyLogoId');
            if (!companyLogoId) {
                return this.getBasePath() + ('client/img/logo.png');
            }
            return this.getBasePath() + '?entryPoint=LogoImage&id='+companyLogoId;
        },

        login: function () {
                var userName = $('#field-userName').val();
                var trimmedUserName = userName.trim();
                if (trimmedUserName !== userName) {
                    $('#field-userName').val(trimmedUserName);
                    userName = trimmedUserName;
                }

                var password = $('#field-password').val();

                var $submit = this.$el.find('#btn-login');

                if (userName == '') {

                    this.isPopoverDestroyed = false;
                    var $el = $("#field-userName");

                    var message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');

                    $el.popover({
                        placement: 'bottom',
                        container: 'body',
                        content: message,
                        trigger: 'manual',
                    }).popover('show');

                    var $cell = $el.closest('.form-group');
                    $cell.addClass('has-error');
                    $el.one('mousedown click', function () {
                        $cell.removeClass('has-error');
                        if (this.isPopoverDestroyed) return;
                        $el.popover('destroy');
                        this.isPopoverDestroyed = true;
                    }.bind(this));
                    return;
                }

                $submit.addClass('disabled').attr('disabled', 'disabled');

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                Espo.Ajax.getRequest('App/user', null, {
                    login: true,
                    headers: {
                        'Authorization': 'Basic ' + Base64.encode(userName  + ':' + password),
                        'Espo-Authorization': Base64.encode(userName + ':' + password),
                        'Espo-Authorization-By-Token': false,
                        'Espo-Authorization-Create-Token-Secret': true,
                    },
                }).then(
                    function (data) {
                        this.notify(false);
                        this.trigger('login', {
                            auth: {
                                userName: userName,
                                token: data.token
                            },
                            user: data.user,
                            preferences: data.preferences,
                            acl: data.acl,
                            settings: data.settings,
                            appParams: data.appParams,
                        });
                    }.bind(this)
                ).fail(
                    function (xhr) {
                        $submit.removeClass('disabled').removeAttr('disabled');
                        if (xhr.status == 401) {
                            this.onWrongCredentials();
                        }
                    }.bind(this)
                );
        },

        onWrongCredentials: function () {
            var cell = $('#login .form-group');
            cell.addClass('has-error');
            this.$el.one('mousedown click', function () {
                cell.removeClass('has-error');
            });
            Espo.Ui.error(this.translate('wrongUsernamePasword', 'messages', 'User'));
        },

        showPasswordChangeRequest: function () {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
            this.createView('passwordChangeRequest', 'views/modals/password-change-request', {
                url: window.location.href
            }, function (view) {
                view.render();
                Espo.Ui.notify(false);
            });
        }
    });

});
