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
        const previous = this.collection.offset > 0;
        const next = this.collection.total - this.collection.offset > this.collection.maxSize ||
            this.collection.total === -1;

        const last = next && this.collection.total >= 0;

        const from = this.collection.offset + 1;
        const to = this.collection.offset + this.collection.length;
        const total = this.collection.total;

        const noTotal = !this.options.displayTotalCount || this.collection.total < 0;

        return {
            total: this.getHelper().numberUtil.formatInt(total),
            from: this.getHelper().numberUtil.formatInt(from),
            to: this.getHelper().numberUtil.formatInt(to),
            previous: previous,
            next: next,
            last: last,
            noTotal: noTotal,
        };
    }

    setup() {
        this.listenTo(this.collection, 'sync', () => this.reRender());
    }
}

export default RecordListPagination;
