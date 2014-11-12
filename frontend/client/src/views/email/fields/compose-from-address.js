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
Espo.define('Views.Email.Fields.ComposeFromAddress', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({
    
        editTemplate: 'email.fields.compose-from-address.edit',
        
        data: function () {
            return _.extend({
                list: this.list,
                noSmtpMessage: this.translate('noSmtpSetup', 'messages', 'Email').replace('{link}', '<a href="#Preferences">'+this.translate('Preferences')+'</a>')
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.list = [];
            
            if (this.getUser().get('emailAddress') && this.getPreferences().get('smtpServer')) {
                this.list.push(this.getUser().get('emailAddress'));
            }
            
            if (this.getConfig().get('outboundEmailIsShared') && this.getConfig().get('outboundEmailFromAddress')) {
                this.list.push(this.getConfig().get('outboundEmailFromAddress'));
            }            
        },
    });

});
