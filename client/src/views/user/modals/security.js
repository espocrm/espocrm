/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/user/modals/security', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        templateContent: '<div class="record">{{{record}}}</div>',

        className: 'dialog dialog-record',

        setup: function () {
            this.buttonList = [
                {
                    name: 'apply',
                    label: 'Apply',
                    hidden: true,
                    style: 'danger',
                },
                {
                    name: 'cancel',
                    label: 'Close',
                }
            ];

            this.dropdownItemList = [
                {
                    name: 'reset',
                    html: this.translate('Reset 2FA'),
                    hidden: true,
                },
            ];

            this.userModel = this.options.userModel;

            this.headerHtml = this.translate('Security') + ' <span class="chevron-right"></span> ' +
                this.getHelper().escapeString(this.userModel.get('userName'));

            var model = this.model = new Model();

            model.name = 'UserSecurity';
            model.id = this.userModel.id;
            model.url = 'UserSecurity/' + this.userModel.id;

            var auth2FAMethodList = this.getConfig().get('auth2FAMethodList') || [];

            model.setDefs({
                fields: {
                    'auth2FA': {
                        type: 'bool',
                        labelText: this.translate('auth2FAEnable', 'fields', 'User'),
                    },
                    'auth2FAMethod': {
                        type: 'enum',
                        options: auth2FAMethodList,
                        translation: 'Settings.options.auth2FAMethodList',
                    },
                }
            });

            this.wait(
                model.fetch().then(() => {
                    this.initialAttributes = Espo.Utils.cloneDeep(model.attributes);

                    if (model.get('auth2FA')) {
                        this.showActionItem('reset');
                    }

                    this.createView('record', 'views/record/edit-for-modal', {
                        scope: 'None',
                        el: this.getSelector() + ' .record',
                        model: this.model,
                        detailLayout: [
                            {
                                rows: [
                                    [
                                        {
                                            name: 'auth2FA',
                                            labelText: this.translate('auth2FAEnable', 'fields', 'User'),
                                        },
                                        {
                                            name: 'auth2FAMethod',
                                            labelText: this.translate('auth2FAMethod', 'fields', 'User'),
                                        }
                                    ],
                                ]
                            }
                        ],
                    }, (view) => {
                        this.controlFieldsVisibility(view);

                        this.listenTo(this.model, 'change:auth2FA', () => {
                            this.controlFieldsVisibility(view);
                        });
                    });
                })
            );

            this.listenTo(this.model, 'change', () => {
                if (this.initialAttributes ) {
                    if (this.isChanged()) {
                        this.showButton('apply');
                    }
                    else {
                        this.hideButton('apply');
                    }
                }
            });
        },

        controlFieldsVisibility: function (view) {
            if (this.model.get('auth2FA')) {
                view.showField('auth2FAMethod');
                view.setFieldRequired('auth2FAMethod');
            }
            else {
                view.hideField('auth2FAMethod');
                view.setFieldNotRequired('auth2FAMethod');
            }
        },

        isChanged: function () {
            return this.initialAttributes.auth2FA !== this.model.get('auth2FA') ||
                this.initialAttributes.auth2FAMethod !== this.model.get('auth2FAMethod')
        },

        actionReset: function (dialog) {
            this.confirm(this.translate('security2FaResetConfimation', 'messages', 'User'), () => {
                this.actionApply(dialog, true);
            });
        },

        actionApply: function (dialog, reset) {
            var data = this.getView('record').processFetch();

            if (!data) {
                return;
            }

            this.hideButton('apply');

            new Promise((resolve) => {
                this.createView('dialog', 'views/user/modals/password', {}, (passwordView) => {
                    passwordView.render();

                    this.listenToOnce(passwordView, 'cancel', () => this.showButton('apply'));

                    this.listenToOnce(passwordView, 'proceed', (data) => {
                        this.model.set('password', data.password);

                        passwordView.close();

                        resolve();
                    });
                });
            }).then(() => this.processApply(reset));
        },

        processApply: function (reset) {
            if (this.model.get('auth2FA')) {
                var auth2FAMethod = this.model.get('auth2FAMethod');

                var view = this.getMetadata().get(['app', 'authentication2FAMethods', auth2FAMethod, 'userApplyView']);

                if (view) {
                    Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                    this.createView('dialog', view, {
                        model: this.model,
                        reset: reset,
                    }, (view) => {
                        Espo.Ui.notify(false);

                        view.render();

                        this.listenToOnce(view, 'cancel', () => {
                            this.close();
                        });

                        this.listenToOnce(view, 'apply', () => {
                            view.close();

                            this.processSave();
                        });

                        this.listenToOnce(view, 'done', () => {
                            Espo.Ui.success(this.translate('Done'));
                            this.trigger('done');

                            view.close();
                            this.close();
                        });
                    });

                    return ;
                }

                if (reset) {
                    this.model.set('auth2FA', false);
                }

                this.processSave();

                return;
            }

            this.processSave();
        },

        processSave: function () {
            this.hideButton('apply');

            this.model
                .save()
                .then(() => {
                    this.close();

                    Espo.Ui.success(this.translate('Done'));
                })
                .catch(() => this.showButton('apply'));
        },

    });
});
