/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

Espo.define('views/stream/notes/assign', 'views/stream/note', function (Dep) {

    return Dep.extend({

        template: 'stream/notes/assign',

        messageName: 'assign',

        data: function () {
            return _.extend({
            }, Dep.prototype.data.call(this));
        },

        init: function () {
            if (this.getUser().isAdmin()) {
                this.isRemovable = true;
            }
            Dep.prototype.init.call(this);
        },

        setup: function () {
            var data = this.model.get('data');

            this.assignedUserId = data.assignedUserId || null;
            this.assignedUserName = data.assignedUserName || null;

            this.messageData['assignee'] = '<a href="#User/view/' + this.getHelper().escapeString(data.assignedUserId) + '">' + this.getHelper().escapeString(data.assignedUserName) + '</a>';

            if (this.isUserStream) {
                if (this.assignedUserId) {
                    if (this.assignedUserId == this.model.get('createdById')) {
                        this.messageName += 'Self';
                    } else {
                        if (this.assignedUserId == this.getUser().id) {
                            this.messageName += 'You';
                        }
                    }
                } else {
                    this.messageName += 'Void';
                }
            } else {
                if (this.assignedUserId) {
                    if (this.assignedUserId == this.model.get('createdById')) {
                        this.messageName += 'Self';
                    }
                } else {
                    this.messageName += 'Void';
                }
            }

            this.createMessage();
        },

    });
});

