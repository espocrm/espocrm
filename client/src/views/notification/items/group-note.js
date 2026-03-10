/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import BaseNotificationItemView from 'views/notification/items/base';

// noinspection JSUnusedGlobalSymbols
export default class GroupNoteNotificationItemView extends BaseNotificationItemView {

    // language=Handlebars
    templateContent = `
        <div class="stream-head-container">
            {{#if iconClass}}
                <div class="pull-left stream-head-left-icon-container">
                    <span
                        class=" {{iconClass}} text-muted action icon"
                        style="
                            cursor: pointer;
                            {{#if color}} color: {{color}}; {{/if}}
                        "
                        title="{{translate 'View'}}"
                        data-action="quickView"
                        data-id="{{relatedParentId}}"
                        data-scope="{{relatedParentType}}"
                    ></span>
                </div>
            {{/if}}
            <div class="stream-head-text-container">
                <span class="text-muted message">{{{message}}}</span>
            </div>
        </div>
        {{~#if isFeatured~}}
            <div class="stream-post-container">
                <span class="label label-default">{{translate 'Assigned'}}</span>
            </div>
        {{~/if~}}
        <div class="stream-date-container">
            <span class="text-muted small">{{{createdAt}}}</span>
        </div>
    `

    data() {
        const relatedParentType = this.model.attributes.relatedParentType
        const iconClass = this.getMetadata().get(`clientDefs.${relatedParentType}.iconClass`);
        const color = this.getMetadata().get(`clientDefs.${relatedParentType}.color`);

        return {
            ...super.data(),
            relatedParentId: this.model.attributes.relatedParentId,
            relatedParentType: this.model.attributes.relatedParentType,
            isFeatured: this.model.attributes.isFeatured,
            iconClass,
            color,
        };
    }

    setup() {
        const relatedParentType = this.model.attributes.relatedParentType;

        if (relatedParentType) {
            this.messageData['entityType'] = this.translateEntityType(relatedParentType);
        }

        this.messageData['entity'] = 'field:relatedParent';

        const newCount = this.model.attributes.groupedUnreadCount ?? 0;

        this.messageData['number'] = newCount.toString();

        this.messageName = newCount > 1 ? 'groupUpdatesMultiple' : 'groupUpdatesOne';

        if (newCount === 0) {
            this.messageName = 'groupUpdates';
        }

        this.createMessage();
    }
}
