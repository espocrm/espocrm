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

define('views/user-security/modals/totp',
    ['views/modal', 'model', 'lib!qrcode'],
    function (Dep, Model, QRCode) {

    return Dep.extend({

        template: 'user-security/modals/totp',

        className: 'dialog dialog-record',

        setup: function () {
            this.buttonList = [
                {
                    name: 'apply',
                    label: 'Apply',
                    style: 'danger',
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                }
            ];

            this.headerHtml = false;

            var model = new Model();

            model.name = 'UserSecurity';

            this.wait(
                Espo.Ajax
                    .postRequest('UserSecurity/action/getTwoFactorUserSetupData', {
                        id: this.model.id,
                        password: this.model.get('password'),
                        auth2FAMethod: this.model.get('auth2FAMethod'),
                        reset: this.options.reset,
                    })
                    .then(data => {
                        this.label = data.label;
                        this.secret = data.auth2FATotpSecret;

                        model.set('secret', data.auth2FATotpSecret);
                    })
            );

            model.setDefs({
                fields: {
                    'code': {
                        type: 'varchar',
                        required: true,
                        maxLength: 7,
                    },
                    'secret': {
                        type: 'varchar',
                        readOnly: true,
                    },
                }
            });

            this.createView('record', 'views/record/edit-for-modal', {
                scope: 'None',
                el: this.getSelector() + ' .record',
                model: model,
                detailLayout: [
                    {
                        rows: [
                            [
                                {
                                    name: 'secret',
                                    labelText: this.translate('Secret', 'labels', 'User'),
                                },
                                false
                            ],
                            [
                                {
                                    name: 'code',
                                    labelText: this.translate('Code', 'labels', 'User'),
                                },
                                false
                            ]
                        ]
                    }
                ],
            });
        },

        afterRender: function () {
            new QRCode(this.$el.find('.qrcode').get(0), {
                text: 'otpauth://totp/' + this.label + '?secret=' + this.secret,
                width: 256,
                height: 256,
                colorDark : '#000000',
                colorLight : '#ffffff',
                correctLevel : QRCode.CorrectLevel.H,
            });
        },

        actionApply: function () {
            var data = this.getView('record').processFetch();

            if (!data) {
                return;
            }

            this.model.set('code', data.code);

            this.hideButton('apply');
            this.hideButton('cancel');

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.model
                .save()
                .then(() => {
                    Espo.Ui.notify(false);

                    this.trigger('done');
                })
                .catch(() => {
                    this.showButton('apply');
                    this.showButton('cancel');
                });
        },

    });
});
