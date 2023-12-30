/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import SidePanelView from 'views/record/panels/side';

/**
 * A default side panel.
 */
class DefaultSidePanelView extends SidePanelView {

    data() {
        const data = super.data();

        if (
            this.complexCreatedDisabled &&
            this.complexModifiedDisabled || (!this.hasComplexCreated && !this.hasComplexModified)
        ) {
            data.complexDateFieldsDisabled = true;
        }

        data.hasComplexCreated = this.hasComplexCreated;
        data.hasComplexModified = this.hasComplexModified;

        return data;
    }

    setup() {
        this.fieldList = Espo.Utils.cloneDeep(this.fieldList);

        this.hasComplexCreated =
            !!this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'createdAt']) &&
            !!this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'createdBy']);

        this.hasComplexModified =
            !!this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'modifiedAt']) &&
            !!this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'modifiedBy']);

        super.setup();
    }

    setupFields() {
        super.setupFields();

        if (!this.complexCreatedDisabled) {
            if (this.hasComplexCreated) {
                this.fieldList.push({
                    name: 'complexCreated',
                    labelText: this.translate('Created'),
                    isAdditional: true,
                    view: 'views/fields/complex-created',
                    readOnly: true,
                });

                if (!this.model.get('createdById')) {
                    this.recordViewObject.hideField('complexCreated');
                }
            }
        } else {
            this.recordViewObject.hideField('complexCreated');
        }

        if (!this.complexModifiedDisabled) {
            if (this.hasComplexModified) {
                this.fieldList.push({
                    name: 'complexModified',
                    labelText: this.translate('Modified'),
                    isAdditional: true,
                    view: 'views/fields/complex-created',
                    readOnly: true,
                    options: {
                        baseName: 'modified',
                    },
                });
            }
            if (!this.model.get('modifiedById')) {
                this.recordViewObject.hideField('complexModified');
            }
        } else {
            this.recordViewObject.hideField('complexModified');
        }

        if (!this.complexCreatedDisabled && this.hasComplexCreated) {
            this.listenTo(this.model, 'change:createdById', () => {
                if (!this.model.get('createdById')) {
                    return;
                }

                this.recordViewObject.showField('complexCreated');
            });
        }

        if (!this.complexModifiedDisabled && this.hasComplexModified) {
            this.listenTo(this.model, 'change:modifiedById', () => {
                if (!this.model.get('modifiedById')) {
                    return;
                }

                this.recordViewObject.showField('complexModified');
            });
        }

        if (this.getMetadata().get(['scopes', this.model.entityType ,'stream']) && !this.getUser().isPortal()) {
            this.fieldList.push({
                name: 'followers',
                labelText: this.translate('Followers'),
                isAdditional: true,
                view: 'views/fields/followers',
                readOnly: true,
            });

            this.controlFollowersField();

            this.listenTo(this.model, 'change:followersIds', () => this.controlFollowersField());
        }
    }

    controlFollowersField() {
        if (this.model.get('followersIds') && this.model.get('followersIds').length) {
            this.recordViewObject.showField('followers');
        } else {
            this.recordViewObject.hideField('followers');
        }
    }
}

export default DefaultSidePanelView;
