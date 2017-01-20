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

Espo.define('Views.Fields.LinkMultipleWithRoleActive', 'Views.Fields.LinkMultipleWithRole', function (Dep) {

    return Dep.extend({

        type: 'linkMultipleWithRoleActive',

        getDetailLinkHtml: function (id, name) {
            name = name || this.nameHash[id];

            var role = (this.columns[id] || {})[this.columnName] || '';
            var active = typeof this.columns[id] === 'undefined' || typeof this.columns[id].active === 'undefined' || this.columns[id].active;
            var roleHtml = '';
            if (role != '') {
                roleHtml = '<span class="text-muted small"> &#187; ' + this.getLanguage().translateOption(role, this.roleField, this.roleFieldScope) + '</span>';
            }
            if (!active) {
                return '<div>' + '<a href="#' + this.foreignScope + '/view/' + id + '"><span style="text-decoration:line-through;color:#999">' + name + '</span></a> ' + roleHtml + '</div>';
            } else {
                return '<div>' + '<a href="#' + this.foreignScope + '/view/' + id + '">' + name + '</a> ' + roleHtml + '</div>';
            }
        },

    });
});


