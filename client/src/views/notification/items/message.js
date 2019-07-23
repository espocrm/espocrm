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

Espo.define('views/notification/items/message', 'views/notification/items/base', function (Dep) {

    return Dep.extend({

        template: 'notification/items/message',

        data: function () {
            return _.extend({
                style: this.style,
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            var data = this.model.get('data') || {};

            this.style = data.style || 'text-muted';

            this.messageTemplate = this.model.get('message') || data.message || '';

            this.userId = data.userId;

            this.messageData['entityType'] = this.getHelper().escapeString(Espo.Utils.upperCaseFirst((this.translate(data.entityType, 'scopeNames') || '').toLowerCase()));

            this.messageData['user'] = '<a href="#User/view/' + this.getHelper().escapeString(data.userId) + '">' + this.getHelper().escapeString(data.userName) + '</a>';
            this.messageData['entity'] = '<a href="#'+this.getHelper().escapeString(data.entityType)+'/view/' + this.getHelper().escapeString(data.entityId) + '">' + this.getHelper().escapeString(data.entityName) + '</a>';

            this.createMessage();
        }

    });
});

