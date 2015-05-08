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

Espo.define('Views.Stream.Notes.CreateRelated', 'Views.Stream.Note', function (Dep) {

    return Dep.extend({

        template: 'stream.notes.create-related',

        messageName: 'createRelated',

        data: function () {
            return _.extend({
                relatedTypeString: this.translate(this.entityType, 'scopeNames').toLowerCase()
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

            this.messageData['relatedEntityType'] = this.translate(this.entityType, 'scopeNames').toLowerCase();
            this.messageData['relatedEntity'] = '<a href="#' + this.entityType + '/view/' + this.entityId + '">' + this.entityName +'</a>';

            this.createMessage();
        },
    });
});

