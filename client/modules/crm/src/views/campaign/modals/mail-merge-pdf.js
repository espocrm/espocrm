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

define('crm:views/campaign/modals/mail-merge-pdf', ['views/modal', 'ui/select'],
function (Dep, /** module:ui/select */ Select) {

    return Dep.extend({

        template: 'crm:campaign/modals/mail-merge-pdf',

        data: function () {
            return {
                linkList: this.linkList,
            };
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerText = this.translate('Generate Mail Merge PDF', 'labels', 'Campaign');

            var linkList = ['contacts', 'leads', 'accounts', 'users'];
            this.linkList = [];

            linkList.forEach(link => {
                if (!this.model.get(link + 'TemplateId')) {
                    return;
                }

                let targetEntityType = this.getMetadata()
                    .get(['entityDefs', 'TargetList', 'links', link, 'entity']);

                if (!this.getAcl().checkScope(targetEntityType)) {
                    return;
                }

                this.linkList.push(link);
            });

            this.buttonList.push({
                name: 'proceed',
                label: 'Proceed',
                style: 'danger'
            });

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });
        },

        afterRender: function () {
            Select.init(this.$el.find('.field[data-name="link"] select'));
        },

        actionProceed: function () {
            let link = this.$el.find('.field[data-name="link"] select').val();

            this.trigger('proceed', link);
        },
    });
});
