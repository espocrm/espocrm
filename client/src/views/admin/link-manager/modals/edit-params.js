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
import EditForModalRecordView from 'views/record/edit-for-modal';
import BoolFieldView from 'views/fields/bool';

export default class LinkManagerEditParamsModalView extends ModalView {

    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `

    /**
     * @private
     * @type {string}
     */
    type

    /**
     * @param {{
     *     entityType: string,
     *     link: string,
     * }} props
     */
    constructor(props) {
        super();

        this.props = props;
    }

    setup() {
        this.headerText = this.translate('Parameters', 'labels', 'EntityManager') + ' · ' +
            this.translate(this.props.entityType, 'scopeNames') + ' · ' +
            this.translate(this.props.link, 'links', this.props.entityType);

        /** @type {{type: string, isCustom: boolean}} */
        const defs = this.getMetadata().get(`entityDefs.${this.props.entityType}.links.${this.props.link}`) || {};
        this.type = defs.type;

        this.buttonList = [
            {
                name: 'save',
                style: 'danger',
                label: 'Save',
                onClick: () => this.save(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.close(),
            },
        ];

        if (!defs.isCustom) {
            this.addDropdownItem({
                name: 'resetToDefault',
                text: this.translate('Reset to Default', 'labels', 'Admin'),
                onClick: () => this.resetToDefault(),
            });
        }

        this.formModel = new Model(this.getParamsFromMetadata());

        this.recordView = new EditForModalRecordView({
            model: this.formModel,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new BoolFieldView({
                                    name: 'readOnly',
                                    labelText: this.translate('readOnly', 'fields', 'Admin'),
                                    params: {
                                        tooltip: 'EntityManager.linkParamReadOnly',
                                    },
                                }),
                            },
                            false
                        ]
                    ]
                }
            ]
        });

        if (!this.hasReadOnly()) {
            this.recordView.hideField('readOnly');
            this.recordView.setFieldReadOnly('readOnly');
        }

        this.assignView('record', this.recordView, '.record');
    }

    /**
     * @private
     * @return {boolean}
     */
    hasReadOnly() {
        return ['hasMany', 'hasChildren'].includes(this.type);
    }

    /**
     * @private
     * @return {Record}
     */
    getParamsFromMetadata() {
        /** @type {Record} */
        const defs = this.getMetadata().get(`entityDefs.${this.props.entityType}.links.${this.props.link}`) || {};

        return {
            readOnly: defs.readOnly || false,
        };
    }

    /**
     * @private
     */
    disableAllActionItems() {
        this.disableButton('save');
        this.hideActionItem('resetToDefault');
    }

    /**
     * @private
     */
    enableAllActionItems() {
        this.enableButton('save');
        this.showActionItem('resetToDefault');
    }

    /**
     * @private
     */
    async save() {
        if (this.recordView.validate()) {
            return;
        }

        this.disableAllActionItems();
        Espo.Ui.notifyWait();

        const params = {};

        if (this.hasReadOnly()) {
            params.readOnly = this.formModel.attributes.readOnly;
        }

        try {
            await Espo.Ajax.postRequest('EntityManager/action/updateLinkParams', {
                entityType: this.props.entityType,
                link: this.props.link,
                params: params,
            });
        } catch (e) {
            this.enableAllActionItems();

            return;
        }

        await Promise.all([this.getMetadata().loadSkipCache()]);
        this.broadcastUpdate();

        this.close();

        Espo.Ui.success(this.translate('Saved'));
    }

    /**
     * @private
     */
    async resetToDefault() {
        this.disableAllActionItems();
        Espo.Ui.notifyWait();

        try {
            await Espo.Ajax.postRequest('EntityManager/action/resetLinkParamsToDefault', {
                entityType: this.props.entityType,
                link: this.props.link,
            });
        } catch (e) {
            this.enableAllActionItems();

            return;
        }

        await Promise.all([this.getMetadata().loadSkipCache()]);
        this.broadcastUpdate();
        this.formModel.setMultiple(this.getParamsFromMetadata());
        this.enableAllActionItems();

        Espo.Ui.success(this.translate('Saved'));
    }

    /**
     * @private
     */
    broadcastUpdate() {
        this.getHelper().broadcastChannel.postMessage('update:metadata');
    }
}
