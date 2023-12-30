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

define('crm:views/campaign/record/detail-bottom', ['views/record/detail-bottom'], function (Dep) {

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
                recordListView: 'crm:views/mass-email/record/list-for-campaign',
                rowActionsView: 'crm:views/mass-email/record/row-actions/for-campaign',
                index: -2,
            });

            this.panelList.unshift({
                name: 'trackingUrls',
                label: this.translate('trackingUrls', 'links', 'Campaign'),
                view: 'views/record/panels/relationship',
                sticked: true,
                hidden: true,
                select: false,
                rowActionsView: 'views/record/row-actions/relationship-no-unlink',
                index: -1,
            });

            this.listenTo(this.model, 'change', () => {
                this.manageMassEmails();
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.manageMassEmails();
        },

        manageMassEmails: function () {
            var parentView = this.getParentView();

            if (!parentView) {
                return;
            }

            if (~['Email', 'Newsletter'].indexOf(this.model.get('type'))) {
                parentView.showPanel('massEmails');
                parentView.showPanel('trackingUrls');
            } else {
                parentView.hidePanel('massEmails');
                parentView.hidePanel('trackingUrls');
            }
        },

    });
});
