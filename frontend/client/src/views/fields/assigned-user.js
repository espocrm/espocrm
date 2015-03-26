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

Espo.define('Views.Fields.AssignedUser', 'Views.Fields.UserWithAvatar', function (Dep) {

    return Dep.extend({

        init: function () {
            this.assignmentPermission = this.getAcl().get('assignmentPermission');
            if (this.assignmentPermission == 'no') {
                this.readOnly = true;
            }
            Dep.prototype.init.call(this);
        },

        getSelectBoolFilterList: function () {
            if (this.assignmentPermission == 'team') {
                return {'onlyMyTeam': true};
            }
        },

        getAutocompleteUrl: function () {
            var url = Dep.prototype.getAutocompleteUrl.call(this);
            if (this.assignmentPermission == 'team') {
                url += '&where%5B0%5D%5Btype%5D=bool&where%5B0%5D%5Bvalue%5D%5B%5D=onlyMyTeam';
            }

            return url;
        },

    });
});

