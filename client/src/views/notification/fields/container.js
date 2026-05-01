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

import BaseFieldView from 'views/fields/base';
import NotificationListRecordView from 'views/notification/record/list';
import NotificationPanelView from 'views/notification/panel';
import Ajax from 'ajax';
import Ui from 'ui';

class NotificationContainerFieldView extends BaseFieldView {

    type = 'notification'

    listTemplate = 'notification/fields/container'
    detailTemplate = 'notification/fields/container'

    /**
     * @private
     * @type {string[]}
     */
    types = [
        'Assign',
        'EmailReceived',
        'EntityRemoved',
        'Message',
        'System',
        'UserReaction',
        'Collaborating',
    ]

    inlineEditDisabled = true

    /**
     * @private
     * @type {boolean}
     */
    isGroupExpanded = false

    /**
     * @private
     * @type {boolean}
     */
    groupingEnabled

    data() {
        const count = this.model.attributes.groupedCount ?? 0;

        return {
            hasGrouped: count > 1 || count < 0,
            isGroupExpanded: this.isGroupExpanded,
            hasMarkGroupRead: this.groupingEnabled && !this.model.attributes.read,
        };
    }

    setup() {
        this.groupingEnabled = this.options.groupingEnabled ?? false;

        if (this.model.attributes.groupType) {
            this.wait(this.processGroup());
        } else {
            switch (this.model.attributes.type) {
                case 'Note':
                    this.processNote(this.model.attributes.noteData);

                    break;

                case 'MentionInPost':
                    this.processMentionInPost(this.model.attributes.noteData);

                    break;

                default:
                    this.process();
            }
        }

        this.addActionHandler('showGrouped', () => this.showGrouped());
        this.addActionHandler('markGroupRead', () => this.markGroupRead());
    }

    process() {
        let type = this.model.get('type');

        if (!type) {
            return;
        }

        type = Espo.Utils.upperCaseFirst(type.replace(/ /g, ''));

        let viewName = this.getMetadata().get(`clientDefs.Notification.itemViews.${type}`);

        if (!viewName) {
            if (!this.types.includes(type)) {
                return;
            }

            viewName = 'views/notification/items/' + Espo.Utils.camelCaseToHyphen(type);
        }

        const parentSelector = this.options.containerSelector ?? this.getSelector();

        this.createView('notification', viewName, {
            model: this.model,
            fullSelector: `${parentSelector} li[data-id="${this.model.id}"]`,
        });
    }

    /**
     * @private
     */
    async processGroup() {
        const groupType = this.model.attributes.groupType;

        let viewName;

        if (groupType === 'Record') {
            viewName = 'views/notification/items/group-note';
        } else if (groupType === 'EmailReceived') {
            viewName = 'views/notification/items/group-email-received';
        }

        if (!viewName) {
            return;
        }

        const parentSelector = this.options.containerSelector ?? this.getSelector();

        await this.createView('notification', viewName, {
            model: this.model,
            fullSelector: `${parentSelector} li[data-id="${this.model.id}"]`,
        });
    }

    /**
     * @private
     * @param {Record} data
     */
    processNote(data) {
        if (!data) {
            return;
        }

        this.wait(true);

        this.getModelFactory().create('Note', model => {
            model.set(data);

            let viewName = this.getMetadata().get(`clientDefs.Note.itemViews.${data.type}`);

            if (!viewName) {
                // @todo Check if type exists.
                viewName = 'views/stream/notes/' + Espo.Utils.camelCaseToHyphen(data.type);
            }

            const parentSelector = this.options.containerSelector ?? this.getSelector();

            this.createView('notification', viewName, {
                model: model,
                isUserStream: true,
                fullSelector: `${parentSelector} li[data-id="${this.model.id}"] .cell[data-name="data"]`,
                onlyContent: true,
                isNotification: true,
                isInGroup: this.options.isInGroup ?? false,
            });

            this.wait(false);
        });
    }

    /**
     * @private
     * @param {Record} data
     */
    processMentionInPost(data) {
        if (!data) {
            return;
        }

        this.wait(true);

        this.getModelFactory().create('Note', model => {
            model.set(data);

            const viewName = 'views/stream/notes/mention-in-post';

            const parentSelector = this.options.containerSelector ?? this.getSelector();

            this.createView('notification', viewName, {
                model: model,
                userId: this.model.get('userId'),
                isUserStream: true,
                fullSelector: `${parentSelector} li[data-id="${this.model.id}"]`,
                onlyContent: true,
                isNotification: true,
            });

            this.wait(false);
        });
    }

    /**
     * @private
     */
    async showGrouped() {
        const collection = await this.getCollectionFactory().create('Notification');

        if (this.model.attributes.groupType) {
            collection.url = `Notification/group?type=${this.model.attributes.groupType}&id=` +
                this.model.id;

            collection.maxSize = this.getConfig().get('recordsPerPageSmall');
        } else {
            collection.url = `Notification/${this.model.id}/group`;
        }

        const button = this.element.querySelector('a[data-action="showGrouped"]');

        if (button instanceof HTMLElement) {
            button.classList.add('disabled');
        }

        Ui.notifyWait();

        try {
            await collection.fetch();
        } catch (e) {
            await this.reRender();

            return;
        }

        Ui.notify();

        this.isGroupExpanded = true;

        const view = new NotificationListRecordView({
            collection: collection,
            showCount: false,
            selector: '.notification-grouped',
            listLayout: {
                rows: [
                    [
                        {
                            name: 'data',
                            view: 'views/notification/fields/container',
                            options: {
                                isInGroup: true,
                            },
                        },
                    ],
                ],
            },
        });

        await this.assignView('groupedList', view);

        await this.reRender();

        this.triggerUpdateRead();
    }


    /**
     * @private
     */
    async markGroupRead() {
        await Ajax.postRequest(`Notification/group/${this.model.id}/markRead`);

        this.model.set('read', true, {sync: true});

        await this.reRender();

        this.triggerUpdateRead();
    }

    /**
     * @private
     */
    triggerUpdateRead() {
        let viewPointer = this;

        while (true) {
            viewPointer = viewPointer.getParentView();

            if (!viewPointer || viewPointer instanceof NotificationPanelView) {
                break;
            }
        }

        if (viewPointer instanceof NotificationPanelView) {
            viewPointer.trigger('collection-fetched');
        }
    }
}

export default NotificationContainerFieldView;
