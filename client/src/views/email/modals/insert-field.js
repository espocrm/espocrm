/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/email/modals/insert-field', ['views/modal', 'field-language'], function (Dep, FieldLanguage) {

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
                        <a href="javascript:"
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
                var name = $(e.currentTarget).data('name');
                var type = $(e.currentTarget).data('type');
                this.insert(type, name);
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerHtml = this.translate('Insert Field', 'labels', 'Email');

            this.fieldLanguage = new FieldLanguage(this.getMetadata(), this.getLanguage());

            this.wait(
                Espo.Ajax.getRequest('Email/action/getInsertFieldData', {
                    parentId: this.options.parentId,
                    parentType: this.options.parentType,
                    to: this.options.to,
                }).then(
                    function (fetchedData) {
                        this.fetchedData = fetchedData;
                        this.prepareData();
                    }.bind(this)
                )
            );
        },

        prepareData: function () {
            this.dataList = [];
            var fetchedData = this.fetchedData;
            var typeList = ['parent', 'to'];

            typeList.forEach(function (type) {
                if (!fetchedData[type]) return;

                var entityType = fetchedData[type].entityType;
                var id = fetchedData[type].id;

                for (var it of this.dataList) {
                    if (it.id === id && it.entityType === entityType) {
                        return;
                    }
                }

                var dataList = this.prepareDisplayValueList(fetchedData[type].entityType, fetchedData[type].values);
                if (!dataList.length) return;

                this.dataList.push({
                    type: type,
                    entityType: entityType,
                    id: id,
                    name: fetchedData[type].name,
                    dataList: dataList,
                    label: this.translate(type, 'fields', 'Email'),
                });
            }, this);
        },

        prepareDisplayValueList: function (scope, values) {
            var list = [];

            var attributeList = Object.keys(values);
            var labels = {};

            attributeList.forEach(function (item) {
                labels[item] = this.fieldLanguage.translateAttribute(scope, item);
            }, this);

            attributeList = attributeList.sort(function (v1, v2) {
                return labels[v1].localeCompare(labels[v2]);
            }.bind(this));

            var ignoreAttributeList = ['id', 'modifiedAt', 'modifiedByName'];

            var fm = this.getFieldManager();

            fm.getEntityTypeFieldList(scope).forEach(function (field) {
                var type = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

                if (~['link', 'linkOne', 'image', 'filed', 'linkParent'].indexOf(type)) {
                    ignoreAttributeList.push(field + 'Id');
                }
                if (type === 'linkParent') {
                    ignoreAttributeList.push(field + 'Type');
                }
            }, this);

            attributeList.forEach(function (item) {
                if (~ignoreAttributeList.indexOf(item)) return;
                var value = values[item];
                if (value === null || value === '') return;
                if (typeof value == 'boolean') return;
                if (Array.isArray(value)) {
                    for (let v in value) {
                        if (typeof v  !== 'string') return;
                    }
                    value = value.split(', ');
                };

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
            }, this);

            return list;
        },

        insert: function (type, name) {
            for (var g of this.dataList) {
                if (g.type !== type) continue;

                for (var i of g.dataList) {
                    if (i.name !== name) continue;
                    this.trigger('insert', i.value);
                    break;
                }
                break;
            }

            this.close();
        },

    });
});
