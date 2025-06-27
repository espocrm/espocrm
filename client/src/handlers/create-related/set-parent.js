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

import CreateRelatedHandler from 'handlers/create-related';
import {inject} from 'di';
import ModelFactory from 'model-factory';

export default class SetParentHandler extends CreateRelatedHandler {

    /**
     * @private
     * @type {ModelFactory}
     */
    @inject(ModelFactory)
    modelFactory

    async getAttributes(model, link) {
        const entityType = model.getLinkParam(link, 'entity');

        if (!entityType) {
            return {};
        }

        const seed = await this.modelFactory.create(entityType);

        /** @type {string[]} */
        const parentEntityTypeList = seed.getFieldParam('parent', 'entityList') ?? [];

        if (!parentEntityTypeList.includes(model.entityType)) {
            return {};
        }

        return {
            parentId: model.id,
            parentName: model.attributes.name,
            parentType: model.entityType,
        }
    }
}

