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

define('views/modals/resolve-save-conflict', ['views/modal'], function (Dep) {

    return Dep.extend({

        backdrop: true,

        fitHeight: true,

        template: 'modals/resolve-save-conflict',

        resolutionList: [
            'current',
            'actual',
            'original',
        ],

        defaultResolution: 'current',

        data: function () {
            var dataList = [];

            this.fieldList.forEach(function (item) {
                var o = {
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
        },

        setup: function () {
            this.headerHtml = this.getHelper().sanitizeHtml(this.translate('Resolve Conflict'));

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

            var attributeList = this.options.attributeList;

            var fieldList = [];

            this.getFieldManager().getEntityTypeFieldList(this.entityType).forEach(function (field) {
                var fieldAttributeList = this.getFieldManager()
                    .getEntityTypeFieldAttributeList(this.entityType, field);

                var intersect = attributeList.filter(value => fieldAttributeList.includes(value));

                if (intersect.length) {
                    fieldList.push(field);
                }
            }, this);

            this.fieldList = fieldList;

            this.wait(
                this.getModelFactory().create(this.entityType)
                    .then(
                        function (model) {
                            this.model = model;

                            this.fieldList.forEach(function (field) {
                                this.setResolution(field, this.defaultResolution);
                            }, this);

                            this.fieldList.forEach(function (field) {
                                this.createField(field);
                            }, this);
                        }.bind(this)
                    )
            );
        },

        setResolution: function (field, resolution) {
            var attributeList = this.getFieldManager()
                .getEntityTypeFieldAttributeList(this.entityType, field);

            var values = {};

            var source = this.currentAttributes;

            if (resolution === 'actual') {
                source = this.actualAttributes;
            }
            else if (resolution === 'original') {
                source = this.originalAttributes;
            }

            for (let attribute of attributeList) {
                values[attribute] = source[attribute] || null;
            }

            this.model.set(values);
        },

        createField: function (field) {
            var type = this.model.getFieldType(field);

            var viewName =
                this.model.getFieldParam(field, 'view') ||
                this.getFieldManager().getViewName(type);

            this.createView(field + 'Field', viewName, {
                readOnly: true,
                model: this.model,
                name: field,
                el: this.getSelector() + ' [data-name="field"][data-field="' + field + '"]',
                mode: 'list',
            });
        },

        afterRender: function () {
            this.$el.find('[data-name="resolution"]').on('change', function (e) {
                var $el = $(e.currentTarget);

                var field = $el.attr('data-field');
                var resolution = $el.val();

                this.setResolution(field, resolution);
            }.bind(this));
        },

        actionApply: function () {
            var attributes = this.model.attributes;

            this.originalModel.set(attributes);

            this.trigger('resolve');

            this.close();
        },
    });
});
