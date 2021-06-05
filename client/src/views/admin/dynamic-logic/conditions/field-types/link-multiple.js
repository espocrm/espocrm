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

define(
    'views/admin/dynamic-logic/conditions/field-types/link-multiple',
    'views/admin/dynamic-logic/conditions/field-types/base',
    function (Dep) {

    return Dep.extend({

        getValueFieldName: function () {
            return this.name;
        },

        getValueViewName: function () {
            return 'views/fields/link';
        },

        createValueViewContains: function () {
            this.createLinkValueField();
        },

        createValueViewNotContains: function () {
            this.createLinkValueField();
        },

        createLinkValueField: function () {
            var viewName = 'views/fields/link'
            var fieldName = 'link';

            this.createView('value', viewName, {
                model: this.model,
                name: fieldName,
                el: this.getSelector() + ' .value-container',
                mode: 'edit',
                readOnlyDisabled: true,
                foreignScope: this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'entity']) ||
                    this.getMetadata().get(['entityDefs', this.scope, 'links', this.field, 'entity']),
            }, function (view) {
                if (this.isRendered()) {
                    view.render();
                }
            }, this);
        },

        fetch: function () {
            var valueView = this.getView('value');

            var item = {
                type: this.type,
                attribute: this.field + 'Ids',
                data: {
                    field: this.field
                },
            };

            if (valueView) {
                valueView.fetchToModel();

                item.value = this.model.get('linkId');

                var values = {};

                values['linkName'] = this.model.get('linkName');
                values['linkId'] = this.model.get('linkId');

                item.data.values = values;
            }

            return item;
        },

    });
});
