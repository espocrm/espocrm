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

Espo.define('views/stream/notes/mention-in-post', 'views/stream/note', function (Dep) {

    return Dep.extend({

        template: 'stream/notes/post',

        messageName: 'mentionInPost',

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.showAttachments = !!(this.model.get('attachmentsIds') || []).length;
            data.showPost = !!this.model.get('post');
            return data;
        },

        setup: function () {
            if (this.model.get('post')) {
                this.createField('post', null, null, 'views/stream/fields/post');
            }
            if ((this.model.get('attachmentsIds') || []).length) {
                this.createField('attachments', 'attachmentMultiple', {}, 'views/stream/fields/attachment-multiple', {
                    previewSize: this.options.isNotification ? 'small' : null
                });
            }

            var data = this.model.get('data');

            this.messageData['mentioned'] = this.options.userId;

            if (!this.model.get('parentId')) {
                this.messageName = 'mentionInPostTarget';
            }

            if (this.isUserStream) {
                if (this.options.userId == this.getUser().id) {
                    if (!this.model.get('parentId')) {
                        this.messageName = 'mentionYouInPostTarget';
                        if (this.model.get('isGlobal')) {
                            this.messageName = 'mentionYouInPostTargetAll';
                        } else {
                            this.messageName = 'mentionYouInPostTarget';
                            if (this.model.has('teamsIds') && this.model.get('teamsIds').length) {
                                var teamIdList = this.model.get('teamsIds');
                                var teamNameHash = this.model.get('teamsNames') || {};

                                var targetHtml = '';
                                var teamHtmlList = [];
                                teamIdList.forEach(function (teamId) {
                                    var teamName = teamNameHash[teamId];
                                    if (teamName) {
                                        teamHtmlList.push('<a href="#Team/view/' + this.getHelper().escapeString(teamId) + '">' + this.getHelper().escapeString(teamName) + '</a>');
                                    }
                                }, this);

                                this.messageData['target'] = teamHtmlList.join(', ');
                            } else if (this.model.has('usersIds') && this.model.get('usersIds').length) {
                                var userIdList = this.model.get('usersIds');
                                var userNameHash = this.model.get('usersNames') || {};

                                if (userIdList.length === 1 && userIdList[0] === this.model.get('createdById')) {
                                    this.messageName = 'mentionYouInPostTargetNoTarget';
                                } else {
                                    var userHtml = '';
                                    var userHtmlList = [];
                                    userIdList.forEach(function (userId) {
                                        var userName = userNameHash[userId];
                                        if (userName) {
                                            userHtmlList.push('<a href="#User/view/' + this.getHelper().escapeString(userId) + '">' + this.getHelper().escapeString(userName) + '</a>');
                                        }
                                    }, this);
                                    this.messageData['target'] = userHtmlList.join(', ');
                                }
                            } else if (this.model.get('targetType') === 'self') {
                                this.messageName = 'mentionYouInPostTargetNoTarget';
                            }
                        }
                    } else {
                        this.messageName = 'mentionYouInPost';
                    }
                }
            }

            this.createMessage();
        }

    });
});
