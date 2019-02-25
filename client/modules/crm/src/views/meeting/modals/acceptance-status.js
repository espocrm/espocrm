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

define('crm:views/meeting/modals/acceptance-status', 'views/modal', function (Dep) {

    return Dep.extend({

        backdrop: true,

        templateContent: `
            {{#each viewObject.statusDataList}}
                <p>
                    <button class="action btn btn-{{style}} btn-x-wide" type="button" data-action="setStatus" data-status="{{name}}">{{label}}</button>
                </p>
            {{/each}}
        `,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.headerHtml = this.escapeString(this.translate(this.model.entityType, 'scopeNames'))  + ' &raquo ' +
                this.escapeString(this.model.get('name')) + ' &raquo ' + this.translate('Acceptance', 'labels', 'Meeting');

            var statusList = this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'options']) || [];

            this.statusDataList = [];
            statusList.forEach(function (item) {
                var o = {
                    name: item,
                    style: this.getMetadata().get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', item]) || 'default',
                    label: this.getLanguage().translateOption(item, 'acceptanceStatus', this.model.entityType)
                };

                this.statusDataList.push(o);
            }, this);
        },

        actionSetStatus: function (data) {
            this.trigger('set-status', data.status);
            this.close();
        }
    });
});
