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

import View from 'view';

class KanbanRecordItem extends View {

    template = 'record/kanban-item'

    data() {
        return {
            layoutDataList: this.layoutDataList,
            rowActionsDisabled: this.rowActionsDisabled,
            isStarred: this.hasStars && this.model.attributes.isStarred,
        };
    }

    setup() {
        this.itemLayout = this.options.itemLayout;
        this.rowActionsView = this.options.rowActionsView;
        this.rowActionsDisabled = this.options.rowActionsDisabled;
        this.hasStars = this.options.hasStars;

        this.layoutDataList = [];

        this.itemLayout.forEach((item, i) => {
            const name = item.name;
            const key = name + 'Field';

            const o = {
                name: name,
                isAlignRight: item.align === 'right',
                isLarge: item.isLarge,
                isMuted: item.isMuted,
                isFirst: i === 0,
                key: key,
            };

            this.layoutDataList.push(o);

            let viewName = item.view || this.model.getFieldParam(name, 'view');

            if (!viewName) {
                const type = this.model.getFieldType(name) || 'base';

                viewName = this.getFieldManager().getViewName(type);
            }

            let mode = 'list';

            if (item.link) {
                mode = 'listLink';
            }

            this.createView(key, viewName, {
                model: this.model,
                name: name,
                mode: mode,
                readOnly: true,
                selector: '.field[data-name="'+name+'"]',
            });
        });

        if (!this.rowActionsDisabled) {
            const acl = {
                edit: this.getAcl().checkModel(this.model, 'edit'),
                delete: this.getAcl().checkModel(this.model, 'delete'),
            };

            this.createView('itemMenu', this.rowActionsView, {
                selector: '.item-menu-container',
                model: this.model,
                acl: acl,
                moveOverRowAction: this.options.moveOverRowAction,
                statusFieldIsEditable: this.options.statusFieldIsEditable,
                rowActionHandlers: this.options.rowActionHandlers || {},
                additionalActionList: this.options.additionalRowActionList,
                scope: this.options.scope,
            });
        }
    }
}

export default KanbanRecordItem;
