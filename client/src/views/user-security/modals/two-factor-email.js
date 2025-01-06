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

import ModalView from 'views/modal';
import Model from 'model';

// noinspection JSUnusedGlobalSymbols
export default class TwoFactorEmailModalView extends ModalView {

    template = 'user-security/modals/two-factor-email'

    className = 'dialog dialog-record'

    shortcutKeys = {
        'Control+Enter': 'apply',
    }

    setup() {
        this.addActionHandler('sendCode', () => this.actionSendCode());

        this.buttonList = [
            {
                name: 'apply',
                label: 'Apply',
                style: 'danger',
                hidden: true,
                onClick: () => this.actionApply(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        this.headerHtml = '&nbsp';

        const codeLength = this.getConfig().get('auth2FAEmailCodeLength') || 7;

        const model = new Model();
        model.entityType = model.name = 'UserSecurity';

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
                        selector: '.record',
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
    }

    afterRender() {
        this.$sendCode = this.$el.find('[data-action="sendCode"]');

        this.$pInfo = this.$el.find('p.p-info');
        this.$pButton = this.$el.find('p.p-button');
        this.$pInfoAfter = this.$el.find('p.p-info-after');
    }

    actionSendCode() {
        this.$sendCode.attr('disabled', 'disabled').addClass('disabled');

        Espo.Ajax
            .postRequest('TwoFactorEmail/action/sendCode', {
                id: this.model.id,
                emailAddress: this.internalModel.get('emailAddress'),
            })
            .then(() => {
                this.showActionItem('apply');

                this.$pInfo.addClass('hidden');
                this.$pButton.addClass('hidden');
                this.$pInfoAfter.removeClass('hidden');

                this.getRecordView().setFieldReadOnly('emailAddress');
                this.getRecordView().showField('code');
            })
            .catch(() => {
                this.$sendCode.removeAttr('disabled').removeClass('disabled');
            });
    }

    /**
     * @return {import('views/record/edit').default}
     */
    getRecordView() {
        return this.getView('record');
    }

    actionApply() {
        const data = this.getRecordView().processFetch();

        if (!data) {
            return;
        }

        this.model.set('code', data.code);

        this.hideActionItem('apply');
        this.hideActionItem('cancel');

        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

        this.model
            .save()
            .then(() => {
                Espo.Ui.notify(false);

                this.trigger('done');
            })
            .catch(() => {
                this.showActionItem('apply');
                this.showActionItem('cancel');
            });
    }
}
