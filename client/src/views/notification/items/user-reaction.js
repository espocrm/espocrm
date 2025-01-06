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

import BaseNotificationItemView from 'views/notification/items/base';
import ReactionsHelper from 'helpers/misc/reactions';

// noinspection JSUnusedGlobalSymbols
export default class UserReactionNotificationItemView extends BaseNotificationItemView {

    // language=Handlebars
    templateContent = `
        <div class="stream-head-container">
            <div class="pull-left">
                {{{avatar}}}
            </div>
            <div class="stream-head-text-container">
                <span
                    class="{{reactionIconClass}} text-muted action icon"
                    style="cursor: pointer;"
                    title="{{translate 'View'}}"
                    data-action="quickView"
                    data-id="{{noteId}}"
                    data-scope="Note"
                ></span><span class="text-muted message">{{{message}}}</span>
            </div>
        </div>

        <div class="stream-date-container">
            <span class="text-muted small">{{{createdAt}}}</span>
        </div>
    `

    messageName = 'userPostReaction'

    /**
     * @private
     * @type {string|null}
     */
    reactionIconClass

    /**
     * @private
     * @type {string}
     */
    noteId

    data() {
        return {
            ...super.data(),
            reactionIconClass: this.reactionIconClass,
            noteId: this.noteId,
        };
    }

    setup() {
        const data = /** @type {Object.<string, *>} */this.model.attributes.data || {};

        const relatedParentId = this.model.attributes.relatedParentId;
        const relatedParentType = this.model.attributes.relatedParentType;

        this.userId = this.model.attributes.createdById || data.userId;
        this.noteId = this.model.attributes.relatedId;

        const userName = data.userName || this.model.attributes.createdByName;

        this.messageData['type'] = this.translate(data.type, 'reactions');

        const reactionsHelper = new ReactionsHelper();
        this.reactionIconClass = reactionsHelper.getIconClass(data.type);

        const userElement = document.createElement('a');
        userElement.href = `#User/view/${this.model.attributes.createdById}`;
        userElement.dataset.id = this.model.attributes.createdById;
        userElement.dataset.scope = 'User';
        userElement.textContent = userName;

        this.messageData['user'] = userElement;

        if (relatedParentId && relatedParentType) {
            const relatedParentElement = document.createElement('a');
            relatedParentElement.href = `#${relatedParentType}/view/${relatedParentId}`;
            relatedParentElement.dataset.id = relatedParentId;
            relatedParentElement.dataset.scope = relatedParentType;
            relatedParentElement.textContent = data.entityName || relatedParentType;

            this.messageData['entityType'] = this.translateEntityType(relatedParentType);
            this.messageData['entity'] = relatedParentElement;

            this.messageName = 'userPostInParentReaction';
        }

        let postLabel = this.getLanguage().translateOption('Post', 'type', 'Note');

        if (!this.toUpperCaseFirstLetter()) {
            postLabel = Espo.Utils.lowerCaseFirst(postLabel);
        }

        const postElement = document.createElement('a');
        postElement.href = `#Note/view/${this.noteId}`;
        postElement.dataset.id = this.noteId;
        postElement.dataset.scope = 'Note';
        postElement.textContent = postLabel;

        this.messageData['post'] = postElement;

        this.createMessage();
    }
}
