/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/preferences/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        sideView: null,

        saveAndContinueEditingAction: false,

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            }
        ],

        dynamicLogicDefs: {
            fields: {
                'smtpUsername': {
                    visible: {
                        conditionGroup: [
                            {
                                type: 'isTrue',
                                attribute: 'smtpAuth',
                            },
                            {
                                type: 'isNotEmpty',
                                attribute: 'smtpServer',
                            }
                        ]
                    }
                },
                'smtpPassword': {
                    visible: {
                        conditionGroup: [
                            {
                                type: 'isTrue',
                                attribute: 'smtpAuth',
                            },
                            {
                                type: 'isNotEmpty',
                                attribute: 'smtpServer',
                            }
                        ]
                    }
                },
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
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.addDropdownItem({
                name: 'reset',
                html: this.getLanguage().translate('Reset to Default', 'labels', 'Admin'),
                style: 'danger'
            });

            var forbiddenEditFieldList = this.getAcl().getScopeForbiddenFieldList('Preferences', 'edit');

            if (!~forbiddenEditFieldList.indexOf('dashboardLayout')) {
                this.addDropdownItem({
                    name: 'resetDashboard',
                    html: this.getLanguage().translate('Reset Dashboard to Default', 'labels', 'Preferences')
                });
            }

            if (this.model.isPortal()) {
                this.layoutName = 'detailPortal';
            }

            if (this.model.id === this.getUser().id) {
                this.on('after:save', () => {
                    var data = this.model.toJSON();
                    delete data['smtpPassword'];
                    this.getPreferences().set(data);
                    this.getPreferences().trigger('update');
                });
            }

            if (!this.getUser().isAdmin() || this.model.isPortal()) {
                this.hideField('dashboardLayout');
            }

            this.controlFollowCreatedEntityListVisibility();
            this.listenTo(this.model, 'change:followCreatedEntities', this.controlFollowCreatedEntityListVisibility);

            this.controlColorsField();
            this.listenTo(this.model, 'change:scopeColorsDisabled', this.controlColorsField, this);

            var hideNotificationPanel = true;

            if (!this.getConfig().get('assignmentEmailNotifications') || this.model.isPortal()) {
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
                this.model.isPortal()
            ) {
                this.hideField('assignmentNotificationsIgnoreEntityTypeList');
            } else {
                hideNotificationPanel = false;
            }

            if (this.getConfig().get('emailForceUseExternalClient')) {
                this.hideField('emailUseExternalClient');
            }

            if (!this.getConfig().get('mentionEmailNotifications') || this.model.isPortal()) {
                this.hideField('receiveMentionEmailNotifications');
            } else {
                hideNotificationPanel = false;
            }

            if (!this.getConfig().get('streamEmailNotifications') && !this.model.isPortal()) {
                this.hideField('receiveStreamEmailNotifications');
            } else if (!this.getConfig().get('portalStreamEmailNotifications') && this.model.isPortal()) {
                this.hideField('receiveStreamEmailNotifications');
            } else {
                hideNotificationPanel = false;
            }

            if (this.getConfig().get('scopeColorsDisabled')) {
                this.hideField('scopeColorsDisabled');
                this.hideField('tabColorsDisabled');
            }

            if (this.getConfig().get('tabColorsDisabled')) {
                this.hideField('tabColorsDisabled');
            }

            if (hideNotificationPanel) {
                this.hidePanel('notifications');
            }

            if (this.getConfig().get('userThemesDisabled')) {
                this.hideField('theme');
            }

            this.on('save', (initialAttributes) => {
                if (
                    this.model.get('language') !== initialAttributes.language ||
                    this.model.get('theme') !== initialAttributes.theme

                ) {
                    this.setConfirmLeaveOut(false);

                    window.location.reload();
                }
            });

            this.listenTo(this.model, 'change:smtpSecurity', (model, smtpSecurity, o) => {
                if (!o.ui) {
                    return;
                }

                if (smtpSecurity === 'SSL') {
                    this.model.set('smtpPort', '465');
                } else if (smtpSecurity === 'TLS') {
                    this.model.set('smtpPort', '587');
                } else {
                    this.model.set('smtpPort', '25');
                }
            });

            if (this.getAcl().checkScope('EmailAccount') && !this.model.get('smtpServer')) {
                this.hideField('smtpServer');
                this.hideField('smtpPort');
                this.hideField('smtpSecurity');
                this.hideField('smtpServer');
                this.hideField('smtpAuth');
                this.hideField('smtpUsername');
                this.hideField('smtpPassword');
                this.hideField('testSend');
                this.hideField('smtpEmailAddress');
                this.hidePanel('smtp');
            }
        },

        controlFollowCreatedEntityListVisibility: function () {
            if (!this.model.get('followCreatedEntities')) {
                this.showField('followCreatedEntityTypeList');
            } else {
                this.hideField('followCreatedEntityTypeList');
            }
        },

        controlColorsField: function () {
            if (this.model.get('scopeColorsDisabled')) {
                this.hideField('tabColorsDisabled');
            } else {
                this.showField('tabColorsDisabled');
            }
        },

        controlAssignmentEmailNotificationsVisibility: function () {
            if (this.model.get('receiveAssignmentEmailNotifications')) {
                this.showField('assignmentEmailNotificationsIgnoreEntityTypeList');
            } else {
                this.hideField('assignmentEmailNotificationsIgnoreEntityTypeList');
            }
        },

        actionReset: function () {
            this.confirm(this.translate('resetPreferencesConfirmation', 'messages'), () => {
                $.ajax({
                    url: 'Preferences/' + this.model.id,
                    type: 'DELETE',
                }).then((data) => {
                    Espo.Ui.success(this.translate('resetPreferencesDone', 'messages'));

                    this.model.set(data);

                    for (var attribute in data) {
                        this.setInitalAttributeValue(attribute, data[attribute]);
                    }

                    this.getPreferences().set(this.model.toJSON());
                    this.getPreferences().trigger('update');

                    this.setIsNotChanged();
                });
            });
        },

        actionResetDashboard: function () {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                this.ajaxPostRequest('Preferences/action/resetDashboard', {
                    id: this.model.id
                }).then((data) =>  {
                    Espo.Ui.success(this.translate('Done'));

                    this.model.set(data);

                    for (var attribute in data) {
                        this.setInitalAttributeValue(attribute, data[attribute]);
                    }

                    this.getPreferences().set(this.model.toJSON());
                    this.getPreferences().trigger('update');
                });
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        exit: function (after) {
            if (after === 'cancel') {
                var url = '#User/view/' + this.model.id;

                if (!this.getAcl().checkModel(this.getUser())) {
                    url = '#';
                }

                this.getRouter().navigate(url, {trigger: true});
            }
        },
    });
});
