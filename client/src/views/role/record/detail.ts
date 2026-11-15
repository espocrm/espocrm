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
import {LayoutModifier} from 'helpers/misc/role/layout';

export default class RoleDetailRecordView extends DetailRecordView {

    protected tableView: string = 'views/role/record/table'

    protected sideView = null
    protected isWide = true
    protected editModeDisabled = true
    protected stickButtonsContainerAllTheWay = true
    protected hasModifyDetailLayout = true

    protected aclType: 'acl' | 'aclPortal' = 'acl'

    setup() {
        super.setup();

        this.createView('extra', this.tableView, {
            selector: '.extra',
            model: this.model,
        });
    }

    protected modifyDetailLayout(layout: any) {
        new LayoutModifier().modify(this.aclType, layout);
    }
}
