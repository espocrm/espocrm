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

import EditRecordView from 'views/record/edit';
import type TableView from 'views/role/record/table';
import {LayoutModifier} from 'helpers/misc/role/layout';

export default class RoleEditRecordView extends EditRecordView {

    protected tableView: string = 'views/role/record/table'

    protected sideView = null
    protected isWide = true
    protected stickButtonsContainerAllTheWay = true
    protected hasModifyDetailLayout = true

    protected aclType: 'acl' | 'aclPortal' = 'acl'

    fetch() {
        const data = super.fetch();

        data['data'] = this.getTableView().fetchScopeData();
        data['fieldData'] = this.getTableView().fetchFieldData();

        return data;
    }

    setup() {
        super.setup();

        this.wait(
            (async () => {
                const view = await this.createView('extra', this.tableView, {
                    mode: 'edit',
                    selector: '.extra',
                    model: this.model,
                });

                this.listenTo(view, 'change', () => {
                    const data = this.fetch();

                    this.model.setMultiple(data, {ui: true});
                });
            })()
        )
    }

    protected getTableView(): TableView {
        return this.getView('extra') as TableView;
    }

    protected modifyDetailLayout(layout: any) {
        new LayoutModifier().modify(this.aclType, layout);
    }
}
