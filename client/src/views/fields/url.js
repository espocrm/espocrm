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

/** @module views/fields/url */

import VarcharFieldView from 'views/fields/varchar';

/**
 * A URL field.
 *
 * @extends BaseFieldView<module:views/fields/url~params>
 */
class UrlFieldView extends VarcharFieldView {

    /**
     * @typedef {Object} module:views/fields/url~options
     * @property {
     *     module:views/fields/varchar~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/url~params
     * @property {number} [maxLength] A max length.
     * @property {boolean} [required] Required.
     * @property {boolean} [copyToClipboard] To display a Copy-to-clipboard button.
     * @property {boolean} [strip] To strip.
     */

    /**
     * @param {
     *     module:views/fields/url~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'url'

    listTemplate = 'fields/url/list'
    detailTemplate = 'fields/url/detail'
    defaultProtocol = 'https:'

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = [
        'required',
        'valid',
        'maxLength',
    ]

    noSpellCheck = true
    optionalProtocol = true

    DEFAULT_MAX_LENGTH =255

    data() {
        const data = super.data();

        data.url = this.getUrl();

        return data;
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            this.$element.on('change', () => {
                const value = this.$element.val() || '';

                const parsedValue = this.parse(value);

                if (parsedValue === value) {
                    return;
                }

                const decoded = parsedValue ? this.decodeURI(parsedValue) : '';

                this.$element.val(decoded);
            });
        }
    }

    getValueForDisplay() {
        const value = this.model.get(this.name);

        return value ? this.decodeURI(value) : null;
    }

    /**
     * @private
     * @param {string} value
     * @return {string}
     */
    decodeURI(value) {
        try {
            return decodeURI(value);
        } catch (e) {
            console.warn(`Malformed URI ${value}.`);

            return value;
        }
    }

    /**
     * @param {string} value
     * @return {string}
     */
    parse(value) {
        value = value.trim();

        if (this.params.strip) {
            value = this.strip(value);
        }

        try {
            if (value === decodeURI(value)) {
                value = encodeURI(value);
            }
        } catch (e) {
            console.warn(`Malformed URI ${value}.`);

            return value;
        }

        return value;
    }

    /**
     * @param {string} value
     * @return {string}
     */
    strip(value) {
        if (value.indexOf('//') !== -1) {
            value = value.substring(value.indexOf('//') + 2);
        }

        value = value.replace(/\/+$/, '');

        return value;
    }

    getUrl() {
        let url = this.model.get(this.name);

        if (url && url !== '') {
            if (url.indexOf('//') === -1) {
                url = this.defaultProtocol + '//' + url;
            }

            return url;
        }

        return url;
    }

    // noinspection JSUnusedGlobalSymbols
    validateValid() {
        const value = this.model.get(this.name);

        if (!value) {
            return false;
        }

        const patternName = this.optionalProtocol ? 'uriOptionalProtocol' : 'uri';

        /** @var {string} */
        const pattern = this.getMetadata().get(['app', 'regExpPatterns', patternName, 'pattern']);

        const regExp = new RegExp('^' + pattern + '$');

        if (regExp.test(value)) {
            return false;
        }

        const msg = this.translate('fieldInvalid', 'messages')
            .replace('{field}', this.getLabelText());

        this.showValidationMessage(msg);

        return true;
    }

    // noinspection JSUnusedGlobalSymbols
    validateMaxLength() {
        const maxLength = this.params.maxLength || this.DEFAULT_MAX_LENGTH;

        const value = this.model.get(this.name);

        if (!value || !value.length) {
            return false;
        }

        if (value.length <= maxLength) {
            return false;
        }

        const msg = this.translate('fieldUrlExceedsMaxLength', 'messages')
            .replace('{maxLength}', maxLength.toString())
            .replace('{field}', this.getLabelText());

        this.showValidationMessage(msg);

        return true;
    }

    fetch() {
        const data = super.fetch();

        const value = data[this.name];

        if (!value) {
            return data;
        }

        data[this.name] = this.parse(value);

        return data;
    }
}

export default UrlFieldView;
