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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/ 

Espo.define('Crm:Views.Case.Record.Panels.Activities', 'Crm:Views.Record.Panels.Activities', function (Dep) {

    return Dep.extend({
        
        getComposeEmailAttributes: function (data, callback) {
            data = data || {};
            var attributes = {
                status: 'Draft',
                name: '[#' + this.model.get('number') + '] ' + this.model.get('name')
            };
            
            if (this.model.get('contactId')) {
                this.getModelFactory().create('Contact', function (contact) {
                    contact.id = this.model.get('contactId');
                    
                    this.listenToOnce(contact, 'sync', function () {
                        var emailAddress = contact.get('emailAddress');                        
                        if (emailAddress) {
                            attributes.to = emailAddress;
                        }
                        
                        callback.call(this, attributes);
                    });                    
                    contact.fetch({
                        error: function () {
                            callback.call(this, attributes);
                        }.bind(this)
                    });
                }, this);
            } else {
                callback.call(this, attributes);
            }
        },
        
    });
});

