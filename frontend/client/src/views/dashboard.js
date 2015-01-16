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

Espo.define('Views.Dashboard', 'View', function (Dep) {

    return Dep.extend({
    
        template: 'dashboard',
    
        dashboardLayout: null,
        
        events: {
            'click button.add-dashlet': function () {
                this.createView('addDashlet', 'Modals.AddDashlet', {}, function (view) {
                    view.render();
                });
            },
        },

        data: function () {
            return {
                displayTitle: this.options.displayTitle
            };
        },
    
        getDashletsLayout: function (callback) {
            var dashboardLayout = this.dashboardLayout = this.getPreferences().get('dashboardLayout') || [[],[]];
            
            if (this.dashboardLayout.length == 0) {
                this.dashboardLayout = dashboardLayout = [[], []];
            }
        
            var layout = {
                type: 'columns-2',
                layout: [],
            };
            dashboardLayout.forEach(function (col) {
                var c = [];
                col.forEach(function (defs) {
                    if (defs && defs.name && defs.id) {
                        var o = {
                            name: 'dashlet-' + defs.id,
                            id: 'dashlet-container-' + defs.id,
                            view: 'Dashlet',
                            options: {
                                name: defs.name,
                                id: defs.id,
                            }
                        };
                        c.push(o);
                    }
                });
                layout.layout.push(c);
            });
            callback(layout);
        },
    
        setup: function () {
        },
        
        afterRender: function () {
            this.getDashletsLayout(function (layout) {
                this.createView('dashlets', 'Base', {
                    _layout: layout,
                    el: '#dashlets',
                    noCache: true,
                }, function (view) {
                    
                    view.once('after:render', function () {
                        this.makeSortable();
                    }.bind(this));
                    view.render();
                }.bind(this));
            }.bind(this));
        },
    
        makeSortable: function () {
            $('#dashlets > div').css('min-height', '100px');
            $('#dashlets > div').sortable({
                handle: '.dashlet .panel-heading',
                connectWith: '#dashlets > div',
                forcePlaceholderSize: true,
                placeholder: 'dashlet-placeholder',
                start: function (e, ui) {
                    $(ui.placeholder).css('height', $(ui.item).height());
                },
                stop: function (e, ui) {
                    this.updateDom();
                    this.updateDashletsLayout();
                }.bind(this)
            });
        },
        
        updateDom: function () {
            var layout = [];
            $('#dashlets > div').each(function (i, col) {
                var c = [];
                $(col).children().each(function (i, cell) {
                    var name = $(cell).find('.dashlet').data('name');
                    var id = $(cell).find('.dashlet').data('id');
                    c.push({
                        name: name,
                        id: id,
                    });
                });
                layout.push(c);
            });
            this.dashboardLayout = layout;
        },
        
        updateDashletsLayout: function () {
            this.getPreferences().save({
                dashboardLayout: this.dashboardLayout
            }, {patch: true});
            this.getPreferences().trigger('update');
        },
    
        removeDashlet: function (id) {
            this.dashboardLayout.forEach(function (col, i) {
                col.forEach(function (o, j) {
                    if (o.id == id) {
                        col.splice(j, 1);
                        return;
                    }
                });
            });
            
            this.getPreferences().unsetDashletOptions(id);
            this.updateDashletsLayout();
        },
        
        addDashlet: function (name) {
            var id = 'd' + (Math.floor(Math.random() * 1000001)).toString();
            
            this.dashboardLayout[0].unshift({
                name: name,
                id: id
            });
            
            this.updateDashletsLayout();
        
            $('#dashlets').children().first().prepend('<div id="dashlet-container-' + id + '"></div>');
            
            this.getView('dashlets').createView('dashlet-' + id, 'Dashlet', {
                label: name,
                name: name,
                id: id,
                el: '#dashlet-container-' + id
            }, function (view) {
                view.render();
            });
        },
    });
});

