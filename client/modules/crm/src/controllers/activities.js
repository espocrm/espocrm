/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import Controller from 'controller';

export default class ActivitiesController extends Controller {

    checkAccess(action) {
        return this.getAcl().check('Activities');
    }

    // noinspection JSUnusedGlobalSymbols
    actionActivities(options) {
        this.processList('activities', options.entityType, options.id, options.targetEntityType);
    }

    actionHistory(options) {
        this.processList('history', options.entityType, options.id, options.targetEntityType);
    }

    /**
     * @private
     * @param {'activities'|'history'} type
     * @param {string} entityType
     * @param {string} id
     * @param {string} targetEntityType
     */
    async processList(type, entityType, id, targetEntityType) {
        const viewName = 'modules/crm/views/activities/list';

        const model = await this.modelFactory.create(entityType)

        model.id = id;

        await model.fetch({main: true});

        const collection = await this.collectionFactory.create(targetEntityType);
        collection.url = `Activities/${model.entityType}/${id}/${type}/list/${targetEntityType}`;

        this.main(viewName, {
            scope: entityType,
            model: model,
            collection: collection,
            link:  `${type}_${targetEntityType}`,
            type: type,
        });
    }
}
