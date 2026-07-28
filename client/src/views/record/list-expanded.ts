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

import ListBaseRecordView, {ListBaseRecordViewOptions, ListBaseRecordViewSchema} from 'views/record/list-base';
import type Model from 'model';

type Cell = {
    name: string,
    view?: string,
    soft?: boolean,
    small?: boolean,
    link?: boolean;
    align?: 'left' | 'right',
    type?: string;
    params?: Record<string, any>;
    options?: Record<string, any>;
}

type Row = Cell[];

export interface ListExpandedLayout {
    rows: Row[];
    right: {
        name?: string,
        view?: string,
        options?: Record<string, any>,
        width?: string,
    } | null;
}

export interface ListExpandedRecordViewOptions extends ListBaseRecordViewOptions<ListExpandedLayout> {
    /**
     * Force pagination.
     */
    forcePagination?: boolean;
}

export interface ListExpandedRecordViewSchema extends ListBaseRecordViewSchema<ListExpandedLayout> {
    options: ListExpandedRecordViewOptions;
}

class ListExpandedRecordView<
    S extends ListExpandedRecordViewSchema = ListExpandedRecordViewSchema
> extends ListBaseRecordView<ListExpandedLayout, S> {

    template = 'record/list-expanded'

    protected checkboxes: boolean = false

    protected selectable: boolean = false

    protected rowActionsView: string | null = null

    /**
     * @internal
     */
    protected _internalLayoutType: string = 'list-row-expanded'

    protected paginationDisabled: boolean = true

    protected header: boolean = false

    protected listContainerEl: string = '> .list > ul'

    protected columnResize = false

    protected init() {
        if (this.options.forcePagination) {
            this.paginationDisabled = false;
        }

        super.init();
    }

    protected setup() {
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

    /**
     * @internal
     */
    protected _loadListLayout(callback: (layout: ListExpandedLayout) => void) {
        const type = this.type + 'Expanded';

        this.layoutLoadCallbackList.push(callback);

        if (this.layoutIsBeingLoaded) {
            return;
        }

        this.layoutIsBeingLoaded = true;

        this.getHelper().layoutManager.get(this.collection.entityType, type, (listLayout: any) => {
            this.layoutLoadCallbackList.forEach(callbackItem => {
                callbackItem(listLayout);

                this.layoutLoadCallbackList = [];
                this.layoutIsBeingLoaded = false;
            });
        });
    }

    /**
     * @internal
     */
    protected _convertLayout(listLayout: ListExpandedLayout, model?: Model) {
        model = model || this.collection.prepareModel();

        const layout = {
            rows: [],
            right: false,
        } as any;

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
                } as any;

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
                        name: name,
                        defs: {
                            params: {
                                width: listLayout.right.width || '7%',
                            }
                        }
                    },
                };
            }
        } else if (this.rowActionsView) {
            layout.right = this.getRowActionsDefs();
        }

        return layout;
    }

    protected getRowSelector(id: string): string {
        return `li[data-id="${id}"]`;
    }

    protected getCellSelector(
        model: Model,
        item: Record<string, any> & {columnName: string},
    ): string {

        const name = item.field || item.columnName;

        if (!model.id) {
            console.warn(`No record ID.`);
        }

        return `${this.getSelector()} ${this.getRowSelector(model.id!)} .cell[data-name="${name}"]`;
    }

    protected getRowContainerHtml(id: string): string {
        const li = document.createElement('li');
        li.dataset.id = id;
        li.className = 'list-group-item list-row';

        if (this.isRecordStared(id)) {
            li.classList.add('starred');
        }

        return li.outerHTML;
    }

    /**
     * @internal
     */
    protected prepareInternalLayout(internalLayout: any, model: Model) {
        const rows = (internalLayout.rows ?? []) as any[][];

        rows.forEach(row => {
            row.forEach(cell => {
                cell.options ??= {};
                cell.options.fullSelector = this.getCellSelector(model, cell)
            });
        });

        if (internalLayout.right) {
            internalLayout.right.options ??= {};
            internalLayout.right.options.fullSelector = this.getCellSelector(model, internalLayout.right);
        }
    }

    protected fetchAttributeListFromLayout(): string[] {
        const entityType = this.entityType;

        if (!this.listLayout?.rows || !entityType) {
            return [];
        }

        const list: string[] = [];

        this.listLayout.rows.forEach(row => {
            row.forEach(item => {
                if (!item.name) {
                    return;
                }

                const field = item.name;

                const fieldType = this.getMetadata().get(['entityDefs', entityType, 'fields', field, 'type']);

                if (!fieldType) {
                    return;
                }

                this.getFieldManager()
                    .getEntityTypeFieldAttributeList(entityType, field)
                    .forEach((attribute) => list.push(attribute));
            });
        });

        return list;
    }
}

export default ListExpandedRecordView;
