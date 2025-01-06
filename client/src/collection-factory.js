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

/** @module collection-factory */

/**
 * A collection factory.
 */
class CollectionFactory {
    /**
     * @param {module:model-factory} modelFactory
     * @param {module:models/settings} config
     * @param {module:metadata} metadata
     */
    constructor(modelFactory, config, metadata) {
        /** @private */
        this.modelFactory = modelFactory;
        /** @private */
        this.metadata = metadata;
        /** @private */
        this.recordListMaxSizeLimit = config.get('recordListMaxSizeLimit') || 200;
    }

    /**
     * Create a collection.
     *
     * @param {string} entityType An entity type.
     * @param {Function} [callback] Deprecated.
     * @param {Object} [context] Deprecated.
     * @returns {Promise<module:collection>}
     */
    create(entityType, callback, context) {
        return new Promise(resolve => {
            context = context || this;

            this.modelFactory.getSeed(entityType, Model => {
                const orderBy = this.modelFactory.metadata
                    .get(['entityDefs', entityType, 'collection', 'orderBy']);

                const order = this.modelFactory.metadata
                    .get(['entityDefs', entityType, 'collection', 'order']);

                const className = this.modelFactory.metadata
                    .get(['clientDefs', entityType, 'collection']) || 'collection';

                const defs = this.metadata.get(['entityDefs', entityType]) || {};

                Espo.loader.require(className, Collection => {
                    const collection = new Collection(null, {
                        entityType: entityType,
                        orderBy: orderBy,
                        order: order,
                        defs: defs,
                    });

                    collection.model = Model;
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

export default CollectionFactory;
