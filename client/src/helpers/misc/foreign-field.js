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

/** @module helpers/misc/foreign-field */

export default class {

    /**
     * @param {module:views/fields/base} view A field view.
     */
    constructor(view) {
        /**
         * @private
         * @type {module:views/fields/base}
         */
        this.view = view;

        const metadata = view.getMetadata();
        const model = view.model;
        const field = view.params.field;
        const link = view.params.link;

        const entityType = metadata.get(['entityDefs', model.entityType, 'links', link, 'entity']) ||
            model.entityType;

        const fieldDefs = metadata.get(['entityDefs', entityType, 'fields', field]) || {};
        const type = fieldDefs.type;

        const ignoreList = [
            'default',
            'audited',
            'readOnly',
            'required',
        ];

        /** @private */
        this.foreignParams = {};

        view.getFieldManager().getParamList(type).forEach(defs => {
            const name = defs.name;

            if (ignoreList.includes(name)) {
                return;
            }

            this.foreignParams[name] = fieldDefs[name] || null;
        });
    }

    /**
     * @return {Object.<string, *>}
     */
    getForeignParams() {
        return Espo.Utils.cloneDeep(this.foreignParams);
    }
}
