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

export default class ListColumnWidthControlHelper {

    /**
     * A min width in pixels.
     *
     * @private
     * @type {number}
     */
    minWidth = 30

    /**
     * @param {{
     *     view?: import('views/record/list').default,
     *     helper: import('helpers/list/settings').default,
     *     layoutProvider?: function(): {
     *     name: string,
     *     width?: number,
     *     widthPx?: number,
     *     hidden?: boolean,
     * }[]
     * }} options
     */
    constructor(options) {
        /** @private */
        this.view = options.view;
        /** @private */
        this.helper = options.helper;
        /** @private */
        this.layoutProvider = options.layoutProvider;
    }

    /**
     * Adjust widths.
     *
     * @param {{
     *     tableWidth?: number,
     *     staticWidth?: number,
     * }} [options]
     * @return {boolean}
     */
    adjust(options = {}) {
        let tableWidthData;

        if (options.tableWidth === undefined || options.staticWidth === undefined) {
            tableWidthData = this.getTableWidths();
        }

        const tableWidth = options.tableWidth === undefined ? tableWidthData.table : options.tableWidth;
        const staticWidth = options.staticWidth === undefined ? tableWidthData.static : options.staticWidth;

        const widthMap = this.helper.getColumnWidthMap();

        /**
         * @type {{
         *     name: string,
         *     width: {
         *         value: number,
         *         unit: 'px'|'%',
         *     }|null,
         *     isCustom: boolean,
         *     widthPx: number|null,
         * }[]}
         */
        const list = this.layoutProvider()
            .filter(it => !this.helper.isColumnHidden(it.name, it.hidden))
            .map(it => {
                let widthItem = widthMap[it.name];

                const isCustom = !!widthItem;

                if (!widthItem) {
                    widthItem = null;

                    if (it.width) {
                        widthItem = {value: it.width, unit: '%'};
                    } else if (it.widthPx) {
                        widthItem = {value: it.widthPx, unit: 'px'};
                    }
                }

                let widthPx = null;

                if (widthItem) {
                    if (widthItem.unit === 'px') {
                        widthPx = widthItem.value;
                    } else {
                        widthPx = tableWidth * (widthItem.value / 100.0);
                    }
                }

                return {
                    name: it.name,
                    width: widthItem,
                    isCustom: isCustom,
                    widthPx: widthPx,
                };
            });

        const flexColumnCount = list.filter(it => !it.width).length;
        const extraWidth = flexColumnCount * this.minWidth;

        let sumWidth = 0;

        list.filter(it => it.widthPx)
            .forEach(it => sumWidth += it.widthPx);

        if (tableWidth - extraWidth - staticWidth - sumWidth >= 0) {
            return true;
        }

        const listSorted = list
            .filter(it => it.widthPx && it.width)
            .sort((a, b) => b.widthPx - a.widthPx);

        if (!listSorted.length) {
            return true;
        }

        const item = listSorted[0];

        const reduceWidthPx = 10;

        if (item.widthPx < reduceWidthPx) {
            return true;
        }

        /** @type {{value: number, unit: 'px'|'%'}} */
        let newWidth;

        if (item.width.unit === 'px') {
            newWidth = {
                value: item.width.value - reduceWidthPx,
                unit: 'px',
            };
        } else {
            const factor = Math.pow(10, 4);
            const reducePercent = Math.floor(factor * (reduceWidthPx / tableWidth) * 100) / factor;

            newWidth = {
                value: item.width.value - reducePercent,
                unit: '%',
            }
        }

        const map = this.helper.getColumnWidthMap();
        map[item.name] = newWidth;

        this.helper.storeColumnWidthMap(map);

        this.adjust({tableWidth, staticWidth});

        return false;
    }

    /**
     * @private
     * @return {{
     *     table: number,
     *     static: number,
     * }|null}
     */
    getTableWidths() {
        const tableElement = this.view.element.querySelector('.list > table');

        if (!tableElement) {
            return null;
        }

        const tableWidth = tableElement.clientWidth;
        let staticWidth = 0;

        tableElement.querySelectorAll(':scope > thead > tr > th').forEach(th => {
            if (
                !th.classList.contains('field-header-cell') ||
                th.classList.contains('action-cell')
            ) {
                staticWidth += th.clientWidth;
            }
        });

        return {
            table: tableWidth,
            static: staticWidth,
        };
    }
}
