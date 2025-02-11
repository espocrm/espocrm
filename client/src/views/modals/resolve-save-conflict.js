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
import Select from 'ui/select';

class ResolveSaveConflictModalView extends ModalView {

    // language=Handlebars
    templateContent = `
        <div class="margin-bottom-3x">
            <p>{{translate 'resolveSaveConflict' category='messages'}}</p>
        </div>

        <div class="panel panel-default no-side-margin">
            <table class="table" style="table-layout: fixed;">
                <thead>
                <tr>
                    <th style="width: 25%">{{translate 'Field'}}</th>
                    <th style="width: 25%">{{translate 'Resolution'}}</th>
                    <th>{{translate 'Value'}}</th>
                </tr>
                </thead>
                <tbody>
                {{#each dataList}}
                    <tr>
                        <td class="cell cell-nowrap">
                    <span>
                        {{translate field category='fields' scope=../entityType}}
                    </span>
                        </td>
                        <td class="cell">
                            <select class="form-control" data-name="resolution" data-field="{{field}}">
                                {{options ../resolutionList resolution field='saveConflictResolution'}}
                            </select>
                        </td>
                        <td class="cell">
                            <div data-name="field" data-field="{{field}}">
                                {{{var viewKey ../this}}}
                            </div>
                        </td>
                    </tr>
                {{/each}}
                </tbody>
            </table>
        </div>
    `

    backdrop = true

    resolutionList = [
        'current',
        'actual',
        'original',
    ]

    defaultResolution = 'current'

    /**
     * @private
     * @type {string[]}
     */
    fieldList

    data() {
        const dataList = [];

        this.fieldList.forEach(item => {
            const o = {
                field: item,
                viewKey: item + 'Field',
                resolution: this.defaultResolution,
            };

            dataList.push(o);
        });

        return {
            dataList: dataList,
            entityType: this.entityType,
            resolutionList: this.resolutionList,
        };
    }

    setup() {
        this.headerText = this.translate('Resolve Conflict');

        this.buttonList = [
            {
                name: 'apply',
                label: 'Apply',
                style: 'danger',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        this.entityType = this.model.entityType;

        this.originalModel = this.model;

        this.originalAttributes = Espo.Utils.cloneDeep(this.options.originalAttributes);
        this.currentAttributes = Espo.Utils.cloneDeep(this.options.currentAttributes);
        this.actualAttributes = Espo.Utils.cloneDeep(this.options.actualAttributes);

        const attributeList = this.options.attributeList;

        const fieldList = [];

        this.getFieldManager()
            .getEntityTypeFieldList(this.entityType)
            .forEach(field => {
                const fieldAttributeList = this.getFieldManager()
                    .getEntityTypeFieldAttributeList(this.entityType, field);

                const intersect = attributeList.filter(value => fieldAttributeList.includes(value));

                if (intersect.length) {
                    fieldList.push(field);
                }
            });

        this.fieldList = fieldList;

        this.wait(
            this.getModelFactory().create(this.entityType)
                .then(model => {
                    this.model = model;

                    this.fieldList.forEach(field => {
                        this.setResolution(field, this.defaultResolution);
                    });

                    this.fieldList.forEach(field => {
                        this.createField(field);
                    });
                })
        );
    }

    setResolution(field, resolution) {
        const attributeList = this.getFieldManager()
            .getEntityTypeFieldAttributeList(this.entityType, field);

        const values = {};

        let source = this.currentAttributes;

        if (resolution === 'actual') {
            source = this.actualAttributes;
        }
        else if (resolution === 'original') {
            source = this.originalAttributes;
        }

        for (const attribute of attributeList) {
            values[attribute] = source[attribute] || null;
        }

        this.model.set(values);
    }

    createField(field) {
        const type = this.model.getFieldType(field);

        const viewName =
            this.model.getFieldParam(field, 'view') ||
            this.getFieldManager().getViewName(type);

        this.createView(field + 'Field', viewName, {
            readOnly: true,
            model: this.model,
            name: field,
            selector: `[data-name="field"][data-field="${field}"]`,
            mode: 'list',
        });
    }

    afterRender() {
        this.$el.find('[data-name="resolution"]').on('change', e => {
            const $el = $(e.currentTarget);

            const field = $el.attr('data-field');
            const resolution = $el.val();

            this.setResolution(field, resolution);
        });

        this.fieldList.forEach(field => {
            const selectElement = this.element.querySelector(`select[data-field="${field}"]`);

            Select.init(selectElement);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionApply() {
        const attributes = this.model.attributes;

        this.originalModel.set(attributes);

        this.trigger('resolve');
        this.close();
    }
}

export default ResolveSaveConflictModalView;
