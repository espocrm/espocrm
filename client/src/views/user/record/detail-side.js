/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/user/record/detail-side', 'views/record/detail-side', function (Dep) {

    return Dep.extend({

        setupPanels: function () {
            Dep.prototype.setupPanels.call(this);

            if (this.model.isApi() || this.model.isSystem()) {
                this.hidePanel('activities');
                this.hidePanel('history');
                this.hidePanel('tasks');
                this.hidePanel('stream');
                return;
            }

            var showActivities = this.getAcl().checkUserPermission(this.model);

            if (!showActivities) {
                if (this.getAcl().get('userPermission') === 'team') {
                    if (!this.model.has('teamsIds')) {
                        this.listenToOnce(this.model, 'sync', function () {
                            if (this.getAcl().checkUserPermission(this.model)) {
                                this.showPanel('activities', function () {
                                    this.getView('activities').actionRefresh();
                                });
                                this.showPanel('history', function () {
                                    this.getView('history').actionRefresh();
                                });
                                if (!this.model.isPortal()) {
                                    this.showPanel('tasks', function () {
                                        this.getView('tasks').actionRefresh();
                                    });
                                }
                            }
                        }, this);
                    }
                }
            }

            if (!showActivities) {
                this.hidePanel('activities');
                this.hidePanel('history');
                this.hidePanel('tasks');
            }

            if (this.model.isPortal()) {
                this.hidePanel('tasks');
            }
        }
    });
});
