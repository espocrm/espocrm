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

/** @module module:views/record/list-expanded */

import ListRecordView from 'views/record/list';

class ListExpandedRecordView extends ListRecordView {

    template = 'record/list-expanded'

    checkboxes = false
    selectable = false
    rowActionsView = false
    _internalLayoutType = 'list-row-expanded'
    presentationType = 'expanded'
    paginationDisabled = true
    header = false
    _internalLayout = null
    checkedList = null
    listContainerEl = '> .list > ul'
    columnResize = false

    init() {
        if (this.options.forcePagination) {
            this.paginationDisabled = false;
        }

        super.init();
    }

    setup() {
        super.setup();

        this.on('after:save', model => {
            const view = this.getView(model.id);

            if (!view) {
                return;
            }

            view.reRender();
        });

        // Prevents displaying an empty buttons container.
        this.displayTotalCount = false;
    }

    _loadListLayout(callback) {
        const type = this.type + 'Expanded';

        this.layoutLoadCallbackList.push(callback);

        if (this.layoutIsBeingLoaded) {
            return;
        }

        this.layoutIsBeingLoaded = true;

        this._helper.layoutManager.get(this.collection.entityType, type, listLayout => {
            this.layoutLoadCallbackList.forEach(c => {
                c(listLayout);

                this.layoutLoadCallbackList = [];
                this.layoutIsBeingLoaded = false;
            });
        });
    }

    _convertLayout(listLayout, model) {
        model = model || this.collection.prepareModel();

        const layout = {
            rows: [],
            right: false,
        };

        for (const i in listLayout.rows) {
            const row = listLayout.rows[i];
            const layoutRow = [];

            for (const j in row) {
                const rowItem = row[j];
                const type = rowItem.type || model.getFieldType(rowItem.name) || 'base';

                const item = {
                    name: rowItem.name + 'Field',
                    field: rowItem.name,
                    view: rowItem.view ||
                        model.getFieldParam(rowItem.name, 'view') ||
                        this.getFieldManager().getViewName(type),
                    options: {
                        defs: {
                            name: rowItem.name,
                            params: rowItem.params || {}
                        },
                        mode: 'list',
                    },
                    align: rowItem.align,
                    small: rowItem.small,
                    soft: rowItem.soft,
                };

                if (rowItem.options) {
                    for (const optionName in rowItem.options) {
                        if (typeof item.options[optionName] !== 'undefined') {
                            continue;
                        }

                        item.options[optionName] = rowItem.options[optionName];
                    }
                }

                if (rowItem.link) {
                    item.options.mode = 'listLink';
                }

                layoutRow.push(item);
            }

            layout.rows.push(layoutRow);
        }

        if ('right' in listLayout) {
            if (listLayout.right) {
                const name = listLayout.right.name || 'right';

                layout.right = {
                    field: name,
                    name: name,
                    view: listLayout.right.view,
                    options: {
                        defs: {
                            params: {
                                width: listLayout.right.width || '7%',
                            }
                        }
                    },
                };
            }
        }
        else if (this.rowActionsView) {
            layout.right = this.getRowActionsDefs();
        }

        return layout;
    }

    getRowSelector(id) {
        return 'li[data-id="' + id + '"]';
    }

    getCellSelector(model, item) {
        const name = item.field || item.columnName;

        return `${this.getSelector()} ${this.getRowSelector(model.id)} .cell[data-name="${name}"]`;
    }

    getRowContainerHtml(id) {
        return $('<li>')
            .attr('data-id', id)
            .addClass('list-group-item list-row')
            .get(0).outerHTML;
    }

    prepareInternalLayout(internalLayout, model) {
        const rows = internalLayout.rows || [];

        rows.forEach(row => {
            row.forEach(cell => {
                //cell.fullSelector = this.getCellSelector(model, cell);

                cell.options ??= {};
                cell.options.fullSelector = this.getCellSelector(model, cell)
            });
        });

        if (internalLayout.right) {
            //internalLayout.right.fullSelector = this.getCellSelector(model, internalLayout.right);

            internalLayout.right.options ??= {};
            internalLayout.right.options.fullSelector = this.getCellSelector(model, internalLayout.right);
        }
    }

    fetchAttributeListFromLayout() {
        const list = [];

        if (this.listLayout.rows) {
            this.listLayout.rows.forEach((row) => {
                row.forEach(item => {
                    if (!item.name) {
                        return;
                    }

                    const field = item.name;

                    const fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', field, 'type']);

                    if (!fieldType) {
                        return;
                    }

                    this.getFieldManager()
                        .getEntityTypeFieldAttributeList(this.scope, field)
                        .forEach((attribute) => {
                            list.push(attribute);
                        });
                });
            });
        }

        return list;
    }
}

export default ListExpandedRecordView;
