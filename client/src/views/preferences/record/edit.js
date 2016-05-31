/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('views/preferences/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        sideView: null,

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
            {
                name: 'reset',
                label: 'Reset',
                style: 'danger'
            }
        ],

        dependencyDefs: {
            'smtpAuth': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['smtpUsername', 'smtpPassword']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['smtpUsername', 'smtpPassword']
                    }
                ]
            },
            'useCustomTabList': {
                map: {
                    true: [
                        {
                            action: 'show',
                            fields: ['tabList']
                        }
                    ]
                },
                default: [
                    {
                        action: 'hide',
                        fields: ['tabList']
                    }
                ]
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.id == this.getUser().id) {
                this.on('after:save', function () {
                    var data = this.model.toJSON();
                    delete data['smtpPassword'];
                    this.getPreferences().set(data);
                    this.getPreferences().trigger('update');
                }, this);
            }

            if (!this.getUser().isAdmin()) {
                this.hideField('dashboardLayout');
            }
        },

        actionReset: function () {
            if (confirm(this.translate('resetPreferencesConfirmation', 'messages'))) {
                $.ajax({
                    url: 'Preferences/' + this.model.id,
                    type: 'DELETE',
                }).done(function (data) {
                    Espo.Ui.success(this.translate('resetPreferencesDone', 'messages'));
                    this.model.set(data);
                    this.getPreferences().set(this.model.toJSON());
                    this.getPreferences().trigger('update');
                }.bind(this));
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (!this.getConfig().get('assignmentEmailNotifications')) {
                this.hideField('receiveAssignmentEmailNotifications');
            }

            if (this.getConfig().get('userThemesDisabled')) {
                this.hideField('theme');
            }

            var smtpSecurityField = this.getFieldView('smtpSecurity');
            this.listenTo(smtpSecurityField, 'change', function () {
                var smtpSecurity = smtpSecurityField.fetch()['smtpSecurity'];
                if (smtpSecurity == 'SSL') {
                    this.model.set('smtpPort', '465');
                } else if (smtpSecurity == 'TLS') {
                    this.model.set('smtpPort', '587');
                } else {
                    this.model.set('smtpPort', '25');
                }
            }.bind(this));
        },

        exit: function (after) {
            if (after == 'cancel') {
                this.getRouter().navigate('#User/view/' + this.model.id, {trigger: true});
            }
        },

    });

});
