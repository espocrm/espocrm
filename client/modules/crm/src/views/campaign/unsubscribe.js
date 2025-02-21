/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import View from 'view';

class CampaignUnsubscribeView extends View {

    template = 'crm:campaign/unsubscribe'

    data() {
        return {
            isSubscribed: this.isSubscribed,
            inProcess: this.inProcess,
        };
    }

    setup() {
        super.setup();

        this.actionData = /** @type {Record} */this.options.actionData;

        this.isSubscribed = this.actionData.isSubscribed;
        this.inProcess = false;

        const endpointUrl = this.actionData.hash && this.actionData.emailAddress ?
            `Campaign/unsubscribe/${this.actionData.emailAddress}/${this.actionData.hash}`:
            `Campaign/unsubscribe/${this.actionData.queueItemId}`;

        this.addActionHandler('subscribe', () => {
            Espo.Ui.notifyWait();

            this.inProcess = true;
            this.reRender();

            Espo.Ajax.deleteRequest(endpointUrl)
                .then(() => {
                    this.isSubscribed = true;
                    this.inProcess = false;

                    this.reRender().then(() => {
                        const message = this.translate('subscribedAgain', 'messages', 'Campaign');

                        Espo.Ui.notify(message, 'success', 0, {closeButton: true});
                    });
                })
                .catch(() => {
                    this.inProcess = false;
                    this.reRender();
                });
        });

        this.addActionHandler('unsubscribe', () => {
            Espo.Ui.notifyWait();

            this.inProcess = true;
            this.reRender();

            Espo.Ajax.postRequest(endpointUrl)
                .then(() => {
                    Espo.Ui.success(this.translate('unsubscribed', 'messages', 'Campaign'), {closeButton: true});

                    this.isSubscribed = false;
                    this.inProcess = false;

                    this.reRender().then(() => {
                        const message = this.translate('unsubscribed', 'messages', 'Campaign');

                        Espo.Ui.notify(message, 'success', 0, {closeButton: true});
                    });
                })
                .catch(() => {
                    this.inProcess = false;
                    this.reRender();
                });
        });
    }
}

export default CampaignUnsubscribeView;
