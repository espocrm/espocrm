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

import RelationshipPanelView from 'views/record/panels/relationship';
import RecordModal from 'helpers/record-modal';

// noinspection JSUnusedGlobalSymbols
export default class CampaignLogRecordsPanelView extends RelationshipPanelView {

    filterList = [
        "all",
        "sent",
        "opened",
        "optedOut",
        "bounced",
        "clicked",
        "optedIn",
        "leadCreated",
    ]

    setup() {
        if (this.getAcl().checkScope('TargetList', 'create')) {
            this.actionList.push({
                action: 'createTargetList',
                label: 'Create Target List',
            });
        }

        this.filterList = Espo.Utils.clone(this.filterList);

        if (!this.getConfig().get('massEmailOpenTracking')) {
            const i = this.filterList.indexOf('opened');

            if (i >= 0) {
                this.filterList.splice(i, 1);
            }
        }

        super.setup();
    }

    actionCreateTargetList() {
        const attributes = {
            sourceCampaignId: this.model.id,
            sourceCampaignName: this.model.attributes.name,
        };

        if (!this.collection.data.primaryFilter) {
            attributes.includingActionList = [];
        } else {
            const status = Espo.Utils.upperCaseFirst(this.collection.data.primaryFilter)
                .replace(/([A-Z])/g, ' $1');

            attributes.includingActionList = [status];
        }

        const helper = new RecordModal();

        helper.showCreate(this, {
            entityType: 'TargetList',
            attributes: attributes,
            fullFormDisabled: true,
            layoutName: 'createFromCampaignLog',
            afterSave: () => {
                Espo.Ui.success(this.translate('Done'));
            },
            beforeRender: view => {
                view.getRecordView().setFieldRequired('includingActionList')
            },
        });
    }
}
