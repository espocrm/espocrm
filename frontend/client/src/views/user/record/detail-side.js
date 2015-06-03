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
 ************************************************************************/

Espo.define('Views.User.Record.DetailSide', 'Views.Record.DetailSide', function (Dep) {

    return Dep.extend({

        panelList: [
            {
                name: 'default',
                label: false,
                view: 'Record.Panels.Side',
                options: {
                    fieldList: ['avatar'],
                    mode: 'detail',
                }
            }
        ],

        setupPanels: function () {
            Dep.prototype.setupPanels.call(this);

            var showActivities = false;

            if (this.getUser().isAdmin()) {
                showActivities = true;
            } else {
                if (this.getAcl().get('userPermission') === 'no') {
                    if (this.model.id == this.getUser().id) {
                        showActivities = true;
                    }
                } else if (this.getAcl().get('userPermission') === 'team') {
                    if (this.model.has('teamsIds')) {
                        this.model.get('teamsIds').forEach(function (id) {
                            if (~(this.getUser().get('teamsIds') || []).indexOf(id)) {
                                showActivities = true;
                            }
                        }, this);
                    } else {
                        this.listenToOnce(this.model, 'sync', function () {
                            this.model.get('teamsIds').forEach(function (id) {
                                if (~(this.getUser().get('teamsIds') || []).indexOf(id)) {
                                    this.getParentView().showPanel('activities');
                                    this.getParentView().showPanel('history');
                                    this.getView('activities').actionRefresh();
                                    this.getView('history').actionRefresh();
                                }
                            }, this);
                        }, this);
                    }
                } else {
                    showActivities = true;
                }
            }

            this.panelList.push({
                "name":"activities",
                "label":"Activities",
                "view":"Crm:Record.Panels.Activities",
                "hidden": !showActivities
            });
            this.panelList.push({
                "name":"history",
                "label":"History",
                "view":"Crm:Record.Panels.History",
                "hidden": !showActivities
            });

        },

    });

});

