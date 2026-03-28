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

import RowActionHandler from 'handlers/row-action';

export default class PipelineMoveRowActionHandler extends RowActionHandler
{
    isAvailable(model, action) {
        const collection = model.collection;

        if (!collection) {
            return false;
        }

        if (action === 'moveToTop' || action === 'moveUp') {
            if (collection.indexOf(model) <= 0) {
                return false;
            }
        }

        if (action === 'moveToBottom' || action === 'moveDown') {
            if (collection.indexOf(model) >= collection.total - 1) {
                return false;
            }
        }

        console.log(collection.orderBy);

        return collection.orderBy === 'order' && collection.order === 'asc';
    }

    async process(model, action) {
        let type;

        if (action === 'moveToTop') {
            type = 'top';
        } else if (action === 'moveToBottom') {
            type = 'bottom';
        } else  if (action === 'moveUp') {
            type = 'up';
        } else if (action === 'moveDown') {
            type = 'down';
        } else {
            throw new Error();
        }

        Espo.Ui.notifyWait();

        await Espo.Ajax.postRequest(`${model.entityType}/${model.id}/move/${type}`, {
            whereGroup: this.collection.getWhere(),
        });

        await this.collection.fetch();

        Espo.Ui.notify();
    }
}
