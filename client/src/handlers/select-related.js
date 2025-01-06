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

/** @module handlers/select-related */

/**
 * @typedef {Object} module:handlers/select-related~filters
 * @property {Object.<string, module:search-manager~advancedFilter>} [advanced]
 *  Advanced filters map. A field name as a key.
 * @property {string[]} [bool] Bool filters.
 * @property {string} [primary] A primary filter.
 * @property {string} [orderBy] A field to order by.
 * @property {'asc'|'desc'} [order] An order direction.
 */

/**
 * Prepares filters for selecting records to relate.
 *
 * @abstract
 */
class SelectRelatedHandler {

    /**
     * @protected
     * @type {import('view-helper').default}
     */
    viewHelper

    /**
     * @param {import('view-helper').default} viewHelper
     */
    constructor(viewHelper) {
        this.viewHelper = viewHelper;
    }

    /**
     * Get filters for selecting records to relate.
     *
     * @abstract
     * @param {module:model} model A model.
     * @return {Promise<module:handlers/select-related~filters>} Filters.
     */
    getFilters(model) {
        return Promise.resolve({});
    }
}

export default SelectRelatedHandler;
