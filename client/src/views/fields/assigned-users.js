/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/fields/assigned-users', ['views/fields/link-multiple'], function (Dep) {

    return Dep.extend({

        init: function () {
            this.assignmentPermission = this.getAcl().get('assignmentPermission');

            if (this.assignmentPermission === 'no') {
                this.readOnly = true;
            }

            Dep.prototype.init.call(this);
        },

        getSelectBoolFilterList: function () {
            if (this.assignmentPermission === 'team') {
                return ['onlyMyTeam'];
            }
        },

        getSelectPrimaryFilterName: function () {
            return 'active';
        },
        
        getDetailLinkHtml: function (id) {
            let name = this.nameHash[id] || id;

            if (!name && id) {
                name = this.translate(this.foreignScope, 'scopeNames');
            }

            let $a = $('<a>')
                .attr('href', this.getUrl(id))
                .attr('data-id', id)
                .text(name);

            $a.prepend(this.getAvatarHtml(id));

            return $a.get(0).outerHTML;
        },

        getAvatarHtml: function (id) {
            return this.getHelper()
                .getAvatarHtml(id, 'small', 14, 'avatar-link');
        },
    });
});
