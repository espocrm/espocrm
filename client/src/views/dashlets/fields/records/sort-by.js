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

import EnumFieldView from 'views/fields/enum';

export default class extends EnumFieldView {

    setup() {
        super.setup();

        this.listenTo(this.model, 'change:entityType', () => {
            this.setupOptions();
            this.reRender();
        });
    }

    setupOptions() {
        const entityType = this.model.get('entityType');
        const scope = entityType;

        if (!entityType) {
            this.params.options = [];

            return;
        }

        /** @type {Record<string, Record>} */
        const fieldDefs = this.getMetadata().get(`entityDefs.${scope}.fields`) || {};

        const orderableFieldList = Object.keys(fieldDefs)
            .filter(item => {
                if (fieldDefs[item].orderDisabled || fieldDefs[item].utility) {
                    return false;
                }

                return true;
            })
            .sort((v1, v2) => {
                return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
            });

        const translatedOptions = {};

        orderableFieldList.forEach(item => {
            translatedOptions[item] = this.translate(item, 'fields', scope);
        });

        this.params.options = orderableFieldList;
        this.translatedOptions = translatedOptions;
    }
}
