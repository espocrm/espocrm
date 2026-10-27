/************************************************************************
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import Utils from 'utils';
import Ajax from 'ajax';
import Ui from 'ui';

class UserSecurityModalView extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `

    className = 'dialog dialog-record'

    shortcutKeys = {
        'Control+Enter': 'apply',
    }

    /**
     * @private
     * @type {boolean}
     */
    auth2FAIsSystemEnabled

    /**
     * @private
     * @type {import('views/record/edit').default}
     */
    recordView

    /**
     * @private
     * @type {import('models/user').default}
     */
    userModel

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
            },
        ];

        this.dropdownItemList = [
            {
                name: 'reset',
                text: this.translate('Reset 2FA'),
                hidden: true,
                onClick: () => this.reset(),
            },
        ];

        this.addButton({
            name: 'revokeAllTokens',
            labelTranslation: 'User.strings.revokeAllAuthenticationTokens',
            position: 'right',
            onClick: () => this.processRevokeAllTokens(),
        });

        this.userModel = this.options.userModel;

        this.headerHtml =
            (() => {
                const span = document.createElement('span');
                span.textContent = this.translate('Security');

                return span.outerHTML;
            })() +
            ` <span class="chevron-right"></span> ` +
            (() => {
                const span = document.createElement('span');
                span.textContent = this.userModel.get('userName');

                return span.outerHTML;
            })();

        const model = this.model = new Model();

        model.name = 'UserSecurity';
        model.id = this.userModel.id;
        model.url = 'UserSecurity/' + this.userModel.id;

        const auth2FAMethodList = this.getConfig().get('auth2FAMethodList') ?? [];

        this.auth2FAIsSystemEnabled = this.userModel.isPortal() ?
            (this.getConfig().get('auth2FAInPortal') ?? false) :
            (this.getConfig().get('auth2FA') ?? false);

        model.setDefs({
            fields: {
                auth2FA: {
                    type: 'bool',
                    labelText: this.translate('auth2FAEnable', 'fields', 'User'),
                },
                auth2FAMethod: {
                    type: 'enum',
                    options: auth2FAMethodList,
                    translation: 'Settings.options.auth2FAMethodList',
                },
            }}
        );

        this.wait(
            (async () => {
                await model.fetch();

                this.initialAttributes = Utils.cloneDeep(model.attributes);

                if (model.attributes.auth2FA && this.auth2FAIsSystemEnabled) {
                    await this.showActionItem('reset');
                }

                this.recordView = await this.createView('record', 'views/record/edit-for-modal', {
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
                });

                this.controlFieldsVisibility();
                this.listenTo(this.model, 'change:auth2FA', () => this.controlFieldsVisibility());
            })()
        );

        this.listenTo(this.model, 'change', () => {
            if (this.initialAttributes) {
                this.isChanged() ?
                    this.showActionItem('apply') :
                    this.hideActionItem('apply');
            }
        });
    }

    /**
     * @private
     */
    controlFieldsVisibility() {
        if (this.model.attributes.auth2FA) {
            this.recordView.showField('auth2FAMethod');
            this.recordView.setFieldRequired('auth2FAMethod');
        } else {
            this.recordView.hideField('auth2FAMethod');
            this.recordView.setFieldNotRequired('auth2FAMethod');
        }

        if (!this.auth2FAIsSystemEnabled) {
            if (!this.model.attributes.auth2FA) {
                this.recordView.setFieldReadOnly('auth2FA');
                this.recordView.hideField('auth2FA');
            }

            this.recordView.setFieldReadOnly('auth2FAMethod');
            this.recordView.setFieldNotRequired('auth2FAMethod');
        }
    }

    isChanged() {
        return this.initialAttributes.auth2FA !== this.model.attributes.auth2FA ||
            this.initialAttributes.auth2FAMethod !== this.model.attributes.attributes
    }

    reset() {
        this.confirm(this.translate('security2FaResetConfirmation', 'messages', 'User'), () => {
            this.apply(true);
        });
    }

    /**
     * @return {import('views/record/edit').default}
     */
    getRecordView() {
        return /** @type {import('views/record/edit').default} */this.getView('record');
    }

    /**
     * @private
     * @param {boolean} reset
     */
    apply(reset = false) {
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

    /**
     * @private
     * @param {boolean} reset
     */
    processApply(reset = false) {
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

    /**
     * @private
     */
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

    /**
     * @private
     */
    async processRevokeAllTokens() {
        await this.confirm({
            message: this.translate('confirmation', 'messages'),
        });

        await this.disableButton('revokeAllTokens');

        try {
            await Ajax.postRequest(`User/${this.userModel.id}/revokeAllAuthenticationTokens`);
        } catch (e) {
            await this.enableButton('revokeAllTokens');

            return;
        }

        Ui.success(this.translate('Done'));
    }
}

export default UserSecurityModalView;
