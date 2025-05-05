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
import VarcharFieldView from 'views/fields/varchar';
import FieldAttributesFieldView from 'views/admin/field-manager/detail-fields/attributes';
import DetailRecordView from 'views/record/detail';
import BoolFieldView from 'views/fields/bool';

export default class ViewDetailsModalView extends ModalView {

    templateContent = `
        <div class="record-container no-side-margin">{{{record}}}</div>
    `

    backdrop = true

    /**
     * @private
     * @type {DetailRecordView}
     */
    recordView

    /**
     * @private
     * @type {Model}
     */
    formModel

    /**
     * @private
     * @type {string}
     */
    fieldType

    /**
     * @private
     * @type {{
     *     type?: string|undefined,
     *     notStorable?: boolean,
     * } & Record}
     */
    fieldDefs

    /**
     * @param {{
     *     field: string,
     *     entityType: string,
     * }} options
     */
    constructor(options) {
        super(options);

        this.field = options.field;
        this.entityType = options.entityType;
    }

    setup() {
        this.headerText =
            this.translate(this.entityType, 'scopeNames') + ' · ' +
            this.translate(this.field, 'fields', this.entityType);

        /** @type {{type: string|undefined}} */
        this.fieldDefs = this.getMetadata().get(`entityDefs.${this.entityType}.fields.${this.field}`) || {};

        this.fieldType = this.fieldDefs.type;

        this.formModel = new Model({
            name: this.field,
            attributes: this.getFieldAttributes(),
            readOnly: this.fieldDefs.readOnly || false,
            type: this.fieldDefs.type,
        });

        this.recordView = new DetailRecordView({
            model: this.formModel,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'name',
                                    labelText: this.translate('name', 'fields'),
                                }),
                            },
                            false
                        ],
                        [
                            {
                                view: new VarcharFieldView({
                                    name: 'type',
                                    labelText: this.translate('type', 'fields', 'Admin'),
                                }),
                            },
                            false
                        ],
                        [
                            {
                                view: new BoolFieldView({
                                    name: 'readOnly',
                                    labelText: this.translate('readOnly', 'fields', 'Admin'),
                                }),
                            },
                            false
                        ],
                        [
                            {
                                view: new FieldAttributesFieldView({
                                    name: 'attributes',
                                    labelText: this.translate('attributes', 'otherFields', 'FieldManager'),
                                }),
                            },
                        ],
                    ]
                }
            ],
            readOnly: true,
            sideView: null,
            isWide: true,
            buttonsDisabled: true,
        });

        this.assignView('record', this.recordView);


    }

    /**
     * @private
     * @return {{
     *     name: string,
     *     type: string,
     *     notStorable: boolean,
     *     readOnly: boolean,
     * }[]}
     */
    getFieldAttributes() {
        if (
            this.fieldType === 'link' ||
            this.fieldType === 'linkOne' ||
            this.fieldType === 'file' ||
            this.fieldType === 'image'
        ) {
            return [
                {
                    name: this.field + 'Id',
                    type: 'varchar',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Name',
                    type: 'varchar',
                    notStorable: true,
                    readOnly: true,
                },
            ]
        }

        if (this.fieldType === 'linkParent') {
            return [
                {
                    name: this.field + 'Id',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Type',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Name',
                    type: 'string',
                    notStorable: true,
                    readOnly: true,
                },
            ];
        }

        if (
            this.fieldType === 'linkMultiple' ||
            this.fieldType === 'attachmentMultiple'
        ) {
            const attributes = [
                {
                    name: this.field + 'Ids',
                    type: 'string[]',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Names',
                    type: 'Record.<string, string>',
                    notStorable: true,
                    readOnly: this.fieldDefs.readOnly || true,
                },
            ];

            if (this.fieldDefs.columns) {
                attributes.push({
                    name: this.field + 'Columns',
                    type: 'Record.<string, Record.<string, string>>',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                });
            }

            return attributes;
        }

        if (this.fieldType === 'currency') {
            return [
                {
                    name: this.field,
                    type: this.fieldDefs.decimal ? 'string' : 'float',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Currency',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false || this.fieldDefs.onlyDefaultCurrency,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'personName') {
            return [
                {
                    name: this.field,
                    type: 'string',
                    notStorable: true,
                    readOnly: true,
                },
                {
                    name: 'salutation' + Espo.Utils.upperCaseFirst(this.field),
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: 'first' + Espo.Utils.upperCaseFirst(this.field),
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: 'last' + Espo.Utils.upperCaseFirst(this.field),
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: 'middle' + Espo.Utils.upperCaseFirst(this.field),
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'address') {
            return [
                {
                    name: this.field,
                    type: 'string',
                    notStorable: true,
                    readOnly: true,
                },
                {
                    name: this.field + 'Street',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'City',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Country',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'State',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'PostalCode',
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (
            ['enum'].includes(this.fieldType)
        ) {
            let valueTypeString = 'string'

            if (this.fieldDefs.options && Array.isArray(this.fieldDefs.options)) {
                /** @type {string[]} */
                let options = this.fieldDefs.options;


                if (this.fieldDefs.required) {
                    options = options.filter(it => it !== '');
                }

                valueTypeString = options
                    .map(it => {
                        if (it === '') {
                            return 'null';
                        }

                        return `"${it}"`;
                    })
                    .join('|');
            }

            return [
                {
                    name: this.field,
                    type: valueTypeString,
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (
            [
                'varchar',
                'text',
                'wysiwyg',
                'colorpicker',
                'barcode',
                'url',
                'enum',
                'date',
                'datetime',
                'datetimeOptional',
            ].includes(this.fieldType)
        ) {
            return [
                {
                    name: this.field,
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'bool') {
            return [
                {
                    name: this.field,
                    type: 'boolean',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }


        if (this.fieldType === 'int' || this.fieldType === 'enumInt') {
            return [
                {
                    name: this.field,
                    type: 'int',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'float' || this.fieldType === 'enumFloat') {
            return [
                {
                    name: this.field,
                    type: 'float',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'autoincrement') {
            return [
                {
                    name: this.field,
                    type: 'int',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: true,
                },
            ];
        }

        if (this.fieldType === 'number') {
            return [
                {
                    name: this.field,
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: true,
                },
            ];
        }

        if (
            [
                'multiEnum',
                'checklist',
                'array',
                'urlMultiple'
            ].includes(this.fieldType)
        ) {
            return [
                {
                    name: this.field,
                    type: 'string[]',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'email') {
            return [
                {
                    name: this.field,
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Data',
                    type:
                        '{emailAddress: string, lower: string, primary: boolean, optOut: boolean, invalid: boolean}[]',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        if (this.fieldType === 'phone') {
            return [
                {
                    name: this.field,
                    type: 'string',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
                {
                    name: this.field + 'Data',
                    type: '{phoneNumber: string, primary: boolean, optOut: boolean, invalid: boolean}[]',
                    notStorable: this.fieldDefs.notStorable || false,
                    readOnly: this.fieldDefs.readOnly || false,
                },
            ];
        }

        return undefined;
    }
}
