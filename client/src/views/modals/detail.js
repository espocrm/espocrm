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

/** @module views/modals/detail */

import ModalView from 'views/modal';
import ActionItemSetup from 'helpers/action-item-setup';
import Backbone from 'backbone';
import RecordModal from 'helpers/record-modal';

/**
 * A quick view modal.
 */
class DetailModalView extends ModalView {

    template = 'modals/detail'

    cssName = 'detail-modal'
    className = 'dialog dialog-record'
    editDisabled = false
    fullFormDisabled = false
    detailView = null
    removeDisabled = true
    backdrop = true
    fitHeight = true
    sideDisabled = false
    bottomDisabled = false
    fixedHeaderHeight = true
    flexibleHeaderFontSize = true
    duplicateAction = false

    /**
     * @private
     * @type {string}
     */
    nameAttribute

    shortcutKeys = {
        /** @this DetailModalView */
        'Control+Space': function (e) {
            if (this.editDisabled) {
                return;
            }

            if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
                return;
            }

            if (this.buttonList.findIndex(item => item.name === 'edit') === -1) {
                return;
            }

            e.stopPropagation();
            e.preventDefault();

            this.actionEdit()
                .then(view => {
                    view.$el
                        .find('.form-control:not([disabled])')
                        .first()
                        .focus();
                });
        },
        /** @this DetailModalView */
        'Control+Backslash': function (e) {
            this.getRecordView().handleShortcutKeyControlBackslash(e);
        },
        /** @this DetailModalView */
        'Control+ArrowLeft': function (e) {
            this.handleShortcutKeyControlArrowLeft(e);
        },
        /** @this DetailModalView */
        'Control+ArrowRight': function (e) {
            this.handleShortcutKeyControlArrowRight(e);
        },
    }

    /**
     * @typedef {Record} module:views/modals/detail~options
     *
     * @property {string} entityType An entity type.
     * @property {string} [id] An ID.
     * @property {string} [layoutName] A layout name.
     * @property {import('view-record-helper')} [recordHelper] A record helper.
     * @property {boolean} [editDisabled] Disable edit.
     * @property {boolean} [removeDisabled] Disable remove.
     * @property {boolean} [fullFormDisabled] Disable full-form.
     * @property {boolean} [quickEditDisabled] Disable quick edit.
     * @property {string} [rootUrl] A root URL.
     * @property {string} [fullFormUrl] A full-form URL. As of v9.0.
     */

    /**
     * @param {module:views/modals/detail~options} options
     */
    constructor(options) {
        super(options);
    }

    setup() {
        this.scope = this.scope || this.options.scope || this.options.entityType;
        this.entityType = this.options.entityType || this.scope;
        this.id = this.options.id;

        this.buttonList = [];

        if ('editDisabled' in this.options) {
            this.editDisabled = this.options.editDisabled;
        }

        if (this.options.removeDisabled !== undefined) {
            this.removeDisabled = this.options.removeDisabled;
        }

        this.editDisabled = this.getMetadata().get(['clientDefs', this.entityType, 'editDisabled']) ||
            this.editDisabled;
        this.removeDisabled = this.getMetadata().get(['clientDefs', this.entityType, 'removeDisabled']) ||
            this.removeDisabled;

        this.nameAttribute = this.getMetadata().get(`clientDefs.${this.entityType}.nameAttribute`) || 'name';

        this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;
        this.layoutName = this.options.layoutName || this.layoutName;

        this.setupRecordButtons();

        if (this.model) {
            this.controlRecordButtonsVisibility();
        }

        if (!this.fullFormDisabled) {
            this.buttonList.push({
                name: 'fullForm',
                label: 'Full Form',
            });
        }

        this.buttonList.push({
            name: 'cancel',
            label: 'Close',
            title: 'Esc',
        });

        if (this.model && this.model.collection && !this.navigateButtonsDisabled) {
            this.buttonList.push({
                name: 'previous',
                html: '<span class="fas fa-chevron-left"></span>',
                title: this.translate('Previous Entry'),
                position: 'right',
                className: 'btn-icon',
                style: 'text',
                disabled: true,
            });

            this.buttonList.push({
                name: 'next',
                html: '<span class="fas fa-chevron-right"></span>',
                title: this.translate('Next Entry'),
                position: 'right',
                className: 'btn-icon',
                style: 'text',
                disabled: true,
            });

            this.indexOfRecord = this.model.collection.indexOf(this.model);
        }
        else {
            this.navigateButtonsDisabled = true;
        }

        this.waitForView('record');

        this.sourceModel = this.model;

        this.getModelFactory().create(this.entityType).then(model => {
            if (!this.sourceModel) {
                this.model = model;
                this.model.id = this.id;

                this.setupAfterModelCreated();

                this.listenTo(this.model, 'sync', () => {
                    this.controlRecordButtonsVisibility();

                    this.trigger('model-sync');
                });

                this.listenToOnce(this.model, 'sync', () => {
                    this.setupActionItems();
                    this.createRecordView();
                });

                this.model.fetch();

                return;
            }

            this.model = this.sourceModel.clone();
            this.model.collection = this.sourceModel.collection.clone();

            this.setupAfterModelCreated();

            this.listenTo(this.model, 'change', () => {
                this.sourceModel.set(this.model.getClonedAttributes());
            });

            this.listenTo(this.model, 'sync', () => {
                this.controlRecordButtonsVisibility();

                this.trigger('model-sync');
            });

            this.once('after:render', () => {
                this.model.fetch();
            });

            this.setupActionItems();
            this.createRecordView();
        });

        this.listenToOnce(this.getRouter(), 'routed', () => {
            this.remove();
        });

        if (this.duplicateAction && this.getAcl().checkScope(this.entityType, 'create')) {
            this.addDropdownItem({
                name: 'duplicate',
                label: 'Duplicate',
                groupIndex: 0,
            });
        }
    }

    /** @private */
    setupActionItems() {
        const actionItemSetup = new ActionItemSetup();

        actionItemSetup.setup(
            this,
            'modalDetail',
            promise => this.wait(promise),
            item => this.addDropdownItem(item),
            name => this.showActionItem(name),
            name => this.hideActionItem(name),
            {listenToViewModelSync: true}
        );
    }

    /**
     * @protected
     */
    setupAfterModelCreated() {}

    /**
     * @protected
     */
    setupRecordButtons() {
        if (!this.removeDisabled) {
            this.addRemoveButton();
        }

        if (!this.editDisabled) {
            this.addEditButton();
        }
    }

    /**
     * @protected
     */
    controlRecordButtonsVisibility() {
        if (this.getAcl().check(this.model, 'edit')) {
            this.showButton('edit');
        } else {
            this.hideButton('edit');
        }

        if (this.getAcl().check(this.model, 'delete')) {
            this.showActionItem('remove');
        } else {
            this.hideActionItem('remove');
        }
    }

    addEditButton() {
        this.addButton({
            name: 'edit',
            label: 'Edit',
            title: 'Ctrl+Space',
        }, true);
    }

    // noinspection JSUnusedGlobalSymbols
    removeEditButton() {
        this.removeButton('edit');
    }

    addRemoveButton() {
        this.addDropdownItem({
            name: 'remove',
            label: 'Remove',
            groupIndex: 0,
        });
    }

    // noinspection JSUnusedGlobalSymbols
    removeRemoveButton() {
        this.removeButton('remove');
    }

    /**
     * @internal Used. Do not remove.
     */
    getScope() {
        return this.scope;
    }

    createRecordView(callback) {
        const model = this.model;
        const scope = this.getScope();

        this.headerHtml = '';

        this.headerHtml += $('<span>')
            .text(this.getLanguage().translate(scope, 'scopeNames'))
            .get(0).outerHTML;

        if (model.attributes[this.nameAttribute]) {
            this.headerHtml += ' ' +
                $('<span>')
                    .addClass('chevron-right')
                    .get(0).outerHTML;

            this.headerHtml += ' ' +
                $('<span>')
                    .text(model.attributes[this.nameAttribute])
                    .get(0).outerHTML;
        }

        if (!this.fullFormDisabled) {
            const url = this.options.fullFormUrl || `#${scope}/view/${this.id}`;

            this.headerHtml =
                $('<a>')
                    .attr('href', url)
                    .addClass('action font-size-flexible')
                    .attr('title', this.translate('Full Form'))
                    .attr('data-action', 'fullForm')
                    .append(this.headerHtml)
                    .get(0).outerHTML;
        }

        this.headerHtml = this.getHelper().getScopeColorIconHtml(this.entityType) + this.headerHtml;

        if (!this.editDisabled) {
            const editAccess = this.getAcl().check(model, 'edit', true);

            if (editAccess) {
                this.showButton('edit');
            } else {
                this.hideButton('edit');

                if (editAccess === null) {
                    this.listenToOnce(model, 'sync', () => {
                        if (this.getAcl().check(model, 'edit')) {
                            this.showButton('edit');
                        }
                    });
                }
            }
        }

        if (!this.removeDisabled) {
            const removeAccess = this.getAcl().check(model, 'delete', true);

            if (removeAccess) {
                this.showActionItem('remove');
            } else {
                this.hideActionItem('remove');

                if (removeAccess === null) {
                    this.listenToOnce(model, 'sync', () => {
                        if (this.getAcl().check(model, 'delete')) {
                            this.showActionItem('remove');
                        }
                    });
                }
            }
        }

        const viewName =
            this.detailView ||
            this.getMetadata().get(['clientDefs', model.entityType, 'recordViews', 'detailSmall']) ||
            this.getMetadata().get(['clientDefs', model.entityType, 'recordViews', 'detailQuick']) ||
            'views/record/detail-small';

        const options = {
            model: model,
            fullSelector: this.containerSelector + ' .record-container',
            type: 'detailSmall',
            layoutName: this.layoutName || 'detailSmall',
            buttonsDisabled: true,
            inlineEditDisabled: true,
            sideDisabled: this.sideDisabled,
            bottomDisabled: this.bottomDisabled,
            recordHelper: this.options.recordHelper,
            exit: function () {
            },
        };

        this.createView('record', viewName, options, callback);
    }

    /**
     * @return {module:views/record/detail}
     */
    getRecordView() {
        return this.getView('record');
    }

    afterRender() {
        super.afterRender();

        setTimeout(() => {
            this.$el.children().first().scrollTop(0);
        }, 50);

        if (!this.navigateButtonsDisabled) {
            this.controlNavigationButtons();
        }
    }

    controlNavigationButtons() {
        const recordView = this.getRecordView();

        if (!recordView) {
            return;
        }

        const collection = this.model.collection;

        const indexOfRecord = this.indexOfRecord;

        let previousButtonEnabled = false;
        let nextButtonEnabled = false;

        if (indexOfRecord > 0 || collection.offset > 0) {
            previousButtonEnabled = true;
        }

        if (indexOfRecord < collection.total - 1 - collection.offset) {
            nextButtonEnabled = true;
        }
        else if (collection.total === -1) {
            nextButtonEnabled = true;
        }
        else if (collection.total === -2 && indexOfRecord < collection.length - 1 - collection.offset) {
            nextButtonEnabled = true;
        }

        if (previousButtonEnabled) {
            this.enableButton('previous');
        } else {
            this.disableButton('previous');
        }

        if (nextButtonEnabled) {
            this.enableButton('next');
        } else {
             this.disableButton('next');
        }
    }

    switchToModelByIndex(indexOfRecord) {
        if (!this.model.collection) {
            return;
        }

        const previousModel = this.model;

        this.sourceModel = this.model.collection.at(indexOfRecord);

        if (!this.sourceModel) {
            throw new Error("Model is not found in collection by index.");
        }

        this.indexOfRecord = indexOfRecord;

        this.id = this.sourceModel.id;
        this.scope = this.sourceModel.entityType;

        this.model = this.sourceModel.clone();
        this.model.collection = this.sourceModel.collection.clone();

        this.stopListening(previousModel, 'change');
        this.stopListening(previousModel, 'sync');

        this.listenTo(this.model, 'change', () => {
            this.sourceModel.set(this.model.getClonedAttributes());
        });

        this.listenTo(this.model, 'sync', () => {
            this.controlRecordButtonsVisibility();

            this.trigger('model-sync');
        });

        this.createRecordView(() => {
            this.reRender()
                .then(() => {
                    this.model.fetch();
                })
        });

        this.controlNavigationButtons();
        this.trigger('switch-model', this.model, previousModel);
    }

    actionPrevious() {
        if (!this.model.collection) {
            return;
        }

        const collection = this.model.collection;

        if (this.indexOfRecord <= 0 && !collection.offset) {
            return;
        }

        if (
            this.indexOfRecord === 0 &&
            collection.offset > 0 &&
            collection.maxSize
        ) {
            collection.offset = Math.max(0, collection.offset - collection.maxSize);

            collection.fetch()
                .then(() => {
                    const indexOfRecord = collection.length - 1;

                    if (indexOfRecord < 0) {
                        return;
                    }

                    this.switchToModelByIndex(indexOfRecord);
                });

            return;
        }

        const indexOfRecord = this.indexOfRecord - 1;

        this.switchToModelByIndex(indexOfRecord);
    }

    actionNext() {
        if (!this.model.collection) {
            return;
        }

        const collection = this.model.collection;

        if (
            !(this.indexOfRecord < collection.total - 1 - collection.offset) &&
            this.model.collection.total >= 0
        ) {
            return;
        }

        if (
            collection.total === -2 &&
            this.indexOfRecord >= collection.length - 1 - collection.offset
        ) {
            return;
        }

        const indexOfRecord = this.indexOfRecord + 1;

        if (indexOfRecord <= collection.length - 1 - collection.offset) {
            this.switchToModelByIndex(indexOfRecord);

            return;
        }

        collection
            .fetch({
                more: true,
                remove: false,
            })
            .then(() => {
                this.switchToModelByIndex(indexOfRecord);
            });
    }

    /**
     * @return {Promise<import('views/modals/edit').default>}
     */
    async actionEdit() {
        if (this.options.quickEditDisabled) {
            const options = {
                id: this.id,
                model: this.model,
                returnUrl: this.getRouter().getCurrentUrl(),
            };

            if (this.options.rootUrl) {
                options.rootUrl = this.options.rootUrl;
            }

            this.getRouter().navigate(`#${this.scope}/edit/${this.id}`, {trigger: false});
            this.getRouter().dispatch(this.scope, 'edit', options);

            return Promise.reject();
        }

        const helper = new RecordModal();

        // noinspection UnnecessaryLocalVariableJS
        const modalView = await helper.showEdit(this, {
            entityType: this.entityType,
            id: this.id,
            fullFormDisabled: this.fullFormDisabled,
            collapseDisabled: true,
            beforeSave: (model, o) => {
                this.trigger('before:save', model, o);
            },
            afterSave: (model, o) => {
                this.model.set(model.getClonedAttributes());

                this.trigger('after:save', model, o);

                this.controlRecordButtonsVisibility();

                this.trigger('model-sync');

                // Triggers stream panel update.
                this.model.trigger('sync', this.model, null, {});
            },
            beforeRender: view => {
                this.listenToOnce(view, 'remove', () => this.dialog.show());
                this.listenToOnce(view, 'leave', () => this.remove());
            },
        });

        // Not to be hidden as it interferes with the collapsible modal.
        //this.dialog.hide();

        return modalView;
    }

    // noinspection JSUnusedGlobalSymbols
    actionRemove() {
        const model = this.getRecordView().model;

        this.confirm(this.translate('removeRecordConfirmation', 'messages'), () => {
            const $buttons = this.dialog.$el.find('.modal-footer button');

            $buttons.addClass('disabled').attr('disabled', 'disabled');

            this.trigger('before:delete', model);

            model.destroy()
                .then(() => {
                    this.trigger('after:delete', model);
                    this.trigger('after:destroy', model); // For bc.

                    this.dialog.close();

                    Espo.Ui.success(this.translate('Removed'));
                })
                .catch(() => {
                    $buttons.removeClass('disabled').removeAttr('disabled');
                });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionFullForm() {
        let url;
        const router = this.getRouter();

        const scope = this.getScope();

        url = '#' + scope + '/view/' + this.id;

        let attributes = this.getRecordView().fetch();
        const model = this.getRecordView().model;

        attributes = _.extend(attributes, model.getClonedAttributes());

        const options = {
            attributes: attributes,
            returnUrl: Backbone.history.fragment,
            model: this.sourceModel || this.model,
            id: this.id,
        };

        if (this.options.rootUrl) {
            options.rootUrl = this.options.rootUrl;
        }

        setTimeout(() => {
            router.dispatch(scope, 'view', options);
            router.navigate(url, {trigger: false});
        }, 10);

        this.trigger('leave');
        this.dialog.close();
    }

    // noinspection JSUnusedGlobalSymbols
    actionDuplicate() {
        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest(this.scope + '/action/getDuplicateAttributes', {id: this.model.id})
            .then(attributes => {
                Espo.Ui.notify(false);

                const url = '#' + this.scope + '/create';

                this.getRouter().dispatch(this.scope, 'create', {
                    attributes: attributes,
                    returnUrl: this.getRouter().getCurrentUrl(),
                    options: {
                        duplicateSourceId: this.model.id,
                        returnAfterCreate: true,
                    },
                });

                this.getRouter().navigate(url, {trigger: false});
            });
    }

    /**
     * @protected
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyControlArrowLeft(e) {
        if (!this.model.collection) {
            return;
        }

        if (this.buttonList.findIndex(item => item.name === 'previous' && !item.disabled) === -1) {
            return;
        }

        if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.actionPrevious();
    }

    /**
     * @protected
     * @param {KeyboardEvent} e
     */
    handleShortcutKeyControlArrowRight(e) {
        if (!this.model.collection) {
            return;
        }

        if (this.buttonList.findIndex(item => item.name === 'next' && !item.disabled) === -1) {
            return;
        }

        if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        this.actionNext();
    }
}

export default DetailModalView;
