/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import BaseFieldView from 'views/fields/base';
import Select from 'ui/select';

export default class extends BaseFieldView {

    inlineEditDisabled = true

    detailTemplate = 'email-template/fields/insert-field/detail'
    editTemplate = 'email-template/fields/insert-field/edit'

    data() {
        return {};
    }

    setup() {
        this.addActionHandler('insert', () => {
            const entityType = this.$entityType.val();
            const field = this.$field.val();

            if (!field) {
                return;
            }

            this.insert(entityType, field);
        });

        if (this.mode !== this.MODE_LIST) {
            const defs = this.getMetadata().get('scopes');

            const entityList = Object.keys(defs).filter(scope => {
                if (scope === 'Email') {
                    return;
                }

                if (!this.getAcl().checkScope(scope)) {
                    return;
                }

                return (defs[scope].entity && (defs[scope].object));
            });

            this.translatedOptions = {};

            const entityPlaceholders = {};

            entityList.forEach(scope => {
                this.translatedOptions[scope] = {};

                entityPlaceholders[scope] = this.getScopeAttributeList(scope);

                entityPlaceholders[scope].forEach(item => {
                    this.translatedOptions[scope][item] = this.translatePlaceholder(scope, item);
                });

                /** @type {Record<string, Record>} */
                const links = this.getMetadata().get(`entityDefs.${scope}.links`) || {};

                const linkList = Object.keys(links).sort((v1, v2) => {
                    return this.translate(v1, 'links', scope).localeCompare(this.translate(v2, 'links', scope));
                });

                linkList.forEach(link => {
                    const type = links[link].type;

                    if (type !== 'belongsTo') {
                        return;
                    }

                    const foreignScope = links[link].entity;

                    if (!foreignScope) {
                        return;
                    }

                    if (links[link].disabled || links[link].utility) {
                        return;
                    }

                    if (
                        this.getMetadata().get(['entityAcl', scope, 'links', link, 'onlyAdmin']) ||
                        this.getMetadata().get(['entityAcl', scope, 'links', link, 'forbidden']) ||
                        this.getMetadata().get(['entityAcl', scope, 'links', link, 'internal'])
                    ) {
                        return;
                    }

                    const attributeList = this.getScopeAttributeList(foreignScope, true);

                    attributeList.forEach(item => {
                        entityPlaceholders[scope].push(`${link}.${item}`);

                        this.translatedOptions[scope][`${link}.${item}`] =
                            this.translatePlaceholder(scope, `${link}.${item}`);
                    });
                });
            });

            entityPlaceholders['Person'] = [
                'name',
                'firstName',
                'lastName',
                'salutationName',
                'emailAddress',
                'assignedUserName',
            ];

            this.translatedOptions['Person'] = {};

            this.entityList = entityList;
            this.entityFields = entityPlaceholders;
        }
    }

    /**
     * @private
     * @param {string} scope
     * @param {boolean} [isForeign]
     * @return {string[]}
     */
    getScopeAttributeList(scope, isForeign = false) {
        let fieldList = this.getFieldManager().getEntityTypeFieldList(scope);

        let list = [];

        fieldList = fieldList.sort((v1, v2) => {
            return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
        });

        fieldList.forEach(field => {
            const fieldType = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

            /** @type {Record} */
            const aclDefs = this.getMetadata().get(['entityAcl', scope, 'fields', field]) || {};
            /** @type {Record} */
            const fieldDefs = this.getMetadata().get(['entityDefs', scope, 'fields', field]) || {};

            if (
                aclDefs.onlyAdmin ||
                aclDefs.forbidden ||
                aclDefs.internal ||
                fieldDefs.disabled ||
                fieldDefs.utility ||
                fieldDefs.directAccessDisabled && (isForeign || !fieldDefs.loaderClassName) ||
                fieldDefs.templatePlaceholderDisabled
            ) {
                return false;
            }

            if (fieldType === 'map') {
                return;
            }

            if (fieldType === 'linkMultiple') {
                return;
            }

            if (fieldType === 'attachmentMultiple') {
                return;
            }

            if (
                this.getMetadata().get(['entityAcl', scope, 'fields', field, 'onlyAdmin']) ||
                this.getMetadata().get(['entityAcl', scope, 'fields', field, 'forbidden']) ||
                this.getMetadata().get(['entityAcl', scope, 'fields', field, 'internal'])
            ) {
                return;
            }

            const fieldAttributeList = this.getFieldManager().getAttributeList(fieldType, field);

            fieldAttributeList.forEach(attribute => {
                if (list.includes(attribute)) {
                    return;
                }

                list.push(attribute);
            });
        });

        const forbiddenList = this.getAcl().getScopeForbiddenAttributeList(scope);

        list = list.filter((item) => {
            if (~forbiddenList.indexOf(item)) {
                return;
            }

            return true;
        });

        list.push('id');

        if (this.getMetadata().get('entityDefs.' + scope + '.fields.name.type') === 'personName') {
            list.unshift('name');
        }

        return list;
    }

    /**
     * @private
     * @param {string} entityType
     * @param {string} item
     * @return {string}
     */
    translatePlaceholder(entityType, item) {
        let field = item;
        let scope = entityType;
        let isForeign = false;
        let link;

        if (item.includes('.')) {
            isForeign = true;
            field = item.split('.')[1];
            link = item.split('.')[0];

            scope = this.getMetadata().get(`entityDefs.${entityType}.links.${link}.entity`);
        }

        let label = this.translate(field, 'fields', scope);

        if (field.indexOf('Id') === field.length - 2) {
            const baseField = field.substr(0, field.length - 2);

            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('id', 'fields') + ')';
            }
        }
        else if (field.indexOf('Name') === field.length - 4) {
            const baseField = field.substr(0, field.length - 4);

            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('name', 'fields') + ')';
            }
        }
        else if (field.indexOf('Type') === field.length - 4) {
            const baseField = field.substr(0, field.length - 4);

            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('type', 'fields') + ')';
            }
        }

        if (field.indexOf('Ids') === field.length - 3) {
            const baseField = field.substr(0, field.length - 3);

            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('ids', 'fields') + ')';
            }
        }
        else if (field.indexOf('Names') === field.length - 5) {
            const baseField = field.substr(0, field.length - 5);

            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('names', 'fields') + ')';
            }
        }
        else if (field.indexOf('Types') === field.length - 5) {
            const baseField = field.substr(0, field.length - 5);

            if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                label = this.translate(baseField, 'fields', scope) + ' (' + this.translate('types', 'fields') + ')';
            }
        }

        if (isForeign) {
            label = this.translate(link, 'links', entityType) + ' . ' + label;
        }

        return label;
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_EDIT) {
            const entityTranslation = {};

            this.entityList.forEach((scope) => {
                entityTranslation[scope] = this.translate(scope, 'scopeNames');
            });

            this.entityList.sort((a, b) => {
                return a.localeCompare(b);
            });

            const $entityType = this.$entityType = this.$el.find('[data-name="entityType"]');

            this.$field = this.$el.find('[data-name="field"]');

            $entityType.on('change', () => {
                this.changeEntityType();
            });

            $entityType.append(
                $('<option>')
                    .val('Person')
                    .text(this.translate('Person'))
            );

            this.entityList.forEach(scope => {
                $entityType.append(
                    $('<option>')
                        .val(scope)
                        .text(entityTranslation[scope])
                );
            });

            Select.init(this.$field);

            this.changeEntityType();

            Select.init(this.$entityType);
        }
    }

    /**
     * @private
     */
    changeEntityType() {
        const entityType = this.$entityType.val();
        const fieldList = this.entityFields[entityType];

        Select.setValue(this.$field, '');

        Select.setOptions(this.$field, fieldList.map(field => {
            return {
                value: field,
                label: this.translateItem(entityType, field),
            };
        }));
    }

    /**
     * @private
     * @param {string} entityType
     * @param {string} item
     * @return {string}
     */
    translateItem(entityType, item) {
        if (this.translatedOptions[entityType][item]) {
            return this.translatedOptions[entityType][item];
        }

        return this.translate(item, 'fields');
    }

    /**
     * @private
     * @param {string} entityType
     * @param {string} field
     */
    insert(entityType, field) {
        this.model.trigger('insert-field', {
            entityType: entityType,
            field: field,
        });
    }
}
