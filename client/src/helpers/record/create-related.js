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
import RecordModal from 'helpers/record-modal';

/**
 * @internal
 */
class CreateRelatedHelper {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @param {import('view').default} view
     */
    constructor(view) {
        /** @private */
        this.view = view;
    }

    /**
     * @param {import('model').default} model
     * @param {string} link
     * @param {{
     *     afterSave: function(import('model').default),
     * }} [options]
     */
    async process(model, link, options = {}) {
        const scope = model.defs['links'][link].entity;
        const foreignLink = model.defs['links'][link].foreign;

        /** @type {Record} */
        const panelDefs = this.metadata.get(`clientDefs.${model.entityType}.relationshipPanels.${link}`) || {};

        const attributeMap = panelDefs.createAttributeMap || {};
        const handler = panelDefs.createHandler;

        let attributes = {};

        Object.keys(attributeMap).forEach(attr => attributes[attributeMap[attr]] = model.get(attr));

        if (handler) {
            const Handler = await Espo.loader.requirePromise(handler);
            /** @type {import('handlers/create-related').default} */
            const handlerObj = new Handler(this.view.getHelper());

            const additionalAttributes = await handlerObj.getAttributes(model, link);

            attributes = {...attributes, ...additionalAttributes};
        }

        const helper = new RecordModal();

        await helper.showCreate(this.view, {
            entityType: scope,
            relate: {
                model: model,
                link: foreignLink,
            },
            attributes: attributes,
            afterSave: m => {
                if (options.afterSave) {
                    options.afterSave(m);
                }

                model.trigger(`update-related:${link}`);
                model.trigger('after:relate');
                model.trigger(`after:relate:${link}`);
            },
        });
    }
}

export default CreateRelatedHelper;
