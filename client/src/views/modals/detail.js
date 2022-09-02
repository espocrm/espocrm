/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/modals/detail', ['views/modal', 'helpers/action-item-setup'], function (Dep, ActionItemSetup) {

    /**
     * A quick view modal.
     *
     * @class
     * @name Class
     * @memberOf module:views/modals/detail
     * @extends module:views/modal.Class
     */
    return Dep.extend(/** @lends module:views/modals/detail.Class# */{

        cssName: 'detail-modal',

        template: 'modals/detail',

        editDisabled: false,

        fullFormDisabled: false,

        detailView: null,

        removeDisabled: true,

        backdrop: true,

        fitHeight: true,

        className: 'dialog dialog-record',

        sideDisabled: false,

        bottomDisabled: false,

        fixedHeaderHeight: true,

        flexibleHeaderFontSize: true,

        duplicateAction: false,

        shortcutKeys: {
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
            'Control+Backslash': function (e) {
                this.getRecordView().handleShortcutKeyControlBackslash(e);
            },
            'Control+ArrowLeft': function (e) {
                this.handleShortcutKeyControlArrowLeft(e);
            },
            'Control+ArrowRight': function (e) {
                this.handleShortcutKeyControlArrowRight(e);
            },
        },

        setup: function () {
            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            this.buttonList = [];

            if ('editDisabled' in this.options) {
                this.editDisabled = this.options.editDisabled;
            }

            if ('removeDisabled' in this.options) {
                this.removeDisabled = this.options.removeDisabled;
            }

            this.editDisabled = this.getMetadata().get(['clientDefs', this.scope, 'editDisabled']) ||
                this.editDisabled;
            this.removeDisabled = this.getMetadata().get(['clientDefs', this.scope, 'removeDisabled']) ||
                this.removeDisabled;

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

            this.getModelFactory().create(this.scope).then(model => {
                if (!this.sourceModel) {
                    this.model = model;
                    this.model.id = this.id;

                    this.setupAfterModelCreated();

                    this.listenTo(this.model, 'sync', () => {
                        this.controlRecordButtonsVisibility();

                        this.trigger('model-sync');
                    });

                    this.listenToOnce(this.model, 'sync', () => this.createRecordView());

                    this.model.fetch();

                    return;
                }

                this.model = this.sourceModel.clone();
                this.model.collection = this.sourceModel.collection;

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

            if (this.duplicateAction && this.getAcl().checkScope(this.scope, 'create')) {
                this.addDropdownItem({
                    name: 'duplicate',
                    label: 'Duplicate',
                });
            }
        },

        /**
         * @private
         */
        setupActionItems: function () {
            /** @var {module:helpers/action-item-setup.Class} */
            let actionItemSetup = new ActionItemSetup(
                this.getMetadata(),
                this.getHelper(),
                this.getAcl(),
                this.getLanguage()
            );

            actionItemSetup.setup(
                this,
                'modalDetail',
                promise => this.wait(promise),
                item => this.addDropdownItem(item),
                name => this.showActionItem(name),
                name => this.hideActionItem(name),
                {listenToViewModelSync: true}
            );
        },

        /**
         * @protected
         */
        setupAfterModelCreated: function () {},

        /**
         * @protected
         */
        setupRecordButtons: function () {
            if (!this.removeDisabled) {
                this.addRemoveButton();
            }

            if (!this.editDisabled) {
                this.addEditButton();
            }
        },

        controlRecordButtonsVisibility: function () {
            if (this.getAcl().check(this.model, 'edit')) {
                this.showButton('edit');
            } else {
                this.hideButton('edit');
            }

            if (this.getAcl().check(this.model, 'delete')) {
                this.showButton('remove');
            } else {
                this.hideButton('remove');
            }
        },

        addEditButton: function () {
            this.addButton({
                name: 'edit',
                label: 'Edit',
                title: 'Ctrl+Space',
            }, true);
        },

        removeEditButton: function () {
            this.removeButton('edit');
        },

        addRemoveButton: function () {
            this.addDropdownItem({
                name: 'remove',
                label: 'Remove',
            });
        },

        removeRemoveButton: function () {
            this.removeButton('remove');
        },

        getScope: function () {
            return this.scope;
        },

        createRecordView: function (callback) {
            var model = this.model;
            var scope = this.getScope();

            this.headerHtml = '';

            this.headerHtml += $('<span>')
                .text(this.getLanguage().translate(scope, 'scopeNames'))
                .get(0).outerHTML;

            if (model.get('name')) {
                this.headerHtml += ' ' +
                    $('<span>')
                        .addClass('chevron-right')
                        .get(0).outerHTML;

                this.headerHtml += ' ' +
                    $('<span>')
                        .text(model.get('name'))
                        .get(0).outerHTML;
            }

            if (!this.fullFormDisabled) {
                let url = '#' + scope + '/view/' + this.id;

                this.headerHtml =
                    $('<a>')
                        .attr('href', url)
                        .addClass('action font-size-flexible')
                        .attr('title', this.translate('Full Form'))
                        .attr('data-action', 'fullForm')
                        .append(this.headerHtml)
                        .get(0).outerHTML;
            }

            this.headerHtml = this.getHelper().getScopeColorIconHtml(this.scope) + this.headerHtml;

            if (!this.editDisabled) {
                var editAccess = this.getAcl().check(model, 'edit', true);

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
                var removeAccess = this.getAcl().check(model, 'delete', true);

                if (removeAccess) {
                    this.showButton('remove');
                }
                else {
                    this.hideButton('remove');

                    if (removeAccess === null) {
                        this.listenToOnce(model, 'sync', () => {
                            if (this.getAcl().check(model, 'delete')) {
                                this.showButton('remove');
                            }
                        });
                    }
                }
            }

            var viewName =
                this.detailViewName ||
                this.detailView ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'detailSmall']) ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'detailQuick']) ||
                'views/record/detail-small';

            let options = {
                model: model,
                el: this.containerSelector + ' .record-container',
                type: 'detailSmall',
                layoutName: this.layoutName || 'detailSmall',
                buttonsDisabled: true,
                inlineEditDisabled: true,
                sideDisabled: this.sideDisabled,
                bottomDisabled: this.bottomDisabled,
                exit: function () {},
            };

            this.createView('record', viewName, options, callback);
        },

        /**
         * @return {module:views/record/detail.Class}
         */
        getRecordView: function () {
            return this.getView('record');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            setTimeout(() => {
                this.$el.children(0).scrollTop(0);
            }, 50);

            if (!this.navigateButtonsDisabled) {
                this.controlNavigationButtons();
            }
        },

        controlNavigationButtons: function () {
            var recordView = this.getView('record');

            if (!recordView) {
                return;
            }

            var indexOfRecord = this.indexOfRecord;

            var previousButtonEnabled = false;
            var nextButtonEnabled = false;

            if (indexOfRecord > 0) {
                previousButtonEnabled = true;
            }

            if (indexOfRecord < this.model.collection.total - 1) {
                nextButtonEnabled = true;
            }
            else {
                if (this.model.collection.total === -1) {
                    nextButtonEnabled = true;
                } else if (this.model.collection.total === -2) {
                    if (indexOfRecord < this.model.collection.length - 1) {
                        nextButtonEnabled = true;
                    }
                }
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
        },

        switchToModelByIndex: function (indexOfRecord) {
            if (!this.model.collection) {
                return;
            }

            let previousModel = this.model;

            this.sourceModel = this.model.collection.at(indexOfRecord);

            if (!this.sourceModel) {
                throw new Error("Model is not found in collection by index.");
            }

            this.indexOfRecord = indexOfRecord;

            this.id = this.sourceModel.id;
            this.scope = this.sourceModel.name;

            this.model = this.sourceModel.clone();
            this.model.collection = this.sourceModel.collection;

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
        },

        actionPrevious: function () {
            if (!this.model.collection) {
                return;
            }

            if (!(this.indexOfRecord > 0)) {
                return;
            }

            var indexOfRecord = this.indexOfRecord - 1;
            this.switchToModelByIndex(indexOfRecord);
        },

        actionNext: function () {
            if (!this.model.collection) {
                return;
            }

            if (!(this.indexOfRecord < this.model.collection.total - 1) && this.model.collection.total >= 0) {
                return;
            }

            if (this.model.collection.total === -2 && this.indexOfRecord >= this.model.collection.length - 1) {
                return;
            }

            var collection = this.model.collection;

            var indexOfRecord = this.indexOfRecord + 1;

            if (indexOfRecord <= collection.length - 1) {
                this.switchToModelByIndex(indexOfRecord);

                return;
            }

            var initialCount = collection.length;

            collection
                .fetch({
                    more: true,
                    remove: false,
                })
                .then(() => {
                    var model = collection.at(indexOfRecord);

                    this.switchToModelByIndex(indexOfRecord);
                });
        },

        /**
         * @return {Promise}
         */
        actionEdit: function () {
            if (this.options.quickEditDisabled) {
                let options = {
                    id: this.id,
                    model: this.model,
                    returnUrl: this.getRouter().getCurrentUrl(),
                };

                if (this.options.rootUrl) {
                    options.rootUrl = this.options.rootUrl;
                }

                this.getRouter().navigate('#' + this.scope + '/edit/' + this.id, {trigger: false});
                this.getRouter().dispatch(this.scope, 'edit', options);

                return Promise.reject();
            }

            let viewName = this.getMetadata().get(['clientDefs', this.scope, 'modalViews', 'edit']) ||
                'views/modals/edit';

            Espo.Ui.notify(this.translate('loading', 'messages'));

            return new Promise(resolve => {
                this.createView('quickEdit', viewName, {
                    scope: this.scope,
                    entityType: this.model.entityType,
                    id: this.id,
                    fullFormDisabled: this.fullFormDisabled
                }, view => {
                    this.listenToOnce(view, 'remove', () => {
                        this.dialog.show();
                    });

                    this.listenToOnce(view, 'leave', () => {
                        this.remove();
                    });

                    this.listenTo(view, 'after:save', (model, o) => {
                        this.model.set(model.getClonedAttributes());

                        this.trigger('after:save', model, o);
                        this.controlRecordButtonsVisibility();

                        this.trigger('model-sync');
                    });

                    view.render()
                        .then(() => {
                            Espo.Ui.notify(false);
                            this.dialog.hide();

                            resolve(view);
                        });
                });
            });
        },

        actionRemove: function () {
            var model = this.getView('record').model;

            this.confirm(this.translate('removeRecordConfirmation', 'messages'), () => {
                var $buttons = this.dialog.$el.find('.modal-footer button');

                $buttons.addClass('disabled').attr('disabled', 'disabled');

                model.destroy()
                    .then(() => {
                        this.trigger('after:destroy', model);
                        this.dialog.close();
                    })
                    .catch(() => {
                        $buttons.removeClass('disabled').removeAttr('disabled');
                    });
            });
        },

        actionFullForm: function () {
            var url;
            var router = this.getRouter();

            var scope = this.getScope();

            url = '#' + scope + '/view/' + this.id;

            var attributes = this.getView('record').fetch();
            var model = this.getView('record').model;

            attributes = _.extend(attributes, model.getClonedAttributes());

            var options = {
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
        },

        actionDuplicate: function () {
            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            Espo.Ajax
                .postRequest(this.scope + '/action/getDuplicateAttributes', {
                    id: this.model.id,
                })
                .then(attributes => {
                    Espo.Ui.notify(false);

                    let url = '#' + this.scope + '/create';

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
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyControlArrowLeft: function (e) {
            if (!this.model.collection) {
                return;
            }

            if (this.buttonList.findIndex(item => item.name === 'previous' && !item.disabled) === -1) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionPrevious();
        },

        /**
         * @protected
         * @param {JQueryKeyEventObject} e
         */
        handleShortcutKeyControlArrowRight: function (e) {
            if (!this.model.collection) {
                return;
            }

            if (this.buttonList.findIndex(item => item.name === 'next' && !item.disabled) === -1) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionNext();
        },
    });
});
