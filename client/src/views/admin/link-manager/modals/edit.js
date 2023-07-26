/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import ModalView from 'views/modal';
import Model from 'model';
import Index from 'views/admin/link-manager/index';
import EnumFieldView from 'views/fields/enum';

class LinkManagerEditModalView extends ModalView {

    template = 'admin/link-manager/modals/edit'
    cssName = 'edit'
    className = 'dialog dialog-record'

    shortcutKeys = {
        /** @this LinkManagerEditModalView */
        'Control+KeyS': function (e) {
            this.save({noClose: true});

            e.preventDefault();
            e.stopPropagation();
        },
        /** @this LinkManagerEditModalView */
        'Control+Enter': function (e) {
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

        let scope = this.scope = this.options.scope;
        let link = this.link = this.options.link || false;

        let entity = scope;

        let isNew = this.isNew = (false === link);

        let header = 'Create Link';

        if (!isNew) {
            header = 'Edit Link';
        }

        this.headerText = this.translate(header, 'labels', 'Admin');

        let model = this.model = new Model();
        model.name = 'EntityManager';

        this.model.set('entity', scope);

        let allEntityList = this.getMetadata().getScopeEntityList()
            .filter(item => {
                return this.getMetadata().get(['scopes', item, 'customizable']);
            })
            .sort((v1, v2) => {
                var t1 = this.translate(v1, 'scopeNames');
                var t2 = this.translate(v2, 'scopeNames');

                return t1.localeCompare(t2);
            });

        let isCustom = true;

        let linkType;

        if (!isNew) {
            let entityForeign = this.getMetadata().get('entityDefs.' + scope + '.links.' + link + '.entity');
            let linkForeign = this.getMetadata().get('entityDefs.' + scope + '.links.' + link + '.foreign');
            let label = this.getLanguage().translate(link, 'links', scope);
            let labelForeign = this.getLanguage().translate(linkForeign, 'links', entityForeign);

            let type = this.getMetadata().get('entityDefs.' + entity + '.links.' + link + '.type');
            let foreignType = this.getMetadata()
                .get('entityDefs.' + entityForeign + '.links.' + linkForeign + '.type');

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

                let foreignLinkEntityTypeList = this.getForeignLinkEntityTypeList(entity, link, entityTypeList);

                this.model.set('foreignLinkEntityTypeList', foreignLinkEntityTypeList);
            }
            else {
                linkType = Index.prototype.computeRelationshipType.call(this, type, foreignType);
            }

            this.model.set('linkType', linkType);
            this.model.set('entityForeign', entityForeign);
            this.model.set('link', link);
            this.model.set('linkForeign', linkForeign);
            this.model.set('label', label);
            this.model.set('labelForeign', labelForeign);

            let linkMultipleField =
                this.getMetadata().get(['entityDefs', scope, 'fields', link, 'type']) === 'linkMultiple' &&
                !this.getMetadata().get(['entityDefs', scope, 'fields', link, 'noLoad']);

            let linkMultipleFieldForeign =
                this.getMetadata()
                    .get(['entityDefs', entityForeign, 'fields', linkForeign, 'type']) === 'linkMultiple' &&
                !this.getMetadata().get(['entityDefs', entityForeign, 'fields', linkForeign, 'noLoad']);

            this.model.set('linkMultipleField', linkMultipleField);
            this.model.set('linkMultipleFieldForeign', linkMultipleFieldForeign);

            if (linkType === 'manyToMany') {
                let relationName = this.getMetadata()
                    .get('entityDefs.' + entity + '.links.' + link + '.relationName');

                this.model.set('relationName', relationName);
            }

            let audited = this.getMetadata().get(['entityDefs', scope, 'links', link, 'audited']) || false;
            let auditedForeign = this.getMetadata()
                .get(['entityDefs', entityForeign, 'links', linkForeign, 'audited']) || false;

            this.model.set('audited', audited);
            this.model.set('auditedForeign', auditedForeign);

            let layout = this.getMetadata()
                .get(['clientDefs', scope, 'relationshipPanels', link, 'layout']);
            let layoutForeign = this.getMetadata()
                .get(['clientDefs', entityForeign, 'relationshipPanels', linkForeign, 'layout']);

            this.model.set('layout', layout);
            this.model.set('layoutForeign', layoutForeign);

            isCustom = this.getMetadata().get('entityDefs.' + entity + '.links.' + link + '.isCustom');
        }

        let scopes = this.getMetadata().get('scopes') || null;

        let entityList = (Object.keys(scopes) || [])
            .filter(item => {
                let d = scopes[item];

                return d.customizable && d.entity;
            })
            .sort((v1, v2) => {
                let t1 = this.translate(v1, 'scopeNames');
                let t2 = this.translate(v2, 'scopeNames');

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
                    options: ['', 'oneToMany', 'manyToOne', 'manyToMany',
                        'oneToOneRight', 'oneToOneLeft', 'childrenToParent']
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

        let layouts = ['', ...this.getEntityTypeLayouts(this.scope)];
        let layoutTranslatedOptions = this.getEntityTypeLayoutsTranslations(this.scope);

        this.layoutFieldView = new EnumFieldView({
            model: model,
            mode: 'edit',
            defs: {
                name: 'layout',
            },
            params: {
                options: [''],
            },
        });

        this.layoutForeignFieldView = new EnumFieldView({
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

            let view = this.getView('foreignLinkEntityTypeList');

            if (view) {
                if (!this.noParentEntityTypeList) {
                    view.setOptionList(this.model.get('parentEntityTypeList') || []);
                }
            }

            let checkedList = Espo.Utils.clone(this.model.get('foreignLinkEntityTypeList') || []);

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
    }

    getEntityTypeLayouts(entityType) {
        let defs = this.getMetadata().get(['clientDefs', entityType, 'additionalLayouts'], {});

        return Object.keys(defs)
            .filter(item => ['list', 'listSmall'].includes(defs[item].type));
    }

    getEntityTypeLayoutsTranslations(entityType) {
        let map = {};

        this.getEntityTypeLayouts(entityType).forEach(item => {
            map[item] = this.getLanguage().has(item, 'layouts', entityType) ?
                this.getLanguage().translate(item, 'layouts', entityType) :
                this.getLanguage().translate(item, 'layouts', 'Admin');
        });

        return map;
    }

    controlLayoutField() {
        let foreignEntityType = this.model.get('entityForeign');

        let layouts = foreignEntityType ?
            ['', ...this.getEntityTypeLayouts(foreignEntityType)] :
            [''];

        this.layoutFieldView.translatedOptions = foreignEntityType ?
            this.getEntityTypeLayoutsTranslations(foreignEntityType) :
            {};

        this.layoutFieldView.setOptionList(layouts);
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
        let entityForeign = this.model.get('entityForeign');
        let linkType = this.model.get('linkType');

        let link;
        let linkForeign;

        if (linkType === 'childrenToParent') {
                this.model.set('link', 'parent');
                this.model.set('label', 'Parent');

                linkForeign = this.toPlural(Espo.Utils.lowerCaseFirst(this.scope));

                if (this.getMetadata().get(['entityDefs', this.scope, 'links', 'parent'])) {
                    this.model.set('link', 'parentAnother');
                    this.model.set('label', 'Parent Another');

                    linkForeign += 'Another';
                }

                this.model.set('linkForeign', linkForeign);

                this.model.set('labelForeign', '');
                this.model.set('entityForeign', null);

                return;
        }
        else {
            if (!entityForeign || !linkType) {
                this.model.set('link', '');
                this.model.set('linkForeign', '');

                this.model.set('label', '');
                this.model.set('labelForeign', '');

                return;
            }
        }

        switch (linkType) {
            case 'oneToMany':
                linkForeign = Espo.Utils.lowerCaseFirst(this.scope);
                link = this.toPlural(Espo.Utils.lowerCaseFirst(entityForeign));

                if (entityForeign === this.scope) {

                    if (linkForeign === Espo.Utils.lowerCaseFirst(this.scope)) {
                        linkForeign = linkForeign + 'Parent';
                    }
                }

                break;

            case 'manyToOne':
                linkForeign = this.toPlural(Espo.Utils.lowerCaseFirst(this.scope));
                link = Espo.Utils.lowerCaseFirst(entityForeign);

                if (entityForeign === this.scope) {
                    if (link === Espo.Utils.lowerCaseFirst(this.scope)) {
                        link = link + 'Parent';
                    }
                }
                break;

            case 'manyToMany':
                linkForeign = this.toPlural(Espo.Utils.lowerCaseFirst(this.scope));
                link = this.toPlural(Espo.Utils.lowerCaseFirst(entityForeign));

                if (link === linkForeign) {
                    link = link + 'Right';
                    linkForeign = linkForeign + 'Left';
                }

                let relationName;

                if (this.scope.localeCompare(entityForeign)) {
                    relationName = Espo.Utils.lowerCaseFirst(this.scope) + entityForeign;
                } else {
                    relationName = Espo.Utils.lowerCaseFirst(entityForeign) + this.scope;
                }

                this.model.set('relationName', relationName);

                break;

            case 'oneToOneLeft':
                linkForeign = Espo.Utils.lowerCaseFirst(this.scope);
                link = Espo.Utils.lowerCaseFirst(entityForeign);

                if (entityForeign === this.scope) {
                    if (linkForeign === Espo.Utils.lowerCaseFirst(this.scope)) {
                        link = link + 'Parent';
                    }
                }

                break;

            case 'oneToOneRight':
                linkForeign = Espo.Utils.lowerCaseFirst(this.scope);
                link = Espo.Utils.lowerCaseFirst(entityForeign);

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

        this.model.set('label', label);
        this.model.set('labelForeign', labelForeign);
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
        let view = this.getView(name);

        if (view) {
            view.disabled = true;
        }

        this.$el.find('.cell[data-name=' + name+']').addClass('hidden-cell');
    }

    showField(name) {
        let view = this.getView(name);

        if (view) {
            view.disabled = false;
        }

        this.$el.find('.cell[data-name=' + name+']').removeClass('hidden-cell');
    }

    handleLinkTypeChange() {
        var linkType = this.model.get('linkType');

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
            }
            else if (linkType === 'manyToOne') {
                this.hideField('linkMultipleField');
                this.showField('linkMultipleFieldForeign');

                this.hideField('audited');
                this.showField('auditedForeign');

                this.hideField('layout');
                this.showField('layoutForeign');
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
                }
                else if (linkType === 'childrenToParent') {
                    this.hideField('audited');
                    this.showField('auditedForeign');

                    this.hideField('layout');
                    this.showField('layoutForeign');

                    this.hideField('entityForeign');
                    this.hideField('labelForeign');

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

        let arr = [
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
            'parentEntityTypeList',
            'foreignLinkEntityTypeList',
        ];

        let notValid = false;

        arr.forEach(item => {
            if (!this.hasView(item)) {
                return;
            }

            if (this.getView(item).mode !== 'edit') {
                return;
            }

            this.getView(item).fetchToModel();
        });

        arr.forEach(item => {
            if (!this.hasView(item)) {
                return;
            }

            let view = this.getView(item);

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

        let entity = this.scope;
        let entityForeign = this.model.get('entityForeign');
        let link = this.model.get('link');
        let linkForeign = this.model.get('linkForeign');
        let label = this.model.get('label');
        let labelForeign = this.model.get('labelForeign');
        let relationName = this.model.get('relationName');

        let linkMultipleField = this.model.get('linkMultipleField');
        let linkMultipleFieldForeign = this.model.get('linkMultipleFieldForeign');

        let audited = this.model.get('audited');
        let auditedForeign = this.model.get('auditedForeign');

        let layout = this.model.get('layout');
        let layoutForeign = this.model.get('layoutForeign');

        let linkType = this.model.get('linkType');

        let attributes = {
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

                let data;

                data = ((this.getLanguage().data || {}) || {})[entity] || {};
                (data.fields || {})[link] = label;
                (data.links || {})[link] = label;

                if (entityForeign) {
                    data = ((this.getLanguage().data || {}) || {})[entityForeign];

                    if (linkForeign) {
                        (data.fields || {})[linkForeign] = labelForeign;
                        (data.links || {})[linkForeign] = labelForeign;
                    }
                }

                this.getMetadata().loadSkipCache().then(() => {
                    this.broadcastUpdate();

                    this.trigger('after:save');

                    if (!options.noClose) {
                        this.close();
                    }

                    if (options.noClose) {
                        this.$el.find('button[data-name="save"]').removeClass('disabled').removeAttr('disabled');
                    }
                });
            })
            .catch(xhr => {
                if (xhr.status === 409) {
                    var msg = this.translate('linkConflict', 'messages', 'EntityManager');
                    var statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');

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
        let list = [];

        entityTypeList.forEach(item => {
            let linkDefs = this.getMetadata().get(['entityDefs', item, 'links']) || {};

            let isFound = false;

            for (let i in linkDefs) {
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

    broadcastUpdate() {
        this.getHelper().broadcastChannel.postMessage('update:metadata');
        this.getHelper().broadcastChannel.postMessage('update:language');
    }
}

export default LinkManagerEditModalView;
