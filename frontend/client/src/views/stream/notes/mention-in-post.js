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

Espo.define('Views.Stream.Notes.MentionInPost', 'Views.Stream.Note', function (Dep) {

    return Dep.extend({
            
        template: 'stream.notes.post',
        
        messageName: 'mentionInPost',
        
        setup: function () {        
            if (this.model.get('post')) {
                this.createField('post', null, null, 'Stream.Fields.Post');
            }            
            if ((this.model.get('attachmentsIds') || []).length) {
                this.createField('attachments', 'attachmentMultiple', {}, 'Stream.Fields.AttachmentMultiple');
            }
            
            var data = this.model.get('data');
            
            this.messageData['mentioned'] = this.options.userId;
            
            if (this.isUserStream) {
                if (this.options.userId == this.getUser().id) {
                    this.messageData['mentioned'] = this.translate('you');
                }
            }
            
            this.createMessage();
        },        
    });
});

