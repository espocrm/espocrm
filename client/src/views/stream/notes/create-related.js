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

Espo.define('views/stream/notes/create-related', 'views/stream/note', function (Dep) {

    return Dep.extend({

        template: 'stream/notes/create-related',

        messageName: 'createRelated',

        data: function () {
            return _.extend({
                relatedTypeString: this.translateEntityType(this.entityType),
                iconHtml: this.getIconHtml(this.entityType, this.entityId)
            }, Dep.prototype.data.call(this));
        },

        init: function () {
            if (this.getUser().isAdmin()) {
                this.isRemovable = true;
            }
            Dep.prototype.init.call(this);
        },

        setup: function () {
            var data = this.model.get('data') || {};

            this.entityType = this.model.get('relatedType') || data.entityType || null;
            this.entityId = this.model.get('relatedId') || data.entityId || null;
            this.entityName = this.model.get('relatedName') ||  data.entityName || null;

            this.messageData['relatedEntityType'] = this.translateEntityType(this.entityType);
            this.messageData['relatedEntity'] = '<a href="#' + this.getHelper().escapeString(this.entityType) + '/view/' + this.getHelper().escapeString(this.entityId) + '">' + this.getHelper().escapeString(this.entityName) +'</a>';

            this.createMessage();
        }
    });
});

