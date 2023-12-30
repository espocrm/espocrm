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

define('views/template/fields/variables', ['views/fields/base', 'ui/select'],
function (Dep, /** module:ui/select */Select) {

    return Dep.extend({

        inlineEditDisabled: true,

        detailTemplate: 'template/fields/variables/detail',
        editTemplate: 'template/fields/variables/edit',

        data: function () {
            return {
                attributeList: this.attributeList,
                entityType: this.model.get('entityType'),
                translatedOptions: this.translatedOptions
            };
        },

        events: {
            'change [data-name="variables"]': function () {
                var attribute = this.$el.find('[data-name="variables"]').val();

                var $copy = this.$el.find('[data-name="copy"]');

                if (attribute !== '') {
                    if (this.textVariables[attribute]) {
                        $copy.val('{{{' + attribute + '}}}');
                    } else {
                        $copy.val('{{' + attribute + '}}');
                    }
                } else {
                    $copy.val('');
                }
            }
        },

        setup: function () {
            this.setupAttributeList();
            this.setupTranslatedOptions();

            this.listenTo(this.model, 'change:entityType', () => {
                this.setupAttributeList();
                this.setupTranslatedOptions();
                this.reRender();
            });
        },

        setupAttributeList: function () {
            this.translatedOptions = {};

            var entityType = this.model.get('entityType');

            var fieldList = this.getFieldManager().getEntityTypeFieldList(entityType);

            var ignoreFieldList = [];

            fieldList.forEach(field => {
                let aclDefs = this.getMetadata().get(['entityAcl', entityType, 'fields', field]) || {};
                let fieldDefs = this.getMetadata().get(['entityDefs', entityType, 'fields', field]) || {};

                if (
                    aclDefs.onlyAdmin ||
                    aclDefs.forbidden ||
                    aclDefs.internal ||
                    fieldDefs.disabled ||
                    fieldDefs.utility ||
                    fieldDefs.directAccessDisabled ||
                    fieldDefs.templatePlaceholderDisabled
                ) {
                    ignoreFieldList.push(field);
                }
            });

            var attributeList = this.getFieldManager().getEntityTypeAttributeList(entityType) || [];

            var forbiddenList = Espo.Utils.clone(this.getAcl().getScopeForbiddenAttributeList(entityType));

            ignoreFieldList.forEach((field) => {
                this.getFieldManager().getEntityTypeFieldAttributeList(entityType, field).forEach(function (attribute) {
                    forbiddenList.push(attribute);
                });
            });

            attributeList = attributeList.filter((item) => {
                if (~forbiddenList.indexOf(item)) return;

                var fieldType = this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'type']);

                if (fieldType === 'map') {
                    return;
                }

                return true;
            });


            attributeList.push('id');

            if (this.getMetadata().get('entityDefs.' + entityType + '.fields.name.type') === 'personName') {
                if (!~attributeList.indexOf('name')) {
                    attributeList.unshift('name');
                }
            }

            this.addAdditionalPlaceholders(entityType, attributeList);

            attributeList = attributeList.sort((v1, v2) => {
                return this.translate(v1, 'fields', entityType).localeCompare(this.translate(v2, 'fields', entityType));
            });

            this.attributeList = attributeList;

            this.textVariables = {};

            this.attributeList.forEach((item) => {
                if (
                    ~['text', 'wysiwyg']
                        .indexOf(this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'type']))
                ) {
                    this.textVariables[item] = true;
                }
            });

            if (!~this.attributeList.indexOf('now')) {
                this.attributeList.unshift('now');
            }

            if (!~this.attributeList.indexOf('today')) {
                this.attributeList.unshift('today');
            }

            attributeList.unshift('pagebreak');

            this.attributeList.unshift('');

            var links = this.getMetadata().get('entityDefs.' + entityType + '.links') || {};

            var linkList = Object.keys(links).sort((v1, v2) => {
                return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
            });

            linkList.forEach((link) => {
                var type = links[link].type;

                if (type !== 'belongsTo') {
                    return;
                }

                var scope = links[link].entity;
                if (!scope) return;

                if (links[link].disabled || links[link].utility) {
                    return;
                }

                if (
                    this.getMetadata().get(['entityAcl', entityType, 'links', link, 'onlyAdmin'])
                    ||
                    this.getMetadata().get(['entityAcl', entityType, 'links', link, 'forbidden'])
                    ||
                    this.getMetadata().get(['entityAcl', entityType, 'links', link, 'internal'])
                ) {
                    return;
                }

                var fieldList = this.getFieldManager().getEntityTypeFieldList(scope);

                var ignoreFieldList = [];

                fieldList.forEach(field => {
                    let aclDefs = this.getMetadata().get(['entityAcl', entityType, 'fields', field]) || {};
                    let fieldDefs = this.getMetadata().get(['entityDefs', entityType, 'fields', field]) || {};

                    if (
                        aclDefs.onlyAdmin ||
                        aclDefs.forbidden ||
                        aclDefs.internal ||
                        fieldDefs.disabled ||
                        fieldDefs.utility ||
                        fieldDefs.directAccessDisabled ||
                        fieldDefs.templatePlaceholderDisabled
                    ) {
                        ignoreFieldList.push(field);
                    }
                });

                var attributeList = this.getFieldManager().getEntityTypeAttributeList(scope) || [];

                var forbiddenList = Espo.Utils.clone(this.getAcl().getScopeForbiddenAttributeList(scope));

                ignoreFieldList.forEach((field) => {
                    this.getFieldManager().getEntityTypeFieldAttributeList(scope, field).forEach((attribute) => {
                        forbiddenList.push(attribute);
                    });
                });

                attributeList = attributeList.filter((item) => {
                    if (~forbiddenList.indexOf(item)) {
                        return;
                    }

                    var fieldType = this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']);

                    if (fieldType === 'map') {
                        return;
                    }

                    return true;
                });

                attributeList.push('id');

                if (this.getMetadata().get('entityDefs.' + scope + '.fields.name.type') === 'personName') {
                    attributeList.unshift('name');
                }

                var originalAttributeList = Espo.Utils.clone(attributeList);

                this.addAdditionalPlaceholders(scope, attributeList, link, entityType);

                attributeList.sort((v1, v2) => {
                    return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                });

                attributeList.forEach((item) => {
                    if (~originalAttributeList.indexOf(item)) {
                        this.attributeList.push(link + '.' + item);
                    } else {
                        this.attributeList.push(item);
                    }
                });

                attributeList.forEach((item) => {
                    var variable = link + '.' + item;

                    if (
                        ~['text', 'wysiwyg']
                            .indexOf(this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']))
                    ) {
                        this.textVariables[variable] = true;
                    }
                });
            });

            return this.attributeList;
        },

        addAdditionalPlaceholders: function (entityType, attributeList, link, superEntityType) {
            function removeItem(attributeList, item) {
                for (var i = 0; i < attributeList.length; i++) {
                    if (attributeList[i] === item) {
                        attributeList.splice(i, 1);
                    }
                }
            }

            var fieldDefs = this.getMetadata().get(['entityDefs', entityType, 'fields']) || {};

            for (var field in fieldDefs) {
                var fieldType = fieldDefs[field].type;

                var item = field;
                if (link) item = link + '.' + item;

                var cAttributeList = Espo.Utils.clone(attributeList);

                if (fieldType === 'image') {
                    removeItem(attributeList, field + 'Name');
                    removeItem(attributeList, field + 'Id');

                    var value = 'imageTag '+item+'Id';
                    attributeList.push(value);

                    this.translatedOptions[value] = this.translate(field, 'fields', entityType);
                    if (link) {
                        this.translatedOptions[value] = this.translate(link, 'links', superEntityType) + '.' +
                            this.translatedOptions[value];
                    }
                } else if (fieldType === 'barcode') {
                    removeItem(attributeList, field);

                    var barcodeType = this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'codeType']);
                    var value = "barcodeImage "+item+" type='"+barcodeType+"'";

                    attributeList.push(value);

                    this.translatedOptions[value] = this.translate(field, 'fields', entityType);
                    if (link) {
                        this.translatedOptions[value] = this.translate(link, 'links', superEntityType) + '.' +
                            this.translatedOptions[value];
                    }
                }
            }
        },

        setupTranslatedOptions: function () {
            var entityType = this.model.get('entityType');

            this.attributeList.forEach((item) => {
                if (~['today', 'now', 'pagebreak'].indexOf(item)) {
                    if (!this.getMetadata().get(['entityDefs', entityType, 'fields', item])) {
                        this.translatedOptions[item] = this.getLanguage()
                            .translateOption(item, 'placeholders', 'Template');

                        return;
                    }
                }

                var field = item;
                var scope = entityType;
                var isForeign = false;

                if (~item.indexOf('.')) {
                    isForeign = true;
                    field = item.split('.')[1];
                    var link = item.split('.')[0];
                    scope = this.getMetadata().get('entityDefs.' + entityType + '.links.' + link + '.entity');
                }

                if (this.translatedOptions[item]) {
                    return;
                }

                this.translatedOptions[item] = this.translate(field, 'fields', scope);

                if (field.indexOf('Id') === field.length - 2) {
                    var baseField = field.substr(0, field.length - 2);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('id', 'fields') + ')';
                    }
                }
                else if (field.indexOf('Name') === field.length - 4) {
                    var baseField = field.substr(0, field.length - 4);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('name', 'fields') + ')';
                    }
                }
                else if (field.indexOf('Type') === field.length - 4) {
                    var baseField = field.substr(0, field.length - 4);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('type', 'fields') + ')';
                    }
                }

                if (field.indexOf('Ids') === field.length - 3) {
                    var baseField = field.substr(0, field.length - 3);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('ids', 'fields') + ')';
                    }
                }
                else if (field.indexOf('Names') === field.length - 5) {
                    var baseField = field.substr(0, field.length - 5);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('names', 'fields') + ')';
                    }
                }
                else if (field.indexOf('Types') === field.length - 5) {
                    var baseField = field.substr(0, field.length - 5);

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                            ' (' + this.translate('types', 'fields') + ')';
                    }
                }

                if (isForeign) {
                    this.translatedOptions[item] =  this.translate(link, 'links', entityType) + '.' +
                        this.translatedOptions[item];
                }
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === this.MODE_EDIT) {
                Select.init(this.$el.find('[data-name="variables"]'));
            }
        },

        fetch: function () {},

    });
});
