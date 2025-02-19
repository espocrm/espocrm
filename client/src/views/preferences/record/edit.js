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

import EditRecordView from 'views/record/edit';

class PreferencesEditRecordView extends EditRecordView {

    sideView = null
    saveAndContinueEditingAction = false

    buttonList = [
        {
            name: 'save',
            label: 'Save',
            style: 'primary',
        },
        {
            name: 'cancel',
            label: 'Cancel',
        }
    ]

    dynamicLogicDefs = {
        fields: {
            'tabList': {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isTrue',
                            attribute: 'useCustomTabList',
                        }
                    ]
                }
            },
            'addCustomTabs': {
                visible: {
                    conditionGroup: [
                        {
                            type: 'isTrue',
                            attribute: 'useCustomTabList',
                        }
                    ]
                }
            },
        },
    }

    setup() {
        super.setup();

        const model = /** @type {import('models/preferences').default} */this.model;

        this.addDropdownItem({
            name: 'reset',
            text: this.getLanguage().translate('Reset to Default', 'labels', 'Admin'),
            style: 'danger',
            onClick: () => this.actionReset(),
        });

        const forbiddenEditFieldList = this.getAcl().getScopeForbiddenFieldList('Preferences', 'edit');

        if (!forbiddenEditFieldList.includes('dashboardLayout') && !model.isPortal()) {
            this.addDropdownItem({
                name: 'resetDashboard',
                text: this.getLanguage().translate('Reset Dashboard to Default', 'labels', 'Preferences'),
                onClick: () => this.actionResetDashboard(),
            });
        }

        if (model.isPortal()) {
            this.layoutName = 'detailPortal';
        }

        if (this.model.id === this.getUser().id) {
            const preferencesModel = this.getPreferences();

            this.on('save', (a, attributeList) => {
                const data = this.model.getClonedAttributes();

                delete data['smtpPassword'];

                preferencesModel.set(data);
                preferencesModel.trigger('update', attributeList);
            });
        }

        if (!this.getUser().isAdmin() || model.isPortal()) {
            this.hidePanel('dashboard');
            this.hideField('dashboardLayout');
        }

        this.controlFollowCreatedEntityListVisibility();
        this.listenTo(this.model, 'change:followCreatedEntities', this.controlFollowCreatedEntityListVisibility);

        this.controlColorsField();
        this.listenTo(this.model, 'change:scopeColorsDisabled', () => this.controlColorsField());

        let hideNotificationPanel = true;

        if (!this.getConfig().get('assignmentEmailNotifications') || model.isPortal()) {
            this.hideField('receiveAssignmentEmailNotifications');
            this.hideField('assignmentEmailNotificationsIgnoreEntityTypeList');
        } else {
            hideNotificationPanel = false;

            this.controlAssignmentEmailNotificationsVisibility();

            this.listenTo(this.model, 'change:receiveAssignmentEmailNotifications', () => {
                this.controlAssignmentEmailNotificationsVisibility();
            });
        }

        if ((this.getConfig().get('assignmentEmailNotificationsEntityList') || []).length === 0) {
            this.hideField('assignmentEmailNotificationsIgnoreEntityTypeList');
        }

        if (
            (this.getConfig().get('assignmentNotificationsEntityList') || []).length === 0 ||
            model.isPortal()
        ) {
            this.hideField('assignmentNotificationsIgnoreEntityTypeList');
        } else {
            hideNotificationPanel = false;
        }

        if (this.getConfig().get('emailForceUseExternalClient')) {
            this.hideField('emailUseExternalClient');
        }

        if (!this.getConfig().get('mentionEmailNotifications') || model.isPortal()) {
            this.hideField('receiveMentionEmailNotifications');
        } else {
            hideNotificationPanel = false;
        }

        if (!this.getConfig().get('streamEmailNotifications') && !model.isPortal()) {
            this.hideField('receiveStreamEmailNotifications');
        } else if (!this.getConfig().get('portalStreamEmailNotifications') && model.isPortal()) {
            this.hideField('receiveStreamEmailNotifications');
        } else {
            hideNotificationPanel = false;
        }

        if (hideNotificationPanel) {
            this.hidePanel('notifications');
        }

        if (this.getConfig().get('userThemesDisabled')) {
            this.hideField('theme');
        }

        this.on('save', /** Record */initialAttributes => {
            if (
                this.model.get('language') !== initialAttributes.language ||
                this.model.get('theme') !== initialAttributes.theme ||
                (this.model.get('themeParams') || {}).navbar !== (initialAttributes.themeParams || {}).navbar ||
                this.model.get('pageContentWidth') !== initialAttributes.pageContentWidth
            ) {
                this.setConfirmLeaveOut(false);

                window.location.reload();
            }
        });
    }

    controlFollowCreatedEntityListVisibility() {
        if (!this.model.get('followCreatedEntities')) {
            this.showField('followCreatedEntityTypeList');
        } else {
            this.hideField('followCreatedEntityTypeList');
        }
    }

    controlColorsField() {
        if (this.model.get('scopeColorsDisabled')) {
            this.hideField('tabColorsDisabled');
        } else {
            this.showField('tabColorsDisabled');
        }
    }

    controlAssignmentEmailNotificationsVisibility() {
        if (this.model.get('receiveAssignmentEmailNotifications')) {
            this.showField('assignmentEmailNotificationsIgnoreEntityTypeList');
        } else {
            this.hideField('assignmentEmailNotificationsIgnoreEntityTypeList');
        }
    }

    actionReset() {
        this.confirm(this.translate('resetPreferencesConfirmation', 'messages'), () => {
            Espo.Ajax
                .deleteRequest(`Preferences/${this.model.id}`)
                .then(data => {
                    Espo.Ui.success(this.translate('resetPreferencesDone', 'messages'));

                    this.model.set(data);

                    for (const attribute in data) {
                        this.setInitialAttributeValue(attribute, data[attribute]);
                    }

                    this.getPreferences().set(this.model.getClonedAttributes());
                    this.getPreferences().trigger('update');

                    this.setIsNotChanged();
                });
        });
    }

    actionResetDashboard() {
        this.confirm(this.translate('confirmation', 'messages'), () => {
            Espo.Ajax.postRequest('Preferences/action/resetDashboard', {id: this.model.id})
                .then(data =>  {
                    const isChanged = this.isChanged;

                    Espo.Ui.success(this.translate('Done'));

                    this.model.set(data);

                    for (const attribute in data) {
                        this.setInitialAttributeValue(attribute, data[attribute]);
                    }

                    this.getPreferences().set(this.model.getClonedAttributes());
                    this.getPreferences().trigger('update');

                    if (!isChanged) {
                        this.setIsNotChanged();
                    }
                });
        });
    }

    exit(after) {
        if (after === 'cancel') {
            let url = `#User/view/${this.model.id}`;

            if (!this.getAcl().checkModel(this.getUser())) {
                url = '#';
            }

            this.getRouter().navigate(url, {trigger: true});
        }
    }

    handleShortcutKeyCtrlS(e) {
        this.handleShortcutKeyCtrlEnter(e);
    }
}

export default PreferencesEditRecordView;
