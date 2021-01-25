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

Espo.define('views/user/record/panels/stream', 'views/stream/panel', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            var assignmentPermission = this.getAcl().checkAssignmentPermission(this.model);

            if (this.model.id == this.getUser().id) {
                this.placeholderText = this.translate('writeMessageToSelf', 'messages');
            } else {
                this.placeholderText = this.translate('writeMessageToUser', 'messages').replace('{user}', this.model.get('name'));
            }

            if (!assignmentPermission) {
                this.postDisabled = true;

                if (this.getAcl().get('assignmentPermission') === 'team') {
                    if (!this.model.has('teamsIds')) {
                        this.listenToOnce(this.model, 'sync', function () {
                            assignmentPermission = this.getAcl().checkUserPermission(this.model);
                            if (assignmentPermission) {
                                this.postDisabled = false;
                                this.$el.find('.post-container').removeClass('hidden');;
                            }
                        }, this);
                    }
                }
            }
        },

        prepareNoteForPost: function (model) {
            var userIdList = [this.model.id];
            var userNames = {};
            userNames[userIdList] = this.model.get('name');

            model.set('usersIds', userIdList);
            model.set('usersNames', userNames);
            model.set('targetType', 'users');
        }

    });

});

