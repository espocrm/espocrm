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

Espo.define('Views.Admin.Extensions.Ready', 'Views.Modal', function (Dep) {

    return Dep.extend({
    
        cssName: 'ready-modal',    
        
        header: false,
        
        template: 'admin.extensions.ready',
        
        createButton: true,
        
        data: function () {        
            return {
                version: this.upgradeData.version,
                text: this.translate('installExtension', 'messages', 'Admin').replace('{version}', this.upgradeData.version)
                                                                             .replace('{name}', this.upgradeData.name)
            };
        },
                
        setup: function () {
            
            this.buttons = [
                {
                    name: 'run',
                    text: this.translate('Install', 'labels', 'Admin'),
                    style: 'danger',
                    onClick: function (dialog) {
                        this.run();
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
            
            this.upgradeData = this.options.upgradeData;
                    
            this.header = this.getLanguage().translate('Ready for installation', 'labels', 'Admin');                
            
        },
        
        run: function () {
            this.trigger('run');
            this.remove();
        }    
    });
});

