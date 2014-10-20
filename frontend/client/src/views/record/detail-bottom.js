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

Espo.define('Views.Record.DetailBottom', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.bottom',
        
        mode: 'detail',

        data: function () {
            return {
                panels: this.panels
            };
        },

        events: {
            'click .action': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var panel = $target.data('panel');
                var data = $target.data();
                if (action) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    var d = _.clone(data);
                    delete d['action'];
                    delete d['panel'];
                    var view = this.getView(panel);
                    if (view && typeof view[method] == 'function') {
                        view[method].call(view, d);
                    }
                }
            }
        },

        setup: function () {
            this.panels = [];                
            var scope = this.model.name;
            
            this.wait(true);
            
            var panels = _.clone(this.getMetadata().get('clientDefs.' + scope + '.bottomPanels.' + this.mode) || []);
            
            if (this.mode == 'detail' && this.getMetadata().get('scopes.' + scope + '.stream')) {
                panels.unshift({
                    "name":"stream",
                    "label":"Stream",
                    "view":"Stream.Panel"
                });
            }
                                    
            panels.forEach(function (p) {
                var name = p.name;
                this.panels.push(p);
                this.createView(name, p.view, {
                    model: this.model,
                    panelName: name,
                    el: this.options.el + ' .panel-body-' + Espo.Utils.toDom(name)
                }, function (view) {
                    if ('getActions' in view) {
                        p.actions = this.filterActions(view.getActions());
                    }
                    if ('getButtons' in view) {
                        p.buttons = this.filterActions(view.getButtons());
                    }
                    if (p.label) {
                        p.title = this.translate(p.label, 'labels', scope);                
                    } else {
                        p.title = view.title;
                    }
                }.bind(this));
            }.bind(this));            
            
            this._helper.layoutManager.get(this.model.name, 'relationships', function (layout) {
                        
                var panelList = layout;
                panelList.forEach(function (name) {
                    var p = {name: name};
                    var name = p.name;
                    
                    var foreignScope = this.model.defs.links[name].entity;                        
                    if (!this.getAcl().check(foreignScope, 'read')) {
                        return;
                    }
                    
                    this.panels.push(p);
                    
                    var viewName = 'Record.Panels.Relationship';
                    var defs = this.getMetadata().get('clientDefs.' + scope + '.relationshipPanels.' + name) || {};                        

                    var total = 8;

                    this.createView(name, viewName, {
                        model: this.model,
                        total: total,
                        panelName: name,
                        defs: defs,
                        el: this.options.el + ' .panel-body-' + Espo.Utils.toDom(p.name)
                    }, function (view) {
                        if ('getActions' in view) {
                            p.actions = this.filterActions(view.getActions());
                        }
                        if ('getButtons' in view) {
                            p.buttons = this.filterActions(view.getButtons());
                        }
                        p.title = view.title;
                    }.bind(this));                        
                }.bind(this));
                
                this.wait(false);
            }.bind(this));
        },
        


        filterActions: function (actions) {
            var filtered = [];
            actions.forEach(function (item) {
                if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item)) {
                    filtered.push(item);
                }
            }.bind(this));
            return filtered;
        },

    });
});


