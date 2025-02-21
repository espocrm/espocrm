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

import LoginHandler from 'handlers/login';
import Base64 from 'js-base64';

class OidcLoginHandler extends LoginHandler {

    /** @inheritDoc */
    process() {
        const proxy = window.open(
            'about:blank',
            'ConnectWithOAuth',
            'location=0,status=0,width=800,height=800'
        );

        Espo.Ui.notifyWait();

        return new Promise((resolve, reject) => {
            Espo.Ajax.getRequest('Oidc/authorizationData')
                .then(data => {
                    Espo.Ui.notify(false);

                    this.processWithData(data, proxy)
                        .then(info => {
                            const code = info.code;
                            const nonce = info.nonce;

                            const authString = Base64.encode('**oidc:' + code);

                            const headers = {
                                'Espo-Authorization': authString,
                                'Authorization': 'Basic ' + authString,
                                'X-Oidc-Authorization-Nonce': nonce,
                            };

                            resolve(headers);
                        })
                        .catch(() => {
                            proxy.close();
                            reject();
                        });
                })
                .catch(() => {
                    Espo.Ui.notify(false)

                    proxy.close();
                    reject();
                });
        });
    }

    /**
     * @private
     * @param {{
     *  endpoint: string,
     *  clientId: string,
     *  redirectUri: string,
     *  scopes: string[],
     *  claims: ?string,
     *  prompt: 'login'|'consent'|'select_account',
     *  maxAge: ?Number,
     * }} data
     * @param {WindowProxy} proxy
     * @return {Promise<{code: string, nonce: string}>}
     */
    processWithData(data, proxy) {
        const state = (Math.random() + 1).toString(36).substring(4);
        const nonce = (Math.random() + 1).toString(36).substring(4);

        const params = {
            client_id: data.clientId,
            redirect_uri: data.redirectUri,
            response_type: 'code',
            scope: data.scopes.join(' '),
            state: state,
            nonce: nonce,
            prompt: data.prompt,
        };

        if (data.maxAge || data.maxAge === 0) {
            params.max_age = data.maxAge;
        }

        if (data.claims) {
            params.claims = data.claims;
        }

        const partList = Object.entries(params)
            .map(([key, value]) => {
                return key + '=' + encodeURIComponent(value);
            });

        const url = data.endpoint + '?' + partList.join('&');

        return this.processWindow(url, state, nonce, proxy);
    }

    /**
     * @private
     * @param {string} url
     * @param {string} state
     * @param {string} nonce
     * @param {WindowProxy} proxy
     * @return {Promise<{code: string, nonce: string}>}
     */
    processWindow(url, state, nonce, proxy) {
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
                }
                catch (e) {
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

                if ((parsedData.error || parsedData.code) && parsedData.state !== state) {
                    fail();
                    Espo.Ui.error('State mismatch', true);

                    return;
                }

                if (parsedData.error) {
                    fail();
                    Espo.Ui.error(parsedData.errorDescription || this.loginView.translate('Error'), true);

                    return;
                }

                if (parsedData.code) {
                    window.clearInterval(interval);
                    proxy.close();

                    resolve({
                        code: parsedData.code,
                        nonce: nonce,
                    });
                }
            }, 300);
        });
    }

    /**
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
        }
        catch (e) {
            return null;
        }
    }
}

export default OidcLoginHandler;
