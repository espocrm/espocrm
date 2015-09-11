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


Espo.define('crm:views/campaign/record/detail-bottom', 'views/record/detail-bottom', function (Dep) {

    return Dep.extend({

        setupPanels: function () {
            Dep.prototype.setupPanels.call(this);

            this.panelList.unshift({
                name: 'massEmails',
                label: this.translate('massEmails', 'links', 'Campaign'),
                view: 'views/record/panels/relationship',
                sticked: true,
                hidden: true,
                select: false,
                rowActionsView: 'views/record/row-actions/relationship-no-unlink'
            });

            this.panelList.unshift({
                name: 'trackingUrls',
                label: this.translate('trackingUrls', 'links', 'Campaign'),
                view: 'views/record/panels/relationship',
                sticked: true,
                hidden: true,
                select: false,
                rowActionsView: 'views/record/row-actions/relationship-no-unlink'
            });

            this.listenTo(this.model, 'change', function () {
                this.manageMassEmails();
            }, this);
        },

        afterRender: function () {
            Dep.prototype.setupPanels.call(this);
            this.manageMassEmails();
        },

        manageMassEmails: function () {
            var parentView = this.getParentView();
            if (!parentView) return;
            if (this.model.get('type') == 'Email') {
                parentView.showPanel('massEmails');
                parentView.showPanel('trackingUrls');
            } else {
                parentView.hidePanel('massEmails');
                parentView.showPanel('trackingUrls');
            }
        }


    });
});


