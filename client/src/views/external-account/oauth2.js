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

define('views/external-account/oauth2', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'external-account/oauth2',

        data: function () {
            return {
                integration: this.integration,
                helpText: this.helpText,
                isConnected: this.isConnected,
            };
        },

        isConnected: false,

        events: {
            'click button[data-action="cancel"]': function () {
                this.getRouter().navigate('#ExternalAccount', {trigger: true});
            },
            'click button[data-action="save"]': function () {
                this.save();
            },
            'click [data-action="connect"]': function () {
                this.connect();
            }
        },

        setup: function () {
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
        },

        hideField: function (name) {
            this.$el.find('label[data-name="'+name+'"]').addClass('hide');
            this.$el.find('div.field[data-name="'+name+'"]').addClass('hide');

            var view = this.getView(name);

            if (view) {
                view.disabled = true;
            }
        },

        showField: function (name) {
            this.$el.find('label[data-name="'+name+'"]').removeClass('hide');
            this.$el.find('div.field[data-name="'+name+'"]').removeClass('hide');

            var view = this.getView(name);

            if (view) {
                view.disabled = false;
            }
        },

        afterRender: function () {
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
        },

        createFieldView: function (type, name, readOnly, params) {
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
        },

        save: function () {
            this.fieldList.forEach(field => {
                var view = this.getView(field);

                if (!view.readOnly) {
                    view.fetchToModel();
                }
            });

            var notValid = false;

            this.fieldList.forEach((field) => {
                notValid = this.getView(field).validate() || notValid;
            });

            if (notValid) {
                this.notify('Not valid', 'error');
                return;
            }

            this.listenToOnce(this.model, 'sync', () => {
                this.notify('Saved', 'success');

                if (!this.model.get('enabled')) {
                    this.setNotConnected();
                }
            });

            Espo.Ui.notify(this.translate('saving', 'messages'));

            this.model.save();
        },

        popup: function (options, callback) {
            options.windowName = options.windowName ||  'ConnectWithOAuth';
            options.windowOptions = options.windowOptions || 'location=0,status=0,width=800,height=400';
            options.callback = options.callback || function(){ window.location.reload(); };

            var self = this;

            var path = options.path;

            var arr = [];
            var params = (options.params || {});

            for (var name in params) {
                if (params[name]) {
                    arr.push(name + '=' + encodeURI(params[name]));
                }
            }
            path += '?' + arr.join('&');

            var parseUrl = function (str) {
                var code = null;
                var error = null;

                str = str.substr(str.indexOf('?') + 1, str.length);

                str.split('&').forEach((part) => {
                    var arr = part.split('=');
                    var name = decodeURI(arr[0]);
                    var value = decodeURI(arr[1] || '');

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

            let popup = window.open(path, options.windowName, options.windowOptions);

            let interval;

            interval = window.setInterval(() => {
                if (popup.closed) {
                    window.clearInterval(interval);
                } else {
                    var res = parseUrl(popup.location.href.toString());

                    if (res) {
                        callback.call(self, res);
                        popup.close();
                        window.clearInterval(interval);
                    }
                }
            }, 500);
        },

        connect: function () {
            this.popup({
                path: this.getMetadata().get('integrations.' + this.integration + '.params.endpoint'),
                params: {
                    client_id: this.clientId,
                    redirect_uri: this.redirectUri,
                    scope: this.getMetadata().get('integrations.' + this.integration + '.params.scope'),
                    response_type: 'code',
                    access_type: 'offline',
                    approval_prompt: 'force',
                }
            }, function (res) {
                if (res.error) {
                    Espo.Ui.notify(false);

                    return;
                }

                if (res.code) {
                    this.$el.find('[data-action="connect"]').addClass('disabled');

                    Espo.Ajax
                        .postRequest('ExternalAccount/action/authorizationCode', {
                            id: this.id,
                            code: res.code,
                        })
                        .then(response => {
                            Espo.Ui.notify(false);

                            if (response === true) {
                                this.setConnected();
                            } else {
                                this.setNotConneted();
                            }

                            this.$el.find('[data-action="connect"]').removeClass('disabled');
                        })
                        .catch(() => {
                            this.$el.find('[data-action="connect"]').removeClass('disabled');
                        });
                } else {
                    this.notify('Error occurred', 'error');
                }
            });
        },

        setConnected: function () {
            this.isConnected = true;

            this.$el.find('[data-action="connect"]').addClass('hidden');;
            this.$el.find('.connected-label').removeClass('hidden');
        },

        setNotConnected: function () {
            this.isConnected = false;

            this.$el.find('[data-action="connect"]').removeClass('hidden');;
            this.$el.find('.connected-label').addClass('hidden');
        },
    });
});
