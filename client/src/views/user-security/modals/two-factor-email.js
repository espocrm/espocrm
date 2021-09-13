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

define('views/user-security/modals/two-factor-email',
    ['views/modal', 'model'],
    function (Dep, Model) {

    return Dep.extend({

        template: 'user-security/modals/two-factor-email',

        className: 'dialog dialog-record',

        events: {
            'click [data-action="sendCode"]': function () {
                this.actionSendCode();
            },
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'apply',
                    label: 'Apply',
                    style: 'danger',
                    hidden: true,
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                }
            ];

            this.headerHtml = false;

            let codeLength = this.getConfig().get('auth2FAEmailCodeLength') || 7;

            let model = new Model();

            model.name = 'UserSecurity';

            model.set('emailAddress', null);

            model.setDefs({
                fields: {
                    'code': {
                        type: 'varchar',
                        required: true,
                        maxLength: codeLength,
                    },
                    'emailAddress': {
                        type: 'enum',
                        required: true,
                    },
                }
            });

            this.internalModel = model;

            this.wait(
                Espo.Ajax
                    .postRequest('UserSecurity/action/getTwoFactorUserSetupData', {
                        id: this.model.id,
                        password: this.model.get('password'),
                        auth2FAMethod: this.model.get('auth2FAMethod'),
                        reset: this.options.reset,
                    })
                    .then(data => {
                        this.emailAddressList = data.emailAddressList;

                        this.createView('record', 'views/record/edit-for-modal', {
                            scope: 'None',
                            el: this.getSelector() + ' .record',
                            model: model,
                            detailLayout: [
                                {
                                    rows: [
                                        [
                                            {
                                                name: 'emailAddress',
                                                labelText: this.translate('emailAddress', 'fields', 'User'),
                                            },
                                            false
                                        ],
                                        [
                                            {
                                                name: 'code',
                                                labelText: this.translate('Code', 'labels', 'User'),
                                            },
                                            false
                                        ],
                                    ]
                                }
                            ],
                        }, view => {
                            view.setFieldOptionList('emailAddress', this.emailAddressList);

                            if (this.emailAddressList.length) {
                                model.set('emailAddress', this.emailAddressList[0]);
                            }

                            view.hideField('code');
                        });
                    })
            );
        },

        afterRender: function () {
            this.$sendCode = this.$el.find('[data-action="sendCode"]');

            this.$pInfo = this.$el.find('p.p-info');
            this.$pButton = this.$el.find('p.p-button');
            this.$pInfoAfter = this.$el.find('p.p-info-after');
        },

        actionSendCode: function () {
            this.$sendCode.attr('disabled', 'disabled').addClass('disabled');

            Espo.Ajax
                .postRequest('TwoFactorEmail/action/sendCode', {
                    id: this.model.id,
                    emailAddress: this.internalModel.get('emailAddress'),
                })
                .then(() => {
                    this.showButton('apply');

                    this.$pInfo.addClass('hidden');
                    this.$pButton.addClass('hidden');
                    this.$pInfoAfter.removeClass('hidden');

                    this.getView('record').setFieldReadOnly('emailAddress');
                    this.getView('record').showField('code');
                })
                .catch(() => {
                    this.$sendCode.removeAttr('disabled').removeClass('disabled');
                });
        },

        actionApply: function () {
            let data = this.getView('record').processFetch();

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
