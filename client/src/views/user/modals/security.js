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

class UserSecurityModalView extends ModalView {

    templateContent = '<div class="record no-side-margin">{{{record}}}</div>'

    className = 'dialog dialog-record'

    shortcutKeys = {
        'Control+Enter': 'apply',
    }

    setup() {
        this.buttonList = [
            {
                name: 'apply',
                label: 'Apply',
                hidden: true,
                style: 'danger',
                onClick: () => this.apply(),
            },
            {
                name: 'cancel',
                label: 'Close',
            }
        ];

        this.dropdownItemList = [
            {
                name: 'reset',
                text: this.translate('Reset 2FA'),
                hidden: true,
                onClick: () => this.reset(),
            },
        ];

        this.userModel = this.options.userModel;

        this.$header = $('<span>').append(
            $('<span>').text(this.translate('Security')),
            ' <span class="chevron-right"></span> ',
            $('<span>').text(this.userModel.get('userName'))
        );

        const model = this.model = new Model();

        model.name = 'UserSecurity';
        model.id = this.userModel.id;
        model.url = 'UserSecurity/' + this.userModel.id;

        const auth2FAMethodList = this.getConfig().get('auth2FAMethodList') || [];

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
                    selector: '.record',
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
            if (this.initialAttributes) {
                this.isChanged() ?
                    this.showActionItem('apply') :
                    this.hideActionItem('apply');
            }
        });
    }

    controlFieldsVisibility(view) {
        if (this.model.get('auth2FA')) {
            view.showField('auth2FAMethod');
            view.setFieldRequired('auth2FAMethod');
        }
        else {
            view.hideField('auth2FAMethod');
            view.setFieldNotRequired('auth2FAMethod');
        }
    }

    isChanged() {
        return this.initialAttributes.auth2FA !== this.model.get('auth2FA') ||
            this.initialAttributes.auth2FAMethod !== this.model.get('auth2FAMethod')
    }

    reset() {
        this.confirm(this.translate('security2FaResetConfirmation', 'messages', 'User'), () => {
            this.apply(true);
        });
    }

    /**
     * @return {module:views/record/edit}
     */
    getRecordView() {
        return this.getView('record');
    }

    apply(reset) {
        const data = this.getRecordView().processFetch();

        if (!data) {
            return;
        }

        this.hideActionItem('apply');

        new Promise(resolve => {
            this.createView('dialog', 'views/user/modals/password', {}, passwordView => {
                passwordView.render();

                this.listenToOnce(passwordView, 'cancel', () => this.showActionItem('apply'));

                this.listenToOnce(passwordView, 'proceed', (data) => {
                    this.model.set('password', data.password);

                    passwordView.close();

                    resolve();
                });
            });
        }).then(() => this.processApply(reset));
    }

    processApply(reset) {
        if (this.model.get('auth2FA')) {
            const auth2FAMethod = this.model.get('auth2FAMethod');

            const view = this.getMetadata().get(['app', 'authentication2FAMethods', auth2FAMethod, 'userApplyView']);

            if (view) {
                Espo.Ui.notifyWait();

                this.createView('dialog', view, {
                    model: this.model,
                    reset: reset,
                }, view => {
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
    }

    processSave() {
        this.hideActionItem('apply');

        this.model
            .save()
            .then(() => {
                this.close();

                Espo.Ui.success(this.translate('Done'));
            })
            .catch(() => this.showActionItem('apply'));
    }
}

export default UserSecurityModalView;
