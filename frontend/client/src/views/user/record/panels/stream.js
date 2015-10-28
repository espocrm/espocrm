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

Espo.define('views/user/record/panels/stream', 'views/stream/panel', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            var assignmentPermission = this.getAcl().checkAssignmentPermission(this.model);

            this.placeholderText = this.translate('writeMessageToUser', 'messages').replace('{user}', this.model.get('name'));

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

    });

});

