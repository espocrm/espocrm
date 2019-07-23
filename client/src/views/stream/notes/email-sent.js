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

Espo.define('views/stream/notes/email-sent', 'views/stream/note', function (Dep) {

    return Dep.extend({

        template: 'stream/notes/email-sent',

        isRemovable: false,

        data: function () {
            return _.extend({
                emailId: this.emailId,
                emailName: this.emailName,
                hasPost: this.hasPost,
                hasAttachments: this.hasAttachments,
                emailIconClassName: this.getMetadata().get(['clientDefs', 'Email', 'iconClass']) || ''
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            var data = this.model.get('data') || {};

            this.emailId = data.emailId;
            this.emailName = data.emailName;

            if (
                this.parentModel
                &&
                (this.model.get('parentType') == this.parentModel.name && this.model.get('parentId') == this.parentModel.id)
            ) {
                if (this.model.get('post')) {
                    this.createField('post', null, null, 'views/stream/fields/post');
                    this.hasPost = true;
                }
                if ((this.model.get('attachmentsIds') || []).length) {
                    this.createField('attachments', 'attachmentMultiple', {}, 'views/stream/fields/attachment-multiple');
                    this.hasAttachments = true;
                }
            }

            this.messageData['email'] = '<a href="#Email/view/' + this.getHelper().escapeString(data.emailId) + '">' + this.getHelper().escapeString(data.emailName) + '</a>';

            this.messageName = 'emailSent';

            this.messageData['by'] = '<a href="#'+this.getHelper().escapeString(data.personEntityType)+'/view/' + this.getHelper().escapeString(data.personEntityId) + '">' + this.getHelper().escapeString(data.personEntityName) + '</a>';


            if (this.isThis) {
                this.messageName += 'This';
            }

            this.createMessage();
        },

    });
});
