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

Espo.define('Views.Record.ListExpanded', 'Views.Record.List', function (Dep) {

    return Dep.extend({

        template: 'record.list-expanded',

        checkboxes: false,

        selectable: false,

        rowActionsView: false,            
        
        _internalLayoutType: 'list-row-expanded',
        
        presentationType: 'expanded',
        
        pagination: false,

        header: false,

        _internalLayout: null,

        checkedList: null,
        
        listContainerEl: '.list > ul',

        _loadListLayout: function (callback) {
            var type = this.type + 'Expanded';
            this._helper.layoutManager.get(this.collection.name, type, function (listLayout) {
                callback(listLayout);
            });
        },

        _convertLayout: function (listLayout, model) {                
            model = model || this.collection.model.prototype;
            
            var layout = {
                rows: [],
                right: false,
            };                                

            for (var i in listLayout.rows) {
                var row = listLayout.rows[i];                    
                var layoutRow = [];                 
                for (var j in row) {
                
                    var e = row[j];
                    var type = e.type || model.getFieldType(e.name) || 'base';
                    
                    var item = {
                        name: e.name,
                        view: e.view || model.getFieldParam(e.name, 'view') || this.getFieldManager().getViewName(type),
                        options: {
                            defs: {
                                name: e.name,
                                params: e.params || {}
                            },
                            mode: 'list'
                        }
                    };
                    if (e.link) {
                        item.options.mode = 'listLink';
                    }
                    layoutRow.push(item);                        
                }
                layout.rows.push(layoutRow);
            }
            
            if ('right' in listLayout) {    
                if (listLayout.right != false) {            
                    layout.right = {
                        name: listLayout.right.name || 'right',
                        view: listLayout.right.view,
                        options: {
                            defs: {
                                params: {
                                    width: listLayout.right.width || '7%'
                                }
                            }
                        }
                    };
                }    
            } else {            
                if (this.rowActionsView) {
                    layout.right = this.getRowActionsDefs();
                }
            }
            return layout;
        },
    });
});


