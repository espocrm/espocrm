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

Espo.define('Views.Stream.Note', 'View', function (Dep) {

    return Dep.extend({
    
        messageName: null,
        
        messageTemplate: null,
        
        messageData: null,
    
        data: function () {
            return {
                isUserStream: this.isUserStream,
                acl: this.options.acl,
                onlyContent: this.options.onlyContent,
                avatar: this.getAvatarHtml()
            };
        },
    
        init: function () {
            this.createField('createdAt', null, null, 'Fields.DatetimeShort');
            this.isUserStream = this.options.isUserStream;
            
            if (this.isUserStream) {
                this.createField('parent');
            }
            
            if (this.messageName) {
                if (!this.isUserStream) {
                    this.messageName += 'This';
                }
            }
            
            this.messageData = {
                'user': 'field:createdBy',
                'entity': 'field:parent',
                'entityType': (this.translate(this.model.get('parentType'), 'scopeNames') || '').toLowerCase(),
            };
        },
        
        createField: function (name, type, params, view) {
            type = type || this.model.getFieldType(name) || 'base';
            this.createView(name, view || this.getFieldManager().getViewName(type), {
                model: this.model,
                defs: {
                    name: name,
                    params: params || {}
                },
                el: this.options.el + ' .cell-' + name,
                mode: 'list'
            });
            
        },
        
        
        createMessage: function () {
            if (!this.messageTemplate) {
                this.messageTemplate = this.translate(this.messageName, 'streamMessages') || '';
            }
            
            this.createView('message', 'Stream.Message', {
                messageTemplate: this.messageTemplate,
                el: this.options.el + ' .message',
                model: this.model,
                messageData: this.messageData
            });
        },

        getAvatarHtml: function () {
            var t;
            var cache = this.getCache();
            if (cache) {
                t = cache.get('app', 'timestamp');
            } else {
                t = Date.now();
            }
            return '<img class="avatar" width="18" src="?entryPoint=avatar&size=small&id=' + this.model.get('createdById') + '&t='+t+'">';
        },
        

    });
});

