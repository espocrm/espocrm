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

/**
 * A regular expression pattern helper.
 */
class RegExpPatternHelper {

    /**
     * @param {module:metadata} metadata
     * @param {module:language} language
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
     *
     * @param {string} pattern
     * @param {string|null} value
     * @param {string} [field]
     * @param {string} [entityType]
     * @return {{message: string}|null}
     */
    validate(pattern, value, field, entityType) {
        if (value === '' || value === null) {
            return null;
        }

        let messageKey = 'fieldNotMatchingPattern';

        if (pattern[0] === '$') {
            const patternName = pattern.slice(1);
            const foundPattern = this.metadata.get(['app', 'regExpPatterns', patternName, 'pattern']);

            if (foundPattern) {
                messageKey += '$' + patternName;
                pattern = foundPattern;
            }
        }

        const regExp = new RegExp('^' + pattern + '$');

        if (regExp.test(value)) {
            return null;
        }

        let message = this.language.translate(messageKey, 'messages')
            .replace('{pattern}', pattern);

        if (field && entityType) {
            message = message.replace('{field}', this.language.translate(field, 'fields', entityType));
        }

        return {message: message};
    }
}

export default RegExpPatternHelper;
