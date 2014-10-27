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
    
Espo.define('Views.Login', 'View', function (Dep) {

    return Dep.extend({
    
        template: 'login',
        
        views: {
            footer: {
                el: 'body > footer',
                view: 'Site.Footer'
            },
        },
    
        events: {
            'submit #login-form': function (e) {
                this.login();
                return false;
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
                return 'client/img/logo.png';
            }
            return '?entryPoint=LogoImage&size=small-logo';
        },
        
        login: function () {
                var userName = $("#field-userName").val();
                var password = $("#field-password").val();
                
                if (userName == '') {                    
                    var $el = $("#field-userName");
                
                    var message = this.getLanguage().translate('Username can not be empty!');                
                    $el.popover({
                        placement: 'bottom',
                        content: message,
                        trigger: 'manual',
                    }).popover('show');
                    
                    var cell = $el.closest('.form-group');
                    cell.addClass('has-error');                
                    this.$el.one('mousedown click', function () {
                        cell.removeClass('has-error');
                        $el.popover('destroy');
                    });                    
                    return;
                }
                
                this.notify('Please wait...');
                
                $.ajax({
                    url: 'App/user',
                    headers: {
                        'Authorization': 'Basic ' + Base64.encode(userName  + ':' + password),
                        "Espo-Authorization": Base64.encode(userName + ":" + password)
                    },
                    success: function (data) {                                                
                        this.notify(false);            
                        this.trigger('login', {
                            auth: {
                                userName: userName,
                                token: data.token
                            },
                            user: data.user,
                            preferences: data.preferences,
                            acl: data.acl
                        });
                    }.bind(this),
                    error: function (xhr) {
                        if (xhr.status == 401) {
                            this.onWrong();
                        }
                    }.bind(this),
                    login: true,
                });
        },
        
        onWrong: function () {
            var cell = $('#login .form-group');
            cell.addClass('has-error');                
            this.$el.one('mousedown click', function () {
                cell.removeClass('has-error');
            });
            this.notify('Wrong username/password', 'error');    
        },
    });

});
