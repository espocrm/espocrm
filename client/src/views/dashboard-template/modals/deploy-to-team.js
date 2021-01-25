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

define('views/dashboard-template/modals/deploy-to-team', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        className: 'dialog dialog-record',

        templateContent: '<div class="record">{{{record}}}</div>',

        setup: function () {
            this.buttonList = [
                {
                    name: 'deploy',
                    html: this.translate('Deploy for Team', 'labels', 'DashboardTemplate'),
                    style: 'danger',
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                },
            ];

            this.headerHtml = this.getHelper().escapeString(this.model.get('name'));

            this.formModel = new Model();
            this.formModel.name = 'None';

            this.formModel.setDefs({
                fields: {
                    'team': {
                        type: 'link',
                        entity: 'Team',
                        required: true
                    },
                    'append': {
                        type: 'bool'
                    },
                }
            });

            this.createView('record', 'views/record/edit-for-modal', {
                scope: 'None',
                model: this.formModel,
                el: this.getSelector() + ' .record',
                detailLayout: [
                    {
                        rows: [
                            [
                                {
                                    name: 'team',
                                    labelText: this.translate('team', 'links'),
                                },
                                {
                                    name: 'append',
                                    labelText: this.translate('append', 'fields', 'DashboardTemplate'),
                                }
                            ]
                        ]
                    }
                ],
            });
        },

        actionDeploy: function () {
            if (this.getView('record').processFetch()) {
                Espo.Ajax.postRequest('DashboardTemplate/action/deployToTeam', {
                    id: this.model.id,
                    teamId: this.formModel.get('teamId'),
                    append: this.formModel.get('append'),
                }).then(function () {
                    Espo.Ui.success(this.translate('Done'));
                    this.close();
                }.bind(this));
            }
        },
    });
});
