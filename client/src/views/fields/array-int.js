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

import ArrayFieldView from 'views/fields/array';

class ArrayIntFieldView extends ArrayFieldView {

    type = 'arrayInt'

    fetchFromDom() {
        let selected = [];

        this.$el.find('.list-group .list-group-item').each((i, el) => {
            let value = $(el).data('value');

            if (typeof value === 'string' || value instanceof String) {
                value = parseInt($(el).data('value'));
            }

            selected.push(value);
        });

        this.selected = selected;
    }

    addValue(value) {
        value = parseInt(value);

        if (isNaN(value)) {
            return;
        }

        super.addValue(value);
    }

    removeValue(value) {
        value = parseInt(value);

        if (isNaN(value)) {
            return;
        }

        const valueInternal = CSS.escape(value.toString());

        this.$list.children('[data-value="' + valueInternal + '"]').remove();

        const index = this.selected.indexOf(value);

        this.selected.splice(index, 1);
        this.trigger('change');
    }
}

export default ArrayIntFieldView;
