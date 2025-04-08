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

import View from 'view';
import Model from 'model';

class FieldManagerEditView extends View {

    template = 'admin/field-manager/edit'

    paramWithTooltipList = [
        'audited',
        'required',
        'default',
        'min',
        'max',
        'maxLength',
        'after',
        'before',
        'readOnly',
        'readOnlyAfterCreate',
    ]

    /**
     * @type {{
     *     forbidden?: boolean,
     *     internal?: boolean,
     *     onlyAdmin?: boolean,
     *     readOnly?: boolean,
     *     nonAdminReadOnly?: boolean,
     * }|{}}
     */
    globalRestriction = null

    hasAnyGlobalRestriction = false

    /**
     * @readonly
     */
    globalRestrictionTypeList = [
        'forbidden',
        'internal',
        'onlyAdmin',
        'readOnly',
        'nonAdminReadOnly',
    ]

    /** @type {Model & {fetchedAttributes?: Record}}*/
    model
    /** @type {Record[]} */
    paramList

    data() {
        return {
            scope: this.scope,
            field: this.field,
            defs: this.defs,
            paramList: this.paramList,
            type: this.type,
            fieldList: this.fieldList,
            isCustom: this.defs.isCustom,
            isNew: this.isNew,
            hasDynamicLogicPanel: this.hasDynamicLogicPanel,
            hasResetToDefault: !this.defs.isCustom && !this.entityTypeIsCustom && !this.isNew,
        };
    }

    events = {
        /** @this FieldManagerEditView */
        'click button[data-action="close"]': function () {
            this.actionClose();
        },
        /** @this FieldManagerEditView */
        'click button[data-action="save"]': function () {
            this.save();
        },
        /** @this FieldManagerEditView */
        'click button[data-action="resetToDefault"]': function () {
            this.resetToDefault();
        },
        /** @this FieldManagerEditView */
        'keydown.form': function (e) {
            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (key === 'Control+KeyS' || key === 'Control+Enter') {
                this.save();

                e.preventDefault();
                e.stopPropagation();
            }
        },
    }

    setupFieldData(callback) {
        this.defs = {};
        this.fieldList = [];

        this.model = new Model();
        this.model.name = 'Admin';
        this.model.urlRoot = 'Admin/fieldManager/' + this.scope;

        this.model.defs = {
            fields: {
                name: {required: true, maxLength: 50},
                label: {required: true},
                tooltipText: {},
            }
        };

        this.entityTypeIsCustom = !!this.getMetadata().get(['scopes', this.scope, 'isCustom']);

        this.globalRestriction = {};

        if (!this.isNew) {
            this.model.id = this.field;
            this.model.scope = this.scope;

            this.model.set('name', this.field);
            this.model.set(
                'label',
                this.getLanguage().translate(this.field, 'fields', this.scope)
            );

            if (this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'tooltip'])) {
                this.model.set(
                    'tooltipText',
                    this.getLanguage().translate(this.field, 'tooltips', this.scope)
                );
            }

            this.globalRestriction = this.getMetadata().get(['entityAcl', this.scope, 'fields', this.field]) || {};

            const globalRestrictions = this.globalRestrictionTypeList.filter(item => this.globalRestriction[item]);

            if (globalRestrictions.length) {
                this.model.set('globalRestrictions', globalRestrictions);
                this.hasAnyGlobalRestriction = true;
            }
        }
        else {
            this.model.scope = this.scope;
            this.model.set('type', this.type);
        }

        this.listenTo(this.model, 'change:readOnly', () => {
            this.readOnlyControl();
        });

        let hasRequired = false;

        this.getModelFactory().create(this.scope, model => {
            if (!this.isNew) {
                this.type = model.getFieldType(this.field);
            }

            if (
                this.getMetadata().get(['scopes', this.scope, 'hasPersonalData']) &&
                this.getMetadata().get(['fields', this.type, 'personalData'])
            ) {
                this.hasPersonalData = true;
            }

            this.hasInlineEditDisabled = !['foreign', 'autoincrement'].includes(this.type) &&
                !this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field,
                        'customizationInlineEditDisabledDisabled']);

            this.hasTooltipText = !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field,
                'customizationTooltipTextDisabled']);

            new Promise(resolve => {
                if (this.isNew) {
                    resolve();

                    return;
                }

                Espo.Ajax.getRequest('Admin/fieldManager/' + this.scope + '/' + this.field)
                    .then(data => {
                        this.defs = data;

                        resolve();
                    });
            })
            .then(() => {
                const promiseList = [];
                this.paramList = [];
                const paramList = Espo.Utils.clone(this.getFieldManager().getParamList(this.type) || []);

                if (!this.isNew) {
                    const fieldManagerAdditionalParamList =
                        this.getMetadata()
                            .get([
                                'entityDefs', this.scope, 'fields',
                                this.field, 'fieldManagerAdditionalParamList'
                            ]) || [];

                    fieldManagerAdditionalParamList.forEach((item) =>  {
                        paramList.push(item);
                    });
                }

                /** @var {string[]|null} */
                const fieldManagerParamList = this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'fieldManagerParamList']);

                paramList.forEach(o => {
                    const item = o.name;

                    if (fieldManagerParamList && fieldManagerParamList.indexOf(item) === -1) {
                        return;
                    }

                    if (
                        item === 'readOnly' &&
                        this.globalRestriction &&
                        this.globalRestriction.readOnly
                    ) {
                        return;
                    }

                    if (item === 'required') {
                        hasRequired = true;
                    }

                    if (
                        item === 'createButton' &&
                        ['assignedUser', 'assignedUsers', 'teams', 'collaborators'].includes(this.field)
                    ) {
                        return;
                    }

                    if (
                        item === 'autocompleteOnEmpty' &&
                        ['assignedUser'].includes(this.field)
                    ) {
                        return;
                    }

                    const disableParamName = 'customization' + Espo.Utils.upperCaseFirst(item) + 'Disabled';

                    const isDisabled =
                        this.getMetadata()
                            .get(['entityDefs', this.scope, 'fields', this.field, disableParamName]);

                    if (isDisabled) {
                        return;
                    }

                    const viewParamName = 'customization' + Espo.Utils.upperCaseFirst(item) + 'View';

                    const view = this.getMetadata()
                        .get(['entityDefs', this.scope, 'fields', this.field, viewParamName]);

                    if (view) {
                        o.view = view;
                    }

                    this.paramList.push(o);
                });

                if (this.hasPersonalData) {
                    this.paramList.push({
                        name: 'isPersonalData',
                        type: 'bool',
                    });
                }

                if (
                    this.hasInlineEditDisabled &&
                    !this.globalRestriction.readOnly
                ) {
                    this.paramList.push({
                        name: 'inlineEditDisabled',
                        type: 'bool',
                    });
                }

                if (this.hasTooltipText) {
                    this.paramList.push({
                        name: 'tooltipText',
                        type: 'text',
                        rowsMin: 1,
                        trim: true,
                    });
                }

                if (fieldManagerParamList) {
                    this.paramList = this.paramList
                        .filter(item => fieldManagerParamList.indexOf(item.name) !== -1);
                }

                this.paramList = this.paramList
                    .filter(item => {
                        return !(this.globalRestriction.readOnly && item.name === 'required');
                    });

                const customizationDisabled = this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'customizationDisabled']);

                if (
                    customizationDisabled ||
                    this.globalRestriction.forbidden
                ) {
                    this.paramList = [];
                }

                if (this.hasAnyGlobalRestriction) {
                    this.paramList.push({
                        name: 'globalRestrictions',
                        type: 'array',
                        readOnly: true,
                        displayAsList: true,
                        translation: 'FieldManager.options.globalRestrictions',
                        options: this.globalRestrictionTypeList,
                    });
                }

                this.paramList.forEach(o => {
                    this.model.defs.fields[o.name] = o;
                });

                this.model.set(this.defs);

                if (this.isNew) {
                    this.model.populateDefaults();
                }

                promiseList.push(
                    this.createFieldView('varchar', 'name', !this.isNew, {trim: true})
                );

                promiseList.push(
                    this.createFieldView('varchar', 'label', null, {trim: true})
                );

                this.hasDynamicLogicPanel = false;

                promiseList.push(
                    this.setupDynamicLogicFields(hasRequired)
                );

                this.model.fetchedAttributes = this.model.getClonedAttributes();

                this.paramList.forEach(o => {
                    if (o.hidden) {
                        return;
                    }

                    const options = {};

                    if (o.tooltip || ~this.paramWithTooltipList.indexOf(o.name)) {
                        options.tooltip = true;

                        let tooltip = o.name;

                        if (typeof o.tooltip === 'string') {
                            tooltip = o.tooltip;
                        }

                        options.tooltipText = this.translate(tooltip, 'tooltips', 'FieldManager');
                    }

                    if (o.readOnlyNotNew && !this.isNew) {
                        options.readOnly = true;
                    }

                    promiseList.push(
                        this.createFieldView(o.type, o.name, null, o, options)
                    );
                });

                Promise.all(promiseList).then(() => callback());
            });
        });

        this.listenTo(this.model, 'change', (m, o) => {
            if (!o.ui) {
                return;
            }

            this.setIsChanged();
        });
    }

    setup() {
        this.scope = this.options.scope;
        this.field = this.options.field;
        this.type = this.options.type;

        this.isNew = !this.field;

        if (
            !this.getMetadata().get(['scopes', this.scope, 'customizable']) ||
            this.getMetadata().get(`scopes.${this.scope}.entityManager.fields`) === false ||
            (
                this.field &&
                this.getMetadata().get(`entityDefs.${this.scope}.fields.${this.field}.customizationDisabled`)
            )
        ) {
            Espo.Ui.notify(false);

            throw new Espo.Exceptions.NotFound("Entity type is not customizable.");
        }

        this.wait(true);

        this.setupFieldData(() => {
            this.wait(false);
        });
    }

    setupDynamicLogicFields(hasRequired) {
        const defs = /** @type {Record}*/
            this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field]) || {};

        if (
            defs.disabled ||
            defs.dynamicLogicDisabled ||
            defs.layoutDetailDisabled ||
            defs.utility
        ) {
            return Promise.resolve();
        }

        const promiseList = [];

        if (!defs.dynamicLogicVisibleDisabled) {
            const isVisible =
                this.getMetadata().get(['logicDefs', this.scope, 'fields', this.field, 'visible']);

            this.model.set(
                'dynamicLogicVisible',
                isVisible
            );

            promiseList.push(
                this.createFieldView(null, 'dynamicLogicVisible', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope
                })
            );

            this.hasDynamicLogicPanel = true;
        }

        const readOnly = this.getMetadata().get(['fields', this.type, 'readOnly']);

        if (!defs.dynamicLogicRequiredDisabled && !readOnly && hasRequired) {
            const dynamicLogicRequired =
                this.getMetadata().get(['logicDefs', this.scope, 'fields', this.field, 'required']);

            this.model.set('dynamicLogicRequired', dynamicLogicRequired);

            promiseList.push(
                this.createFieldView(null, 'dynamicLogicRequired', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                })
            );

            this.hasDynamicLogicPanel = true;
        }

        if (!defs.dynamicLogicReadOnlyDisabled && !readOnly) {
            const dynamicLogicReadOnly = this.getMetadata()
                .get(['logicDefs', this.scope, 'fields', this.field, 'readOnly']);

            this.model.set('dynamicLogicReadOnly', dynamicLogicReadOnly);

            promiseList.push(
                this.createFieldView(null, 'dynamicLogicReadOnly', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                })
            );

            this.hasDynamicLogicPanel = true;
        }

        const typeDynamicLogicOptions = this.getMetadata().get(['fields', this.type, 'dynamicLogicOptions']);

        if (typeDynamicLogicOptions && !defs.dynamicLogicOptionsDisabled) {
            const dynamicLogicOptions =
                this.getMetadata().get(['logicDefs', this.scope, 'options', this.field]);

            this.model.set('dynamicLogicOptions', dynamicLogicOptions);

            promiseList.push(
                this.createFieldView(null, 'dynamicLogicOptions', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-options',
                    scope: this.scope,
                })
            );

            this.hasDynamicLogicPanel = true;
        }

        if (!defs.dynamicLogicInvalidDisabled && !readOnly) {
            const dynamicLogicInvalid =
                this.getMetadata().get(['logicDefs', this.scope, 'fields', this.field, 'invalid']);

            this.model.set('dynamicLogicInvalid', dynamicLogicInvalid);

            promiseList.push(
                this.createFieldView(null, 'dynamicLogicInvalid', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                })
            );

            this.hasDynamicLogicPanel = true;
        }

        if (!defs.dynamicLogicReadOnlySavedDisabled && !readOnly) {
            const dynamicLogicReadOnlySaved = this.getMetadata()
                .get(['logicDefs', this.scope, 'fields', this.field, 'readOnlySaved']);

            this.model.set('dynamicLogicReadOnlySaved', dynamicLogicReadOnlySaved);

            promiseList.push(
                this.createFieldView(null, 'dynamicLogicReadOnlySaved', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                })
            );

            this.hasDynamicLogicPanel = true;
        }

        return Promise.all(promiseList);
    }

    afterRender() {
        this.getView('name').on('change', () => {
            let name = this.model.get('name');

            let label = name;

            if (label.length) {
                 label = label.charAt(0).toUpperCase() + label.slice(1);
            }

            this.model.set('label', label);

            if (name) {
                name = name
                    .replace(/-/g, '')
                    .replace(/_/g, '')
                    .replace(/[^\w\s]/gi, '')
                    .replace(/ (.)/g, (match, g) => {
                        return g.toUpperCase();
                    })
                    .replace(' ', '');

                if (name.length) {
                     name = name.charAt(0).toLowerCase() + name.slice(1);
                }
            }

            this.model.set('name', name);
        });
    }

    readOnlyControl() {
        if (this.model.get('readOnly')) {
            this.hideField('dynamicLogicReadOnly');
            this.hideField('dynamicLogicRequired');
            this.hideField('dynamicLogicOptions');
            this.hideField('dynamicLogicInvalid');
            this.hideField('dynamicLogicPreSave');
        }
        else {
            this.showField('dynamicLogicReadOnly');
            this.showField('dynamicLogicRequired');
            this.showField('dynamicLogicOptions');
            this.showField('dynamicLogicInvalid');
            this.showField('dynamicLogicPreSave');
        }
    }

    hideField(name) {
        const f = () => {
            const view = /** @type {import('views/fields/base').default} */
                this.getView(name);

            if (view) {
                this.$el.find('.cell[data-name="' + name + '"]').addClass('hidden');

                view.setDisabled();
            }
        };

        if (this.isRendered()) {
            f();
        }
        else {
            this.once('after:render', f);
        }
    }

    showField(name) {
        const f = () => {
            const view = /** @type {import('views/fields/base').default} */
                this.getView(name);

            if (view) {
                this.$el.find('.cell[data-name="' + name + '"]').removeClass('hidden');

                view.setNotDisabled();
            }
        };

        if (this.isRendered()) {
            f();
        }
        else {
            this.once('after:render', f);
        }
    }

    createFieldView(type, name, readOnly, params, options, callback) {
        const viewName = (params || {}).view || this.getFieldManager().getViewName(type);

        const o = {
            model: this.model,
            selector: `.field[data-name="${name}"]`,
            defs: {
                name: name,
                params: params
            },
            mode: readOnly ? 'detail' : 'edit',
            readOnly: readOnly,
            scope: this.scope,
            field: this.field,
        };

        _.extend(o, options || {});

        const promise = this.createView(name, viewName, o, callback);

        this.fieldList.push(name);

        return promise;
    }

    disableButtons() {
        this.$el.find('[data-action="save"]').attr('disabled', 'disabled').addClass('disabled');
        this.$el.find('[data-action="resetToDefault"]').attr('disabled', 'disabled').addClass('disabled');
    }

    enableButtons() {
        this.$el.find('[data-action="save"]').removeAttr('disabled').removeClass('disabled');
        this.$el.find('[data-action="resetToDefault"]').removeAttr('disabled').removeClass('disabled');
    }

    save() {
        this.disableButtons();

        this.fieldList.forEach(field => {
            const view = /** @type {import('views/fields/base').default} */
                this.getView(field);

            if (!view.readOnly) {
                view.fetchToModel();
            }
        });

        let notValid = false;

        this.fieldList.forEach(field => {
            const view = /** @type {import('views/fields/base').default} */
                this.getView(field);

            notValid = view.validate() || notValid;
        });

        if (notValid) {
            Espo.Ui.error(this.translate('Not valid'));

            this.enableButtons();

            return;
        }

        if (this.model.get('tooltipText') && this.model.get('tooltipText') !== '') {
            this.model.set('tooltip', true);
        }
        else {
            this.model.set('tooltip', false);
        }

        const onSave = () => {
            Espo.Ui.notify(false);

            this.setIsNotChanged();
            this.enableButtons();

            Promise.all([
                this.getMetadata().loadSkipCache(),
                this.getLanguage().loadSkipCache(),
            ]).then(() => this.trigger('after:save'));

            this.model.fetchedAttributes = this.model.getClonedAttributes();

            this.broadcastUpdate();
        };

        Espo.Ui.notifyWait();

        if (this.isNew) {
            this.model
                .save()
                .then(() => onSave())
                .catch(() => this.enableButtons());

            return;
        }

        const attributes = this.model.getClonedAttributes();

        if (this.model.fetchedAttributes.label === attributes.label) {
            delete attributes.label;
        }

        if (
            this.model.fetchedAttributes.tooltipText === attributes.tooltipText ||
            !this.model.fetchedAttributes.tooltipText && !attributes.tooltipText
        ) {
            delete attributes.tooltipText;
        }

        if ('translatedOptions' in attributes) {
            if (_.isEqual(this.model.fetchedAttributes.translatedOptions, attributes.translatedOptions)) {
                delete attributes.translatedOptions;
            }
        }

        this.model
            .save(attributes, {patch: true})
            .then(() => onSave())
            .catch(() => this.enableButtons());
    }

    resetToDefault() {
        this.confirm(this.translate('confirmation', 'messages'), () => {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax.postRequest('FieldManager/action/resetToDefault', {
                scope: this.scope,
                name: this.field,
            }).then(() => {
                Promise
                .all([
                    this.getMetadata().loadSkipCache(),
                    this.getLanguage().loadSkipCache(),
                ])
                .then(() => {
                    this.setIsNotChanged();

                    this.setupFieldData(() => {
                        Espo.Ui.success(this.translate('Done'));

                        this.reRender();
                        this.broadcastUpdate();
                    });
                });
            });
        });
    }

    broadcastUpdate() {
        this.getHelper().broadcastChannel.postMessage('update:metadata');
        this.getHelper().broadcastChannel.postMessage('update:language');
        this.getHelper().broadcastChannel.postMessage('update:settings');
    }

    actionClose() {
        this.setIsNotChanged();

        this.getRouter().navigate('#Admin/fieldManager/scope=' + this.scope, {trigger: true});
    }

    setConfirmLeaveOut(value) {
        this.getRouter().confirmLeaveOut = value;
    }

    setIsChanged() {
        this.isChanged = true;
        this.setConfirmLeaveOut(true);
    }

    setIsNotChanged() {
        this.isChanged = false;
        this.setConfirmLeaveOut(false);
    }
}

export default FieldManagerEditView;
