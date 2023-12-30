/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module collections/tree */

import Collection from 'collection';

class TreeCollection extends Collection {

    createSeed() {
        const seed = new this.constructor();

        seed.url = this.url;
        seed.model = this.model;
        seed.name = this.name;
        seed.entityType = this.entityType;
        seed.defs = this.defs;

        return seed;
    }

    prepareAttributes(response, options) {
        const list = super.prepareAttributes(response, options);

        const seed = this.clone();

        seed.reset();

        this.path = response.path;
        /**
         * @type {{
         *     name: string,
         *     upperId?: string,
         *     upperName?: string,
         * }|null}
         */
        this.categoryData = response.data || null;

        const f = (l, depth) => {
            l.forEach(d => {
                d.depth = depth;

                const c = this.createSeed();

                if (d.childList) {
                    if (d.childList.length) {
                        f(d.childList, depth + 1);
                        c.set(d.childList);
                        d.childCollection = c;

                        return;
                    }

                    d.childCollection = c;

                    return;
                }

                if (d.childList === null) {
                    d.childCollection = null;

                    return;
                }

                d.childCollection = c;
            });
        };

        f(list, 0);

        return list;
    }

    fetch(options) {
        options = options || {};
        options.data = options.data || {};

        if (this.parentId) {
            options.data.parentId = this.parentId;
        }

        options.data.maxDepth = this.maxDepth;

        return super.fetch(options);
    }
}

export default TreeCollection;
