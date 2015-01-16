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

Espo.define('Views.Admin.Extensions.Done', 'Views.Modal', function (Dep) {

    return Dep.extend({
    
        cssName: 'done-modal',    
        
        header: false,
        
        template: 'admin.extensions.done',
        
        createButton: true,
        
        data: function () {        
            return {
                version: this.options.version,
                name: this.options.name,
                text: this.translate('extensionInstalled', 'messages', 'Admin').replace('{version}', this.options.version)
                                                                               .replace('{name}', this.options.name)
            };
        },
                
        setup: function () {            
            this.buttons = [
                {
                    name: 'close',
                    label: 'Close',
                    onClick: function (dialog) {
                        dialog.close();                                        
                    }.bind(this)
                } 
            ];
                    
            this.header = this.getLanguage().translate('Installed successfully', 'labels', 'Admin');            
            
        },
        
    });
});

