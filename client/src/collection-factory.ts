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

/** @module collection-factory */

import ModelFactory from 'model-factory';
import Settings from 'models/settings';
import Metadata from 'metadata';
import Collection from 'collection';

/**
 * A collection factory.
 */
export default class CollectionFactory {

    private readonly recordListMaxSizeLimit: number

    /**
     * @param {module:model-factory} modelFactory
     * @param {module:models/settings} config
     * @param {module:metadata} metadata
     */
    constructor(
        private modelFactory: ModelFactory,
        config: Settings,
        private metadata: Metadata,
    ) {
        this.recordListMaxSizeLimit = config.get('recordListMaxSizeLimit') || 200;
    }

    /**
     * Create a collection.
     *
     * @param entityType An entity type.
     * @param [callback] Deprecated.
     * @param [context] Deprecated.
     * @returns A created collection.
     */
    create(entityType: string, callback?: Function, context?: object): Promise<Collection> {
        return new Promise(resolve => {
            context = context || this;

            this.modelFactory.getSeed(entityType, Model => {
                const orderBy = this.metadata.get(['entityDefs', entityType, 'collection', 'orderBy']);
                const order = this.metadata.get(['entityDefs', entityType, 'collection', 'order']);
                const className = this.metadata .get(['clientDefs', entityType, 'collection']) || 'collection';
                const defs = this.metadata.get(['entityDefs', entityType]) || {};

                Espo.loader.require(className, (collectionClass: typeof Collection) => {
                    const collection = new collectionClass(null, {
                        entityType: entityType,
                        orderBy: orderBy,
                        order: order,
                        defs: defs,
                        model: Model,
                    });

                    collection.entityType = entityType;
                    collection.maxMaxSize = this.recordListMaxSizeLimit;

                    if (callback) {
                        callback.call(context, collection);
                    }

                    resolve(collection);
                });
            });
        });
    }
}
