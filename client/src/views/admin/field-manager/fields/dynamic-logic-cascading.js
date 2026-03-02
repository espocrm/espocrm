/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import View from 'view';
import Model from 'model';
import Collection from 'collection';
import ModalView from 'views/modal';
import EditForModalRecordView from 'views/record/edit-for-modal';
import EnumFieldView from 'views/fields/enum';
import BoolFieldView from 'views/fields/bool';

// noinspection JSUnusedGlobalSymbols
export default class DynamicLogicCascadingFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#if ids.length}}
            <table class="table" data-role="cascading-items-list">
                {{#each ids}}
                    <tr data-id="{{id}}"><td>{{{lookup ../this this}}}</td></div>
                {{/each}}
            </table>
        {{/if}}

        {{#unless ids.length}}
            {{#if isSet}}
                <span class="none-value">{{translate 'None'}}</span>
            {{else}}
                <span class="loading-value"></span>
            {{/if}}
        {{/unless}}
    `

    // language=Handlebars
    editTemplateContent = `
        <style>
            table[data-role="cascading-items-list"] {
                border-top: 0;

                > thead > tr > th {
                    border-top: 0;
                }

                font-size: var(--13px);
            }
        </style>
        {{#if ids.length}}
            <table class="table" data-role="cascading-items-list">
                <thead>
                    <tr>
                        <th>
                            <div class="row small text-muted">
                                <div class="col-md-5">{{localFieldLabel}}</div>
                                <div class="col-md-5">{{foreignFieldLabel}}</div>
                            </div>
                        </th>
                    </tr>
                </thead>
                {{#each ids}}
                    <tr data-id="{{id}}"><td>{{{lookup ../this this}}}</td></div>
                {{/each}}
            </table>
        {{/if}}

        <div>
            <button
                class="btn btn-link btn-icon"
                data-action="addRow"
                title="{{translate 'Add'}}"
            ><span class="fas fa-plus"></span></button>
        </div>
    `

    /**
     * @private
     * @type {import('collection').default}
     */
    itemCollection

    /**
     * @private
     * @type {ItemView[]}
     */
    itemViews

    /**
     * @private
     * @type {string}
     */
    foreignScope

    data() {
        return {
            isSet: this.subModel.has('items'),
            ids: this.itemCollection.models.map(m => m.id),
            localFieldLabel: this.translate('localField', 'fields', 'DynamicLogic'),
            foreignFieldLabel: this.translate('foreignField', 'fields', 'DynamicLogic'),
        };
    }

    /**
     * Prevents the change event from firing on sub-field change.
     */
    initElement() {}

    setup() {
        this.addActionHandler('addRow', () => this.addItem());

        this.subModel = new Model();

        this.foreignScope = this.params.foreignScope;

        const syncModels = () => {
            const value = this.model.attributes[this.name];

            if (value !== undefined) {
                const items = value?.items ?? [];

                this.subModel.set('items', Espo.Utils.cloneDeep(items));
            } else {
                this.subModel.unset('items');
            }
        };

        syncModels();

        this.listenTo(this.model, 'change:' + this.name, (m, v, o) => {
            if (o.fromView !== this) {
                syncModels();
            }

            this.listenTo(this.subModel, 'change', (m, o) => {
                if (o.ui) {
                    this.trigger('change');
                }
            });
        });
    }

    async prepare() {
        this.destroyItemViews();

        this.itemCollection = new Collection();

        const items = this.getItemsFromModel();

        if (items === undefined) {
            return;
        }

        let mode = this.mode;

        if (mode !== 'detail' && mode !== 'edit') {
            mode = 'detail';
        }

        const promiseList = [];
        this.itemViews = [];

        for (const [i, item] of items.entries()) {
            const model = new Model({...item, id: i.toString()});

            this.listenTo(model, 'change', (m, /** Record */o) => {
                if (o.ui) {
                    this.model.setMultiple({[this.name]: {items: this.getItemsFromCollection()}}, {ui: true});
                }
            });

            this.itemCollection.push(model);

            const view = new ItemView({
                model: model,
                mode: mode,
                onRemove: () => this.removeRow(i),
                scope: this.params.scope,
                foreignScope: this.foreignScope,
            });

            this.itemViews.push(view);

            const promise = this.assignView(view.model.id, view, `[data-id="${view.model.id}"]`);

            promiseList.push(promise);
        }

        await Promise.all(promiseList);
    }

    /**
     * @private
     */
    destroyItemViews() {
        this.itemViews = [];

        if (this.itemCollection) {
            this.itemCollection.models.forEach(model => this.clearView(model.id));
        }
    }

    /**
     * @private
     * @return {{
     *     localField: string,
     *     foreignField: string,
     *     matchRequired: boolean,
     * }[]|undefined}
     */
    getItemsFromModel() {
        return Espo.Utils.cloneDeep(this.model.attributes?.[this.name]?.items ?? undefined);
    }

    /**
     * @private
     * @return {{
     *     localField: string,
     *     foreignField: string,
     *     matchRequired: boolean,
     * }[]}
     */
    getItemsFromCollection() {
        return this.itemCollection.models.map(item => {
            return {
                localField: item.attributes.localField,
                foreignField: item.attributes.foreignField,
                matchRequired: item.attributes.matchRequired,
            };
        });
    }

    /**
     * @private
     */
    async addItem() {
        const view = new AddItemView({
            scope: this.params.scope,
            foreignScope: this.foreignScope,
            onApply: item => this.addRow(item),
        });

        await this.assignView('modal', view);
        await view.render();
    }

    /**
     * @private
     * @param {{
     *     localField: string,
     *     foreignField: string,
     *     matchRequired: boolean,
     * }} item
     */
    async addRow(item) {
        const items = this.getItemsFromModel() ?? [];

        items.push(Espo.Utils.cloneDeep(item));

        this.model.setMultiple({[this.name]: {items}}, {ui: true});

        await this.prepare();
        await this.reRender();
    }

    /**
     * @private
     * @param {number} index
     */
    async removeRow(index) {
        const items = this.getItemsFromModel() || [];

        items.splice(index, 1);

        this.model.setMultiple({[this.name]: {items}}, {ui: true});

        await this.prepare();
        await this.reRender();
    }

    fetch() {
        if (!this.itemCollection) {
            return {[this.name]: null};
        }

        const items = this.getItemsFromCollection().map(item => {
            return {
                localField: item.localField,
                foreignField: item.foreignField,
                matchRequired: item.matchRequired,
            };
        });

        if (!items.length) {
            return {[this.name]: null};
        }

        return {[this.name]: {items}};
    }
}

class ItemView extends View {

    // language=Handlebars
    templateContent = `
        <style></style>
        <div class="row">
            <div class=" {{columnClassName}} ">{{localField}}</div>
            <div class=" {{columnClassName}} ">{{foreignField}}</div>
            <div class="col-md-1 ">{{#if matchRequired}}*{{/if}}</div>
            {{#if isEditMode}}
                <div class="col-md-1" style="text-align: center;">
                    <a
                        role="button"
                        data-action="removeRow"
                        class="pull-right"
                        title="{{translate 'Remove'}}"
                    ><span class="fas fa-times"></span></a>
                </div>
            {{/if}}
        </div>
    `

    /**
     * @type {import('model').default}
     */
    model

    /**
     * @type {'detail'|'edit'}
     */
    mode

    /**
     * @param {{
     *     model: import('model').default,
     *     mode: 'detail'|'edit',
     *     onRemove: function(),
     *     scope: string,
     *     foreignScope: string,
     * }} options
     */
    constructor(options) {
        super();

        this.model = options.model;
        this.mode = options.mode;

        this.options = options;
    }

    data() {

        return {
            isEditMode: this.mode === 'edit',
            columnClassName: this.mode === 'edit' ? 'col-md-5' : 'col-md-5',
            localField: this.translate(this.model.attributes.localField, 'fields', this.options.scope),
            foreignField: this.translate(this.model.attributes.foreignField, 'fields', this.options.foreignScope),
            matchRequired: this.model.attributes.matchRequired,
        };
    }

    setup() {
        this.addActionHandler('removeRow', () => this.options.onRemove());
    }
}

class AddItemView extends ModalView {

    templateContent = `
        <div class="record no-side-margin">{{{record}}}</div>
    `

    /**
     * @param {{
     *     scope: string,
     *     foreignScope: string,
     *     onApply: function({
     *         localField: string,
     *         foreignField: string,
     *         matchRequired: boolean,
     *     }),
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;
    }

    setup() {
        super.setup();

        this.headerText = this.translate('Add');

        this.buttonList = [
            {
                name: 'apply',
                label: 'Apply',
                style: 'primary',
                onClick: () => apply(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.close(),
            }
        ];

        const model = new Model({
            localField: null,
            foreignField: null,
            matchRequired: false,
        });

        const scope = this.options.scope;
        const foreignScope = this.options.foreignScope;

        const localFieldDefs = this.getMetadata().get(`entityDefs.${scope}.fields`) ?? {};
        const foreignFieldDefs = this.getMetadata().get(`entityDefs.${foreignScope}.fields`) ?? {};

        const localFields = Object.keys(localFieldDefs)
            .filter(field => {
                /** @type {Record} */
                const defs = localFieldDefs[field];

                if (defs.utility || defs.disabled) {
                    return false;
                }

                if (!['link', 'linkParent', 'linkOne', 'linkMultiple'].includes(defs.type)) {
                    return false;
                }

                return true;
            });

        const foreignFields = Object.keys(foreignFieldDefs)
            .filter(field => {
                /** @type {Record} */
                const defs = foreignFieldDefs[field];

                if (defs.utility || defs.disabled) {
                    return false;
                }

                if (!['link', 'linkOne', 'linkMultiple'].includes(defs.type)) {
                    return false;
                }

                return true;
            });

        localFields.unshift('');
        foreignFields.unshift('');

        const apply = () => {
            if (recordView.validate()) {
                return;
            }

            this.options.onApply({
                localField: model.attributes.localField,
                foreignField: model.attributes.foreignField,
                matchRequired: model.attributes.matchRequired,
            });

            this.close();
        }

        const recordView = new EditForModalRecordView({
            model,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new EnumFieldView({
                                    name: 'localField',
                                    params: {
                                        required: true,
                                        options: localFields,
                                        isSorted: true,
                                    },
                                    translatedOptions: localFields.reduce((o, it) => {
                                        o[it] = this.translate(it, 'fields', this.options.scope);

                                        return o;
                                    }, {}),
                                    labelText: this.translate('localField', 'fields', 'DynamicLogic'),
                                })
                            },
                            {
                                view: new EnumFieldView({
                                    name: 'foreignField',
                                    params: {
                                        required: true,
                                    },
                                    translatedOptions: foreignFields.reduce((o, it) => {
                                        o[it] = this.translate(it, 'fields', this.options.foreignScope);

                                        return o;
                                    }, {}),
                                    labelText: this.translate('foreignField', 'fields', 'DynamicLogic'),
                                })
                            },
                        ],
                        [
                            {
                                view: new BoolFieldView({
                                    name: 'matchRequired',
                                    labelText: this.translate('matchRequired', 'fields', 'DynamicLogic'),
                                })
                            },
                            false
                        ]
                    ]
                }
            ],
        });

        this.assignView('record', recordView);

        this.listenTo(model, 'change:localField', (m, v, /** Record */ o) => {
            if (!o.ui) {
                return;
            }

            const localField = model.attributes.localField;

            const fields = foreignFields.filter(it => {
                if (it === '') {
                    return true;
                }

                if (!localField) {
                    return false;
                }

                const type = this.getMetadata().get(`entityDefs.${scope}.fields.${localField}.type`);
                const entityType = this.getMetadata().get(`entityDefs.${scope}.links.${localField}.entity`);
                const foreignEntityType = this.getMetadata().get(`entityDefs.${foreignScope}.links.${it}.entity`);

                if (type === 'linkParent') {
                    return true;
                }

                return entityType === foreignEntityType;
            });

            recordView.setFieldOptionList('foreignField', fields);

            setTimeout(() => {
                model.set('foreignField', null);
            }, 0)
        });
    }
}
