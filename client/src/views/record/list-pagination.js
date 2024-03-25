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

import View from 'view';

class RecordListPagination extends View {

    template = 'record/list-pagination'

    isComponent = true

    data() {
        const total = this.collection.total;
        const offset = this.collection.offset;
        const length = this.collection.length;

        const next = this.collection.hasNextPage();
        const last = next && total >= 0;

        const from = offset + 1;
        const to = offset + length;

        return {
            total: this.getHelper().numberUtil.formatInt(total),
            from: this.getHelper().numberUtil.formatInt(from),
            to: this.getHelper().numberUtil.formatInt(to),
            previous: this.collection.hasPreviousPage(),
            next: next,
            last: last,
            noTotal: !this.options.displayTotalCount || total < 0,
        };
    }

    setup() {
        this.listenTo(this.collection, 'update', () => {
            if (!this.element) {
                // A hack. Prevents warnings in console.
                return;
            }

            this.reRender();
        });
    }
}

export default RecordListPagination;
