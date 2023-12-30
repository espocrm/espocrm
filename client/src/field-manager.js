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

/** @module field-manager */

/**
 * Utility for getting field related meta information.
 */
class FieldManager {

    /**
     * Utility for getting field related meta information.
     *
     * @param {Object} [defs] Field type definitions (metadata > fields).
     * @param {module:metadata} [metadata] Metadata.
     * @param {module:acl-manager} [acl] An ACL.
     */
    constructor(defs, metadata, acl) {

        /**
         * @typedef {Object} FieldManager~defs
         * @property {string[]} [actualFields]
         * @property {string[]} [notActualFields]
         * @property {'suffix'|'prefix'} [naming]
         * @property {Object.<string, Object.<string, *>>} [params]
         * @property {boolean} [filter]
         * @property {boolean} [notMergeable]
         * @property {string} [view]
         */


        /**
         * @public
         * @internal
         * @type {FieldManager~defs}
         */
        this.defs = defs || /** @type {FieldManager~defs} */ {};

        /**
         * @public
         * @internal
         * @type {module:metadata}
         */
        this.metadata = metadata;

        /**
         * @public
         * @internal
         * @type {module:acl-manager}
         */
        this.acl = acl;
    }

    /**
     * Get a list of parameters for a specific field type.
     *
     * @param {string} fieldType A field type.
     * @returns {Object.<string, *>[]}
     */
    getParamList(fieldType) {
        if (fieldType in this.defs) {
            return this.defs[fieldType].params || [];
        }

        return [];
    }

    /**
     * Whether search filters are allowed for a field type.
     *
     * @param {string} fieldType A field type.
     * @returns {boolean}
     */
    checkFilter(fieldType) {
        if (fieldType in this.defs) {
            if ('filter' in this.defs[fieldType]) {
                return this.defs[fieldType].filter;
            }

            return false;
        }

        return false;
    }

    /**
     * Whether a merge operation is allowed for a field type.
     *
     * @param {string} fieldType A field type.
     * @returns {boolean}
     */
    isMergeable(fieldType) {
        if (fieldType in this.defs) {
            return !this.defs[fieldType].notMergeable;
        }

        return false;
    }

    /**
     * Get a list of attributes of an entity type.
     *
     * @param {string} entityType An entity type.
     * @returns {string[]}
     */
    getEntityTypeAttributeList(entityType) {
        const list = [];

        const defs = this.metadata.get('entityDefs.' + entityType + '.fields') || {};

        Object.keys(defs).forEach(field => {
            this.getAttributeList(defs[field]['type'], field).forEach(attr => {
                if (!~list.indexOf(attr)) {
                    list.push(attr);
                }
            });
        });

        return list;
    }

    /**
     * Get a list of actual attributes by a given field type and field name.
     * Non-actual attributes contains data that for a representation-only purpose.
     * E.g. `accountId` is actual, `accountName` is non-actual.
     *
     * @param {string} fieldType A field type.
     * @param {string} fieldName A field name.
     * @returns {string[]}
     */
    getActualAttributeList(fieldType, fieldName) {
        const fieldNames = [];

        if (fieldType in this.defs) {
            if ('actualFields' in this.defs[fieldType]) {
                const actualFields = this.defs[fieldType].actualFields;

                let naming = 'suffix';

                if ('naming' in this.defs[fieldType]) {
                    naming = this.defs[fieldType].naming;
                }

                if (naming === 'prefix') {
                    actualFields.forEach(f => {
                        fieldNames.push(f + Espo.Utils.upperCaseFirst(fieldName));
                    });
                }
                else {
                    actualFields.forEach(f => {
                        fieldNames.push(fieldName + Espo.Utils.upperCaseFirst(f));
                    });
                }
            }
            else {
                fieldNames.push(fieldName);
            }
        }

        return fieldNames;
    }

    /**
     * Get a list of non-actual attributes by a given field type and field name.
     * Non-actual attributes contains data that for a representation-only purpose.
     * E.g. `accountId` is actual, `accountName` is non-actual.
     *
     * @param {string} fieldType A field type.
     * @param {string} fieldName A field name.
     * @returns {string[]}
     */
    getNotActualAttributeList(fieldType, fieldName) {
        const fieldNames = [];

        if (fieldType in this.defs) {
            if ('notActualFields' in this.defs[fieldType]) {
                const notActualFields = this.defs[fieldType].notActualFields;

                let naming = 'suffix';

                if ('naming' in this.defs[fieldType]) {
                    naming = this.defs[fieldType].naming;
                }

                if (naming === 'prefix') {
                    notActualFields.forEach(f => {
                        if (f === '') {
                            fieldNames.push(fieldName);
                        }
                        else {
                            fieldNames.push(f + Espo.Utils.upperCaseFirst(fieldName));
                        }
                    });
                }
                else {
                    notActualFields.forEach(f => {
                        fieldNames.push(fieldName + Espo.Utils.upperCaseFirst(f));
                    });
                }
            }
        }

        return fieldNames;
    }

    /**
     * Get an attribute list of a specific field.
     *
     * @param {string} entityType An entity type.
     * @param {string} field A field.
     * @returns {string[]}
     */
    getEntityTypeFieldAttributeList(entityType, field) {
        const type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);

        if (!type) {
            return [];
        }

        return _.union(
            this.getAttributeList(type, field),
            this._getEntityTypeFieldAdditionalAttributeList(entityType, field)
        );
    }

    /**
     * Get an actual attribute list of a specific field.
     *
     * @param {string} entityType An entity type.
     * @param {string} field A field.
     * @returns {string[]}
     */
    getEntityTypeFieldActualAttributeList(entityType, field) {
        const type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);

        if (!type) {
            return [];
        }

        return _.union(
            this.getActualAttributeList(type, field),
            this._getEntityTypeFieldAdditionalAttributeList(entityType, field)
        );
    }

    /**
     * @private
     */
    _getEntityTypeFieldAdditionalAttributeList(entityType, field) {
        const type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);

        if (!type) {
            return [];
        }

        const partList = this.metadata
            .get(['entityDefs', entityType, 'fields', field, 'additionalAttributeList']) || [];

        if (partList.length === 0) {
            return [];
        }

        const isPrefix = (this.defs[type] || {}).naming === 'prefix';

        const list = [];

        partList.forEach(item => {
            if (isPrefix) {
                list.push(item + Espo.Utils.upperCaseFirst(field));

                return;
            }

            list.push(field + Espo.Utils.upperCaseFirst(item));
        });

        return list;
    }

    /**
     * Get a list of attributes by a given field type and field name.
     *
     * @param {string} fieldType A field type.
     * @param {string} fieldName A field name.
     * @returns {string[]}
     */
    getAttributeList(fieldType, fieldName) {
        return _.union(
            this.getActualAttributeList(fieldType, fieldName),
            this.getNotActualAttributeList(fieldType, fieldName)
        );
    }

    /**
     * @typedef {Object} module:field-manager~FieldFilters
     *
     * @property {string} [type] Only of a specific field type.
     * @property {string[]} [typeList] Only of a specific field types.
     * @property {boolean} [onlyAvailable] To exclude disabled, admin-only, internal, forbidden fields.
     * @property {'read'|'edit'} [acl] To exclude fields not accessible for a current user over
     *   a specified access level.
     */

    /**
     * Get a list of fields of a specific entity type.
     *
     * @param {string} entityType An entity type.
     * @param {module:field-manager~FieldFilters} [o] Filters.
     * @returns {string[]}
     */
    getEntityTypeFieldList(entityType, o) {
        let list = Object.keys(this.metadata.get(['entityDefs', entityType, 'fields']) || {});

        o = o || {};

        let typeList = o.typeList;

        if (!typeList && o.type) {
            typeList = [o.type];
        }

        if (typeList) {
            list = list.filter(item => {
                const type = this.metadata.get(['entityDefs', entityType, 'fields', item, 'type']);

                return ~typeList.indexOf(type);
            });
        }

        if (o.onlyAvailable || o.acl) {
            list = list.filter(item => {
                return this.isEntityTypeFieldAvailable(entityType, item);
            });
        }

        if (o.acl) {
            const level = o.acl || 'read';

            const forbiddenEditFieldList = this.acl.getScopeForbiddenFieldList(entityType, level);

            list = list.filter(item => {
                return !~forbiddenEditFieldList.indexOf(item);
            });
        }

        return list;
    }

    /**
     * @deprecated Since v5.7.
     */
    getScopeFieldList(entityType) {
        return this.getEntityTypeFieldList(entityType);
    }

    /**
     * Get a field parameter value.
     *
     * @param {string} entityType An entity type.
     * @param {string} field A field name.
     * @param {string} param A parameter name.
     * @returns {*}
     */
    getEntityTypeFieldParam(entityType, field, param) {
        return this.metadata.get(['entityDefs', entityType, 'fields', field, param]);
    }

    /**
     * Get a view name/path for a specific field type.
     *
     * @param {string} fieldType A field type.
     * @returns {string}
     */
    getViewName(fieldType) {
        if (fieldType in this.defs) {
            if ('view' in this.defs[fieldType]) {
                return this.defs[fieldType].view;
            }
        }

        return 'views/fields/' + Espo.Utils.camelCaseToHyphen(fieldType);
    }

    /**
     * @deprecated Use `getParamList`.
     */
    getParams(fieldType) {
        return this.getParamList(fieldType);
    }

    /**
     * @deprecated Use `getAttributeList`.
     */
    getAttributes(fieldType, fieldName) {
        return this.getAttributeList(fieldType, fieldName);
    }

    /**
     * @deprecated Use `getActualAttributeList`.
     */
    getActualAttributes(fieldType, fieldName) {
        return this.getActualAttributeList(fieldType, fieldName);
    }

    /**
     * @deprecated Use `getNotActualAttributeList`.
     */
    getNotActualAttributes(fieldType, fieldName) {
        return this.getNotActualAttributeList(fieldType, fieldName);
    }

    /**
     * Check whether a field is not disabled, not utility, not only-admin, not forbidden and not internal.
     *
     * @param {string} entityType An entity type.
     * @param {string} field A field name.
     * @returns {boolean}
     */
    isEntityTypeFieldAvailable(entityType, field) {
        const defs = this.metadata.get(['entityDefs', entityType, 'fields', field]) || {};

        if (
            defs.disabled ||
            defs.utility
        ) {
            return false;
        }

        const aclDefs = this.metadata.get(['entityAcl', entityType, 'fields', field]) || {};

        if (
            aclDefs.onlyAdmin ||
            aclDefs.forbidden ||
            aclDefs.internal
        ) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated Use `isEntityTypeFieldAvailable`.
     */
    isScopeFieldAvailable(entityType, field) {
        return this.isEntityTypeFieldAvailable(entityType, field);
    }
}

export default FieldManager;
