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

import ModalView from 'views/modal';
import Model from 'model';
import Index from 'views/admin/link-manager/index';
import EnumFieldView from 'views/fields/enum';

class LinkManagerEditModalView extends ModalView {

    template = 'admin/link-manager/modals/edit'
    cssName = 'edit'
    className = 'dialog dialog-record'
    /** @type {import('model').default & {fetchedAttributes?: Record}} */
    model

    shortcutKeys = {
        /** @this LinkManagerEditModalView */
        'Control+KeyS': function (e) {
            this.save({noClose: true});

            e.preventDefault();
            e.stopPropagation();
        },
        /** @this LinkManagerEditModalView */
        'Control+Enter': function (e) {
            if (document.activeElement instanceof HTMLInputElement) {
                document.activeElement.dispatchEvent(new Event('change', {bubbles: true}));
            }

            this.save();

            e.preventDefault();
            e.stopPropagation();
        },
    }

    setup() {
        this.buttonList = [
            {
                name: 'save',
                label: 'Save',
                style: 'danger',
                onClick: () => {
                    this.save();
                },
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => {
                    this.close();
                },
            }
        ];

        const scope = this.scope = this.options.scope;
        const link = this.link = this.options.link || false;

        const entity = scope;

        const isNew = this.isNew = (false === link);

        this.headerText = this.translate('Create Link', 'labels', 'Admin');

        if (!isNew) {
            this.headerText = this.translate('Edit Link', 'labels', 'Admin') + ' · ' +
                this.translate(scope, 'scopeNames') + ' · ' +
                this.translate(link, 'links', scope);
        }

        const model = this.model = new Model();
        model.name = 'EntityManager';

        this.model.set('entity', scope);

        const allEntityList = this.getMetadata().getScopeEntityList()
            .filter(item => {
                const defs = /** @type {Record} */this.getMetadata().get(['scopes', item]) || {};

                if (!defs.customizable) {
                    return false;
                }

                const emDefs = /** @type {Record} */defs.entityManager || {};

                if (emDefs.relationships === false) {
                    return false;
                }

                return true;
            })
            .sort((v1, v2) => {
                const t1 = this.translate(v1, 'scopeNames');
                const t2 = this.translate(v2, 'scopeNames');

                return t1.localeCompare(t2);
            });

        let isCustom = true;

        let linkType;

        if (!isNew) {
            const entityForeign = this.getMetadata().get(`entityDefs.${scope}.links.${link}.entity`);
            const linkForeign = this.getMetadata().get(`entityDefs.${scope}.links.${link}.foreign`);
            const label = this.getLanguage().translate(link, 'links', scope);
            let labelForeign = this.getLanguage().translate(linkForeign, 'links', entityForeign);

            const type = this.getMetadata().get(`entityDefs.${entity}.links.${link}.type`);
            const foreignType = this.getMetadata().get(`entityDefs.${entityForeign}.links.${linkForeign}.type`);

            if (type === 'belongsToParent') {
                linkType = 'childrenToParent';

                labelForeign = null;

                let entityTypeList = this.getMetadata()
                    .get(['entityDefs', entity, 'fields', link, 'entityList']) || [];

                if (this.getMetadata().get(['entityDefs', entity, 'fields', link, 'entityList']) === null) {
                    entityTypeList = allEntityList;

                    this.noParentEntityTypeList = true;
                }

                this.model.set('parentEntityTypeList', entityTypeList);

                const foreignLinkEntityTypeList = this.getForeignLinkEntityTypeList(entity, link, entityTypeList);

                this.model.set('foreignLinkEntityTypeList', foreignLinkEntityTypeList);
            } else {
                linkType = Index.prototype.computeRelationshipType.call(this, type, foreignType);
            }

            this.model.set('linkType', linkType);
            this.model.set('entityForeign', entityForeign);
            this.model.set('link', link);
            this.model.set('linkForeign', linkForeign);
            this.model.set('label', label);
            this.model.set('labelForeign', labelForeign);

            const linkMultipleField =
                this.getMetadata().get(['entityDefs', scope, 'fields', link, 'type']) === 'linkMultiple' &&
                !this.getMetadata().get(['entityDefs', scope, 'fields', link, 'noLoad']);

            const linkMultipleFieldForeign =
                this.getMetadata()
                    .get(['entityDefs', entityForeign, 'fields', linkForeign, 'type']) === 'linkMultiple' &&
                !this.getMetadata().get(['entityDefs', entityForeign, 'fields', linkForeign, 'noLoad']);

            this.model.set('linkMultipleField', linkMultipleField);
            this.model.set('linkMultipleFieldForeign', linkMultipleFieldForeign);

            if (linkType === 'manyToMany') {
                const relationName = this.getMetadata()
                    .get(`entityDefs.${entity}.links.${link}.relationName`);

                this.model.set('relationName', relationName);
            }

            const audited = this.getMetadata().get(['entityDefs', scope, 'links', link, 'audited']) || false;
            const auditedForeign = this.getMetadata()
                .get(['entityDefs', entityForeign, 'links', linkForeign, 'audited']) || false;

            this.model.set('audited', audited);
            this.model.set('auditedForeign', auditedForeign);

            const layout = this.getMetadata().get(`clientDefs.${scope}.relationshipPanels.${link}.layout`);
            const layoutForeign =
                this.getMetadata().get(`clientDefs.${entityForeign}.relationshipPanels.${linkForeign}.layout`);

            this.model.set('layout', layout);
            this.model.set('layoutForeign', layoutForeign);

            const selectFilter = this.getRelationshipPanelParam(scope, link, 'selectPrimaryFilterName');
            const selectFilterForeign =
                this.getRelationshipPanelParam(entityForeign, linkForeign, 'selectPrimaryFilterName');

            this.model.set('selectFilter', selectFilter);
            this.model.set('selectFilterForeign', selectFilterForeign);

            isCustom = this.getMetadata().get(`entityDefs.${entity}.links.${link}.isCustom`);
        }

        const scopes = this.getMetadata().get('scopes') || null;

        const entityList = (Object.keys(scopes) || [])
            .filter(item => {
                const defs = /** @type {Record} */scopes[item] || {};

                if (!defs.entity || !defs.customizable) {
                    return false;
                }

                const emDefs = /** @type {Record} */defs.entityManager || {};

                if (emDefs.relationships === false) {
                    return false;
                }

                return true;
            })
            .sort((v1, v2) => {
                const t1 = this.translate(v1, 'scopeNames');
                const t2 = this.translate(v2, 'scopeNames');

                return t1.localeCompare(t2);
            });

        entityList.unshift('');

        this.createView('entity', 'views/fields/varchar', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="entity"]',
            defs: {
                name: 'entity'
            },
            readOnly: true,
        });

        this.createView('entityForeign', 'views/fields/enum', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="entityForeign"]',
            defs: {
                name: 'entityForeign',
                params: {
                    required: true,
                    options: entityList,
                    translation: 'Global.scopeNames',
                }
            },
            readOnly: !isNew,
        });

        this.createView('linkType', 'views/fields/enum', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="linkType"]',
            defs: {
                name: 'linkType',
                params: {
                    required: true,
                    options: [
                        '',
                        'oneToMany',
                        'manyToOne',
                        'manyToMany',
                        'oneToOneRight',
                        'oneToOneLeft',
                        'childrenToParent',
                    ]
                }
            },
            readOnly: !isNew,
        });

        this.createView('link', 'views/fields/varchar', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="link"]',
            defs: {
                name: 'link',
                params: {
                    required: true,
                    trim: true,
                    maxLength: 61,
                    noSpellCheck: true,
                },
            },
            readOnly: !isNew,
        });

        this.createView('linkForeign', 'views/fields/varchar', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="linkForeign"]',
            defs: {
                name: 'linkForeign',
                params: {
                    required: true,
                    trim: true,
                    maxLength: 61,
                    noSpellCheck: true,
                },
            },
            readOnly: !isNew,
        });

        this.createView('label', 'views/fields/varchar', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="label"]',
            defs: {
                name: 'label',
                params: {
                    required: true,
                    trim: true,
                },
            },
        });

        this.createView('labelForeign', 'views/fields/varchar', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="labelForeign"]',
            defs: {
                name: 'labelForeign',
                params: {
                    required: true,
                    trim: true,
                },
            },
        });

        if (isNew || this.model.get('relationName')) {
            this.createView('relationName', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                selector: '.field[data-name="relationName"]',
                defs: {
                    name: 'relationName',
                    params: {
                        required: true,
                        trim: true,
                        maxLength: 61,
                        noSpellCheck: true,
                    },
                },
                readOnly: !isNew,
            });
        }

        this.createView('linkMultipleField', 'views/fields/bool', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="linkMultipleField"]',
            defs: {
                name: 'linkMultipleField'
            },
            readOnly: !isCustom,
            tooltip: true,
            tooltipText: this.translate('linkMultipleField', 'tooltips', 'EntityManager'),
        });

        this.createView('linkMultipleFieldForeign', 'views/fields/bool', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="linkMultipleFieldForeign"]',
            defs: {
                name: 'linkMultipleFieldForeign'
            },
            readOnly: !isCustom,
            tooltip: true,
            tooltipText: this.translate('linkMultipleField', 'tooltips', 'EntityManager'),
        });

        this.createView('audited', 'views/fields/bool', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="audited"]',
            defs: {
                name: 'audited'
            },
            tooltip: true,
            tooltipText: this.translate('linkAudited', 'tooltips', 'EntityManager'),
        });

        this.createView('auditedForeign', 'views/fields/bool', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="auditedForeign"]',
            defs: {
                name: 'auditedForeign'
            },
            tooltip: true,
            tooltipText: this.translate('linkAudited', 'tooltips', 'EntityManager'),
        });

        const layouts = ['', ...this.getEntityTypeLayouts(this.scope)];
        const layoutTranslatedOptions = this.getEntityTypeLayoutsTranslations(this.scope);

        this.layoutFieldView = new EnumFieldView({
            name: 'layout',
            model: model,
            mode: 'edit',
            defs: {
                name: 'layout',
            },
            params: {
                options: [''],
            },
            translatedOptions: {'': this.translate('Default')},
        });

        this.layoutForeignFieldView = new EnumFieldView({
            name: 'layoutForeign',
            model: model,
            mode: 'edit',
            defs: {
                name: 'layoutForeign',
            },
            params: {
                options: layouts,
            },
            translatedOptions: layoutTranslatedOptions,
        });

        this.assignView('layout', this.layoutFieldView, '.field[data-name="layout"]');
        this.assignView('layoutForeign', this.layoutForeignFieldView, '.field[data-name="layoutForeign"]');

        this.selectFilterFieldView = new EnumFieldView({
            name: 'selectFilter',
            model: model,
            mode: 'edit',
            defs: {
                name: 'selectFilter',
            },
            params: {
                options: [''],
            },
            translatedOptions: {'': this.translate('all', 'presetFilters')},
            tooltip: true,
            tooltipText: this.translate('linkSelectFilter', 'tooltips', 'EntityManager'),
        });

        this.selectFilterForeignFieldView = new EnumFieldView({
            name: 'selectFilterForeign',
            model: model,
            mode: 'edit',
            defs: {
                name: 'selectFilterForeign',
            },
            params: {
                options: ['', ...this.getEntityTypeFilters(this.scope)],
            },
            translatedOptions: this.getEntityTypeFiltersTranslations(this.scope),
            tooltip: true,
            tooltipText: this.translate('linkSelectFilter', 'tooltips', 'EntityManager'),
        });

        this.assignView('selectFilter', this.selectFilterFieldView, '.field[data-name="selectFilter"]');
        this.assignView('selectFilterForeign', this.selectFilterForeignFieldView,
            '.field[data-name="selectFilterForeign"]');

        this.createView('parentEntityTypeList', 'views/fields/entity-type-list', {
            model: model,
            mode: 'edit',
            selector: '.field[data-name="parentEntityTypeList"]',
            defs: {
                name: 'parentEntityTypeList',
            },
        });

        this.createView('foreignLinkEntityTypeList',
                'views/admin/link-manager/fields/foreign-link-entity-type-list',
            {
                model: model,
                mode: 'edit',
                selector: '.field[data-name="foreignLinkEntityTypeList"]',
                defs: {
                    name: 'foreignLinkEntityTypeList',
                    params: {
                        options: this.model.get('parentEntityTypeList') || [],
                    },
                },
            });

        this.model.fetchedAttributes = this.model.getClonedAttributes();

        this.listenTo(this.model, 'change', () => {
            if (
                !this.model.hasChanged('parentEntityTypeList') &&
                !this.model.hasChanged('linkForeign') &&
                !this.model.hasChanged('link')
            ) {
                return;
            }

            const view = /** @type {import('views/fields/enum').default} */
                this.getView('foreignLinkEntityTypeList');

            if (view && !this.noParentEntityTypeList) {
                view.setOptionList(this.model.get('parentEntityTypeList') || []);
            }

            const checkedList = Espo.Utils.clone(this.model.get('foreignLinkEntityTypeList') || []);

            this.getForeignLinkEntityTypeList(
                this.model.get('entity'),
                this.model.get('link'), this.model.get('parentEntityTypeList') || [], true
            )
                .forEach(item => {
                    if (!~checkedList.indexOf(item)) {
                        checkedList.push(item);
                    }
                });

            this.model.set('foreignLinkEntityTypeList', checkedList);
        });

        this.controlLayoutField();
        this.listenTo(this.model, 'change:entityForeign', () => this.controlLayoutField());

        this.controlFilterField();
        this.listenTo(this.model, 'change:entityForeign', () => this.controlFilterField());
    }

    getEntityTypeLayouts(entityType) {
        const defs = this.getMetadata().get(['clientDefs', entityType, 'additionalLayouts'], {});

        return Object.keys(defs)
            .filter(item => ['list', 'listSmall'].includes(defs[item].type));
    }

    getEntityTypeLayoutsTranslations(entityType) {
        const map = {};

        this.getEntityTypeLayouts(entityType).forEach(item => {
            map[item] = this.getLanguage().has(item, 'layouts', entityType) ?
                this.getLanguage().translate(item, 'layouts', entityType) :
                this.getLanguage().translate(item, 'layouts', 'Admin');
        });

        map[''] = this.translate('Default');

        return map;
    }

    getEntityTypeFiltersTranslations(entityType) {
        const map = {};

        this.getEntityTypeFilters(entityType).forEach(item => {
            map[item] = this.getLanguage().translate(item, 'presetFilters', entityType);
        });

        map[''] = this.translate('all', 'presetFilters');

        return map;
    }

    /**
     * @param {string} entityType
     * @return {string[]}
     */
    getEntityTypeFilters(entityType) {
        return this.getMetadata().get(`clientDefs.${entityType}.filterList`, []).map(item => {
            if (typeof item === 'string') {
                return item;
            }

            return item.name;
        });
    }

    controlLayoutField() {
        const foreignEntityType = this.model.get('entityForeign');

        const layouts = foreignEntityType ?
            ['', ...this.getEntityTypeLayouts(foreignEntityType)] :
            [''];

        this.layoutFieldView.translatedOptions = foreignEntityType ?
            this.getEntityTypeLayoutsTranslations(foreignEntityType) :
            {};

        this.layoutFieldView.setOptionList(layouts)
            .then(() => this.layoutFieldView.reRender());
    }

    controlFilterField() {
        const foreignEntityType = this.model.get('entityForeign');

        const layouts = foreignEntityType ?
            ['', ...this.getEntityTypeFilters(foreignEntityType)] :
            [''];

        this.selectFilterFieldView.translatedOptions = foreignEntityType ?
            this.getEntityTypeFiltersTranslations(foreignEntityType) :
            {};

        this.selectFilterFieldView.setOptionList(layouts)
            .then(() => this.selectFilterFieldView.reRender());
    }

    toPlural(string) {
        if (string.slice(-1) === 'y') {
            return string.substr(0, string.length - 1) + 'ies';
        }

        if (string.slice(-1) === 's') {
            return string.substr(0, string.length) + 'es';
        }

        return string + 's';
    }

    populateFields() {
        const entityForeign = this.model.get('entityForeign');
        const linkType = this.model.get('linkType');

        let link;
        let linkForeign;

        if (linkType === 'childrenToParent') {
            this.model.set('link', 'parent');
            this.model.set('label', 'Parent');

            linkForeign = this.entityTypeToLink(this.scope, true);

            if (this.getMetadata().get(['entityDefs', this.scope, 'links', 'parent'])) {
                this.model.set('link', 'parentAnother');
                this.model.set('label', 'Parent Another');

                linkForeign += 'Another';
            }

            this.model.set('linkForeign', linkForeign);

            this.model.set('labelForeign', null);
            this.model.set('entityForeign', null);

            return;
        }
        else {
            if (!entityForeign || !linkType) {
                this.model.set('link', null);
                this.model.set('linkForeign', null);

                this.model.set('label', null);
                this.model.set('labelForeign', null);

                return;
            }
        }

        switch (linkType) {
            case 'oneToMany':
                linkForeign = this.entityTypeToLink(this.scope);
                link = this.entityTypeToLink(entityForeign, true);

                if (entityForeign === this.scope) {
                    linkForeign = linkForeign + 'Parent';
                }

                break;

            case 'manyToOne':
                linkForeign = this.entityTypeToLink(this.scope, true);
                link = this.entityTypeToLink(entityForeign);

                if (entityForeign === this.scope) {
                    link = link + 'Parent';
                }

                break;

            case 'manyToMany':
                linkForeign =  this.entityTypeToLink(this.scope, true);
                link = this.entityTypeToLink(entityForeign, true);

                if (link === linkForeign) {
                    link = link + 'Right';
                    linkForeign = linkForeign + 'Left';
                }

                const entityStripped = this.stripPrefixFromCustomEntityType(this.scope);
                const entityForeignStripped = this.stripPrefixFromCustomEntityType(entityForeign);


                const relationName = entityStripped.localeCompare(entityForeignStripped) ?
                    Espo.Utils.lowerCaseFirst(entityStripped) + entityForeignStripped :
                    Espo.Utils.lowerCaseFirst(entityForeignStripped) + entityStripped;

                this.model.set('relationName', relationName);

                break;

            case 'oneToOneLeft':
                linkForeign = this.entityTypeToLink(this.scope);
                link = this.entityTypeToLink(entityForeign);

                if (entityForeign === this.scope) {
                    if (linkForeign === Espo.Utils.lowerCaseFirst(this.scope)) {
                        link = link + 'Parent';
                    }
                }

                break;

            case 'oneToOneRight':
                linkForeign = this.entityTypeToLink(this.scope);
                link = this.entityTypeToLink(entityForeign);

                if (entityForeign === this.scope) {
                    if (linkForeign === Espo.Utils.lowerCaseFirst(this.scope)) {
                        linkForeign = linkForeign + 'Parent';
                    }
                }

                break;
        }

        let number = 1;

        while (this.getMetadata().get(['entityDefs', this.scope, 'links', link])) {
            link += number.toString();

            number++;
        }

        number = 1;

        while (this.getMetadata().get(['entityDefs', entityForeign, 'links', linkForeign])) {
            linkForeign += number.toString();

            number++;
        }

        this.model.set('link', link);
        this.model.set('linkForeign', linkForeign);

        let label = Espo.Utils.upperCaseFirst(link.replace(/([a-z])([A-Z])/g, '$1 $2'));
        let labelForeign = Espo.Utils.upperCaseFirst(linkForeign.replace(/([a-z])([A-Z])/g, '$1 $2'));

        if (label.startsWith('C ')) {
            label = label.substring(2);
        }

        if (labelForeign.startsWith('C ')) {
            labelForeign = labelForeign.substring(2);
        }

        // @todo Use entity labels as initial link labels?

        this.model.set('label', label || null);
        this.model.set('labelForeign', labelForeign || null);
    }

    /**
     * @private
     * @param {string} entityType
     * @param {boolean} plural
     * @return {string}
     */
    entityTypeToLink(entityType, plural = false) {
        let string = this.stripPrefixFromCustomEntityType(entityType);

        string = Espo.Utils.lowerCaseFirst(string);

        if (plural) {
            string = this.toPlural(string);
        }

        return string;
    }

    /**
     * @private
     * @param {string} entityType
     * @return {string}
     */
    stripPrefixFromCustomEntityType(entityType) {
        let string = entityType;

        if (
            this.getMetadata().get(`scopes.${entityType}.isCustom`) &&
            entityType[0] === 'C' && /[A-Z]/.test(entityType[1])
        ) {
            string = string.substring(1);
        }

        return string;
    }

    handleLinkChange(field) {
        let value = this.model.get(field);

        if (value) {
            value = value.replace(/-/g, ' ')
                .replace(/_/g, ' ')
                .replace(/[^\w\s]/gi, '').replace(/ (.)/g, (match, g) => {
                    return g.toUpperCase();
                })
                .replace(' ', '');

            if (value.length) {
                 value = Espo.Utils.lowerCaseFirst(value);
            }
        }

        this.model.set(field, value);
    }

    hideField(name) {
        const view = this.getView(name);

        if (view) {
            view.disabled = true;
        }

        this.$el.find('.cell[data-name=' + name+']').addClass('hidden-cell');
    }

    showField(name) {
        const view = this.getView(name);

        if (view) {
            view.disabled = false;
        }

        this.$el.find('.cell[data-name=' + name+']').removeClass('hidden-cell');
    }

    handleLinkTypeChange() {
        const linkType = this.model.get('linkType');

        this.showField('entityForeign');
        this.showField('labelForeign');

        this.hideField('parentEntityTypeList');
        this.hideField('foreignLinkEntityTypeList');

        if (linkType === 'manyToMany') {
            this.showField('relationName');

            this.showField('linkMultipleField');
            this.showField('linkMultipleFieldForeign');

            this.showField('audited');
            this.showField('auditedForeign');

            this.showField('layout');
            this.showField('layoutForeign');

            this.showField('selectFilter');
            this.showField('selectFilterForeign');
        }
        else {
            this.hideField('relationName');

            if (linkType === 'oneToMany') {
                this.showField('linkMultipleField');
                this.hideField('linkMultipleFieldForeign');

                this.showField('audited');
                this.hideField('auditedForeign');

                this.showField('layout');
                this.hideField('layoutForeign');

                this.showField('selectFilter');
                this.showField('selectFilterForeign');
            }
            else if (linkType === 'manyToOne') {
                this.hideField('linkMultipleField');
                this.showField('linkMultipleFieldForeign');

                this.hideField('audited');
                this.showField('auditedForeign');

                this.hideField('layout');
                this.showField('layoutForeign');

                this.showField('selectFilter');
                this.showField('selectFilterForeign');
            }
            else {
                this.hideField('linkMultipleField');
                this.hideField('linkMultipleFieldForeign');

                this.hideField('audited');
                this.hideField('auditedForeign');

                this.hideField('layout');
                this.hideField('layoutForeign');

                if (linkType === 'parentToChildren') {
                    this.showField('audited');
                    this.hideField('auditedForeign');

                    this.showField('layout');
                    this.hideField('layoutForeign');

                    this.hideField('selectFilter');
                    this.hideField('selectFilterForeign');
                }
                else if (linkType === 'childrenToParent') {
                    this.hideField('audited');
                    this.showField('auditedForeign');

                    this.hideField('layout');
                    this.hideField('layoutForeign');

                    this.hideField('entityForeign');
                    this.hideField('labelForeign');

                    this.hideField('selectFilter');
                    this.hideField('selectFilterForeign');

                    if (!this.noParentEntityTypeList) {
                        this.showField('parentEntityTypeList');
                    }

                    if (!this.model.get('linkForeign')) {
                        this.hideField('foreignLinkEntityTypeList');
                    } else {
                        this.showField('foreignLinkEntityTypeList');
                    }
                }
                else {
                    this.hideField('audited');
                    this.hideField('auditedForeign');

                    this.hideField('layout');
                    this.hideField('layoutForeign');

                    if (linkType) {
                        this.showField('selectFilter');
                        this.showField('selectFilterForeign');
                    }
                    else {
                        this.hideField('selectFilter');
                        this.hideField('selectFilterForeign');
                    }
                }
            }
        }

        if (!this.getMetadata().get(['scopes', this.scope, 'stream'])) {
            this.hideField('audited');
        }

        if (!this.getMetadata().get(['scopes', this.model.get('entityForeign'), 'stream'])) {
            this.hideField('auditedForeign');
        }
    }

    afterRender() {
        this.handleLinkTypeChange();

        this.getView('linkType').on('change', () => {
            this.handleLinkTypeChange();
            this.populateFields();
        });

        this.getView('entityForeign').on('change', () => {
            this.populateFields();
        });

        this.getView('link').on('change', () => {
            this.handleLinkChange('link');
        });

        this.getView('linkForeign').on('change', () => {
            this.handleLinkChange('linkForeign');
        });
    }

    /**
     * @param {{noClose?: boolean}} [options]
     */
    save(options) {
        options = options || {};

        const arr = [
            'link',
            'linkForeign',
            'label',
            'labelForeign',
            'linkType',
            'entityForeign',
            'relationName',
            'linkMultipleField',
            'linkMultipleFieldForeign',
            'audited',
            'auditedForeign',
            'layout',
            'layoutForeign',
            'selectFilter',
            'selectFilterForeign',
            'parentEntityTypeList',
            'foreignLinkEntityTypeList',
        ];

        let notValid = false;

        arr.forEach(item => {
            if (!this.hasView(item)) {
                return;
            }

            const view = /** @type {import('views/fields/base').default} */
                this.getView(item);

            if (view.mode !== 'edit') {
                return;
            }

            view.fetchToModel();
        });

        arr.forEach(item => {
            if (!this.hasView(item)) {
                return;
            }

            const view = /** @type {import('views/fields/base').default} */
                this.getView(item);

            if (view.mode !== 'edit') {
                return;
            }

            if (!view.disabled) {
                notValid = view.validate() || notValid;
            }
        });

        if (notValid) {
            return;
        }

        this.$el.find('button[data-name="save"]').addClass('disabled').attr('disabled');

        let url = 'EntityManager/action/createLink';

        if (!this.isNew) {
            url = 'EntityManager/action/updateLink';
        }

        const entity = this.scope;
        const entityForeign = this.model.get('entityForeign');
        const link = this.model.get('link');
        const linkForeign = this.model.get('linkForeign');
        const label = this.model.get('label');
        const labelForeign = this.model.get('labelForeign');
        const relationName = this.model.get('relationName');

        const linkMultipleField = this.model.get('linkMultipleField');
        const linkMultipleFieldForeign = this.model.get('linkMultipleFieldForeign');

        const audited = this.model.get('audited');
        const auditedForeign = this.model.get('auditedForeign');

        const layout = this.model.get('layout');
        const layoutForeign = this.model.get('layoutForeign');

        const linkType = this.model.get('linkType');

        const attributes = {
            entity: entity,
            entityForeign: entityForeign,
            link: link,
            linkForeign: linkForeign,
            label: label,
            labelForeign: labelForeign,
            linkType: linkType,
            relationName: relationName,
            linkMultipleField: linkMultipleField,
            linkMultipleFieldForeign: linkMultipleFieldForeign,
            audited: audited,
            auditedForeign: auditedForeign,
            layout: layout,
            layoutForeign: layoutForeign,
            selectFilter: this.model.get('selectFilter'),
            selectFilterForeign: this.model.get('selectFilterForeign'),
        };

        if (!this.isNew) {
            if (attributes.label === this.model.fetchedAttributes.label) {
                delete attributes.label;
            }

            if (attributes.labelForeign === this.model.fetchedAttributes.labelForeign) {
                delete attributes.labelForeign;
            }
        }

        if (linkType === 'childrenToParent') {
            delete attributes.entityForeign;
            delete attributes.labelForeign;

            attributes.parentEntityTypeList = this.model.get('parentEntityTypeList');
            attributes.foreignLinkEntityTypeList = this.model.get('foreignLinkEntityTypeList');

            if (this.noParentEntityTypeList) {
                attributes.parentEntityTypeList = null;
            }

            delete attributes.selectFilter;
            delete attributes.selectFilterForeign;
        }

        if (linkType === 'parentToChildren') {
            delete attributes.selectFilter;
            delete attributes.selectFilterForeign;
        }

        Espo.Ajax
            .postRequest(url, attributes)
            .then(() => {
                if (!this.isNew) {
                    Espo.Ui.success(this.translate('Saved'));
                }
                else {
                    Espo.Ui.success(this.translate('Created'));
                }

                this.model.fetchedAttributes = this.model.getClonedAttributes();

                Promise.all([
                    this.getMetadata().loadSkipCache(),
                    this.getLanguage().loadSkipCache(),
                ]).then(() => {
                    this.broadcastUpdate();
                    this.trigger('after:save');

                    if (!options.noClose) {
                        this.close();
                    }

                    if (options.noClose) {
                        this.$el.find('button[data-name="save"]')
                            .removeClass('disabled')
                            .removeAttr('disabled');
                    }
                });
            })
            .catch(xhr => {
                if (xhr.status === 409) {
                    const msg = this.translate('linkConflict', 'messages', 'EntityManager');
                    const statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');

                    if (statusReasonHeader) {
                        console.error(statusReasonHeader);
                    }

                    Espo.Ui.error(msg);

                    xhr.errorIsHandled = true;
                }

                this.$el.find('button[data-name="save"]').removeClass('disabled').removeAttr('disabled');
            });
    }

    getForeignLinkEntityTypeList(entityType, link, entityTypeList, onlyNotCustom) {
        const list = [];

        entityTypeList.forEach(item => {
            const linkDefs = /** @type {Object.<string, Record>} */
                this.getMetadata().get(['entityDefs', item, 'links']) || {};

            let isFound = false;

            for (const i in linkDefs) {
                if (
                    linkDefs[i].foreign === link &&
                    linkDefs[i].entity === entityType &&
                    linkDefs[i].type === 'hasChildren'
                ) {
                    if (onlyNotCustom) {
                        if (linkDefs[i].isCustom) {
                            continue;
                        }
                    }

                    isFound = true;

                    break;
                }
            }

            if (isFound) {
                list.push(item);
            }
        });

        return list;
    }

    /**
     * @param {string} entityType
     * @param {string} link
     * @param {string} param
     * @return {*}
     */
    getRelationshipPanelParam(entityType, link, param) {
        return this.getMetadata().get(`clientDefs.${entityType}.relationshipPanels.${link}.${param}`);
    }

    broadcastUpdate() {
        this.getHelper().broadcastChannel.postMessage('update:metadata');
        this.getHelper().broadcastChannel.postMessage('update:language');
    }
}

export default LinkManagerEditModalView;
