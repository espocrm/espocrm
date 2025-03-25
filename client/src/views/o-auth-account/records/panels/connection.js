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

import SidePanelView from 'views/record/panels/side';

export default class OAuthAccountConnectionPanelView extends SidePanelView {

    // language=Handlebars
    templateContent = `
        {{#if hasDisconnect}}
            <div class="margin-bottom">
                <span
                    class="label label-success label-md"
                >{{translate 'Connected' scope='ExternalAccount'}}</span>
            </div>
            <button
                class="btn btn-default"
                data-action="disconnect"
            >{{translate 'Disconnect' scope='ExternalAccount'}}</button>
        {{/if}}

        {{#if hasConnect}}
            <div class="margin-bottom">
                <span
                    class="label label-default label-md"
                >{{translate 'Disconnected' scope='ExternalAccount'}}</span>
            </div>
            <button
                class="btn btn-default"
                data-action="connect"
            >{{translate 'Connect' scope='ExternalAccount'}}</button>
        {{/if}}
    `

    /**
     * @private
     * @type {boolean}
     */
    inProcess = false

    data() {
        const isSet = this.model.attributes.hasAccessToken !== undefined;

        const hasDisconnect = !this.inProcess &&
            isSet &&
            this.model.attributes.hasAccessToken;

        const hasConnect =
            !this.inProcess &&
            isSet &&
            !this.model.attributes.hasAccessToken &&
            this.model.attributes.providerIsActive;

        // noinspection JSValidateTypes
        return {
            hasDisconnect,
            hasConnect,
        }
    }

    setup() {
        super.setup();

        this.listenTo(this.model, 'sync', () => this.reRender());

        this.addActionHandler('connect', () => this.actionConnect());
        this.addActionHandler('disconnect', () => this.actionDisconnect());
    }

    /**
     * @private
     */
    async actionDisconnect() {
        this.inProcess = true;

        await this.reRender();

        Espo.Ui.notifyWait();

        await Espo.Ajax.deleteRequest(`OAuth/${this.model.id}/connection`);

        await this.model.fetch();

        Espo.Ui.notify();

        this.inProcess = false;

        await this.reRender();
    }

    /**
     * @private
     */
    async actionConnect() {
        const data = this.model.attributes.data || {};

        const endpoint = data.endpoint;
        const redirectUri = data.redirectUri;
        const clientId = data.clientId;
        const scope = data.scope;
        const prompt = data.prompt;
        const params = data.params;

        const proxy = window.open('about:blank', 'ConnectWithOAuth', 'location=0,status=0,width=800,height=800');

        const info = await this.processWithData({
            endpoint,
            redirectUri,
            clientId,
            scope,
            prompt,
            params,
        }, proxy);

        this.inProcess = true;

        await this.reRender()

        Espo.Ui.notifyWait();

        try {
            await Espo.Ajax.postRequest(`OAuth/${this.model.id}/connection`, {code: info.code});
        } catch (e) {
            this.inProcess = false;

            await this.reRender();

            return;
        }

        await this.model.fetch();

        Espo.Ui.notify();

        this.inProcess = false;

        await this.reRender();
    }

    /**
     * @private
     * @param {{
     *     endpoint: string,
     *     clientId: string,
     *     redirectUri: string,
     *     scope: string|null,
     *     prompt: string,
     *     params: Record|null,
     * }} data
     * @param {WindowProxy} proxy
     * @return {Promise<{code: string}>}
     */
    processWithData(data, proxy) {
        const state = undefined;

        const params = {
            client_id: data.clientId,
            redirect_uri: data.redirectUri,
            response_type: 'code',
            prompt: data.prompt,
        };

        if (data.scope) {
            params.scope = data.scope;
        }

        if (data.params) {
            for (const name in data.params) {
                params[name] = data.params[name];
            }
        }

        const partList = Object.entries(params)
            .map(([key, value]) => {
                return key + '=' + encodeURIComponent(value);
            });

        const url = data.endpoint + '?' + partList.join('&');

        return this.processWindow(url, state, proxy);
    }

    /**
     * @private
     * @param {string} url
     * @param {string} state
     * @param {WindowProxy} proxy
     * @return {Promise<{code: string}>}
     */
    processWindow(url, state, proxy) {
        proxy.location.href = url;

        return new Promise((resolve, reject) => {
            const fail = () => {
                window.clearInterval(interval);

                if (!proxy.closed) {
                    proxy.close();
                }

                reject();
            };

            const interval = window.setInterval(() => {
                if (proxy.closed) {
                    fail();

                    return;
                }

                let url;

                try {
                    url = proxy.location.href;
                } catch (e) {
                    return;
                }

                if (!url) {
                    return;
                }

                const parsedData = this.parseWindowUrl(url);

                if (!parsedData) {
                    fail();
                    Espo.Ui.error('Could not parse URL', true);

                    return;
                }

                if ((parsedData.error || parsedData.code) && state && parsedData.state !== state) {
                    fail();
                    Espo.Ui.error('State mismatch', true);

                    return;
                }

                if (parsedData.error) {
                    fail();
                    Espo.Ui.error(parsedData.errorDescription || this.translate('Error'), true);

                    return;
                }

                if (parsedData.code) {
                    window.clearInterval(interval);
                    proxy.close();

                    resolve({
                        code: parsedData.code,
                    });
                }
            }, 300);
        });
    }

    /**
     * @private
     * @param {string} url
     * @return {?{
     *     code: ?string,
     *     state: ?string,
     *     error: ?string,
     *     errorDescription: ?string,
     * }}
     */
    parseWindowUrl(url) {
        try {
            const params = new URL(url).searchParams;

            return {
                code: params.get('code'),
                state: params.get('state'),
                error: params.get('error'),
                errorDescription: params.get('errorDescription'),
            };
        } catch (e) {
            return null;
        }
    }
}
