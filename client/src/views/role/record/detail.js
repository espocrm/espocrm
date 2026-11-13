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

import DetailRecordView from 'views/record/detail';

export default class RoleDetailRecordView extends DetailRecordView {

    tableView = 'views/role/record/table'

    /**
     * @protected
     * @type {'acl'|'aclPortal'}
     */
    aclType = 'acl'

    hasModifyDetailLayout = true

    sideView = false
    isWide = true
    editModeDisabled = true
    stickButtonsContainerAllTheWay = true

    setup() {
        super.setup();

        this.createView('extra', this.tableView, {
            selector: '.extra',
            model: this.model,
        });
    }

    modifyDetailLayout(layout) {
        /** @type {string[]} */
        const permissionList = this.getMetadata().get(['app', this.aclType, 'valuePermissionList']) || [];

        const presentSet = new Set();

        layout.forEach(panel => {
            (panel.rows || []).forEach(row => {
                (row || []).forEach(cell => {
                    if (cell && cell.name) {
                        presentSet.add(cell.name);
                    }
                });
            });
        });

        const missingList = permissionList.filter(name => {
            return !presentSet.has(name) &&
                this.getMetadata().get(['entityDefs', this.entityType, 'fields', name]);
        });

        if (!missingList.length) {
            return;
        }

        if (!layout[1]) {
            layout[1] = {rows: []};
        }

        const panel = layout[1];

        panel.rows = panel.rows || [];

        const lastRow = panel.rows[panel.rows.length - 1];
        const columnCount = lastRow && lastRow.length ? lastRow.length : 3;

        if (lastRow) {
            let index = lastRow.length;

            while (index > 0 && !lastRow[index - 1]) {
                index--;
            }

            while (index < lastRow.length && missingList.length) {
                lastRow[index] = {name: missingList.shift()};

                index++;
            }
        }

        for (let i = 0; i < missingList.length; i += columnCount) {
            const row = missingList
                .slice(i, i + columnCount)
                .map(name => ({name}));

            while (row.length < columnCount) {
                row.push(false);
            }

            panel.rows.push(row);
        }
    }
}
