/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('crm:views/meeting/detail', ['views/detail', 'lib!moment'], function (Dep, moment) {

    return Dep.extend({

        cancellationPeriod: '8 hours',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.controlSendInvitationsButton();
            this.controlAcceptanceStatusButton();
            this.controlSendCancellationButton();

            this.listenTo(this.model, 'sync', () => {
                this.controlSendInvitationsButton();
                this.controlSendCancellationButton();
            });

            this.listenTo(this.model, 'sync', () => {
                this.controlAcceptanceStatusButton();
            });

            this.setupCancellationPeriod();
        },

        setupCancellationPeriod: function () {
            this.cancellationPeriodAmount = 0;
            this.cancellationPeriodUnits = 'hours';

            let cancellationPeriod = this.getConfig().get('eventCancellationPeriod') || this.cancellationPeriod;

            if (!cancellationPeriod) {
                return;
            }

            let arr = cancellationPeriod.split(' ');

            this.cancellationPeriodAmount = parseInt(arr[0]);
            this.cancellationPeriodUnits = arr[1] ?? 'hours';
        },

        controlAcceptanceStatusButton: function () {
            if (!this.model.has('status')) {
                return;
            }

            if (!this.model.has('usersIds')) {
                return;
            }

            if (~['Held', 'Not Held'].indexOf(this.model.get('status'))) {
                this.removeMenuItem('setAcceptanceStatus');

                return;
            }

            if (!~this.model.getLinkMultipleIdList('users').indexOf(this.getUser().id)) {
                this.removeMenuItem('setAcceptanceStatus');

                return;
            }

            let acceptanceStatus = this.model.getLinkMultipleColumn('users', 'status', this.getUser().id);

            let text;
            let style = 'default';

            if (acceptanceStatus && acceptanceStatus !== 'None') {
                text = this.getLanguage().translateOption(acceptanceStatus, 'acceptanceStatus', this.model.entityType);

                style = this.getMetadata()
                    .get(['entityDefs', this.model.entityType, 'fields',
                        'acceptanceStatus', 'style', acceptanceStatus]);
            }
            else {
                text = this.translate('Acceptance', 'labels', 'Meeting');
            }

            this.removeMenuItem('setAcceptanceStatus', true);

            let iconHtml = '';

            if (style) {
                let iconClass = ({
                    'success': 'fas fa-check-circle',
                    'danger': 'fas fa-times-circle',
                    'warning': 'fas fa-question-circle',
                })[style];

                iconHtml = $('<span>')
                    .addClass(iconClass)
                    .addClass('text-' + style)
                    .get(0).outerHTML;
            }

            this.addMenuItem('buttons', {
                text: text,
                action: 'setAcceptanceStatus',
                iconHtml: iconHtml,
            });
        },

        controlSendInvitationsButton: function () {
            let show = true;

            if (['Held', 'Not Held'].includes(this.model.get('status'))) {
                show = false;
            }

            if (
                show &&
                !this.getAcl().checkModel(this.model, 'edit')
            ) {
                show = false;
            }

            if (show) {
                let userIdList = this.model.getLinkMultipleIdList('users');
                let contactIdList = this.model.getLinkMultipleIdList('contacts');
                let leadIdList = this.model.getLinkMultipleIdList('leads');

                if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
                    show = false;
                }
                /*else if (
                    !contactIdList.length &&
                    !leadIdList.length &&
                    userIdList.length === 1 &&
                    userIdList[0] === this.getUser().id &&
                    this.model.getLinkMultipleColumn('users', 'status', this.getUser().id) === 'Accepted'
                ) {
                    show = false;
                }*/
            }

            if (show) {
                let dateEnd = this.model.get('dateEnd');

                if (
                    dateEnd &&
                    this.getDateTime().toMoment(dateEnd).isBefore(moment.now())
                ) {
                    show = false;
                }
            }

            if (show) {
                this.addMenuItem('buttons', {
                    text: this.translate('Send Invitations', 'labels', 'Meeting'),
                    action: 'sendInvitations',
                    acl: 'edit',
                });

                return;
            }

            this.removeMenuItem('sendInvitations');
        },

        controlSendCancellationButton: function () {
            let show = this.model.get('status') === 'Not Held';

            if (show) {
                let dateEnd = this.model.get('dateEnd');

                if (
                    dateEnd &&
                    this.getDateTime()
                        .toMoment(dateEnd)
                        .subtract(this.cancellationPeriodAmount, this.cancellationPeriodUnits)
                        .isBefore(moment.now())
                ) {
                    show = false;
                }
            }

            if (show) {
                let userIdList = this.model.getLinkMultipleIdList('users');
                let contactIdList = this.model.getLinkMultipleIdList('contacts');
                let leadIdList = this.model.getLinkMultipleIdList('leads');

                if (!contactIdList.length && !leadIdList.length && !userIdList.length) {
                    show = false;
                }
            }

            if (show) {
                this.addMenuItem('dropdown', {
                    text: this.translate('Send Cancellation', 'labels', 'Meeting'),
                    action: 'sendCancellation',
                    acl: 'edit',
                });

                return;
            }

            this.removeMenuItem('sendCancellation');
        },

        actionSendInvitations: function () {
            Espo.Ui.notify(' ... ');

            this.createView('dialog', 'crm:views/meeting/modals/send-invitations', {
                model: this.model,
            }).then(view => {
                Espo.Ui.notify(false);

                view.render();

                this.listenToOnce(view, 'sent', () => this.model.fetch());
            });
        },

        actionSendCancellation: function () {
            Espo.Ui.notify(' ... ');

            this.createView('dialog', 'crm:views/meeting/modals/send-cancellation', {
                model: this.model,
            }).then(view => {
                Espo.Ui.notify(false);

                view.render();

                this.listenToOnce(view, 'sent', () => this.model.fetch());
            });
        },

        actionSetAcceptanceStatus: function () {
            this.createView('dialog', 'crm:views/meeting/modals/acceptance-status', {
                model: this.model
            }, (view) => {
                view.render();

                this.listenTo(view, 'set-status', (status) => {
                    this.removeMenuItem('setAcceptanceStatus');

                    Espo.Ajax.postRequest(this.model.entityType + '/action/setAcceptanceStatus', {
                        id: this.model.id,
                        status: status,
                    }).then(() => {
                        this.model.fetch();
                    });
                });
            });
        },
    });
});
