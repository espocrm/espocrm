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

import ModalView from 'views/modal';

class RoleAddFieldModalView extends ModalView {

    template = 'role/modals/add-field'

    backdrop = true

    events = {
        /** @this RoleAddFieldModalView */
        'click a[data-action="addField"]': function (e) {
            this.trigger('add-field', $(e.currentTarget).data().name);
        }
    }

    data() {
        const dataList = [];

        this.fieldList.forEach((field, i) => {
            if (i % 4 === 0) {
                dataList.push([]);
            }

            dataList[dataList.length -1].push(field);
        });

        return {
            dataList: dataList,
            scope: this.scope,
        };
    }

    setup() {
        const scope = this.scope = this.options.scope;

        this.headerText = this.translate(scope, 'scopeNamesPlural') + ' · ' + this.translate('Add Field');

        const fields = this.getMetadata().get(`entityDefs.${scope}.fields`) || {};
        const fieldList = [];

        Object.keys(fields).forEach(field => {
            const defs = /** @type {Record} */fields[field];

            if (field in this.options.ignoreFieldList) {
                return;
            }

            if (defs.disabled) {
                return;
            }

            const mandatoryLevel = this.getMetadata()
                .get(['app', this.options.type, 'mandatory', 'scopeFieldLevel', this.scope, field]);

            if (mandatoryLevel != null) {
                return;
            }

            fieldList.push(field);
        });

        this.fieldList = this.getLanguage().sortFieldList(scope, fieldList);
    }
}

export default RoleAddFieldModalView;
