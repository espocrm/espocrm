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

Espo.define('Views.Role.Record.Table', 'View', function (Dep) {

    return Dep.extend({

        template: 'role.table',
    
        scopeList: null,
    
        actionList: ['read', 'edit', 'delete'],
    
        levelList: ['all', 'team', 'own', 'no'],
    
        accessList: ['not-set', 'enabled', 'disabled'],
    
        colors: {
            all: '#6BC924',
            team: '#999900',
            own: '#CC9900',
            no: '#F23333',
        
            enabled: '#6BC924',
            disabled: '#F23333',
            'not-set': '#A8A8A8',            
        },
    
        mode: 'detail',
    
        aclData: null,
    
        data: function () {        
            var data = {};            
            data['editMode'] = this.mode === 'edit';            
            data['actionList'] = this.actionList;
            data['levelList'] = this.levelList;
            data['accessList'] = this.accessList;
            data['colors'] = this.colors;
            data['aclTable'] = this.getAclTable();
            return data;
        },
    
        getAclTable: function () {
            var aclData = this.aclData;
            var aclTable = {};                        
            for (var i in this.scopeList) {
                var controller = this.scopeList[i];
                var o = {};
                
                var access = 'not-set';
                
                if (this.final) {
                    access = 'enabled';
                }
            
                if (controller in aclData) {
                    if (aclData[controller] === false) {
                        access = 'disabled';                            
                    } else {
                        access = 'enabled';
                    }                    
                }
                if (this.aclTypeMap[controller] == 'record') {
                    for (var j in this.actionList) {
                        var action = this.actionList[j];
                        var level = 'all';
                        if (controller in aclData) {
                            if (access == 'enabled') {
                                if (action in aclData[controller]) {
                                    level = aclData[controller][action];
                                }
                            } else {
                                level = 'no';
                            }
                        } 
                        o[action] = {level: level, name: controller + '-' + action};
                    }
                }                
                aclTable[controller] = {
                    acl: o,
                    access: access,
                    name: controller,
                    type: this.aclTypeMap[controller]
                };                
            }
            return aclTable;
        },
    
        setup: function () {
            this.mode = this.options.mode || 'detail';
            this.aclData = this.options.aclData;            
            this.final = this.options.final || false;

            this.aclTypeMap = {};
            
            this.scopeList = [];                
            var scopesAll = Object.keys(this.getMetadata().get('scopes')).sort();
            scopesAll.forEach(function (scope) {
                var acl = this.getMetadata().get('scopes.' + scope + '.acl');
                if (acl) {
                    this.scopeList.push(scope);
                    if (acl == 'boolean') {
                        this.aclTypeMap[scope] = 'boolean';    
                    } else {
                        this.aclTypeMap[scope] = 'record';
                    }
                }
            }.bind(this));    
        },
    });
});


