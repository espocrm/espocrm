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

class ListSettingsHelper {

    /**
     * @param {string} entityType
     * @param {string} type
     * @param {string} userId
     * @param {module:storage} storage
     */
    constructor(entityType, type, userId, storage) {
        /** @private */
        this.storage = storage;

        this.layoutColumnsKey = `${type}-${entityType}-${userId}`;
        this.hiddenColumnMapCache = {};
    }

    /**
     * Get a stored hidden column map.
     *
     * @return {Object.<string, boolean>}
     */
    getHiddenColumnMap() {
        if (this.hiddenColumnMapCache[this.layoutColumnsKey]) {
            return this.hiddenColumnMapCache[this.layoutColumnsKey];
        }

        return this.storage.get('listHiddenColumns', this.layoutColumnsKey) || {};
    }

    /**
     * Store a hidden column map.
     *
     * @param {Object.<string, boolean>} map
     */
    storeHiddenColumnMap(map) {
        delete this.hiddenColumnMapCache[this.layoutColumnsKey];

        this.storage.set('listHiddenColumns', this.layoutColumnsKey, map);
    }

    /**
     * Clear a hidden column map in the storage.
     */
    clearHiddenColumnMap() {
        delete this.hiddenColumnMapCache[this.layoutColumnsKey];

        this.storage.clear('listHiddenColumns', this.layoutColumnsKey);
    }
}

export default ListSettingsHelper;
