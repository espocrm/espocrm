/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import Utils from 'utils';
import ModalView from 'views/modal';
import Collection from 'collection';
import Ajax from 'ajax';

export default class SendInvitationsModalView extends ModalView {

    backdrop = true

    templateContent = `
        <div class="margin-bottom">
            <p>{{message}}</p>
        </div>
        <div class="list-container">{{{list}}}</div>
    `

    data() {
        return {
            message: this.translate('sendInvitationsToSelectedAttendees', 'messages', 'Meeting'),
        };
    }

    setup() {
        this.shortcutKeys = {};
        this.shortcutKeys['Control+Enter'] = e => {
            if (!this.hasAvailableActionItem('send')) {
                return;
            }

            e.preventDefault();

            this.actionSend();
        };

        this.$header = $('<span>').append(
            $('<span>')
                .text(this.translate(this.model.entityType, 'scopeNames')),
            ' <span class="chevron-right"></span> ',
            $('<span>')
                .text(this.model.get('name')),
            ' <span class="chevron-right"></span> ',
            $('<span>')
                .text(this.translate('Send Invitations', 'labels', 'Meeting'))
        );

        this.addButton({
            label: 'Send',
            name: 'send',
            style: 'danger',
            disabled: true,
        });

        this.addButton({
            label: 'Cancel',
            name: 'cancel',
        });

        this.collection = new Collection();
        this.collection.url = this.model.entityType + `/${this.model.id}/attendees`;

        this.wait(
            this.prepareList()
        );
    }

    /**
     * @private
     * @return {Promise<void>}
     */
    async prepareList() {
        await this.collection.fetch();

        Utils.clone(this.collection.models).forEach(model => {
            model.entityType = model.get('_scope');

            if (!model.get('emailAddress')) {
                this.collection.remove(model.id);
            }
        });

        const view = await this.createView('list', 'views/record/list', {
            selector: '.list-container',
            collection: this.collection,
            rowActionsDisabled: true,
            massActionsDisabled: true,
            checkAllResultDisabled: true,
            selectable: true,
            buttonsDisabled: true,
            listLayout: [
                {
                    name: 'name',
                    customLabel: this.translate('name', 'fields'),
                    notSortable: true,
                },
                {
                    name: 'acceptanceStatus',
                    width: 40,
                    customLabel: this.translate('acceptanceStatus', 'fields', 'Meeting'),
                    notSortable: true,
                    view: 'views/fields/enum',
                    params: {
                        options: this.model.getFieldParam('acceptanceStatus', 'options'),
                        style: this.model.getFieldParam('acceptanceStatus', 'style'),
                    },
                },
            ],
        });

        this.collection.models
            .filter(model => {
                const status = model.get('acceptanceStatus');

                return !status || status === 'None';
            })
            .forEach(model => {
                this.getListView().checkRecord(model.id);
            });

        this.listenTo(view, 'check', () => this.controlSendButton());

        this.controlSendButton();
    }

    controlSendButton() {
        this.getListView().getCheckedIds().length ?
            this.enableButton('send') :
            this.disableButton('send');
    }

    /**
     * @return {import('views/record/list').default}
     */
    getListView() {
        return this.getView('list');
    }

    actionSend() {
        this.disableButton('send');

        Espo.Ui.notifyWait();

        const targets = this.getListView().checkedList.map(id => {
            return {
                entityType: this.collection.get(id).entityType,
                id: id,
            };
        });

        Ajax
            .postRequest(`${this.model.entityType}/action/sendInvitations`, {
                id: this.model.id,
                targets: targets,
            })
            .then(result => {
                result ?
                    Espo.Ui.success(this.translate('Sent')) :
                    Espo.Ui.warning(this.translate('nothingHasBeenSent', 'messages', 'Meeting'));

                this.trigger('sent');

                this.close();
            })
            .catch(() => {
                this.enableButton('send');
            });
    }
}
