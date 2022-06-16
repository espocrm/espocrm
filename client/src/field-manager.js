/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('field-manager', [], function () {

    /**
     * Utility for getting field related meta information.
     *
     * @class
     * @name Class
     * @memberOf module:field-manager
     *
     * @param {Object} defs Field type definitions (metadata > fields).
     * @param {module:metadata.Class} metadata Metadata.
     * @param {modules:acl-manager.Class} [acl] An ACL.
     */
    let FieldManager = function (defs, metadata, acl) {
        /**
         * @private
         * @type {Object}
         */
        this.defs = defs || {};

        /**
         * @private
         * @type {module:metadata.Class}
         */
        this.metadata = metadata;

        /**
         * @private
         * @type {module:acl-manager.Class}
         */
        this.acl = acl;
    };

    _.extend(FieldManager.prototype, /** @lends module:field-manager.Class# */{

        /**
         * Get a list of parameters for a specific field type.
         *
         * @param {string} fieldType A field type.
         * @returns {string[]}
         */
        getParamList: function (fieldType) {
            if (fieldType in this.defs) {
                return this.defs[fieldType].params || [];
            }

            return [];
        },

        /**
         * Whether search filters are allowed for a field type.
         *
         * @param {string} fieldType A field type.
         * @returns {boolean}
         */
        checkFilter: function (fieldType) {
            if (fieldType in this.defs) {
                if ('filter' in this.defs[fieldType]) {
                    return this.defs[fieldType].filter;
                }

                return false;
            }

            return false;
        },

        /**
         * Whether a merge operation is allowed for a field type.
         *
         * @param {string} fieldType A field type.
         * @returns {boolean}
         */
        isMergeable: function (fieldType) {
            if (fieldType in this.defs) {
                return !this.defs[fieldType].notMergeable;
            }

            return false;
        },

        /**
         * Get a list of attributes of an entity type.
         *
         * @param {string} entityType An entity type.
         * @returns {string[]}
         */
        getEntityTypeAttributeList: function (entityType) {
            let list = [];

            let defs = this.metadata.get('entityDefs.' + entityType + '.fields') || {};

            Object.keys(defs).forEach(field => {
                this.getAttributeList(defs[field]['type'], field).forEach(attr => {
                    if (!~list.indexOf(attr)) {
                        list.push(attr);
                    }
                });
            });

            return list;
        },

        /**
         * Get a list of actual attributes by a given field type and field name.
         * Non-actual attributes contains data that for a representation-only purpose.
         * E.g. `accountId` is actual, `accountName` is non-actual.
         *
         * @param {string} fieldType A field type.
         * @param {string} fieldName A field name.
         * @returns {string[]}
         */
        getActualAttributeList: function (fieldType, fieldName) {
            let fieldNames = [];

            if (fieldType in this.defs) {
                if ('actualFields' in this.defs[fieldType]) {
                    let actualFields = this.defs[fieldType].actualFields;

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
        },

        /**
         * Get a list of non-actual attributes by a given field type and field name.
         * Non-actual attributes contains data that for a representation-only purpose.
         * E.g. `accountId` is actual, `accountName` is non-actual.
         *
         * @param {string} fieldType A field type.
         * @param {string} fieldName A field name.
         * @returns {string[]}
         */
        getNotActualAttributeList: function (fieldType, fieldName) {
            let fieldNames = [];

            if (fieldType in this.defs) {
                if ('notActualFields' in this.defs[fieldType]) {
                    let notActualFields = this.defs[fieldType].notActualFields;

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
        },

        /**
         * Get an attribute list of a specific field.
         *
         * @param {string} entityType An entity type.
         * @param {string} field A field.
         * @returns {string[]}
         */
        getEntityTypeFieldAttributeList: function (entityType, field) {
            let type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);

            if (!type) {
                return [];
            }

            return _.union(
                this.getAttributeList(type, field),
                this._getEntityTypeFieldAdditionalAttributeList(entityType, field)
            );
        },

        /**
         * Get an actual attribute list of a specific field.
         *
         * @param {string} entityType An entity type.
         * @param {string} field A field.
         * @returns {string[]}
         */
        getEntityTypeFieldActualAttributeList: function (entityType, field) {
            let type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);

            if (!type) {
                return [];
            }

            return _.union(
                this.getActualAttributeList(type, field),
                this._getEntityTypeFieldAdditionalAttributeList(entityType, field)
            );
        },

        /**
         * @private
         */
        _getEntityTypeFieldAdditionalAttributeList: function (entityType, field) {
            let type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);

            if (!type) {
                return [];
            }

            let partList = this.metadata
                .get(['entityDefs', entityType, 'fields', field, 'additionalAttributeList']) || [];

            if (partList.length === 0) {
                return [];
            }

            let isPrefix = (this.defs[type] || {}).naming === 'prefix';

            let list = [];

            partList.forEach(item => {
                if (isPrefix) {
                    list.push(item + Espo.Utils.upperCaseFirst(field));

                    return;
                }

                list.push(field + Espo.Utils.upperCaseFirst(item));
            });

            return list;
        },

        /**
         * Get a list of attributes by a given field type and field name.
         *
         * @param {string} fieldType A field type.
         * @param {string} fieldName A field name.
         * @returns {string}
         */
        getAttributeList: function (fieldType, fieldName) {
            return _.union(
                this.getActualAttributeList(fieldType, fieldName),
                this.getNotActualAttributeList(fieldType, fieldName)
            );
        },

        /**
         * @typedef {Object} module:field-manager.Class~FieldFilters
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
         * @param {module:field-manager.Class~FieldFilters} [o] Filters.
         * @returns {string[]}
         */
        getEntityTypeFieldList: function (entityType, o) {
            let list = Object.keys(this.metadata.get(['entityDefs', entityType, 'fields']) || {});

            o = o || {};

            let typeList = o.typeList;

            if (!typeList && o.type) {
                typeList = [o.type];
            }

            if (typeList) {
                list = list.filter(item => {
                    let type = this.metadata.get(['entityDefs', entityType, 'fields', item, 'type']);

                    return ~typeList.indexOf(type);
                });
            }

            if (o.onlyAvailable || o.acl) {
                list = list.filter(item => {
                    return this.isEntityTypeFieldAvailable(entityType, item);
                });
            }

            if (o.acl) {
                let level = o.acl || 'read';

                let forbiddenEditFieldList = this.acl.getScopeForbiddenFieldList(entityType, level);

                list = list.filter(item => {
                    return !~forbiddenEditFieldList.indexOf(item);
                });
            }

            return list;
        },

        /**
         * @deprecated Since v5.7.
         */
        getScopeFieldList: function (entityType) {
            return this.getEntityTypeFieldList(entityType);
        },

        /**
         * Get a field parameter value.
         *
         * @param {string} entityType An entity type.
         * @param {string} field A field name.
         * @param {string} param A parameter name.
         * @returns {*}
         */
        getEntityTypeFieldParam: function (entityType, field, param) {
            return this.metadata.get(['entityDefs', entityType, 'fields', field, param]);
        },

        /**
         * Get a view name/path for a specific field type.
         *
         * @param {string} fieldType A field type.
         * @returns {string}
         */
        getViewName: function (fieldType) {
            if (fieldType in this.defs) {
                if ('view' in this.defs[fieldType]) {
                    return this.defs[fieldType].view;
                }
            }

            return 'views/fields/' + Espo.Utils.camelCaseToHyphen(fieldType);
        },

        /**
         * @deprecated Use `getParamList`.
         */
        getParams: function (fieldType) {
            return this.getParamList(fieldType);
        },

        /**
         * @deprecated Use `getAttributeList`.
         */
        getAttributes: function (fieldType, fieldName) {
            return this.getAttributeList(fieldType, fieldName);
        },

        /**
         * @deprecated Use `getActualAttributeList`.
         */
        getActualAttributes: function (fieldType, fieldName) {
            return this.getActualAttributeList(fieldType, fieldName);
        },

        /**
         * @deprecated Use `getNotActualAttributeList`.
         */
        getNotActualAttributes: function (fieldType, fieldName) {
            return this.getNotActualAttributeList(fieldType, fieldName);
        },

        /**
         * Check whether a field is not disabled, not only-admin, not forbidden and not internal.
         *
         * @param {string} entityType An entity type.
         * @param {string} field A field name.
         * @returns {boolean}
         */
        isEntityTypeFieldAvailable: function (entityType, field) {
            if (this.metadata.get(['entityDefs', entityType, 'fields', field, 'disabled'])) {
                return false;
            }

            if (
                this.metadata.get(['entityAcl', entityType, 'fields', field, 'onlyAdmin']) ||
                this.metadata.get(['entityAcl', entityType, 'fields', field, 'forbidden']) ||
                this.metadata.get(['entityAcl', entityType, 'fields', field, 'internal'])
            ) {
                return false;
            }

            return true;
        },

        /**
         * @deprecated Use `isEntityTypeFieldAvailable`.
         */
        isScopeFieldAvailable: function (entityType, field) {
            return this.isEntityTypeFieldAvailable(entityType, field);
        },
    });

    return FieldManager;
});
