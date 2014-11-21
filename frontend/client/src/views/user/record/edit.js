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
    
Espo.define('Views.User.Record.Edit', 'Views.Record.Edit', function (Dep) {

    return Dep.extend({
    
        sideView: 'User.Record.EditSide',
        
        setup: function () {
            Dep.prototype.setup.call(this);
            
            if (this.model.id == this.getUser().id) {
                this.listenTo(this.model, 'after:save', function () {
                    this.getUser().set(this.model.toJSON());
                }.bind(this));
            }
        },
        
        getGridLayout: function (callback) {
        
            var self = this;

            this._helper.layoutManager.get(this.model.name, this.options.layoutName || this.layoutName, function (simpleLayout) {
                
                var layout = _.clone(simpleLayout);
                
                if (this.type == 'edit') {
                    layout.push({
                        label: 'Password',
                        rows: [
                            [{
                                name: 'password',
                                type: 'password',
                                params: {
                                    required: self.isNew,
                                    readyToChange: true
                                }
                            },{
                                name: 'generatePassword',
                                view: 'User.Fields.GeneratePassword',
                                customLabel: ''
                            }],
                            [{
                                name: 'passwordConfirm',
                                type: 'password',
                                params: {
                                    required: self.isNew,
                                    readyToChange: true
                                }
                            },{
                                name: 'passwordInfo',
                                customLabel: '',
                                customCode: '{{translate "passwordWillBeSent" scope="User" category="messages"}}'
                            }]
                        ],
                    });
                }
                
                var gridLayout = {
                    type: 'record',
                    layout: this.convertDetailLayout(layout),
                };

                callback(gridLayout);
            }.bind(this));
        },
        
        fetch: function () {
            var data = Dep.prototype.fetch.call(this);
            
            if (!this.isNew) {
                if ('password' in data) {
                    if (data['password'] == '') {
                        delete data['password'];
                        delete data['passwordConfirm'];
                        this.model.unset('password');
                        this.model.unset('passwordConfirm');
                    }
                }
            }
            
            return data;
        }
    
    });
    
});

