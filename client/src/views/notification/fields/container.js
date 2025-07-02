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

import BaseFieldView from 'views/fields/base';
import NotificationListRecordView from 'views/notification/record/list';

class NotificationContainerFieldView extends BaseFieldView {

    type = 'notification'

    listTemplate = 'notification/fields/container'
    detailTemplate = 'notification/fields/container'

    types = [
        'Assign',
        'EmailReceived',
        'EntityRemoved',
        'Message',
        'System',
        'UserReaction',
    ]

    inlineEditDisabled = true

    /**
     * @private
     * @type {boolean}
     */
    isGroupExpanded = false

    data() {
        return {
            hasGrouped: (this.model.attributes.groupedCount ?? 0) > 1,
            isGroupExpanded: this.isGroupExpanded,
        };
    }

    setup() {
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

        this.addActionHandler('showGrouped', () => this.showGrouped());
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

        collection.url = `Notification/${this.model.id}/group`;

        const button = this.element.querySelector('a[data-action="showGrouped"]');

        if (button instanceof HTMLElement) {
            button.classList.add('disabled');
        }

        Espo.Ui.notifyWait();

        try {
            await collection.fetch();
        } catch (e) {
            await this.reRender();

            return;
        }

        Espo.Ui.notify();

        this.isGroupExpanded = true;

        /*if (this.model.attributes.read === false) {
            collection.models.forEach(model => {
                model.set('read', false);
            });
        }*/

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
                        },
                    ],
                ],
                right: {
                    name: 'read',
                    view: 'views/notification/fields/read',
                    width: 'var(--10px)',
                },
            },
        });

        await this.assignView('groupedList', view);

        await this.reRender();
    }
}

export default NotificationContainerFieldView;
