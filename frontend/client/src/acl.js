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


/**
 * Example:
 * Lead: {
 *   edit: 'own',
 *   read: 'team',
 *   delete: 'no',
 * }
 */

Espo.Acl = function (user) {
    this.data = {};
    this.user = user || null;            
}

_.extend(Espo.Acl.prototype, {

    data: null,
    
    user: null,
        
    set: function (data) {
        data = data || {};
        this.data = data;
    },
    
    check: function (controller, action, isOwner, inTeam) {
        if (this.user.isAdmin()) {
            return true;
        }
        
        if (controller in this.data) {            
            if (this.data[controller] === false) {
                return false;
            }
            if (this.data[controller] === true) {
                return true;
            }
            if (typeof action !== 'undefined') {            
                if (action in this.data[controller]) {
                    var value = this.data[controller][action];
                
                    if (value === 'all' || value === true) {
                        return true;                    
                    }
                
                    if (!value || value === 'no') {
                        return false;                    
                    }
                
                    if (typeof isOwner === 'undefined') {
                        return true;
                    }
                
                    if (isOwner) {
                        if (value === 'own' || value === 'team') {
                            return true;
                        }
                    }
                
                    //if (inTeam) {
                        if (value === 'team') {
                            return true;
                        }
                    //}
                
                    return false;
                }
            }
            return true;
        }
        return true;
    },
    
    checkModel: function (model, action) {
        if (action == 'edit') {
            if (!model.isEditable()) {
                return false;
            }
        }
        if (this.user.isAdmin()) {
            return true;
        }
        return this.check(model.name, action, this.user.isOwner(model), this.user.inTeam(model));
    },
    
    clear: function () {
        this.data = {};
    }
});



