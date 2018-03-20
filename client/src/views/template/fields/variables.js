/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
            'change [name="variables"]': function () {
                var attribute = this.$el.find('[name="variables"]').val();
                if (attribute != '') {
                    this.$el.find('[name="copy"]').val('{{' + attribute + '}}');
                } else {
                    this.$el.find('[name="copy"]').val('');
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

            var attributeList = this.getFieldManager().getEntityAttributes(entityType) || [];

            var forbiddenList = this.getAcl().getScopeForbiddenAttributeList(entityType);
            attributeList = attributeList.filter(function (item) {
                if (~forbiddenList.indexOf(item)) return;

                var fieldType = this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'type']);
                if (fieldType === 'map') return;

                if (this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'disabled'])) return;

                return true;
            }, this);

            attributeList.push('id');
            if (this.getMetadata().get('entityDefs.' + entityType + '.fields.name.type') == 'personName') {
                attributeList.unshift('name');
            };
            attributeList = attributeList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', entityType).localeCompare(this.translate(v2, 'fields', entityType));
            }.bind(this));

            this.attributeList = attributeList;

            if (!~this.attributeList.indexOf('now')) {
                this.attributeList.unshift('now');
            }

            if (!~this.attributeList.indexOf('today')) {
                this.attributeList.unshift('today');
            }

            attributeList.unshift('');

            var links = this.getMetadata().get('entityDefs.' + entityType + '.links') || {};

            var linkList = Object.keys(links).sort(function (v1, v2) {
                return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
            }.bind(this));

            linkList.forEach(function (link) {
                var type = links[link].type
                if (type != 'belongsTo') return;
                var scope = links[link].entity;
                if (!scope) return;

                var attributeList = this.getFieldManager().getEntityAttributes(scope) || [];

                var forbiddenList = this.getAcl().getScopeForbiddenAttributeList(scope);
                attributeList = attributeList.filter(function (item) {
                    if (~forbiddenList.indexOf(item)) return;

                    var fieldType = this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']);
                    if (fieldType === 'map') return;

                    if (this.getMetadata().get(['entityDefs', scope, 'fields', item, 'disabled'])) return;

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
                }



                if (isForeign) {
                    this.translatedOptions[item] =  this.translate(link, 'links', entityType) + '.' + this.translatedOptions[item];
                }
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        }

    });

});
