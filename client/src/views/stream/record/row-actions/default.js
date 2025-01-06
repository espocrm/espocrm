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

import DefaultRowActionsView from 'views/record/row-actions/default';
import ReactionsHelper from 'helpers/misc/reactions';
import ReactionsRowActionView from 'views/stream/record/row-actions/reactions/reactions';

class StreamDefaultNoteRowActionsView extends DefaultRowActionsView {

    pinnedMaxCount

    isDetached = false

    /**
     * @private
     * @type {string[]}
     */
    availableReactions

    /**
     * @private
     * @type {ReactionsHelper}
     */
    reactionHelper

    setup() {
        super.setup();

        /** @type import('model').default */
        this.parentModel = this.options.parentModel;

        if (this.options.isThis && this.parentModel) {
            this.listenTo(this.model, 'change:isPinned', () => this.reRender());
            this.listenToOnce(this.parentModel, 'acl-edit-ready', () => this.reRender());

            this.pinnedMaxCount = this.getConfig().get('notePinnedMaxCount');
        }

        // @todo Use service.
        this.reactionHelper = new ReactionsHelper();

        this.availableReactions = this.reactionHelper.getAvailableReactions();
    }

    getActionList() {
        const list = [];

        if (this.options.acl.edit && this.options.isEditable) {
            list.push({
                action: 'quickEdit',
                label: 'Edit',
                data: {
                    id: this.model.id,
                },
                groupIndex: 0,
            });
        }

        if (this.options.acl.edit && this.options.isRemovable) {
            list.push({
                action: 'quickRemove',
                label: 'Remove',
                data: {
                    id: this.model.id,
                },
                groupIndex: 0,
            });
        }

        if (
            this.options.isThis &&
            ['Post', 'EmailReceived', 'EmailSent'].includes(this.model.attributes.type) &&
            this.parentModel &&
            this.getAcl().checkModel(this.parentModel, 'edit') &&
            !this.isDetached
        ) {
            if (this.model.attributes.isPinned) {
                list.push({
                    action: 'unpin',
                    label: 'Unpin',
                    data: {
                        id: this.model.id,
                    },
                    groupIndex: 2,
                });
            } else if (this.pinnedMaxCount > 0) {
                list.push({
                    action: 'pin',
                    label: 'Pin',
                    data: {
                        id: this.model.id,
                    },
                    groupIndex: 2,
                });
            }
        }

        if (
            this.options.isThis &&
            this.model.attributes.type === 'Post' &&
            this.model.attributes.post &&
            !this.isDetached
        ) {
            list.push({
                action: 'quoteReply',
                label: 'Quote Reply',
                data: {
                    id: this.model.id,
                },
                groupIndex: 1,
            });
        }

        if (this.hasReactions()) {
            this.getReactionItems().forEach(item => list.push(item));
        }

        return list;
    }

    /**
     * @private
     * @return {boolean}
     */
    hasReactions() {
        return this.model.attributes.type === 'Post' &&
            this.availableReactions.length &&
            !this.options.isNotification;
    }

    async prepareRender() {
        if (!this.hasReactions() || this.availableReactions.length === 1) {
            return;
        }

        const reactionsView = new ReactionsRowActionView({
            reactions: this.availableReactions.map(type => {
                return {
                    type: type,
                    iconClass: this.reactionHelper.getIconClass(type),
                    label: this.translate(type, 'reactions'),
                    isReacted: this.isUserReacted(type),
                };
            }),
        });

        await this.assignView('reactions', reactionsView, '[data-view-key="reactions"]');
    }

    /**
     * @private
     * @param {string} type
     * @return {boolean}
     */
    isUserReacted(type) {
        /** @type {string[]} */
        const myReactions = this.model.attributes.myReactions || [];

        return myReactions.includes(type);
    }

    /**
     * @private
     * @return {module:views/record/row-actions/actions~item[]}
     */
    getReactionItems() {
        const list = [];

        if (this.availableReactions.length > 1) {
            return [{
                viewKey: 'reactions',
                groupIndex: 11,
            }];
        }

        this.availableReactions.forEach(type => {
            const iconClass = this.reactionHelper.getIconClass(type);

            const label = this.getHelper().escapeString(this.translate(type, 'reactions'));

            let html = iconClass ?
                `<span class="${iconClass} text-soft item-icon"></span><span class="item-text">${label}</span>` :
                label;

            const reacted = this.isUserReacted(type);

            if (reacted) {
                html =
                    `<span class="check-icon fas fa-check pull-right"></span>` +
                    `<div>${html}</div>`;
            }

            list.push({
                action: reacted ? 'unReact' : 'react',
                html: html,
                data: {
                    id: this.model.id,
                    type: type,
                },
                groupIndex: 3,
            });
        });

        return list;
    }
}

export default StreamDefaultNoteRowActionsView;
