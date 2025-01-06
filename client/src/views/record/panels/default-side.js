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

import SidePanelView from 'views/record/panels/side';

/**
 * A default side panel.
 */
class DefaultSidePanelView extends SidePanelView {

    /**
     * @protected
     * @type {boolean}
     */
    complexCreatedDisabled

    /**
     * @protected
     * @type {boolean}
     */
    complexModifiedDisabled

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

        const allFieldList = this.getFieldManager().getEntityTypeFieldList(this.model.entityType);

        this.hasComplexCreated =
            allFieldList.includes('createdAt') ||
            allFieldList.includes('createdBy');

        this.hasComplexModified =
            allFieldList.includes('modifiedAt') ||
            allFieldList.includes('modifiedBy');

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

                if (!this.model.get('createdById') && !this.model.get('createdAt')) {
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

                if (!this.isModifiedVisible()) {
                    this.recordViewObject.hideField('complexModified');
                }
            }
        } else {
            this.recordViewObject.hideField('complexModified');
        }

        if (!this.complexCreatedDisabled && this.hasComplexCreated) {
            this.listenTo(this.model, 'change', () => {
                if (!this.model.hasChanged('createdById') && !this.model.hasChanged('createdAt')) {
                    return;
                }

                if (!this.model.get('createdById') && !this.model.get('createdAt')) {
                    return;
                }

                this.recordViewObject.showField('complexCreated');
            });
        }

        if (!this.complexModifiedDisabled && this.hasComplexModified) {
            this.listenTo(this.model, 'change', () => {
                if (!this.model.hasChanged('modifiedById') && !this.model.hasChanged('modifiedAt')) {
                    return;
                }

                if (!this.isModifiedVisible()) {
                    return;
                }

                this.recordViewObject.showField('complexModified');
            });
        }

        if (
            this.getMetadata().get(['scopes', this.model.entityType ,'stream']) &&
            !this.getUser().isPortal()
        ) {
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

    /**
     * @private
     * @return {boolean}
     */
    isModifiedVisible() {
        if (!this.hasComplexModified) {
            return false;
        }

        if (!this.model.get('modifiedById') && !this.model.get('modifiedAt')) {
            return false;
        }

        if (!this.model.get('modifiedById') && this.model.get('modifiedAt') === this.model.get('createdAt')) {
            return false;
        }

        return true;
    }

    controlFollowersField() {
        if (this.model.get('followersIds') && this.model.get('followersIds').length) {
            this.recordViewObject.showField('followers');

            return;
        }

        this.recordViewObject.hideField('followers');
    }
}

export default DefaultSidePanelView;
