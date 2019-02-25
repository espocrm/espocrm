/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('acl/email', 'acl', function (Dep) {

    return Dep.extend({

        checkModelRead: function (model, data, precise) {
            var result = this.checkModel(model, data, 'read', precise);

            if (result) {
                return true;
            }

            if (data === false) {
                return false;
            }

            var d = data || {};
            if (d.read === 'no') {
                return false;
            }

            if (model.has('usersIds')) {
                if (~(model.get('usersIds') || []).indexOf(this.getUser().id)) {
                    return true;
                }
            } else {
                if (precise) {
                    return null;
                }
            }

            return result;
        },

        checkIsOwner: function (model) {
            if (this.getUser().id === model.get('assignedUserId') || this.getUser().id === model.get('createdById')) {
                return true;
            }

            if (!model.has('assignedUsersIds')) {
                return null;
            }

            if (~(model.get('assignedUsersIds') || []).indexOf(this.getUser().id)) {
                return true;
            }

            return false;
        },

        checkModelDelete: function (model, data, precise) {
            var result = this.checkModel(model, data, 'delete', precise);

            if (result) {
                return true;
            }

            if (data === false) {
                return false;
            }

            var d = data || {};
            if (d.read === 'no') {
                return false;
            }

            if (model.get('createdById') === this.getUser().id) {
                if (model.get('status') !== 'Sent' && model.get('status') !== 'Archived') {
                    return true;
                }
            }

            return result;
        }
    });
});
