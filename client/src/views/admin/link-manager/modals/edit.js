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

Espo.define('views/admin/link-manager/modals/edit', ['views/modal', 'views/admin/link-manager/index', 'model'], function (Dep, Index, Model) {

    return Dep.extend({

        cssName: 'edit',

        template: 'admin/link-manager/modals/edit',

        setup: function () {

            this.buttonList = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'danger',
                    onClick: function (dialog) {
                        this.save();
                    }.bind(this)
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        this.close();
                    }.bind(this)
                }
            ];

            var scope = this.scope = this.options.scope;
            var link = this.link = this.options.link || false;

            var entity = scope;

            var isNew = this.isNew = (false == link);

            var header = 'Create Link';
            if (!isNew) {
                header = 'Edit Link';
            }

            this.header = this.translate(header, 'labels', 'Admin');

            var model = this.model = new Model();
            model.name = 'EntityManager';

            this.model.set('entity', scope);

            var isCustom = true;

            if (!isNew) {
                var entityForeign = this.getMetadata().get('entityDefs.' + scope + '.links.' + link + '.entity');
                var linkForeign = this.getMetadata().get('entityDefs.' + scope + '.links.' + link + '.foreign');
                var label = this.getLanguage().translate(link, 'links', scope);
                var labelForeign = this.getLanguage().translate(linkForeign, 'links', entityForeign);

                var type = this.getMetadata().get('entityDefs.' + entity + '.links.' + link + '.type');
                var foreignType = this.getMetadata().get('entityDefs.' + entityForeign + '.links.' + linkForeign + '.type');

                var linkType = Index.prototype.computeRelationshipType.call(this, type, foreignType);

                this.model.set('linkType', linkType);
                this.model.set('entityForeign', entityForeign);
                this.model.set('link', link);
                this.model.set('linkForeign', linkForeign);
                this.model.set('label', label);
                this.model.set('labelForeign', labelForeign);

                var linkMultipleField = false;
                if (this.getMetadata().get('entityDefs.' + scope + '.fields.' + link + '.type') == 'linkMultiple') {
                    if (!this.getMetadata().get('entityDefs.' + scope + '.fields.' + link + '.noLoad')) {
                        linkMultipleField = true;
                    }
                }
                this.model.set('linkMultipleField', linkMultipleField);

                var linkMultipleFieldForeign = false;
                if (this.getMetadata().get('entityDefs.' + entityForeign + '.fields.' + linkForeign + '.type') == 'linkMultiple') {
                    if (!this.getMetadata().get('entityDefs.' + entityForeign + '.fields.' + linkForeign + '.noLoad')) {
                        linkMultipleFieldForeign = true;
                    }
                }
                this.model.set('linkMultipleFieldForeign', linkMultipleFieldForeign);

                if (linkType == 'manyToMany') {
                    var relationName = this.getMetadata().get('entityDefs.' + entity + '.links.' + link + '.relationName');
                    this.model.set('relationName', relationName);
                }

                var audited = this.getMetadata().get(['entityDefs', scope, 'links', link, 'audited']) || false;
                var auditedForeign = this.getMetadata().get(['entityDefs', entityForeign, 'links', linkForeign, 'audited']) || false;
                this.model.set('audited', audited);
                this.model.set('auditedForeign', auditedForeign);

                isCustom = this.getMetadata().get('entityDefs.' + entity + '.links.' + link + '.isCustom');
            }

            var scopes = this.getMetadata().get('scopes') || null;
            var entityList = (Object.keys(scopes) || []).filter(function (item) {
                var d = scopes[item];
                return d.customizable && d.entity;
            }, this).sort(function (v1, v2) {
                var t1 = this.translate(v1, 'scopeNames');
                var t2 = this.translate(v2, 'scopeNames');
                return t1.localeCompare(t2);
            }.bind(this));

            entityList.unshift('');

            this.createView('entity', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="entity"]',
                defs: {
                    name: 'entity'
                },
                readOnly: true
            });
            this.createView('entityForeign', 'views/fields/enum', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="entityForeign"]',
                defs: {
                    name: 'entityForeign',
                    params: {
                        required: true,
                        options: entityList,
                        translation: 'Global.scopeNames'
                    }
                },
                readOnly: !isNew
            });
            this.createView('linkType', 'views/fields/enum', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="linkType"]',
                defs: {
                    name: 'linkType',
                    params: {
                        required: true,
                        options: ['', 'oneToMany', 'manyToOne', 'manyToMany']
                    }
                },
                readOnly: !isNew
            });

            this.createView('link', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="link"]',
                defs: {
                    name: 'link',
                    params: {
                        required: true,
                        trim: true
                    }
                },
                readOnly: !isNew
            });
            this.createView('linkForeign', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="linkForeign"]',
                defs: {
                    name: 'linkForeign',
                    params: {
                        required: true,
                        trim: true
                    }
                },
                readOnly: !isNew
            });
            this.createView('label', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="label"]',
                defs: {
                    name: 'label',
                    params: {
                        required: true,
                        trim: true
                    }
                }
            });
            this.createView('labelForeign', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="labelForeign"]',
                defs: {
                    name: 'labelForeign',
                    params: {
                        required: true,
                        trim: true
                    }
                }
            });

            if (isNew || this.model.get('relationName')) {
                this.createView('relationName', 'views/fields/varchar', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="relationName"]',
                    defs: {
                        name: 'relationName',
                        params: {
                            required: true,
                            trim: true
                        }
                    },
                    readOnly: !isNew
                });
            }

            this.createView('linkMultipleField', 'views/fields/bool', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="linkMultipleField"]',
                defs: {
                    name: 'linkMultipleField'
                },
                readOnly: !isCustom,
                tooltip: true,
                tooltipText: this.translate('linkMultipleField', 'tooltips', 'EntityManager')
            });

            this.createView('linkMultipleFieldForeign', 'views/fields/bool', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="linkMultipleFieldForeign"]',
                defs: {
                    name: 'linkMultipleFieldForeign'
                },
                readOnly: !isCustom,
                tooltip: true,
                tooltipText: this.translate('linkMultipleField', 'tooltips', 'EntityManager')
            });

            this.createView('audited', 'views/fields/bool', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="audited"]',
                defs: {
                    name: 'audited'
                },
                tooltip: true,
                tooltipText: this.translate('linkAudited', 'tooltips', 'EntityManager')
            });

            this.createView('auditedForeign', 'views/fields/bool', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="auditedForeign"]',
                defs: {
                    name: 'auditedForeign'
                },
                tooltip: true,
                tooltipText: this.translate('linkAudited', 'tooltips', 'EntityManager')
            });


            this.model.fetchedAttributes = this.model.getClonedAttributes();
        },

        toPlural: function (string) {
            if (string.slice(-1) == 'y') {
                return string.substr(0, string.length - 1) + 'ies';
            } else {
                return string + 's';
            }
        },

        populateFields: function () {
            var entityForeign = this.model.get('entityForeign');
            var linkType = this.model.get('linkType');

            if (!entityForeign || !linkType) {
                this.model.set('link', '');
                this.model.set('linkForeign', '');

                this.model.set('label', '');
                this.model.set('labelForeign', '');
                return;
            }

            var link;
            var linkForeign;

            switch (linkType) {
                case 'oneToMany':
                    linkForeign = Espo.Utils.lowerCaseFirst(this.scope);
                    link = this.toPlural(Espo.Utils.lowerCaseFirst(entityForeign));
                    if (entityForeign == this.scope) {

                        if (linkForeign == Espo.Utils.lowerCaseFirst(this.scope)) {
                            linkForeign = linkForeign + 'Parent';
                        }
                    }
                    break;
                case 'manyToOne':
                    linkForeign = this.toPlural(Espo.Utils.lowerCaseFirst(this.scope));
                    link = Espo.Utils.lowerCaseFirst(entityForeign);
                    if (entityForeign == this.scope) {
                        if (link == Espo.Utils.lowerCaseFirst(this.scope)) {
                            link = link + 'Parent';
                        }
                    }
                    break;
                case 'manyToMany':
                    linkForeign = this.toPlural(Espo.Utils.lowerCaseFirst(this.scope));
                    link = this.toPlural(Espo.Utils.lowerCaseFirst(entityForeign));
                    if (link == linkForeign) {
                        link = link + 'Right';
                        linkForeign = linkForeign + 'Left';
                    }
                    var relationName;
                    if (this.scope.localeCompare(entityForeign)) {
                        relationName = Espo.Utils.lowerCaseFirst(this.scope) + entityForeign;
                    } else {
                        relationName = Espo.Utils.lowerCaseFirst(entityForeign) + this.scope;
                    }
                    this.model.set('relationName', relationName);
                    break;
            }

            var number = 1;
            while (this.getMetadata().get(['entityDefs', this.scope, 'links', link])) {
                link += number.toString();
                number++;
            }

            var number = 1;
            while (this.getMetadata().get(['entityDefs', entityForeign, 'links', linkForeign])) {
                linkForeign += number.toString();
                number++;
            }

            this.model.set('link', link);
            this.model.set('linkForeign', linkForeign);

            this.model.set('label', Espo.Utils.upperCaseFirst(link));
            this.model.set('labelForeign', Espo.Utils.upperCaseFirst(linkForeign));

            return;
        },

        handleLinkChange: function (field) {
            var value = this.model.get(field);
            if (value) {
                value = value.replace(/\-/g, ' ').replace(/_/g, ' ').replace(/[^\w\s]/gi, '').replace(/ (.)/g, function (match, g) {
                    return g.toUpperCase();
                }).replace(' ', '');
                if (value.length) {
                     value = Espo.Utils.lowerCaseFirst(value);
                }
            }
            this.model.set(field, value);
        },

        hideField: function (name) {
            var view = this.getView(name);
            if (view) {
                view.disabled = true;
            }
            this.$el.find('.cell[data-name=' + name+']').css('visibility', 'hidden');
        },

        showField: function (name) {
            var view = this.getView(name);
            if (view) {
                view.disabled = false;
            }
            this.$el.find('.cell[data-name=' + name+']').css('visibility', 'visible');
        },

        handleLinkTypeChange: function () {
            var linkType = this.model.get('linkType');
            if (linkType === 'manyToMany') {
                var relationNameView = this.getView('relationName');
                this.showField('relationName');
                this.showField('relationName');

                this.showField('linkMultipleField');
                this.showField('linkMultipleFieldForeign');
                this.showField('audited');
                this.showField('auditedForeign');
            } else {
                this.hideField('relationName');
                if (linkType === 'oneToMany') {
                    this.showField('linkMultipleField');
                    this.hideField('linkMultipleFieldForeign');
                    this.showField('audited');
                    this.hideField('auditedForeign');
                } else if (linkType === 'manyToOne') {
                    this.hideField('linkMultipleField');
                    this.showField('linkMultipleFieldForeign');
                    this.hideField('audited');
                    this.showField('auditedForeign');
                } else {
                    this.hideField('linkMultipleField');
                    this.hideField('linkMultipleFieldForeign');

                    if (linkType == 'parentToChildren') {
                        this.showField('audited');
                        this.hideField('auditedForeign');
                    } else if (linkType == 'childrenToParent') {
                        this.hideField('audited');
                        this.showField('auditedForeign');
                    } else {
                        this.hideField('audited');
                        this.hideField('auditedForeign');
                    }
                }
            }

            if (!this.getMetadata().get(['scopes', this.scope, 'stream'])) {
                this.hideField('audited');
            }
            if (!this.getMetadata().get(['scopes', this.model.get('entityForeign'), 'stream'])) {
                this.hideField('auditedForeign');
            }
        },

        afterRender: function () {
            this.handleLinkTypeChange();

            this.getView('linkType').on('change', function (m) {
                this.handleLinkTypeChange();
                this.populateFields();
            }, this);
            this.getView('entityForeign').on('change', function (m) {
                this.populateFields();
            }, this);

            this.getView('link').on('change', function (m) {
                this.handleLinkChange('link');
            }, this);
            this.getView('linkForeign').on('change', function (m) {
                this.handleLinkChange('linkForeign');
            }, this);
        },

        save: function () {
            var arr = [
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
                'auditedForeign'
            ];

            var notValid = false;

            arr.forEach(function (item) {
                if (!this.hasView(item)) return;
                if (this.getView(item).mode != 'edit') return;
                this.getView(item).fetchToModel();
            }, this);

            arr.forEach(function (item) {
                if (!this.hasView(item)) return;
                var view = this.getView(item);
                if (view.mode != 'edit') return;
                if (!view.disabled) {
                    notValid = view.validate() || notValid;
                }
            }, this);

            if (notValid) {
                return;
            }

            this.$el.find('button[data-name="save"]').addClass('disabled').attr('disabled');

            var url = 'EntityManager/action/createLink';
            if (!this.isNew) {
                url = 'EntityManager/action/updateLink';
            }

            var entity = this.scope;
            var entityForeign = this.model.get('entityForeign');
            var link = this.model.get('link');
            var linkForeign = this.model.get('linkForeign');
            var label = this.model.get('label');
            var labelForeign = this.model.get('labelForeign');
            var relationName = this.model.get('relationName');

            var linkMultipleField = this.model.get('linkMultipleField');
            var linkMultipleFieldForeign = this.model.get('linkMultipleFieldForeign');

            var audited = this.model.get('audited');
            var auditedForeign = this.model.get('auditedForeign');

            var attributes = {
                entity: entity,
                entityForeign: entityForeign,
                link: link,
                linkForeign: linkForeign,
                label: label,
                labelForeign: labelForeign,
                linkType: this.model.get('linkType'),
                relationName: relationName,
                linkMultipleField: linkMultipleField,
                linkMultipleFieldForeign: linkMultipleFieldForeign,
                audited: audited,
                auditedForeign: auditedForeign
            };

            if (!this.isNew) {
                if (attributes.label === this.model.fetchedAttributes.label) {
                    delete attributes.label;
                }
                if (attributes.labelForeign === this.model.fetchedAttributes.labelForeign) {
                    delete attributes.labelForeign;
                }
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: JSON.stringify(attributes),
                error: function (xhr) {
                    if (xhr.status == 409) {
                        var msg = this.translate('linkConflict', 'messages', 'EntityManager');
                        var statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');
                        if (statusReasonHeader) {
                            console.error(statusReasonHeader);
                        }
                        Espo.Ui.error(msg);
                        xhr.errorIsHandled = true;
                    }
                    this.$el.find('button[data-name="save"]').removeClass('disabled').removeAttr('disabled');
                }.bind(this)
            }).done(function () {
                if (!this.isNew) {
                    Espo.Ui.success(this.translate('Saved'));
                } else {
                    Espo.Ui.success(this.translate('Created'));
                }

                this.model.fetchedAttributes = this.model.getClonedAttributes();

                var data;

                data = ((this.getLanguage().data || {}) || {})[entity] || {};
                (data.fields || {})[link] = label;
                (data.links || {})[link] = label;

                data = ((this.getLanguage().data || {}) || {})[entityForeign];
                (data.fields || {})[linkForeign] = labelForeign;
                (data.links || {})[linkForeign] = labelForeign;

                this.getMetadata().load(function () {
                    this.trigger('after:save');
                    this.close();
                }.bind(this), true);
            }.bind(this));
        },

    });
});

