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

Espo.define('Views.Modals.ChangePassword', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'change-password',

        template: 'modals.change-password',        

        setup: function () {
        
            this.buttons = [
                {
                    name: 'change',
                    label: 'Change',
                    style: 'danger',
                    onClick: function (dialog) {
                        this.changePassword();
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
            
            this.header = this.translate('Change Password', 'labels', 'User');
            
            this.wait(true);
            
            this.getModelFactory().create('User', function (user) {            
                this.model = user;
                
                this.createView('password', 'Fields.Password', {
                    model: user,
                    mode: 'edit',
                    el: this.options.el + ' .field-password',
                    defs: {
                        name: 'password',
                        params: {
                            required: true,
                        }
                    }
                });
                this.createView('passwordConfirm', 'Fields.Password', {
                    model: user,
                    mode: 'edit',
                    el: this.options.el + ' .field-passwordConfirm',
                    defs: {
                        name: 'passwordConfirm',
                        params: {
                            required: true,
                        }                        
                    }
                });
                        
                this.wait(false);
            }, this);            

        },
        
        
        changePassword: function () {            
            this.getView('password').fetchToModel();
            this.getView('passwordConfirm').fetchToModel();    
            
            var notValid = this.getView('password').validate() || this.getView('passwordConfirm').validate();
            
            if (notValid) {
                return;
            }
            
            this.$el.find('button[data-name="change"]').addClass('disabled');
            
            $.ajax({            
                url: 'User/action/changeOwnPassword',
                type: 'POST',
                data: JSON.stringify({
                    password: this.model.get('password')
                }),
                error: function () {
                    this.$el.find('button[data-name="change"]').removeClass('disabled');
                }.bind(this)            
            }).done(function () {                
                Espo.Ui.success(this.translate('passwordChanged', 'messages', 'User'));                
                this.trigger('changed');
                this.close();
            }.bind(this));            
        },

    });
});

