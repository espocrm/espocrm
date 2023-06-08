/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

        let metadata = view.getMetadata();
        let model = view.model;
        let field = view.params.field;
        let link = view.params.link;

        let entityType = metadata.get(['entityDefs', model.entityType, 'links', link, 'entity']) ||
            model.entityType;

        let fieldDefs = metadata.get(['entityDefs', entityType, 'fields', field]) || {};
        let type = fieldDefs.type;

        let ignoreList = [
            'default',
            'audited',
            'readOnly',
            'required',
        ];

        /** @private */
        this.foreignParams = {};

        view.getFieldManager().getParamList(type).forEach(defs => {
            let name = defs.name;

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
