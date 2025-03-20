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
import EditRecordView from 'views/record/edit';
import Model from 'model';

export default class LeadCaptureFormView extends View {

    // language=Handlebars
    templateContent = `
        <div class="block-center-5 margin-top">
            {{#if isPosted}}
                <div class="panel paned-default">
                    <div class="panel-body">
                        <div class="complex-text">{{complexText successText}}</div>
                    </div>
                </div>
            {{else}}
                <div class="record">{{{record}}}</div>
                <div class="button-container center-align">
                    <button
                        class="btn btn-primary btn-x-wide"
                        data-action="submit"
                    >{{translate 'Submit'}}</button>
                </div>
            {{/if}}
        </div>
    `

    /**
     * @private
     * @type {boolean}
     */
    isPosted = false

    /**
     * @private
     * @type {boolean}
     */
    isPosting = false

    /**
     * @private
     * @type {HTMLElement}
     */
    submitButtonElement

    /**
     * @param {{
     *     formData: {
     *         requestUrl: string,
     *         detailLayout: module:views/record/detail~panelDefs[],
     *         fieldDefs: Record,
     *         metadata: Record,
     *         language: Record.<string, Record>,
     *         successText: string,
     *         text: string|null,
     *         config: Record,
     *         appParams: Record,
     *         captchaKey: boolean,
     *         isDark: boolean,
     *         title: string|null,
     *     },
     * }} options
     */
    constructor(options) {
        super();

        this.formData = options.formData;
    }

    data() {
        return {
            isPosted: this.isPosted,
            successText: this.formData.successText,
        };
    }

    setup() {
        this.getMetadata().setData(this.formData.metadata);
        this.getConfig().setMultiple(this.formData.config);
        this.getHelper().appParams.setAll(this.formData.appParams);
        this.getHelper().fieldManager.defs = this.getMetadata().get('fields');
        this.getDateTime().setSettingsAndPreferences(this.getConfig(), this.getPreferences());

        if (this.formData.captchaKey) {
            // noinspection JSUnresolvedReference
            grecaptcha.ready(() => {
                // noinspection SpellCheckingInspection
                /** @type {HTMLElement|null} */
                const badge = document.querySelector('.grecaptcha-badge');

                if (badge) {
                    badge.style.zIndex = '4';
                }
            });
        }

        for (const it in this.formData.language) {
            this.getLanguage().setScopeData(it, this.formData.language[it]);
        }

        this.model = new Model({}, {
            defs: {fields: this.formData.fieldDefs},
            entityType: 'Lead',
        });

        this.model.url = this.formData.requestUrl;

        const detailLayout = [
            ...(
                this.formData.text ?
                    [
                        {
                            noteText: this.formData.text,
                            noteStyle: 'default',
                            rows: [],
                        }
                    ] : []
            ),
            ...this.formData.detailLayout,
        ];

        this.recordView = new CustomEditView({
            model: this.model,
            detailLayout: detailLayout,
            sideView: null,
            bottomView: null,
            isWide: true,
            shortcutKeysEnabled: true,
            buttonsDisabled: true,
        });

        this.assignView('record', this.recordView, '.record');

        this.whenReady().then(() => this.initAutocomplete());

        this.listenTo(this.recordView, 'save', () => this.actionCreate());

        this.addActionHandler('submit', () => this.actionCreate());
    }

    afterRender() {
        const subContainer = this.element.querySelector('.detail-button-container .sub-container');

        if (subContainer) {
            subContainer.classList.add('sub-container-center-5');
        }

        this.submitButtonElement = this.element.querySelector('button[data-action="submit"]');
    }

    async actionCreate() {
        if (this.isPosting) {
            return;
        }

        if (this.recordView.validate()) {
            Espo.Ui.error(this.translate('Not valid'));

            return;
        }

        this.isPosting = true;

        this.recordView.disableActionItems();

        this.submitButtonElement.classList.add('disabled');
        this.submitButtonElement.setAttribute('disabled', 'disabled');

        Espo.Ui.notifyWait()

        const token = await this.processCaptcha();

        const headers = token ? {'X-Captcha-Token': token} : undefined;

        /** @type {{redirectUrl: string|null}} */
        let result;

        try {
            result = await Espo.Ajax.postRequest(this.model.url, this.model.attributes, {headers: headers});
        } catch (e) {
            this.recordView.enableActionItems();

            this.submitButtonElement.classList.remove('disabled');
            this.submitButtonElement.removeAttribute('disabled');

            this.isPosting = false;

            return;
        }

        Espo.Ui.notify();

        this.isPosted = true;
        this.isPosting = false;

        this.recordView.remove();

        await this.reRender();

        if (result.redirectUrl) {
            document.location.href = result.redirectUrl;
        }
    }

    /**
     * @return {Promise<string|null>}
     */
    async processCaptcha() {
        if (!this.formData.captchaKey) {
            return null;
        }

        return new Promise(resolve => {
            // noinspection JSUnresolvedReference
            grecaptcha.ready(async () => {
                // noinspection JSUnresolvedReference
                const token = await grecaptcha.execute(this.formData.captchaKey, {action: 'leadCaptureSubmit'});

                resolve(token);
            });
        })
    }

    /**
     * @private
     */
    initAutocomplete() {
        const emailAddressView = this.recordView.getFieldView('emailAddress');

        if (emailAddressView) {
            this.listenTo(emailAddressView, 'after:render', () => {
                /** @type {HTMLInputElement} */
                const element = emailAddressView.element.querySelector('input');

                if (element) {
                    element.autocomplete = 'email';
                }
            });
        }

        const phoneNumberView = this.recordView.getFieldView('phoneNumber');

        if (phoneNumberView) {
            this.listenTo(phoneNumberView, 'after:render', () => {
                /** @type {HTMLInputElement} */
                const element = phoneNumberView.element.querySelector('input');

                if (element) {
                    element.autocomplete = 'tel';
                }
            });
        }

        const nameView = this.recordView.getFieldView('name');

        this.listenTo(nameView, 'after:render', () => {
            /** @type {HTMLInputElement} */
            const elementFirst = nameView.element.querySelector('input[data-name="firstName"]');

            if (elementFirst) {
                elementFirst.autocomplete = 'given-name';
            }

            /** @type {HTMLInputElement} */
            const elementLast = nameView.element.querySelector('input[data-name="lastName"]');

            if (elementLast) {
                elementLast.autocomplete = 'family-name';
            }
        });

        const firstName = this.recordView.getFieldView('firstName');

        if (firstName) {
            this.listenTo(firstName, 'after:render', () => {
                /** @type {HTMLInputElement} */
                const element = firstName.element.querySelector('input');

                if (element) {
                    element.autocomplete = 'given-name';
                }
            });
        }

        const lastName = this.recordView.getFieldView('lastName');

        if (lastName) {
            this.listenTo(lastName, 'after:render', () => {
                /** @type {HTMLInputElement} */
                const element = lastName.element.querySelector('input');

                if (element) {
                    element.autocomplete = 'family-name';
                }
            });
        }
    }
}

class CustomEditView extends EditRecordView {

    handleShortcutKeyCtrlEnter(e) {
        this.trigger('save');
    }
}
