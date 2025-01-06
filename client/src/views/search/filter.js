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

/** @module views/search/filter */

import View from 'view';

class FilterView extends View {

    template = 'search/filter'

    data() {
        return {
            name: this.name,
            scope: this.model.entityType,
            notRemovable: this.options.notRemovable,
        };
    }

    setup() {
        const name = this.name = this.options.name;
        let type = this.model.getFieldType(name);

        if (!type && name === 'id') {
            type = 'id'
        }

        if (type) {
            const viewName =
                this.model.getFieldParam(name, 'view') ||
                this.getFieldManager().getViewName(type);

            this.createView('field', viewName, {
                mode: 'search',
                model: this.model,
                selector: '.field',
                defs: {
                    name: name,
                },
                searchParams: this.options.params,
            }, view => {
                this.listenTo(view, 'change', () => {
                    this.trigger('change');
                });

                this.listenTo(view, 'search', () => {
                    this.trigger('search');
                });
            });
        }
    }

    /**
     * @return {module:views/fields/base}
     */
    getFieldView() {
        return this.getView('field');
    }

    populateDefaults() {
        const view = this.getView('field');

        if (!view) {
            return;
        }

        if (!('populateSearchDefaults' in view)) {
            return;
        }

        view.populateSearchDefaults();
    }
}

export default FilterView;
