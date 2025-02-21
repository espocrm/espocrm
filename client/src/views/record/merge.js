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
import $ from 'jquery';

class MergeRecordView extends View {

    template = 'record/merge'

    scope = ''

    events = {
        /** @this MergeRecordView */
        'change input[type="radio"][name="check-all"]': function (e) {
            e.stopPropagation();

            const id = e.currentTarget.value;

            $('input[data-id="' + id + '"]').prop('checked', true);
        },
        /** @this MergeRecordView */
        'click button[data-action="cancel"]': function () {
            this.getRouter().navigate('#' + this.scope, {trigger: true});
        },
        /** @this MergeRecordView */
        'click button[data-action="merge"]': function () {
            const id = $('input[type="radio"][name="check-all"]:checked').val();

            let model;

            this.models.forEach(m => {
                if (m.id === id) {
                    model = m;
                }
            });

            const attributes = {};

            $('input.field-radio:checked').each((i, el) => {
                const field = el.name;
                const id = $(el).attr('data-id');

                if (model.id === id) {
                    return;
                }

                const fieldType = model.getFieldParam(field, 'type');
                const fields = this.getFieldManager().getActualAttributeList(fieldType, field);

                let modelFrom;

                this.models.forEach(itemModel => {
                    if (itemModel.id === id) {
                        modelFrom = itemModel;
                    }
                });

                fields.forEach(field => {
                    attributes[field] = modelFrom.get(field);
                });
            });

            Espo.Ui.notifyWait();

            const sourceIdList = this.models
                .filter(m => m.id !== model.id)
                .map(m => m.id);

            Espo.Ajax
                .postRequest('Action', {
                    entityType: this.scope,
                    action: 'merge',
                    id: model.id,
                    data: {
                        sourceIdList: sourceIdList,
                        attributes: attributes,
                    },
                })
                .then(() => {
                    Espo.Ui.success(this.translate('Merged'), {suppress: true});

                    this.getRouter().navigate('#' + this.scope + '/view/' + model.id, {trigger: true});

                    if (this.collection) {
                        this.collection.fetch();
                    }
                });
        }
    }

    data() {
        const rows = [];

        this.fields.forEach(field => {
            const o = {
                name: field,
                scope: this.scope,
            };

            o.columns = [];

            this.models.forEach(model => {
                o.columns.push({
                    id: model.id,
                    fieldVariable: model.id + '-' + field,
                    isReadOnly: this.readOnlyFields[field] || false,
                });
            });

            rows.push(o);
        });

        return {
            rows: rows,
            modelList: this.models,
            scope: this.scope,
            hasCreatedAt: this.hasCreatedAt,
            width: Math.round(((80 - this.models.length * 5) / this.models.length * 10)) / 10,
            dataList: this.getDataList(),
        };
    }

    afterRender() {
        $('input[data-id="' + this.models[0].id + '"]').prop('checked', true);
    }

    setup() {
        this.scope = this.options.models[0].name;
        this.models = this.options.models;

        const fieldManager = this.getFieldManager();

        let differentFieldList = [];
        const fieldsDefs = this.models[0].defs.fields;

        this.readOnlyFields = {};

        for (const field in fieldsDefs) {
            const type = fieldsDefs[field].type;

            if (type === 'linkMultiple') {
                continue;
            }

            if (
                fieldsDefs[field].disabled ||
                fieldsDefs[field].utility ||
                fieldsDefs[field].mergeDisabled
            ) {
                continue;
            }

            if (
                field === 'createdAt' ||
                field === 'modifiedAt'
            ) {
                continue;
            }

            if (fieldManager.isMergeable(type)) {
                const actualAttributeList = fieldManager.getActualAttributeList(type, field);

                let differs = false;

                actualAttributeList.forEach(field => {
                    const values = [];

                    this.models.forEach(model => {
                        values.push(model.get(field));
                    });

                    const firstValue = values[0];

                    values.forEach(value => {
                        if (!_.isEqual(firstValue, value)) {
                            differs = true;
                        }
                    });
                });

                if (differs) {
                    differentFieldList.push(field);

                    if (this.models[0].isFieldReadOnly(field)) {
                        this.readOnlyFields[field] = true;
                    }
                }
            }
        }

        differentFieldList.sort((v1, v2) => {
            return this.translate(v1, 'fields', this.scope)
                .localeCompare(this.translate(v2, 'fields', this.scope));
        });

        differentFieldList = differentFieldList.sort((v1, v2) => {
            if (!this.readOnlyFields[v1] && this.readOnlyFields[v2]) {
                return -1;
            }

            return 1;
        });

        this.fields = differentFieldList;

        this.fields.forEach(field => {
            const type = this.models[0].getFieldParam(field, 'type');

            this.models.forEach(model => {
                const viewName =
                    model.getFieldParam(field, 'view') ||
                    this.getFieldManager().getViewName(type);

                this.createView(model.id + '-' + field, viewName, {
                    model: model,
                    fullSelector: '.merge [data-id="' + model.id + '"] .field[data-name="' + field + '"]',
                    defs: {
                        name: field,
                    },
                    mode: 'detail',
                    readOnly: true,
                });
            });
        });

        this.hasCreatedAt = this.getMetadata().get(['entityDefs', this.scope, 'fields', 'createdAt']);

        if (this.hasCreatedAt) {
            this.models.forEach(model => {
                this.createView(model.id + '-' + 'createdAt', 'views/fields/datetime', {
                    model: model,
                    fullSelector: '.merge [data-id="' + model.id + '"] .field[data-name="createdAt"]',
                    defs: {
                        name: 'createdAt',
                    },
                    mode: 'detail',
                    readOnly: true,
                });
            });
        }
    }

    getDataList() {
        const dataList = [];

        this.models.forEach(model => {
            const o = {};

            o.id = model.id;
            o.name = model.get('name');
            o.createdAtViewName = model.id + '-' + 'createdAt';

            dataList.push(o);
        });

        return dataList;
    }
}

export default MergeRecordView;
