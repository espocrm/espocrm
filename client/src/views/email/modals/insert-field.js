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
import FieldLanguage from 'helpers/misc/field-language';

export default class extends ModalView {

    backdrop = true

    // language=Handlebars
    templateContent = `
        {{#each viewObject.dataList}}
            <div class="margin-bottom">
            <h5>{{label}}: {{translate entityType category='scopeNames'}}</h5>
            </div>
            <ul class="list-group no-side-margin">
                {{#each dataList}}
                    <li class="list-group-item clearfix">
                        <a role="button"
                            data-action="insert" class="text-bold" data-name="{{name}}" data-type="{{../type}}">
                            {{label}}
                        </a>

                        <div class="pull-right"
                            style="width: 50%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                            {{valuePreview}}
                        </div>
                    </li>
                {{/each}}
            </ul>
        {{/each}}

        {{#unless viewObject.dataList.length}}
            {{translate 'No Data'}}
        {{/unless}}
    `

    setup() {
        super.setup();

        this.headerText = this.translate('Insert Field', 'labels', 'Email');

        this.fieldLanguage = new FieldLanguage(this.getMetadata(), this.getLanguage());

        this.wait(
            Espo.Ajax
                .getRequest('Email/insertFieldData', {
                    parentId: this.options.parentId,
                    parentType: this.options.parentType,
                    to: this.options.to,
                })
                .then(fetchedData => {
                    this.fetchedData = fetchedData;
                    this.prepareData();
                })
        );

        this.addActionHandler('insert', (e, target) => {
            const name = target.dataset.name;
            const type = target.dataset.type;

            this.insert(type, name);
        })
    }

    prepareData() {
        this.dataList = [];

        const fetchedData = this.fetchedData;
        const typeList = ['parent', 'to'];

        typeList.forEach(type => {
            if (!fetchedData[type]) {
                return;
            }

            const entityType = fetchedData[type].entityType;
            const id = fetchedData[type].id;

            for (const it of this.dataList) {
                if (it.id === id && it.entityType === entityType) {
                    return;
                }
            }

            const dataList = this.prepareDisplayValueList(fetchedData[type].entityType, fetchedData[type].values);

            if (!dataList.length) {
                return;
            }

            this.dataList.push({
                type: type,
                entityType: entityType,
                id: id,
                name: fetchedData[type].name,
                dataList: dataList,
                label: this.translate(type, 'fields', 'Email'),
            });
        });
    }

    prepareDisplayValueList(scope, values) {
        const list = [];

        let attributeList = Object.keys(values);
        const labels = {};

        attributeList.forEach(item => {
            labels[item] = this.fieldLanguage.translateAttribute(scope, item);
        });

        attributeList = attributeList
            .sort((v1, v2) => {
                return labels[v1].localeCompare(labels[v2]);
            });

        const ignoreAttributeList = ['id', 'modifiedAt', 'modifiedByName'];

        const fm = this.getFieldManager();

        fm.getEntityTypeFieldList(scope).forEach(field => {
            const type = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

            if (['link', 'linkOne', 'image', 'filed', 'linkParent'].includes(type)) {
                ignoreAttributeList.push(field + 'Id');
            }

            if (type === 'linkParent') {
                ignoreAttributeList.push(field + 'Type');
            }
        });

        attributeList.forEach(item => {
            if (~ignoreAttributeList.indexOf(item)) {
                return;
            }

            let value = values[item];

            if (value === null || value === '') {
                return;
            }

            if (typeof value == 'boolean') {
                return;
            }

            if (Array.isArray(value)) {
                for (const v in value) {
                    if (typeof v  !== 'string') {
                        return;
                    }
                }

                value = value.split(', ');
            }

            value = this.getHelper().sanitizeHtml(value);

            const valuePreview = value.replace(/<br( \/)?>/gm, ' ');

            // noinspection RegExpUnnecessaryNonCapturingGroup
            value = value.replace(/(?:\r\n|\r|\n)/g, '');
            value = value.replace(/<br( \/)?>/gm, '\n');

            list.push({
                name: item,
                label: labels[item],
                value: value,
                valuePreview: valuePreview,
            });
        });

        return list;
    }

    /**
     * @private
     * @param {string} type
     * @param {string} name
     */
    insert(type, name) {
        for (const g of this.dataList) {
            if (g.type !== type) {
                continue;
            }

            for (const i of g.dataList) {
                if (i.name !== name) {
                    continue;
                }

                this.trigger('insert', i.value);

                break;
            }

            break;
        }

        this.close();
    }
}
