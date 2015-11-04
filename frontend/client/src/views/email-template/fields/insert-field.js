/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
Espo.define('views/email-template/fields/insert-field', 'views/fields/base', function (Dep) {

    return Dep.extend({

        inlineEditDisabled: true,

        detailTemplate: 'email-template/fields/insert-field/detail',

        editTemplate: 'email-template/fields/insert-field/edit',

        data: function () {
            return {
            };
        },

        events: {
            'click [data-action="insert"]': function () {
                var entityType = this.$entityType.val();
                var field = this.$field.val();
                this.insert(entityType, field);
            },
        },

        setup: function () {
            if (this.mode != 'list') {
                var entityList = [];
                var defs = this.getMetadata().get('scopes');
                entityList = Object.keys(defs).filter(function (scope) {
                    return (defs[scope].entity && (defs[scope].tab || defs[scope].object));
                });

                var entityFields = {};
                entityList.forEach(function (scope) {
                    var list = this.getFieldManager().getEntityAttributes(scope) || [];
                    list.push('id');
                    if (this.getMetadata().get('entityDefs.' + scope + '.fields.name.type') == 'personName') {
                        list.unshift('name');
                    };
                    entityFields[scope] = list.sort(function (v1, v2) {
                        return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                    }.bind(this));
                }, this);

                entityFields['Person'] = ['name', 'firstName', 'lastName', 'salutationName', 'emailAddress', 'assignedUserName'];

                this.entityList = entityList;
                this.entityFields = entityFields;
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'edit') {
                var entityTranslation = {};
                this.entityList.forEach(function (scope) {
                    entityTranslation[scope] = this.translate(scope, 'scopeNames');
                }, this);

                this.entityList.sort(function (a, b) {
                    return a.localeCompare(b);
                }, this);

                var $entityType = this.$entityType = this.$el.find('[name="entityType"]');
                var $field = this.$field = this.$el.find('[name="field"]');

                $entityType.on('change', function () {
                    this.changeEntityType();
                }.bind(this));

                $entityType.append('<option value="Person">' + this.translate('Person') + '</option>');

                this.entityList.forEach(function (scope) {
                    $entityType.append('<option value="' + scope + '">' + entityTranslation[scope] + '</option>');
                }, this);

                this.changeEntityType();
            }
        },

        changeEntityType: function () {
            var entityType = this.$entityType.val();
            var fieldList = this.entityFields[entityType];

            this.$field.empty();

            fieldList.forEach(function (field) {
                this.$field.append('<option value="' + field + '">' + this.translate(field, 'fields', entityType) + '</option>');
            }, this);
        },

        insert: function (entityType, field) {
            this.trigger('insert-field', {
                entityType: entityType,
                field: field
            });
        }

    });

});
