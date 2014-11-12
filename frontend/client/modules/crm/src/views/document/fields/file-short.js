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

Espo.define('Crm:Views.Document.Fields.FileShort', 'Views.Fields.File', function (Dep) {

    return Dep.extend({    
        
        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var name = this.model.get(this.nameName);
                var type = this.model.get(this.typeName) || this.defaultType;
                var id = this.model.get(this.idName);
                
                if (!id) {
                    return false;
                }

                return '<a title="'+name+'" href="?entryPoint=download&id=' + id + '"><span class="glyphicon glyphicon-paperclip small"></span></a>';
            }
        },
        
    });

});
