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

import {inject} from 'di';
import Metadata from 'metadata';

// noinspection JSUnusedGlobalSymbols
export default class CascadeLinksHelper {

    /**
     * @type {Metadata}
     * @private}
     */
    @inject(Metadata)
    metadata

    /**
     * @param {{
     *     model: import('model').default,
     *     foreignEntityType: string,
     *     items: {
     *         localField: string,
     *         foreignField: string,
     *         matchRequired: boolean,
     *     }[],
     * }} options
     */
    constructor(options) {
        this.options = options;

        this.model = options.model;
    }

    /**
     * @private
     * @param {
     *     {
     *         localField: string,
     *         foreignField: string,
     *         matchRequired: boolean,
     *     }
     * } item
     * @param {string|null} targetEntityType
     * @return {{id: string, entityType: string, name: string}[]|null}
     */
    prepareItemEntries(item, targetEntityType) {
        const field = item.localField;
        const foreignField = item.foreignField;

        if (!field || !foreignField) {
            console.warn("Bad cascading fields definition.");

            return null;
        }

        const type = this.model.getFieldType(field);
        let entityType = this.model.getLinkParam(field, 'entity');

        /** @type {{id: string, entityType: string, name: string}[]} */
        const entries = [];

        if (type === 'linkParent') {
            entityType = this.model.get(field + 'Type');
        }

        if (!entityType) {
            return null;
        }

        if (targetEntityType !== entityType) {
            return null;
        }

        if (type === 'link' || type === 'linkParent' || type === 'linkOne') {
            const id = this.model.get(field + 'Id');
            const name = this.model.get(field + 'Name');

            if (!id) {
                return null;
            }

            entries.push({id, entityType, name});
        } else if (type === 'linkMultiple') {
            /** @type {string[]} */
            const ids = this.model.get(field + 'Ids') ?? [];
            const names = this.model.get(field + 'Names') ?? {};

            entries.push(
                ...ids.map(id => ({id, entityType, name: names[id]}))
            );
        } else {
            return null;
        }

        return entries;
    }

    /**
     * @return {Object.<string, module:search-manager~advancedFilter>}
     */
    prepareFilters() {
        /**
         * @param {{localField: string, foreignField: string, matchRequired: boolean}} item
         * @return {Object.<string, module:search-manager~advancedFilter>|null}
         */
        const prepareItem = (item) => {
            const field = item.localField;
            const foreignField = item.foreignField;

            if (!field || !foreignField) {
                console.warn("Bad cascading fields definition.");

                return null;
            }

            const linkEntityType = this.getLinkEntityType(foreignField);
            const foreignType = this.getForeignType(foreignField);

            const entries = this.prepareItemEntries(item, linkEntityType);

            if (entries === null || !entries.length) {
                return null;
            }

            if (foreignType === 'link' || foreignType === 'linkOne') {
                if (entries.length > 1) {
                    return {
                        [foreignField]: {
                            type: 'in',
                            attribute: foreignField + 'Id',
                            value: entries.map(it => it.id),
                            data: {
                                type: 'isOneOf',
                                oneOfIdList: entries.map(it => it.id),
                                oneOfNameHash: entries.reduce((p, it) => {
                                    p[it.id] = it.name;

                                    return p;
                                }, {}),
                            },
                        }
                    };
                }

                return {
                    [foreignField]: {
                        type: 'equals',
                        attribute: foreignField + 'Id',
                        value: entries[0].id,
                        data: {
                            type: 'equals',
                            idValue: entries[0].id,
                            nameValue: entries[0].name,
                        }
                    }
                };
            }

            if (foreignType === 'linkMultiple') {
                return {
                    [foreignField]: {
                        type: 'linkedWithAll',
                        attribute: foreignField,
                        value: entries.map(it => it.id),
                        data: {
                            type: 'allOf',
                            nameHash: entries.reduce((p, it) => {
                                p[it.id] = it.name;

                                return p;
                            }, {}),
                        },
                    }
                };
            }

            // Not supported.
            if (foreignType === 'linkParent') {
                if (entries.length > 1) {
                    console.warn("Cascading fields do not support multiple matches for link-parent filters.");

                    return null;
                }

                return {
                    [foreignField]: {
                        type: 'and',
                        attribute: foreignField + 'Id',
                        value: [
                            {
                                type: 'equals',
                                field: foreignField + 'Id',
                                value: entries[0].id,
                            },
                            {
                                type: 'equals',
                                field: foreignField + 'Type',
                                value: entries[0].entityType,
                            }
                        ],
                        data: {
                            type: 'is',
                            idValue: entries[0].id,
                            nameValue: entries[0].name,
                            typeValue: entries[0].entityType,
                        }
                    }
                };
            }

            return null;
        };

        const output = {};

        for (const item of this.options.items) {
            const itemOutput = prepareItem(item);

            if (itemOutput === null && item.matchRequired) {
                return {
                    id: {
                        type: 'isNull',
                        attribute: 'id',
                        data: {
                            type: 'isEmpty',
                        },
                    },
                };
            }

            Object.assign(output, itemOutput);
        }

        return output;
    }

    /**
     * @return {Object.<string, *>}
     */
    prepareCreateAttributes() {
        const attributes = {};

        /**
         * @param {{localField: string, foreignField: string, matchRequired: boolean}} item
         * @return {Object.<string, *>}
         */
        const prepareItem = (item) => {
            const field = item.localField;
            const foreignField = item.foreignField;

            if (!field || !foreignField) {
                console.warn("Bad cascading fields definition.");

                return null;
            }

            const linkEntityType = this.getLinkEntityType(foreignField);
            const foreignType = this.getForeignType(foreignField);

            const entries = this.prepareItemEntries(item, linkEntityType);

            if (entries === null || !entries.length) {
                return null;
            }

            if (foreignType === 'link' || foreignType === 'linkOne') {
                return {
                    [foreignField + 'Id']: entries[0].id,
                    [foreignField + 'Name']: entries[0].name,
                };
            }

            if (foreignType === 'linkMultiple') {
                return {
                    [foreignField + 'Ids']: entries.map(it => it.id),
                    [foreignField + 'Names']: entries.reduce((p, it) => {
                        p[it.id] = it.name;

                        return p;
                    }, {}),
                }
            }

            // Not supported.
            if (foreignType === 'linkParent') {
                return {
                    [foreignField + 'Id']: entries[0].id,
                    [foreignField + 'Name']: entries[0].name,
                    [foreignField + 'Type']: entries[0].entityType,
                };
            }
        };

        for (const item of this.options.items) {
            const itemOutput = prepareItem(item);

            if (itemOutput === null && item.matchRequired) {
                continue;
            }

            Object.assign(attributes, itemOutput);
        }

        return attributes;
    }

    /**
     * @private
     * @param {string} foreignField
     * @return {string|null}
     */
    getLinkEntityType(foreignField) {
        return this.metadata.get(`entityDefs.${this.options.foreignEntityType}.links.${foreignField}.entity`);
    }

    /**
     * @private
     * @param {string} foreignField
     * @return {string|null}
     */
    getForeignType(foreignField) {
        return this.metadata.get(`entityDefs.${this.options.foreignEntityType}.fields.${foreignField}.type`);
    }
}
