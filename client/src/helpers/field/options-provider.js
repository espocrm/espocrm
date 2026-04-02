/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import Metadata from 'metadata';
import Language from 'language';

/**
 * @since 9.4.0
 */
export default class OptionsProvider {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @private
     * @type {Language}
     */
    @inject(Language)
    language

    /**
     * @param {string} entityType
     * @param {string} field
     * @return {{
     *     name: string,
     *     label: string,
     *     style: string|null,
     * }[]}
     */
    get(entityType, field) {
        /**
         * @type {{
         *     options?: string[],
         *     style?: Record<string, string>,
         *     optionsReference?: string|null,
         *     optionsPath?: string|null
         * }} */
        const params = this.metadata.get(`entityDefs.${entityType}.fields.${field}`);

        if (!params) {
            return [];
        }

        let sourceEntityType = entityType;
        let sourceField = field;
        let styleMap = params.style ?? {};

        let optionsPath = params.optionsPath;

        if (!optionsPath && params.optionsReference) {
            const [refEntityType, refField] = params.optionsReference.split('.');

            optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;

            sourceEntityType = refEntityType;
            sourceField = refField;

            styleMap = this.metadata.get(`entityDefs.${refEntityType}.fields.${refField}.style`) ?? {};
        }

        let options = params.options;

        if (optionsPath) {
            options = this.metadata.get(optionsPath);
        }

        return options.map(it => {
            return {
                name: it,
                label: this.language.translateOption(it, sourceField, sourceEntityType),
                style: styleMap[it] ?? null,
            }
        });
    }
}
