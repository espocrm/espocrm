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

import RowActionHandler from 'handlers/row-action';

class MoveActionHandler extends RowActionHandler {

    isAvailable(model, action) {
        return model.collection &&
            model.collection.orderBy === 'order' &&
            model.collection.order === 'asc';
    }

    process(model, action) {
        if (action === 'moveToTop') {
            this.moveToTop(model);

            return;
        }

        if (action === 'moveToBottom') {
            this.moveToBottom(model);

            return;
        }

        if (action === 'moveUp') {
            this.moveUp(model);

            return;
        }

        if (action === 'moveDown') {
            this.moveDown(model);
        }
    }

    moveToTop(model) {
        const index = this.collection.indexOf(model);

        if (index === 0) {
            return;
        }

        Espo.Ui.notifyWait();

        Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveToTop', {
            id: model.id,
            whereGroup: this.collection.getWhere(),
        }).then(() => {
            this.collection.fetch()
                .then(() => Espo.Ui.notify(false));
        });
    }

    moveUp(model) {
        const index = this.collection.indexOf(model);

        if (index === 0) {
            return;
        }

        Espo.Ui.notifyWait();

        Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveUp', {
            id: model.id,
            whereGroup: this.collection.getWhere(),
        }).then(() => {
            this.collection.fetch()
                .then(() => Espo.Ui.notify(false));
        });
    }

    moveDown(model) {
        const index = this.collection.indexOf(model);

        if ((index === this.collection.length - 1) && (this.collection.length === this.collection.total)) {
            return;
        }

        Espo.Ui.notifyWait();

        Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveDown', {
            id: model.id,
            whereGroup: this.collection.getWhere(),
        }).then(() => {
            this.collection.fetch()
                .then(() => Espo.Ui.notify(false));
        });
    }

    moveToBottom(model) {
        const index = this.collection.indexOf(model);

        if ((index === this.collection.length - 1) && (this.collection.length === this.collection.total)) {
            return;
        }

        Espo.Ui.notifyWait();

        Espo.Ajax.postRequest('KnowledgeBaseArticle/action/moveToBottom', {
            id: model.id,
            whereGroup: this.collection.getWhere(),
        }).then(() => {
            this.collection.fetch()
                .then(() => Espo.Ui.notify(false));
        });
    }
}

export default MoveActionHandler;
