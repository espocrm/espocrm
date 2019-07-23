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

Espo.define('views/stream/notes/create', 'views/stream/note', function (Dep) {

    return Dep.extend({

        template: 'stream/notes/create',

        assigned: false,

        messageName: 'create',

        isRemovable: false,

        data: function () {
            return _.extend({
                statusText: this.statusText,
                statusStyle: this.statusStyle
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            if (this.model.get('data')) {
                var data = this.model.get('data');

                this.assignedUserId = data.assignedUserId || null;
                this.assignedUserName = data.assignedUserName || null;

                this.messageData['assignee'] = '<a href="#User/view/' + this.assignedUserId + '">' + this.getHelper().escapeString(this.assignedUserName) + '</a>';

                var isYou = false;
                if (this.isUserStream) {
                    if (this.assignedUserId == this.getUser().id) {
                        isYou = true;
                    }
                }

                if (this.assignedUserId) {
                    this.messageName = 'createAssigned';

                    if (this.isThis) {
                        this.messageName += 'This';

                        if (this.assignedUserId == this.model.get('createdById')) {
                            this.messageName += 'Self';
                        }
                    } else {
                        if (this.assignedUserId == this.model.get('createdById')) {
                            this.messageName += 'Self';
                        } else {
                            if (isYou) {
                                this.messageName += 'You';
                            }
                        }
                    }
                }

                if (data.statusField) {
                    var statusField = this.statusField = data.statusField;
                    var statusValue = data.statusValue;
                    this.statusStyle = data.statusStyle || 'default';
                    this.statusText = this.getLanguage().translateOption(statusValue, statusField, this.model.get('parentType'));
                }
            }

            this.createMessage();
        },
    });
});

