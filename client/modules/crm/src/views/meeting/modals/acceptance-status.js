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

define('crm:views/meeting/modals/acceptance-status', ['views/modal'], function (Dep) {

    return Dep.extend({

        backdrop: true,

        templateContent: `
            <div class="margin-bottom">
            <p>{{viewObject.message}}</p>
            </div>
            <div>
                {{#each viewObject.statusDataList}}
                <div class="margin-bottom">
                    <div>
                        <button
                            class="action btn btn-{{style}} btn-x-wide"
                            type="button"
                            data-action="setStatus"
                            data-status="{{name}}"
                        >
                        {{label}}
                        </button>
                        {{#if selected}}<span class="check-icon fas fa-check" style="vertical-align: middle; margin: 0 10px;"></span>{{/if}}
                    </div>
                </div>
                {{/each}}
            </div>
        `,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.$header = $('<span>').append(
                $('<span>').text(this.translate(this.model.entityType, 'scopeNames')),
                ' <span class="chevron-right"></span> ',
                $('<span>').text(this.model.get('name')),
                ' <span class="chevron-right"></span> ',
                $('<span>').text(this.translate('Acceptance', 'labels', 'Meeting'))
            );

            let statusList = this.getMetadata()
                .get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'options']) || [];

            this.statusDataList = [];

            statusList.filter(item => item !== 'None').forEach(item => {
                let o = {
                    name: item,
                    style: this.getMetadata()
                        .get(['entityDefs', this.model.entityType, 'fields', 'acceptanceStatus', 'style', item]) ||
                        'default',
                    label: this.getLanguage().translateOption(item, 'acceptanceStatus', this.model.entityType),
                    selected: this.model.getLinkMultipleColumn('users', 'status', this.getUser().id) === item,
                };

                this.statusDataList.push(o);
            });

            this.message = this.translate('selectAcceptanceStatus', 'messages', 'Meeting')
        },

        actionSetStatus: function (data) {
            this.trigger('set-status', data.status);
            this.close();
        },
    });
});
