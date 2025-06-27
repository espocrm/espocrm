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

/** @module views/fields/bool */

import BaseFieldView from 'views/fields/base';
import Select from 'ui/select';

/**
 * A boolean field (checkbox).
 *
 * @extends BaseFieldView<module:views/fields/bool~params>
 */
class BoolFieldView extends BaseFieldView {

    /**
     * @typedef {Object} module:views/fields/bool~options
     * @property {
     *     module:views/fields/bool~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/bool~params
     */

    /**
     * @param {
     *     module:views/fields/bool~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'bool'

    listTemplate = 'fields/bool/list'
    detailTemplate = 'fields/bool/detail'
    editTemplate = 'fields/bool/edit'
    searchTemplate = 'fields/bool/search'

    validations = []
    initialSearchIsNotIdle = true

    // noinspection JSCheckFunctionSignatures
    /** @inheritDoc */
    data() {
        const data = super.data();

        data.valueIsSet = this.model.has(this.name);

        // noinspection JSValidateTypes
        return data;
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_SEARCH) {
            this.$element.on('change', () => {
                this.trigger('change');
            });

            Select.init(this.$element);
        }
    }

    fetch() {
        // noinspection JSUnresolvedReference
        const value = this.$element.get(0).checked;

        const data = {};

        data[this.name] = value;

        return data;
    }

    fetchSearch() {
        const type = this.$element.val();

        if (!type) {
            return null;
        }

        if (type === 'any') {
            return {
                type: 'or',
                value: [
                    {
                        type: 'isTrue',
                        attribute: this.name,

                    },
                    {
                        type: 'isFalse',
                        attribute: this.name,
                    },
                ],
                data: {
                    type: type,
                },
            };
        }

        return {
            type: type,
            data: {
                type: type,
            },
        };
    }

    getSearchType() {
        return this.getSearchParamsData().type ||
            this.searchParams.type ||
            'isTrue';
    }
}

export default BoolFieldView;
