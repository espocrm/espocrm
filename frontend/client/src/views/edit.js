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

Espo.define('Views.Edit', 'Views.Main', function (Dep) {

    return Dep.extend({

        template: 'edit',

        el: '#main',

        scope: null,
        
        name: 'Edit',
        
        menu: null,
        
        optionsToPass: ['returnUrl'],

        views: {
            header: {
                selector: '> .page-header',
                view: 'Header'
            },
            body: {
                view: 'Record.Edit',
                selector: '> .body'
            }
        },

        setup: function () {
        },

        getHeader: function () {
            var html = '';
            
            if (this.options.noHeaderLinks) {
                html += this.getLanguage().translate(this.model.name, 'scopeNamesPlural');
            } else {
                html += '<a href="#' + this.model.name + '">' + this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>';
            }
            
            html += ' &raquo ';
            if (this.model.isNew()) {
                html += this.getLanguage().translate('create');
            } else {
                var name = Handlebars.Utils.escapeExpression(this.model.get('name'));
                if (this.options.noHeaderLinks) {
                    html += name;
                } else {
                    html += '<a href="#' + this.model.name + '/view/' + this.model.id + '">' + name + '</a>';
                }
            }
            return html;
        },

        updatePageTitle: function () {
            var title;
            if (this.model.isNew()) {
                title = this.getLanguage().translate('Create') + ' ' + this.getLanguage().translate(this.model.name, 'scopeNames');
            } else {
                var name = this.model.get('name');
                if (name) {
                    title = name;
                } else {
                    title = this.getLanguage().translate(this.model.name, 'scopeNames')
                }
            }
            this.setPageTitle(title);
        },
    });
});


