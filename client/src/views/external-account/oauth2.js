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
import Model from 'model';

/**
 * @internal Do not extend.
 */
class ExternalAccountOauth2View extends View {

    template = 'external-account/oauth2'

    data() {
        return {
            integration: this.integration,
            helpText: this.helpText,
            isConnected: this.isConnected,
        };
    }

    isConnected = false

    setup() {
        this.addActionHandler('connect', () => this.connect());
        this.addActionHandler('save', () => this.save());
        this.addActionHandler('cancel', () => this.getRouter().navigate('#ExternalAccount', {trigger: true}));

        this.integration = this.options.integration;
        this.id = this.options.id;

        this.helpText = false;

        if (this.getLanguage().has(this.integration, 'help', 'ExternalAccount')) {
            this.helpText = this.translate(this.integration, 'help', 'ExternalAccount');
        }

        this.fieldList = [];

        this.dataFieldList = [];

        this.model = new Model();
        this.model.id = this.id;
        this.model.entityType = this.model.name = 'ExternalAccount';
        this.model.urlRoot = 'ExternalAccount';

        this.model.defs = {
            fields: {
                enabled: {
                    required: true,
                    type: 'bool'
                },
            }
        };

        this.wait(true);

        this.model.populateDefaults();

        this.listenToOnce(this.model, 'sync', () => {
            this.createFieldView('bool', 'enabled');

            Espo.Ajax.getRequest('ExternalAccount/action/getOAuth2Info?id=' + this.id)
                .then(response => {
                    this.clientId = response.clientId;
                    this.redirectUri = response.redirectUri;

                    if (response.isConnected) {
                        this.isConnected = true;
                    }

                    this.wait(false);
                });
        });

        this.model.fetch();
    }

    hideField(name) {
        this.$el.find(`label[data-name="${name}"]`).addClass('hide');
        this.$el.find(`div.field[data-name="${name}"]`).addClass('hide');

        const view = this.getView(name);

        if (view) {
            view.disabled = true;
        }
    }

    showField(name) {
        this.$el.find(`label[data-name="${name}"]`).removeClass('hide');
        this.$el.find(`div.field[data-name="${name}"]`).removeClass('hide');

        const view = this.getView(name);

        if (view) {
            view.disabled = false;
        }
    }

    afterRender() {
        if (!this.model.get('enabled')) {
            this.$el.find('.data-panel').addClass('hidden');
        }

        this.listenTo(this.model, 'change:enabled', () => {
            if (this.model.get('enabled')) {
                this.$el.find('.data-panel').removeClass('hidden');
            } else {
                this.$el.find('.data-panel').addClass('hidden');
            }
        });
    }

    createFieldView(type, name, readOnly, params) {
        this.createView(name, this.getFieldManager().getViewName(type), {
            model: this.model,
            selector: '.field[data-name="' + name + '"]',
            defs: {
                name: name,
                params: params
            },
            mode: readOnly ? 'detail' : 'edit',
            readOnly: readOnly,
        });

        this.fieldList.push(name);
    }

    save() {
        this.fieldList.forEach(field => {
            const view = /** @type {import('views/fields/base').default} */this.getView(field);

            if (!view.readOnly) {
                view.fetchToModel();
            }
        });

        let notValid = false;

        this.fieldList.forEach(field => {
            const view = /** @type {import('views/fields/base').default} */this.getView(field);

            notValid = view.validate() || notValid;
        });

        if (notValid) {
            Espo.Ui.error(this.translate('Not valid'));

            return;
        }

        this.listenToOnce(this.model, 'sync', () => {
            Espo.Ui.success(this.translate('Saved'));

            if (!this.model.get('enabled')) {
                this.setNotConnected();
            }
        });

        Espo.Ui.notify(this.translate('saving', 'messages'));

        this.model.save();
    }

    popup(options, callback) {
        options.windowName = options.windowName ||  'ConnectWithOAuth';
        options.windowOptions = options.windowOptions || 'location=0,status=0,width=800,height=400';
        options.callback = options.callback || function(){ window.location.reload(); };

        const self = this;

        let path = options.path;

        const arr = [];
        const params = (options.params || {});

        for (const name in params) {
            if (params[name]) {
                arr.push(name + '=' + encodeURI(params[name]));
            }
        }
        path += '?' + arr.join('&');

        const parseUrl = str => {
            let code = null;
            let error = null;

            str = str.substr(str.indexOf('?') + 1, str.length);

            str.split('&').forEach(part => {
                const arr = part.split('=');
                const name = decodeURI(arr[0]);
                const value = decodeURI(arr[1] || '');

                if (name === 'code') {
                    code = value;
                }

                if (name === 'error') {
                    error = value;
                }
            });

            if (code) {
                return {
                    code: code,
                };
            } else if (error) {
                return {
                    error: error,
                };
            }
        }

        const popup = window.open(path, options.windowName, options.windowOptions);

        let interval;

        interval = window.setInterval(() => {
            if (popup.closed) {
                window.clearInterval(interval);

                return;
            }

            const res = parseUrl(popup.location.href.toString());

            if (res) {
                callback.call(self, res);
                popup.close();
                window.clearInterval(interval);
            }
        }, 500);
    }

    connect() {
        this.popup({
            path: this.getMetadata().get(`integrations.${this.integration}.params.endpoint`),
            params: {
                client_id: this.clientId,
                redirect_uri: this.redirectUri,
                scope: this.getMetadata().get(`integrations.${this.integration}.params.scope`),
                response_type: 'code',
                access_type: 'offline',
                approval_prompt: 'force',
            }
        }, (response) => {
            if (response.error) {
                Espo.Ui.notify(false);

                return;
            }

            if (!response.code) {
                Espo.Ui.error(this.translate('Error occurred'))

                return;
            }

            this.$el.find('[data-action="connect"]').addClass('disabled');

            Espo.Ajax
                .postRequest('ExternalAccount/action/authorizationCode', {
                    id: this.id,
                    code: response.code,
                })
                .then(response => {
                    Espo.Ui.notify(false);

                    if (response === true) {
                        this.setConnected();
                    } else {
                        this.setNotConnected();
                    }

                    this.$el.find('[data-action="connect"]').removeClass('disabled');
                })
                .catch(() => {
                    this.$el.find('[data-action="connect"]').removeClass('disabled');
                });
        });
    }

    setConnected() {
        this.isConnected = true;

        this.$el.find('[data-action="connect"]').addClass('hidden');
        this.$el.find('.connected-label').removeClass('hidden');
    }

    setNotConnected() {
        this.isConnected = false;

        this.$el.find('[data-action="connect"]').removeClass('hidden');
        this.$el.find('.connected-label').addClass('hidden');
    }
}

// noinspection JSUnusedGlobalSymbols
export default ExternalAccountOauth2View;
