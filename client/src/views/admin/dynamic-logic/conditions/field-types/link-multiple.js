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

import DynamicLogicConditionFieldTypeBaseView from 'views/admin/dynamic-logic/conditions/field-types/base';

export default class DynamicLogicConditionFieldTypeLinkMultipleView extends DynamicLogicConditionFieldTypeBaseView {

    getValueFieldName() {
        return this.name;
    }

    getValueViewName() {
        return 'views/fields/link';
    }

    // noinspection JSUnusedGlobalSymbols
    createValueViewContains() {
        this.createLinkValueField();
    }

    // noinspection JSUnusedGlobalSymbols
    createValueViewNotContains() {
        this.createLinkValueField();
    }

    createLinkValueField() {
        const viewName = 'views/fields/link';
        const fieldName = 'link';

        this.createView('value', viewName, {
            model: this.model,
            name: fieldName,
            selector: '.value-container',
            mode: 'edit',
            readOnlyDisabled: true,
            foreignScope: this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'entity']) ||
                this.getMetadata().get(['entityDefs', this.scope, 'links', this.field, 'entity']),
        }, view => {
            if (this.isRendered()) {
                view.render();
            }
        });
    }

    fetch() {
        /** @type {import('views/fields/base').default} */
        const valueView = this.getView('value');

        const item = {
            type: this.type,
            attribute: this.field + 'Ids',
            data: {
                field: this.field,
            },
        };

        if (valueView) {
            valueView.fetchToModel();

            item.value = this.model.get('linkId');

            const values = {};

            values['linkName'] = this.model.get('linkName');
            values['linkId'] = this.model.get('linkId');

            item.data.values = values;
        }

        return item;
    }
}
