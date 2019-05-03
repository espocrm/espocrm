/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

    var FieldManager = function (defs, metadata) {
        this.defs = defs || {};
        this.metadata = metadata;
    };

    _.extend(FieldManager.prototype, {

        defs: null,

        metadata: null,

        getParamList: function (fieldType) {
            if (fieldType in this.defs) {
                return this.defs[fieldType].params || [];
            }
            return [];
        },

        checkFilter: function (fieldType) {
            if (fieldType in this.defs) {
                if ('filter' in this.defs[fieldType]) {
                    return this.defs[fieldType].filter;
                } else {
                    return false;
                }
            }
            return false;
        },

        isMergeable: function (fieldType) {
            if (fieldType in this.defs) {
                return !this.defs[fieldType].notMergeable;
            }
            return false;
        },

        getEntityTypeAttributeList: function (entityType) {
            var list = [];
            var defs = this.metadata.get('entityDefs.' + entityType + '.fields') || {};
            Object.keys(defs).forEach(function (field) {
                this.getAttributeList(defs[field]['type'], field).forEach(function (attr) {
                    if (!~list.indexOf(attr)) {
                        list.push(attr);
                    }
                });
            }, this);
            return list;
        },

        getActualAttributeList: function (fieldType, fieldName) {
            var fieldNames = [];
            if (fieldType in this.defs) {
                if ('actualFields' in this.defs[fieldType]) {
                    var actualfFields = this.defs[fieldType].actualFields;

                    var naming = 'suffix';
                    if ('naming' in this.defs[fieldType]) {
                        naming = this.defs[fieldType].naming;
                    }
                    if (naming == 'prefix') {
                        actualfFields.forEach(function (f) {
                            fieldNames.push(f + Espo.Utils.upperCaseFirst(fieldName));
                        });
                    } else {
                        actualfFields.forEach(function (f) {
                            fieldNames.push(fieldName + Espo.Utils.upperCaseFirst(f));
                        });
                    }
                } else {
                    fieldNames.push(fieldName);
                }
            }
            return fieldNames;
        },

        getNotActualAttributeList: function (fieldType, fieldName) {
            var fieldNames = [];
            if (fieldType in this.defs) {
                if ('notActualFields' in this.defs[fieldType]) {
                    var notActualFields = this.defs[fieldType].notActualFields;

                    var naming = 'suffix';
                    if ('naming' in this.defs[fieldType]) {
                        naming = this.defs[fieldType].naming;
                    }
                    if (naming == 'prefix') {
                        notActualFields.forEach(function (f) {
                            if (f === '') {
                                fieldNames.push(fieldName);
                            } else {
                                fieldNames.push(f + Espo.Utils.upperCaseFirst(fieldName));
                            }
                        });
                    } else {
                        notActualFields.forEach(function (f) {
                            fieldNames.push(fieldName + Espo.Utils.upperCaseFirst(f));
                        });
                    }
                }
            }
            return fieldNames;
        },

        getEntityTypeFieldAttributeList: function (entityType, field) {
            var type = this.metadata.get(['entityDefs', entityType, 'fields', field, 'type']);
            if (!type) return [];
            return _.union(
                this.getAttributeList(type, field),
                this.metadata.get(['entityDefs', entityType, 'fields', field, 'additionalAttributeList']) || []
            );
        },

        getAttributeList: function (fieldType, fieldName) {
            return _.union(this.getActualAttributeList(fieldType, fieldName), this.getNotActualAttributeList(fieldType, fieldName));
        },

        getEntityTypeFieldList: function (entityType) {
            return Object.keys(this.metadata.get(['entityDefs', entityType, 'fields']) || {});
        },

        getScopeFieldList: function (entityType) { // TODO remove in 5.8.0
            return this.getEntityTypeFieldList(entityType);
        },

        getEntityTypeFieldParam: function (entityType, field, param) {
            this.metadata.get(['entityDefs', entityType, 'fields', field, param]);
        },

        getViewName: function (fieldType) {
            if (fieldType in this.defs) {
                if ('view' in this.defs[fieldType]) {
                    return this.defs[fieldType].view;
                }
            }
            return 'views/fields/' + Espo.Utils.camelCaseToHyphen(fieldType);
        },

        getParams: function (fieldType) {
            return this.getParamList(fieldType);
        },

        getAttributes: function (fieldType, fieldName) {
            return this.getAttributeList(fieldType, fieldName);
        },

        getActualAttributes: function (fieldType, fieldName) {
            return this.getActualAttributeList(fieldType, fieldName);
        },

        getNotActualAttributes: function (fieldType, fieldName) {
            return this.getNotActualAttributeList(fieldType, fieldName);
        },

        isEntityTypeFieldAvailable: function (entityType, field) {
            if (this.metadata.get(['entityDefs', entityType, 'fields', field, 'disabled'])) return false;
            if (
                this.metadata.get(['entityAcl', entityType, 'fields', field, 'onlyAdmin'])
                ||
                this.metadata.get(['entityAcl', entityType, 'fields', field, 'forbidden'])
                ||
                this.metadata.get(['entityAcl', entityType, 'fields', field, 'internal'])
            ) return false;

            return true;
        },

        isScopeFieldAvailable: function (entityType, field) {
            return this.isEntityTypeFieldAvailable(entityType, field);
        },

    });

    return FieldManager;
});
