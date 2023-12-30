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

define('crm:views/stream/notes/event-confirmation', ['views/stream/note'], function (Dep) {

    return Dep.extend({

        // language=Handlebars
        templateContent: `
            {{#unless noEdit}}
            <div class="pull-right right-container cell-buttons">
            {{{right}}}
            </div>
            {{/unless}}

            <div class="stream-head-container">
                <div class="pull-left">
                    {{{avatar}}}
                </div>
                <div class="stream-head-text-container">
                    <span class="{{iconClass}} text-{{style}}"></span>
                    <span class="text-muted message">{{{message}}}</span>
                </div>
            </div>
            <div class="stream-date-container">
                <a class="text-muted small" href="#Note/view/{{model.id}}">{{{createdAt}}}</a>
            </div>
        `,

        data: function () {
            let iconClass = ({
                'success': 'fas fa-check fa-sm',
                'danger': 'fas fa-times fa-sm',
                'warning': 'fas fa-question fa-sm',
            })[this.style] || '';

            return _.extend({
                statusText: this.statusText,
                style: this.style,
                iconClass: iconClass,
            }, Dep.prototype.data.call(this));
        },

        init: function () {
            if (this.getUser().isAdmin()) {
                this.isRemovable = true;
            }

            Dep.prototype.init.call(this);
        },

        setup: function () {
            this.inviteeType = this.model.get('relatedType');
            this.inviteeId = this.model.get('relatedId');
            this.inviteeName = this.model.get('relatedName');

            let data = this.model.get('data') || {};

            let status = data.status || 'Tentative';
            this.style = data.style || 'default';
            this.statusText = this.getLanguage().translateOption(status, 'acceptanceStatus', 'Meeting');

            this.messageName = 'eventConfirmation' + status;

            if (this.isThis) {
                this.messageName += 'This';
            }

            this.messageData['invitee'] =
                $('<a>')
                    .attr('href', '#' + this.inviteeType + '/view/' + this.inviteeId)
                    .attr('data-id', this.inviteeId)
                    .attr('data-scope', this.inviteeType)
                    .text(this.inviteeName);

            this.createMessage();
        },
    });
});
