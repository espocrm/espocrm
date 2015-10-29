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

Espo.define('views/stream/notes/post', 'views/stream/note', function (Dep) {

    return Dep.extend({

        template: 'stream.notes.post',

        messageName: 'post',

        isEditable: true,

        isRemovable: true,

        setup: function () {
            if (this.model.get('post')) {
                this.createField('post', null, null, 'views/stream/fields/post');
            }
            if ((this.model.get('attachmentsIds') || []).length) {
                this.createField('attachments', 'attachmentMultiple', {}, 'views/stream/fields/attachment-multiple');

                if (!this.model.get('post') && this.model.get('parentId')) {
                    this.messageName = 'attach';
                    if (this.isThis) {
                        this.messageName += 'This';
                    }
                }
            }

            if (!this.model.get('parentId')) {
                if (this.model.get('isGlobal')) {
                    this.messageName = 'postTargetAll';
                } else {
                    if (this.model.has('teamsIds') && this.model.get('teamsIds').length) {
                        var teamIdList = this.model.get('teamsIds');
                        var teamNameHash = this.model.get('teamsNames') || {};
                        this.messageName = 'postTargetTeam';
                        if (teamIdList.length > 1) {
                            this.messageName = 'postTargetTeams';
                        }

                        var targetHtml = '';
                        var teamHtmlList = [];
                        teamIdList.forEach(function (teamId) {
                            var teamName = teamNameHash[teamId];
                            if (teamName) {
                                teamHtmlList.push('<a href="#Team/view/' + teamId + '">' + teamName + '</a>');
                            }
                        }, this);

                        this.messageData['target'] = teamHtmlList.join(', ');
                    } else if (this.model.has('usersIds') && this.model.get('usersIds').length) {
                        var userIdList = this.model.get('usersIds');
                        var userNameHash = this.model.get('usersNames') || {};

                        this.messageName = 'postTarget';

                        if (userIdList.length === 1 && userIdList[0] === this.model.get('createdById')) {
                            this.messageName = 'postTargetSelf';
                        } else {
                            var userHtml = '';
                            var userHtmlList = [];
                            userIdList.forEach(function (userId) {
                                if (userId === this.getUser().id) {
                                    this.messageName = 'postTargetYou';
                                    if (userIdList.length > 1) {
                                        if (userId === this.model.get('createdById')) {
                                            this.messageName = 'postTargetSelfAndOthers';
                                        } else {
                                            this.messageName = 'postTargetYouAndOthers';
                                        }
                                    }
                                } else {
                                    if (userId === this.model.get('createdById')) {
                                        this.messageName = 'postTargetSelfAndOthers';
                                    } else {
                                        var userName = userNameHash[userId];
                                        if (userName) {
                                            userHtmlList.push('<a href="#User/view/' + userId + '">' + userName + '</a>');
                                        }
                                    }
                                }
                            }, this);
                            this.messageData['target'] = userHtmlList.join(', ');
                        }
                    }
                }
            }

            this.createMessage();
        },
    });
});

