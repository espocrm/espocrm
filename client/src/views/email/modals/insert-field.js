/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/email/modals/insert-field',
['views/modal', 'helpers/misc/field-language'], function (Dep, FieldLanguage) {

    return Dep.extend({

        backdrop: true,

        templateContent: `
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
        `,

        events: {
            'click [data-action="insert"]': function (e) {
                let name = $(e.currentTarget).data('name');
                let type = $(e.currentTarget).data('type');

                this.insert(type, name);
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

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
        },

        prepareData: function () {
            this.dataList = [];

            var fetchedData = this.fetchedData;
            var typeList = ['parent', 'to'];

            typeList.forEach(type => {
                if (!fetchedData[type]) {
                    return;
                }

                let entityType = fetchedData[type].entityType;
                let id = fetchedData[type].id;

                for (let it of this.dataList) {
                    if (it.id === id && it.entityType === entityType) {
                        return;
                    }
                }

                var dataList = this.prepareDisplayValueList(fetchedData[type].entityType, fetchedData[type].values);

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
        },

        prepareDisplayValueList: function (scope, values) {
            let list = [];

            let attributeList = Object.keys(values);
            let labels = {};

            attributeList.forEach(item => {
                labels[item] = this.fieldLanguage.translateAttribute(scope, item);
            });

            attributeList = attributeList
                .sort((v1, v2) => {
                    return labels[v1].localeCompare(labels[v2]);
                });

            let ignoreAttributeList = ['id', 'modifiedAt', 'modifiedByName'];

            let fm = this.getFieldManager();

            fm.getEntityTypeFieldList(scope).forEach(field => {
                let type = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

                if (~['link', 'linkOne', 'image', 'filed', 'linkParent'].indexOf(type)) {
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
                    for (let v in value) {
                        if (typeof v  !== 'string') {
                            return;
                        }
                    }

                    value = value.split(', ');
                }

                value = this.getHelper().sanitizeHtml(value);

                var valuePreview = value.replace(/<br( \/)?>/gm, ' ');

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
        },

        insert: function (type, name) {
            for (let g of this.dataList) {
                if (g.type !== type) {
                    continue;
                }

                for (let i of g.dataList) {
                    if (i.name !== name) {
                        continue;
                    }

                    this.trigger('insert', i.value);

                    break;
                }

                break;
            }

            this.close();
        },
    });
});
