/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

/**
 * Prepares attributes for a related record that is being created.
 *
 * @abstract
 */
class CreateRelatedHandler {

    /**
     * @param {module:view-helper} viewHelper
     */
    constructor(viewHelper) {
        // noinspection JSUnusedGlobalSymbols
        this.viewHelper = viewHelper;
    }

    /**
     * Get attributes for a new record.
     *
     * @abstract
     * @param {module:model} model A model.
     * @param {string} link A link name. As of v9.2.0.
     * @return {Promise<Object.<string, *>>} Attributes.
     */
    getAttributes(model, link) {
        return Promise.resolve({});
    }
}

export default CreateRelatedHandler;
