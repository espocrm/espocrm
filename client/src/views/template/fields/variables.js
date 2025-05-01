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

    detailTemplate = 'template/fields/variables/detail'
    editTemplate = 'template/fields/variables/edit'

    // noinspection JSCheckFunctionSignatures
    data() {
        // noinspection JSValidateTypes
        return {
            attributeList: this.attributeList,
            entityType: this.model.get('entityType'),
            translatedOptions: this.translatedOptions,
        };
    }

    setup() {
        this.addHandler('change', '[data-name="variables"]', () => {
            const attribute = this.$el.find('[data-name="variables"]').val();

            const $copy = this.$el.find('[data-name="copy"]');

            if (attribute !== '') {
                if (this.textVariables[attribute]) {
                    $copy.val('{{{' + attribute + '}}}');
                } else {
                    $copy.val('{{' + attribute + '}}');
                }
            } else {
                $copy.val('');
            }
        });

        this.setupAttributeList();
        this.setupTranslatedOptions();

        this.listenTo(this.model, 'change:entityType', () => {
            this.setupAttributeList();
            this.setupTranslatedOptions();
            this.reRender();
        });
    }

    setupAttributeList() {
        this.translatedOptions = {};

        const entityType = this.model.get('entityType');
        const fieldList = this.getFieldManager().getEntityTypeFieldList(entityType);
        const ignoreFieldList = [];

        fieldList.forEach(field => {
            const aclDefs = /** @type {Record} */
                this.getMetadata().get(['entityAcl', entityType, 'fields', field]) || {};

            const fieldDefs = /** @type {Record} */
                this.getMetadata().get(['entityDefs', entityType, 'fields', field]) || {};

            if (
                aclDefs.onlyAdmin ||
                aclDefs.forbidden ||
                aclDefs.internal ||
                fieldDefs.disabled ||
                fieldDefs.utility ||
                fieldDefs.directAccessDisabled && !fieldDefs.loaderClassName ||
                fieldDefs.templatePlaceholderDisabled
            ) {
                ignoreFieldList.push(field);
            }
        });

        let attributeList = this.getFieldManager().getEntityTypeAttributeList(entityType) || [];
        const forbiddenList = Espo.Utils.clone(this.getAcl().getScopeForbiddenAttributeList(entityType));

        ignoreFieldList.forEach((field) => {
            this.getFieldManager().getEntityTypeFieldAttributeList(entityType, field).forEach(attribute => {
                forbiddenList.push(attribute);
            });
        });

        attributeList = attributeList.filter(item => {
            if (~forbiddenList.indexOf(item)) {
                return;
            }

            const fieldType = this.getMetadata().get(['entityDefs', entityType, 'fields', item, 'type']);

            if (fieldType === 'map') {
                return;
            }

            return true;
        });


        attributeList.push('id');

        if (this.getMetadata().get(`entityDefs.${entityType}.fields.name.type`) === 'personName') {
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

        // noinspection SpellCheckingInspection
        attributeList.unshift('pagebreak');

        this.attributeList.unshift('');

        const links = /** @type {Record<string, Record>} */this.getMetadata().get(`entityDefs.${entityType}.links`) || {};

        const linkList = Object.keys(links).sort((v1, v2) => {
            return this.translate(v1, 'links', entityType).localeCompare(this.translate(v2, 'links', entityType));
        });

        linkList.forEach(link => {
            const type = links[link].type;

            if (type !== 'belongsTo') {
                return;
            }

            const scope = links[link].entity;

            if (!scope) {
                return;
            }

            if (links[link].disabled || links[link].utility) {
                return;
            }

            if (
                this.getMetadata().get(['entityAcl', entityType, 'links', link, 'onlyAdmin']) ||
                this.getMetadata().get(['entityAcl', entityType, 'links', link, 'forbidden']) ||
                this.getMetadata().get(['entityAcl', entityType, 'links', link, 'internal'])
            ) {
                return;
            }

            const fieldList = this.getFieldManager().getEntityTypeFieldList(scope);

            const ignoreFieldList = [];

            fieldList.forEach(field => {
                const aclDefs = /** @type {Record} */
                    this.getMetadata().get(['entityAcl', scope, 'fields', field]) || {};

                const fieldDefs = /** @type {Record} */
                    this.getMetadata().get(['entityDefs', scope, 'fields', field]) || {};

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

            let attributeList = this.getFieldManager().getEntityTypeAttributeList(scope) || [];
            const forbiddenList = Espo.Utils.clone(this.getAcl().getScopeForbiddenAttributeList(scope));

            ignoreFieldList.forEach((field) => {
                this.getFieldManager().getEntityTypeFieldAttributeList(scope, field).forEach((attribute) => {
                    forbiddenList.push(attribute);
                });
            });

            attributeList = attributeList.filter((item) => {
                if (~forbiddenList.indexOf(item)) {
                    return;
                }

                const fieldType = this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']);

                if (fieldType === 'map') {
                    return;
                }

                return true;
            });

            attributeList.push('id');

            if (this.getMetadata().get(`entityDefs.${scope}.fields.name.type`) === 'personName') {
                attributeList.unshift('name');
            }

            const originalAttributeList = Espo.Utils.clone(attributeList);

            this.addAdditionalPlaceholders(scope, attributeList, link, entityType);

            attributeList.sort((v1, v2) => {
                return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
            });

            attributeList.forEach((item) => {
                if (~originalAttributeList.indexOf(item)) {
                    this.attributeList.push(`${link}.${item}`);
                } else {
                    this.attributeList.push(item);
                }
            });

            attributeList.forEach((item) => {
                const variable = `${link}.${item}`;

                if (
                    ~['text', 'wysiwyg']
                        .indexOf(this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']))
                ) {
                    this.textVariables[variable] = true;
                }
            });
        });

        return this.attributeList;
    }

    addAdditionalPlaceholders(entityType, attributeList, link, superEntityType) {
        let value;

        function removeItem(attributeList, item) {
            for (let i = 0; i < attributeList.length; i++) {
                if (attributeList[i] === item) {
                    attributeList.splice(i, 1);
                }
            }
        }

        const fieldDefs = this.getMetadata().get(['entityDefs', entityType, 'fields']) || {};

        for (const field in fieldDefs) {
            const fieldType = fieldDefs[field].type;

            let item = field;

            if (link) {
                item = `${link}.${item}`;
            }

            if (fieldType === 'image') {
                removeItem(attributeList, field + 'Name');
                removeItem(attributeList, field + 'Id');

                value = 'imageTag ' + item + 'Id';
                attributeList.push(value);

                this.translatedOptions[value] = this.translate(field, 'fields', entityType);
                if (link) {
                    this.translatedOptions[value] = this.translate(link, 'links', superEntityType) + ' . ' +
                        this.translatedOptions[value];
                }
            } else if (fieldType === 'barcode') {
                removeItem(attributeList, field);

                const barcodeType = this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'codeType']);
                value = `barcodeImage ${item} type='${barcodeType}'`;

                attributeList.push(value);

                this.translatedOptions[value] = this.translate(field, 'fields', entityType);
                if (link) {
                    this.translatedOptions[value] = this.translate(link, 'links', superEntityType) + ' . ' +
                        this.translatedOptions[value];
                }
            }
        }
    }

    setupTranslatedOptions() {
        const entityType = this.model.get('entityType');

        this.attributeList.forEach((item) => {
            const link = item.split('.')[0];

            // noinspection SpellCheckingInspection
            if (~['today', 'now', 'pagebreak'].indexOf(item)) {
                if (!this.getMetadata().get(['entityDefs', entityType, 'fields', item])) {
                    this.translatedOptions[item] = this.getLanguage()
                        .translateOption(item, 'placeholders', 'Template');

                    return;
                }
            }

            let field = item;
            let scope = entityType;
            let isForeign = false;

            if (~item.indexOf('.')) {
                isForeign = true;
                field = item.split('.')[1];
                scope = this.getMetadata().get(`entityDefs.${entityType}.links.${link}.entity`);
            }

            if (this.translatedOptions[item]) {
                return;
            }

            this.translatedOptions[item] = this.translate(field, 'fields', scope);

            if (field.indexOf('Id') === field.length - 2) {
                const baseField = field.substr(0, field.length - 2);

                if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                    this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                        ' (' + this.translate('id', 'fields') + ')';
                }
            }
            else if (field.indexOf('Name') === field.length - 4) {
                const baseField = field.substr(0, field.length - 4);

                if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                    this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                        ' (' + this.translate('name', 'fields') + ')';
                }
            }
            else if (field.indexOf('Type') === field.length - 4) {
                const baseField = field.substr(0, field.length - 4);

                if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                    this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                        ' (' + this.translate('type', 'fields') + ')';
                }
            }

            if (field.indexOf('Ids') === field.length - 3) {
                const baseField = field.substr(0, field.length - 3);

                if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                    this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                        ' (' + this.translate('ids', 'fields') + ')';
                }
            }
            else if (field.indexOf('Names') === field.length - 5) {
                const baseField = field.substr(0, field.length - 5);

                if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                    this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                        ' (' + this.translate('names', 'fields') + ')';
                }
            }
            else if (field.indexOf('Types') === field.length - 5) {
                const baseField = field.substr(0, field.length - 5);

                if (this.getMetadata().get(['entityDefs', scope, 'fields', baseField])) {
                    this.translatedOptions[item] = this.translate(baseField, 'fields', scope) +
                        ' (' + this.translate('types', 'fields') + ')';
                }
            }

            if (isForeign) {
                this.translatedOptions[item] =  this.translate(link, 'links', entityType) + ' . ' +
                    this.translatedOptions[item];
            }
        });
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_EDIT) {
            Select.init(this.$el.find('[data-name="variables"]'));
        }
    }

    fetch() {}
}
