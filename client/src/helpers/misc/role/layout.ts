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

import Metadata from 'metadata';
import {inject} from 'di';
import {PanelDefs, CellDefs} from 'views/record/detail';

const COLUMN_COUNT = 3;

/**
 * @internal
 */
export class LayoutModifier {

    @inject(Metadata)
    private metadata: Metadata

    modify(type: 'acl' | 'aclPortal', layout: any) {
        if (!isProperLayout(layout)) {
            throw new Error("Bad detail layout.");
        }

        const permissions: string[] = this.metadata.get(`app.${type}.valuePermissionList`) ?? [];

        const present = new Set<string>();

        const panel = layout.find(it => it.name === 'permissions');

        if (!panel) {
            console.warn(`No 'permissions' panel.`);

            return;
        }

        panel.rows ??= []

        panel.rows?.forEach(row => {
            row.forEach(cell => {
                if (!cell || !cell.name) {
                    return;
                }

                present.add(cell.name);
            });
        });

        const missingPermissions = permissions.filter(it => !present.has(it));

        if (!missingPermissions.length) {
            return;
        }

        let row: (CellDefs | false)[] = [];

        const columnCount = COLUMN_COUNT;

        for (const [i, permission] of missingPermissions.entries()) {
            if (i % columnCount === 0) {
                row = [];

                panel.rows?.push(row);
            }

            row.push({
                name: permission,
            });

            if (i === missingPermissions.length - 1) {
                while (row.length < columnCount) {
                    row.push(false);
                }
            }
        }
    }
}

function isProperLayout(value: unknown): value is PanelDefs[] {
    return Array.isArray(value) && value.every(item => {
        return typeof item === 'object' && item !== null;
    });
}

