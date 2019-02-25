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

Espo.define('crm:views/meeting/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            this.controlSendInvitationsButton();
            this.controlAcceptanceStatusButton();

            this.listenTo(this.model, 'sync', function () {
                this.controlSendInvitationsButton();
            }, this);

            this.listenTo(this.model, 'sync', function () {
                this.controlAcceptanceStatusButton();
            }, this);
        },

        controlAcceptanceStatusButton: function () {
            if (!this.model.has('status')) return;
            if (!this.model.has('usersIds')) return;

            if (~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                this.removeMenuItem('setAcceptanceStatus');
                return;
            }

            if (!~this.model.getLinkMultipleIdList('users').indexOf(this.getUser().id)) {
                this.removeMenuItem('setAcceptanceStatus');
                return;
            }

            var acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);

            var html;
            var style = 'default';
            if (acceptanceStatus && acceptanceStatus !== 'None') {
                html = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);
                style = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', acceptanceStatus]);
            } else {
                html = this.translate('Acceptance', 'labels', 'Meeting');
            }

            this.removeMenuItem('setAcceptanceStatus');

            this.addMenuItem('buttons', {
                html: html,
                action: 'setAcceptanceStatus',
                style: style
            });
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
                    html: this.translate('Send Invitations', 'labels', 'Meeting'),
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
                Espo.Ajax.postRequest(this.model.entityType + '/action/sendInvitations', {
                    id: this.model.id
                }).then(function (result) {
                    if (result) {
                        this.notify('Sent', 'success');
                    } else {
                        Espo.Ui.warning(this.translate('nothingHasBeenSent', 'messages', 'Meeting'));
                    }
                    this.enableMenuItem('sendInvitations');
                }.bind(this)).fail(function () {
                    this.enableMenuItem('sendInvitations');
                }.bind(this));
            }, this);
        },

        actionSetAcceptanceStatus: function () {
            var acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);

            this.createView('dialog', 'crm:views/meeting/modals/acceptance-status', {
                model: this.model
            }, function (view) {
                view.render();

                this.listenTo(view, 'set-status', function (status) {
                    this.removeMenuItem('setAcceptanceStatus');
                    Espo.Ajax.postRequest(this.model.entityType + '/action/setAcceptanceStatus', {
                        id: this.model.id,
                        status: status
                    }).then(function () {
                        this.model.fetch();
                    }.bind(this));
                });
            });
        }

    });
});
