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

/** @module helpers/misc/field-language */

/**
 * A field-language util.
 */
class FieldLanguage {

    /**
     * @param {module:metadata} metadata A metadata.
     * @param {module:language} language A language.
     */
    constructor(metadata, language) {
        /**
         * @private
         * @type {module:metadata}
         */
        this.metadata = metadata;

        /**
         * @private
         * @type {module:language}
         */
        this.language = language;
    }

    /**
     * Translate an attribute.
     *
     * @param {string} scope A scope.
     * @param {string} name An attribute name.
     * @returns {string}
     */
    translateAttribute(scope, name) {
        let label = this.language.translate(name, 'fields', scope);

        if (name.indexOf('Id') === name.length - 2) {
            const baseField = name.slice(0, name.length - 2);

            if (this.metadata.get(['entityDefs', scope, 'fields', baseField])) {
                label = this.language.translate(baseField, 'fields', scope) +
                    ' (' + this.language.translate('id', 'fields') + ')';
            }
        }
        else if (name.indexOf('Name') === name.length - 4) {
            const baseField = name.slice(0, name.length - 4);

            if (this.metadata.get(['entityDefs', scope, 'fields', baseField])) {
                label = this.language.translate(baseField, 'fields', scope) +
                    ' (' + this.language.translate('name', 'fields') + ')';
            }
        }
        else if (name.indexOf('Type') === name.length - 4) {
            const baseField = name.slice(0, name.length - 4);

            if (this.metadata.get(['entityDefs', scope, 'fields', baseField])) {
                label = this.language.translate(baseField, 'fields', scope) +
                    ' (' + this.language.translate('type', 'fields') + ')';
            }
        }

        if (name.indexOf('Ids') === name.length - 3) {
            const baseField = name.slice(0, name.length - 3);

            if (this.metadata.get(['entityDefs', scope, 'fields', baseField])) {
                label = this.language.translate(baseField, 'fields', scope) +
                    ' (' + this.language.translate('ids', 'fields') + ')';
            }
        }
        else if (name.indexOf('Names') === name.length - 5) {
            const baseField = name.slice(0, name.length - 5);

            if (this.metadata.get(['entityDefs', scope, 'fields', baseField])) {
                label = this.language.translate(baseField, 'fields', scope) +
                    ' (' + this.language.translate('names', 'fields') + ')';
            }
        }
        else if (name.indexOf('Types') === name.length - 5) {
            const baseField = name.slice(0, name.length - 5);

            if (this.metadata.get(['entityDefs', scope, 'fields', baseField])) {
                label = this.language.translate(baseField, 'fields', scope) +
                    ' (' + this.language.translate('types', 'fields') + ')';
            }
        }

        return label;
    }
}

export default FieldLanguage;
