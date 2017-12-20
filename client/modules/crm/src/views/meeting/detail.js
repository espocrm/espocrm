/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('crm:views/meeting/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.controlSendInvitationsButton();
            this.listenTo(this.model, 'change', function () {
                if (
                    this.model.hasChanged('status')
                    ||
                    this.model.hasChanged('teamsIds')
                ) {
                    this.controlSendInvitationsButton();
                }
            }.bind(this));
        },

        controlSendInvitationsButton: function () {
            var show = true;;

            if (
                ~['Held', 'Not Held'].indexOf(this.model.get('status'))
            ) {
                show = false;
            }

            if (show && (!this.getAcl().checkModel(this.model, 'edit') || !this.getAcl().checkScope('Email', 'create'))) {
                show = false;
            }

            if (show) {
                var userIdList = this.model.getLinkMultipleIdList('users');
                var contactIdList = this.model.getLinkMultipleIdList('contacts');
                var leadIdList = this.model.getLinkMultipleIdList('leads');

                if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
                    show = false;
                }
            }

            if (show) {
                this.addMenuItem('buttons', {
                    label: 'Send Invitations',
                    action: 'sendInvitations',
                    acl: 'edit',
                });
            } else {
                this.removeMenuItem('sendInvitations');
            }
        },

        actionSendInvitations: function () {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                 this.disableMenuItem('sendInvitations');
                this.notify('Sending...');
                $.ajax({
                    url: 'Meeting/action/sendInvitations',
                    type: 'POST',
                    data: JSON.stringify({
                        id: this.model.id
                    }),
                    success: function (result) {
                        if (result) {
                            this.notify('Sent', 'success');
                        } else {
                            Espo.Ui.warning(this.translate('nothingHasBeenSent', 'messages', 'Meeting'));
                        }

                        this.enableMenuItem('sendInvitations');
                    }.bind(this),
                    error: function () {
                        this.enableMenuItem('sendInvitations');
                    }.bind(this),
                });
            }, this);
        }

    });
});

