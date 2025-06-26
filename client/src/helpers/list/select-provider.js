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

import {inject} from 'di';
import LayoutManager from 'layout-manager';
import Metadata from 'metadata';
import FieldManager from 'field-manager';

class SelectProvider {


    /**
     * @type {LayoutManager}
     * @private
     */
    @inject(LayoutManager)
    layoutManager

    /**
     * @type {Metadata}
     * @private
     */
    @inject(Metadata)
    metadata

    /**
     * @type {FieldManager}
     * @private
     */
    @inject(FieldManager)
    fieldManager

    /**
     * Get select attributes.
     *
     * @param {string} entityType
     * @param {string} [layoutName='list']
     * @return {Promise<string[]>}
     */
    get(entityType, layoutName) {
        return new Promise(resolve => {
            this.layoutManager.get(entityType, layoutName || 'list', layout => {
                const list = this.getFromLayout(entityType, layout);

                resolve(list);
            });
        });
    }

    /**
     * Get select attributes from a layout.
     *
     * @param {string} entityType
     * @param {module:views/record/list~columnDefs[]} listLayout
     * @param {import('helpers/list/settings').default} [settings]
     * @return {string[]}
     */
    getFromLayout(entityType, listLayout, settings) {
        const list = [];

        listLayout.forEach(item => {
            if (!item.name) {
                return;
            }

            if (settings?.isColumnHidden(item.name, item.hidden)) {
                return;
            }

            const field = item.name;
            const type = this.metadata.get(`entityDefs.${entityType}.fields.${field}.type`);

            if (!type) {
                return;
            }

            list.push(...this.fieldManager.getEntityTypeFieldAttributeList(entityType, field));
        });

        return list;
    }
}

export default SelectProvider;
