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

/** @module model-factory */

import Metadata from 'metadata';
import Model from 'model';
import loader

/**
 * A model factory.
 */
export default class ModelFactory {

    constructor(private metadata: Metadata) {}

    /**
     * Create a model.
     *
     * @param entityType An entity type.
     * @param [callback] Deprecated.
     * @param [context] Deprecated.
     * @returns A created model.
     */
    create(entityType: string, callback?: Function, context?: object): Promise<Model> {
        return new Promise(resolve => {
            context = context || this;

            this.getSeed(entityType, Seed => {
                const model = new Seed({}, {
                    entityType: entityType,
                    defs: this.metadata.get(['entityDefs', entityType]) || {},
                });

                if (callback) {
                    callback.call(context, model);
                }

                resolve(model);
            });
        });
    }

    /**
     * Get a class.
     *
     * @param {string} entityType An entity type.
     * @param callback A callback.
     * @public
     * @internal
     */
    getSeed(entityType: string, callback: (modelClass: typeof Model) => void) {
        const className = (this.metadata.get(['clientDefs', entityType, 'model']) ?? 'model') as string;

        Espo.loader.require(className, (modelClass: typeof Model) => callback(modelClass));
    }
}
