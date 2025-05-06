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

/** @module views/email/record/list */

import ListRecordView from 'views/record/list';
import MassActionHelper from 'helpers/mass-action';

class EmailListRecordView extends ListRecordView {

    rowActionsView = 'views/email/record/row-actions/default'

    massActionList = [
        'remove',
        'massUpdate',
    ]

    /**
     * @type {string[]}
     * @private
     */
    toRemoveIdList

    collectionEventSyncList = [
        'moving-to-trash',
        'retrieving-from-trash',
        'moving-to-archive',
    ]

    setup() {
        super.setup();

        if (this.collection.url === this.entityType) {
            this.addMassAction({name: 'retrieveFromTrash', groupIndex: -6}, false);

            this.addMassAction({name: 'moveToTrash', groupIndex: -5}, false);
            this.addMassAction({name: 'moveToArchive', groupIndex: -5}, false);
            this.addMassAction({name: 'moveToFolder', groupIndex: -5}, true);

            this.addMassAction({name: 'markAsImportant', groupIndex: -4}, false);
            this.addMassAction({name: 'markAsNotImportant', groupIndex: -4}, false);
            this.addMassAction({name: 'markAsRead', groupIndex: -3}, false);
            this.addMassAction({name: 'markAsNotRead', groupIndex: -3}, false);

            this.dropdownItemList.push({
                name: 'markAllAsRead',
                label: 'Mark all as read',
            });

            this.controlEmailMassActionsVisibility();
            this.listenTo(this.collection, 'select-folder', () => this.controlEmailMassActionsVisibility());
        }

        this.listenTo(this.collection, 'moving-to-trash', (id, keep) => {
            const model = this.collection.get(id);

            if (model) {
                model.attributes.groupFolderId ?
                    model.set('groupFolderStatus', 'Trash') :
                    model.set('inTrash', true);
            }

            if (this.rootData.selectedFolderId !== 'trash' && this.rootData.selectedFolderId !== 'all') {
                if (keep) {
                    this.toRemoveIdList.push(id);

                    return;
                }

                this.removeRecordFromList(id);
            }
        });

        this.listenTo(this.collection, 'retrieving-from-trash', (id, keep) => {
            const model = this.collection.get(id);

            if (model) {
                model.attributes.groupFolderId ?
                    model.set('groupFolderStatus', null) :
                    model.set('inTrash', false);
            }

            if (this.rootData.selectedFolderId === 'all') {
                return;
            }

            if (this.rootData.selectedFolderId === 'trash') {
                if (keep) {
                    this.toRemoveIdList.push(id);

                    return;
                }

                this.removeRecordFromList(id);
            }
        });

        this.listenTo(this.collection, 'moving-to-archive', (id, keep) => {
            const model = this.collection.get(id);

            if (model) {
                model.attributes.groupFolderId ?
                    model.set('groupFolderStatus', 'Archive') :
                    model.set('inArchive', true);
            }

            if (this.rootData.selectedFolderId === 'sent' || this.rootData.selectedFolderId === 'all') {
                return;
            }

            if (this.rootData.selectedFolderId !== 'archive') {
                if (keep) {
                    this.toRemoveIdList.push(id);

                    return;
                }

                this.removeRecordFromList(id);
            }
        });

        this.toRemoveIdList = [];
    }

    /**
     * @internal
     */
    removeQueuedRecord() {
        this.toRemoveIdList.forEach(id => this.removeRecordFromList(id));
    }

    // noinspection JSUnusedGlobalSymbols
    massActionMarkAsRead() {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax.postRequest('Email/inbox/read', {ids: ids});

        ids.forEach(id => {
            const model = this.collection.get(id);

            if (model) {
                model.set('isRead', true);
            }
        });
    }

    // noinspection JSUnusedGlobalSymbols
    massActionMarkAsNotRead() {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax.deleteRequest('Email/inbox/read', {ids: ids});

        ids.forEach(id => {
            const model = this.collection.get(id);

            if (model) {
                model.set('isRead', false);
            }
        });
    }

    massActionMarkAsImportant() {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax.postRequest('Email/inbox/important', {ids: ids});

        ids.forEach(id => {
            const model = this.collection.get(id);

            if (model) {
                model.set('isImportant', true);
            }
        });
    }

    massActionMarkAsNotImportant() {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax.deleteRequest('Email/inbox/important', {ids: ids});

        ids.forEach(id => {
            const model = this.collection.get(id);

            if (model) {
                model.set('isImportant', false);
            }
        });
    }

    // noinspection JSUnusedGlobalSymbols
    massActionMoveToTrash() {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax
            .postRequest('Email/inbox/inTrash', {ids: ids})
            .then(() => {
                Espo.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));
            });

        if (this.rootData.selectedFolderId === 'trash') {
            return;
        }

        ids.forEach(id => {
            this.collection.trigger('moving-to-trash', id);

            this.uncheckRecord(id, null, true);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    massActionRetrieveFromTrash() {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax
            .deleteRequest('Email/inbox/inTrash', {ids: ids})
            .then(() => {
                Espo.Ui.success(this.translate('Done'));
            });

        if (this.rootData.selectedFolderId !== 'trash') {
            return;
        }

        ids.forEach(id => {
            this.collection.trigger('retrieving-from-trash', id);

            this.uncheckRecord(id, null, true);
        });
    }

    /**
     * @param {string} folderId
     */
    async massMoveToFolder(folderId) {
        const params = this.getMassActionSelectionPostData();
        const helper = new MassActionHelper(this);
        const idle = !!params.searchParams && helper.checkIsIdle();

        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

        /** @type {{id?: string, count?: number}} */
        const result = await Espo.Ajax.postRequest('MassAction', {
            entityType: this.entityType,
            action: 'moveToFolder',
            params: params,
            idle: idle,
            data: {folderId: folderId},
        });

        Espo.Ui.notify();

        if (result.id) {
            const view = await helper.process(result.id, 'moveToFolder')

            this.listenToOnce(view, 'close:success', async () => {
                await this.collection.fetch();

                Espo.Ui.success(this.translate('Done'));
            });

            return;
        }

        if (result.count === 0) {
            Espo.Ui.warning(this.translate('No Records Moved', 'labels', 'Email'));

            return;
        }

        if (folderId === 'archive') {
            [...this.checkedList].forEach(id => {
                this.collection.trigger('moving-to-archive', id);

                this.uncheckRecord(id, null, true);
            });

            Espo.Ui.info(this.translate('Moved to Archive', 'labels', 'Email'));

            return;
        }

        await this.collection.fetch()

        Espo.Ui.success(this.translate('Done'));
    }

    /**
     * @private
     * @return {string|null|undefined}
     */
    getSelectedFolderId() {
        return this.rootData.selectedFolderId;
    }

    // noinspection JSUnusedGlobalSymbols
    massActionMoveToFolder() {
        const selectedFolderId = this.getSelectedFolderId();

        this.createView('dialog', 'views/email-folder/modals/select-folder', {
            headerText: this.translate('Move to Folder', 'labels', 'Email'),
            isGroup: selectedFolderId && (selectedFolderId.startsWith('group:') || selectedFolderId === 'all'),
            noArchive: selectedFolderId === 'all',
            currentFolderId: this.rootData.selectedFolderId,
        }, view => {
            view.render();

            this.listenToOnce(view, 'select', async folderId => {
                this.clearView('dialog');

                if (this.allResultIsChecked) {
                    await this.confirm(this.translate('confirmation', 'messages'));
                }

                await this.massMoveToFolder(folderId);
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    massActionMoveToArchive() {
        this.massMoveToFolder('archive');
    }

    actionMarkAsImportant(data) {
        data = data || {};

        const id = data.id;

        Espo.Ajax.postRequest('Email/inbox/important', {id: id});

        const model = this.collection.get(id);

        if (model) {
            model.set('isImportant', true);
        }
    }

    actionMarkAsNotImportant(data) {
        data = data || {};

        const id = data.id;

        Espo.Ajax.deleteRequest('Email/inbox/important', {id: id});

        const model = this.collection.get(id);

        if (model) {
            model.set('isImportant', false);
        }
    }

    actionMarkAllAsRead() {
        Espo.Ajax.postRequest('Email/inbox/read', {all: true});

        this.collection.forEach(model => {
            model.set('isRead', true);
        });

        this.collection.trigger('all-marked-read');
    }

    // noinspection JSUnusedGlobalSymbols
    actionMoveToArchive(data) {
        const id = data.id;

        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest('Email/inbox/folders/archive', {id: id})
            .then(() => {
                Espo.Ui.info(this.translate('Moved to Archive', 'labels', 'Email'));

                this.collection.trigger('moving-to-archive', id);
            });
    }

    actionMoveToTrash(data) {
        const id = data.id;

        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest('Email/inbox/inTrash', {id: id})
            .then(() => {
                Espo.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));

                this.collection.trigger('moving-to-trash', id);
            });
    }

    // noinspection JSUnusedGlobalSymbols
    actionRetrieveFromTrash(data) {
        const id = data.id;

        Espo.Ui.notifyWait();

        this.retrieveFromTrash(id)
            .then(() => {
                Espo.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));

                this.collection.trigger('retrieving-from-trash', id);
            });
    }

    /**
     * @param {string} id
     * @return {Promise}
     */
    retrieveFromTrash(id) {
        return Espo.Ajax.deleteRequest('Email/inbox/inTrash', {id: id});
    }

    massRetrieveFromTrashMoveToFolder(folderId) {
        const ids = [];

        for (const i in this.checkedList) {
            ids.push(this.checkedList[i]);
        }

        Espo.Ajax
            .deleteRequest('Email/inbox/inTrash', {ids: ids})
            .then(() => {
                ids.forEach(id => {
                    this.collection.trigger('retrieving-from-trash', id);
                });

                return Espo.Ajax
                    .postRequest(`Email/inbox/folders/${folderId}`, {ids: ids})
                    .then(() => {
                        Espo.Ui.success(this.translate('Done'));
                    })
            });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @todo Use one API request.
     */
    actionRetrieveFromTrashMoveToFolder(data) {
        const id = data.id;
        const folderId = data.folderId;

        Espo.Ui.notifyWait();

        this.retrieveFromTrash(id)
            .then(() => {
                return this.moveToFolder(id, folderId)
            })
            .then(() => {
                this.collection.fetch().then(() => {
                    Espo.Ui.success(this.translate('Done'));
                });
            });
    }

    /**
     * @param {string} id
     * @param {string} folderId
     * @return {Promise}
     */
    moveToFolder(id, folderId) {
        return Espo.Ajax.postRequest(`Email/inbox/folders/${folderId}`, {id: id});
    }

    actionMoveToFolder(data) {
        const id = data.id;
        const folderId = data.folderId;

        if (folderId) {
            Espo.Ui.notifyWait();

            this.moveToFolder(id, folderId)
                .then(() => {
                    if (folderId === 'archive') {
                        this.collection.trigger('moving-to-archive', id);

                        Espo.Ui.info(this.translate('Moved to Archive', 'labels', 'Email'));

                        return;
                    }

                    this.collection.fetch()
                        .then(() => Espo.Ui.success(this.translate('Done')));
                });

            return;
        }

        const model = this.collection.get(id);

        if (!model) {
            return;
        }

        const currentFolderId = this.rootData.selectedFolderId;

        this.createView('dialog', 'views/email-folder/modals/select-folder', {
            headerText: this.translate('Move to Folder', 'labels', 'Email'),
            isGroup: !!model.attributes.groupFolderId || !model.attributes.isUsers,
            noArchive: !model.attributes.groupFolderId && !model.attributes.isUsers,
            currentFolderId: currentFolderId,
        }, view => {
            view.render();

            this.listenToOnce(view, 'select', folderId => {
                this.clearView('dialog');

                Espo.Ui.notifyWait();

                this.moveToFolder(id, folderId)
                    .then(() => {
                        this.collection.fetch().then(() => {
                            Espo.Ui.success(this.translate('Done'));
                        });
                    });
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @private
     * @param {{id: string}} data
     */
    actionMarkAsRead(data) {
        const id = data.id;

        const model = this.collection.get(id);

        Espo.Ajax.postRequest('Email/inbox/read', {ids: [id]});

        if (model) {
            model.set('isRead', true);
        }
    }

    // noinspection JSUnusedGlobalSymbols
    actionSend(data) {
        const id = data.id;

        this.confirm({
            message: this.translate('sendConfirm', 'messages', 'Email'),
            confirmText: this.translate('Send', 'labels', 'Email'),
        }).then(() => {
            const model = this.collection.get(id);

            if (!model) {
                return;
            }

            Espo.Ui.notify(this.translate('Sending...', 'labels', 'Email'));

            model
                .save({
                    status: 'Sending',
                })
                .then(() => {
                    Espo.Ui.success(this.translate('emailSent', 'messages', 'Email'));

                    if (this.rootData.selectedFolderId === 'drafts') {
                        this.removeRecordFromList(id);
                        this.uncheckRecord(id, null, true);
                        this.collection.trigger('draft-sent');
                    }
                }
            );
        });
    }

    // noinspection JSUnusedGlobalSymbols
    toggleMassMarkAsImportant() {
        const allImportant = !this.checkedList
            .map(id => this.collection.get(id))
            .find(m => !m.get('isImportant'));

        if (allImportant) {
            this.massActionMarkAsNotImportant();

            return;
        }

        this.massActionMarkAsImportant();
    }

    /**
     * @private
     */
    controlEmailMassActionsVisibility() {
        const moveToArchive =
            this.rootData.selectedFolderId !== 'trash' &&
            this.rootData.selectedFolderId !== 'archive' &&
            this.rootData.selectedFolderId !== 'all';

        const moveToTrash =
            this.rootData.selectedFolderId !== 'trash' &&
            this.rootData.selectedFolderId !== 'all';

        const markAsImportant =
            this.rootData.selectedFolderId !== 'important' &&
            this.rootData.selectedFolderId !== 'all';

        const markAsNotRead =
            this.rootData.selectedFolderId !== 'all';

        moveToArchive ?
            this.showMassAction('moveToArchive') :
            this.hideMassAction('moveToArchive');

        moveToTrash ?
            this.showMassAction('moveToTrash') :
            this.hideMassAction('moveToTrash');

        markAsImportant ?
            this.showMassAction('markAsImportant') :
            this.hideMassAction('markAsImportant');

        markAsNotRead ?
            this.showMassAction('markAsNotRead') :
            this.hideMassAction('markAsNotRead');

        if (this.rootData.selectedFolderId === 'trash') {
            this.showMassAction('retrieveFromTrash');
        } else {
            this.hideMassAction('retrieveFromTrash');
        }
    }
}

export default EmailListRecordView;
