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
Espo.define('views/template/fields/variables', 'views/fields/base', function (Dep) {

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

            this.listenTo(this.model, 'change:entityType', function () {
                this.setupAttributeList();
                this.setupTranslatedOptions();
                this.reRender();
            }, this);
        },

        setupAttributeList: function () {
            var entityType = this.model.get('entityType');

            var fieldList = this.getFieldManager().getEntityTypeFieldList(entityType);

            var ignoreFieldList = [];
            fieldList.forEach(function (field) {
                if (
                    this.getMetadata().get(['entityAcl', entityType, 'fields', field, 'onlyAdmin'])
                    ||
                    this.getMetadata().get(['entityAcl', entityType, 'fields', field, 'forbidden'])
                    ||
                    this.getMetadata().get(['entityAcl', entityType, 'fields', field, 'internal'])
                    ||
                    this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'disabled'])
                    ||
                    this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'directAccessDisabled'])
                ) ignoreFieldList.push(field);
            }, this);

            var attributeList = this.getFieldManager().getEntityTypeAttributeList(entityType) || [];

            var forbiddenList = Espo.Utils.clone(this.getAcl().getScopeForbiddenAttributeList(entityType));

            ignoreFieldList.forEach(function (field) {
                this.getFieldManager().getEntityTypeFieldAttributeList(entityType, field).forEach(function (attribute) {
                    forbiddenList.push(attribute);
                });
            }, this);

            attributeList = attributeList.filter(function (item) {
                if (~forbiddenList.indexOf(item)) return;

                var fieldType = this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'type']);
                if (fieldType === 'map') return;

                return true;
            }, this);


            attributeList.push('id');
            if (this.getMetadata().get('entityDefs.' + entityType + '.fields.name.type') == 'personName') {
                if (!~attributeList.indexOf('name')) {
                    attributeList.unshift('name');
                }
            };
            attributeList = attributeList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', entityType).localeCompare(this.translate(v2, 'fields', entityType));
            }.bind(this));

            this.attributeList = attributeList;

            this.textVariables = {};

            this.attributeList.forEach(function (item) {
                if (~['text', 'wysiwyg'].indexOf(this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'type']))) {
                    this.textVariables[item] = true;
                }
            }, this);

            if (!~this.attributeList.indexOf('now')) {
                this.attributeList.unshift('now');
            }

            if (!~this.attributeList.indexOf('today')) {
                this.attributeList.unshift('today');
            }

            var links = this.getMetadata().get('entityDefs.' + entityType + '.links') || {};

            var linkList = Object.keys(links).sort(function (v1, v2) {
                return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
            }.bind(this));

            linkList.forEach(function (link) {
                var type = links[link].type
                if (type != 'belongsTo') return;
                var scope = links[link].entity;
                if (!scope) return;

                if (links[link].disabled) return;

                if (
                    this.getMetadata().get(['entityAcl', entityType, 'links', link, 'onlyAdmin'])
                    ||
                    this.getMetadata().get(['entityAcl', entityType, 'links', link, 'forbidden'])
                    ||
                    this.getMetadata().get(['entityAcl', entityType, 'links', link, 'internal'])
                ) return;

                var fieldList = this.getFieldManager().getEntityTypeFieldList(scope);

                var ignoreFieldList = [];
                fieldList.forEach(function (field) {
                    if (
                        this.getMetadata().get(['entityAcl', scope, 'fields', field, 'onlyAdmin'])
                        ||
                        this.getMetadata().get(['entityAcl', scope, 'fields', field, 'forbidden'])
                        ||
                        this.getMetadata().get(['entityAcl', scope, 'fields', field, 'internal'])
                        ||
                        this.getMetadata().get(['entityDefs', scope, 'fields', field, 'disabled'])
                        ||
                        this.getMetadata().get(['entityDefs', scope, 'fields', field, 'directAccessDisabled'])
                    ) ignoreFieldList.push(field);
                }, this);

                var attributeList = this.getFieldManager().getEntityTypeAttributeList(scope) || [];

                var forbiddenList = Espo.Utils.clone(this.getAcl().getScopeForbiddenAttributeList(scope));

                ignoreFieldList.forEach(function (field) {
                    this.getFieldManager().getEntityTypeFieldAttributeList(scope, field).forEach(function (attribute) {
                        forbiddenList.push(attribute);
                    });
                }, this);

                attributeList = attributeList.filter(function (item) {
                    if (~forbiddenList.indexOf(item)) return;

                    var fieldType = this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']);
                    if (fieldType === 'map') return;

                    return true;
                }, this);

                attributeList.push('id');
                if (this.getMetadata().get('entityDefs.' + scope + '.fields.name.type') == 'personName') {
                    attributeList.unshift('name');
                };

                attributeList.sort(function (v1, v2) {
                    return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                }.bind(this));

                attributeList.forEach(function (item) {
                    this.attributeList.push(link + '.' + item);
                }, this);

                attributeList.forEach(function (item) {
                    var variable = link + '.' + item;
                    if (~['text', 'wysiwyg'].indexOf(this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']))) {
                        this.textVariables[variable] = true;
                    }
                }, this);

            }, this);

            return this.attributeList;
        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};

            var entityType = this.model.get('entityType');
            this.attributeList.forEach(function (item) {
                if (~['today', 'now'].indexOf(item)) {
                    if (!this.getMetadata().get(['entityDefs', entityType, 'fields', item])) {
                        this.translatedOptions[item] = this.getLanguage().translateOption(item, 'placeholders', 'Template');
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

                this.translatedOptions[item] = this.translate(field, 'fields', scope);

                if (field.indexOf('Id') === field.length - 2) {
                    var baseField = field.substr(0, field.length - 2);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) + ' (' + this.translate('id', 'fields') + ')';
                    }
                } else if (field.indexOf('Name') === field.length - 4) {
                    var baseField = field.substr(0, field.length - 4);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) + ' (' + this.translate('name', 'fields') + ')';
                    }
                } else if (field.indexOf('Type') === field.length - 4) {
                    var baseField = field.substr(0, field.length - 4);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) + ' (' + this.translate('type', 'fields') + ')';
                    }
                }

                if (field.indexOf('Ids') === field.length - 3) {
                    var baseField = field.substr(0, field.length - 3);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) + ' (' + this.translate('ids', 'fields') + ')';
                    }
                } else if (field.indexOf('Names') === field.length - 5) {
                    var baseField = field.substr(0, field.length - 5);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) + ' (' + this.translate('names', 'fields') + ')';
                    }
                } else if (field.indexOf('Types') === field.length - 5) {
                    var baseField = field.substr(0, field.length - 5);
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                        this.translatedOptions[item] = this.translate(baseField, 'fields', scope) + ' (' + this.translate('types', 'fields') + ')';
                    }
                }



                if (isForeign) {
                    this.translatedOptions[item] =  this.translate(link, 'links', entityType) + '.' + this.translatedOptions[item];
                }
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        fetch: function () {},

    });

});
