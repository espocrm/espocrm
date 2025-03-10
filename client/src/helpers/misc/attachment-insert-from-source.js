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

import {inject} from 'di';
import Metadata from 'metadata';
import ModelFactory from 'model-factory';

/**
 * @internal
 */
export default class AttachmentInsertSourceFromHelper {

    /**
     * @param {import('views/fields/attachment-multiple').default|import('views/fields/file').default} view
     */
    constructor(view) {
        /** @private */
        this.view = view;

        /** @private */
        this.model = view.model;
    }

    /**
     * @type {Metadata}
     * @private
     */
    @inject(Metadata)
    metadata

    /**
     * @type {ModelFactory}
     * @private
     */
    @inject(ModelFactory)
    modelFactory


    /**
     * @param {{
     *     source: string,
     *     onInsert: function(import('model').default[]),
     * }} params
     */
    insert(params) {
        const source = params.source;

        const viewName =
            this.metadata.get(['clientDefs', 'Attachment', 'sourceDefs', source, 'insertModalView']) ||
            this.metadata.get(['clientDefs', source, 'modalViews', 'select']) ||
            'views/modals/select-records';

        let filters = {};

        if (('getSelectFilters' + source) in this.view) {
            filters = this.view['getSelectFilters' + source]() || {};
        }

        if (this.model.attributes.parentId && this.model.attributes.parentType === 'Account') {
            if (
                this.metadata.get(`entityDefs.${source}.fields.account.type`) === 'link' &&
                this.metadata.get(`entityDefs.${source}.links.account.entity`) === 'Account'
            ) {
                filters = {
                    account: {
                        type: 'equals',
                        attribute: 'accountId',
                        value: this.model.attributes.parentId,
                        data: {
                            type: 'is',
                            idValue: this.model.attributes.parentId,
                            nameValue: this.model.attributes.parentType,
                        },
                    },
                    ...filters,
                };
            }
        }

        let boolFilterList = this.metadata.get(`clientDefs.Attachment.sourceDefs.${source}.boolFilterList`);

        if (('getSelectBoolFilterList' + source) in this.view) {
            boolFilterList = this.view['getSelectBoolFilterList' + source]();
        }

        let primaryFilterName = this.metadata.get(`clientDefs.Attachment.sourceDefs.${source}.primaryFilter`);

        if (('getSelectPrimaryFilterName' + source) in this.view) {
            primaryFilterName = this.view['getSelectPrimaryFilterName' + source]();
        }

        /** @type {module:views/modals/select-records~Options} */
        const options = {
            entityType: source,
            createButton: false,
            filters: filters,
            boolFilterList: boolFilterList,
            primaryFilterName: primaryFilterName,
            multiple: true,
            onSelect: models => {
                models.forEach(async model => {
                    if (model.entityType === 'Attachment') {
                        params.onInsert([model]);

                        return;
                    }

                    /** @type {Record[]} */
                    const attachmentDataList = await Espo.Ajax.postRequest(`${source}/action/getAttachmentList`, {
                        id: model.id,
                        field: this.view.name,
                        parentType: this.view.entityType,
                    });

                    const attachmentSeed = await this.modelFactory.create('Attachment');

                    for (const item of attachmentDataList) {
                        const attachment = attachmentSeed.clone();

                        attachment.set(item);

                        params.onInsert([attachment]);
                    }
                });
            },
        };

        Espo.Ui.notifyWait();

        this.view.createView('modal', viewName, options, view => {
            view.render();

            Espo.Ui.notify();
        });
    }
}
