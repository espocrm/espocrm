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
Espo.define('Views.User.Fields.GeneratePassword', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({
    
        _template: '<button type="button" class="btn" data-action="generatePassword">{{translate \'Generate\' scope=\'User\'}}</button>',
    
        events: {
            'click [data-action="generatePassword"]': function () {
                var password = Math.random().toString(36).slice(-8);                
                $('input[name="password"]').val(password);
                $('input[name="passwordConfirm"]').val(password);
            }
        },
        
        fetch: function () {
            return {};
        },
        
    });
    
});
