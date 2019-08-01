/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/modals/detail', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'detail-modal',

        template: 'modals/detail',

        editDisabled: false,

        fullFormDisabled: false,

        detailView: null,

        removeDisabled: true,

        columnCount: 2,

        backdrop: true,

        fitHeight: true,

        className: 'dialog dialog-record',

        sideDisabled: false,

        bottomDisabled: false,

        fixedHeaderHeight: true,

        flexibleHeaderFontSize: true,

        setup: function () {

            var self = this;

            this.buttonList = [];

            if ('editDisabled' in this.options) {
                this.editDisabled = this.options.editDisabled;
            }

            if ('removeDisabled' in this.options) {
                this.removeDisabled = this.options.removeDisabled;
            }

            this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

            this.layoutName = this.options.layoutName || this.layoutName;

            this.setupRecordButtons();

            if (this.model) {
                this.controlRecordButtonsVisibility();
            }

            if (!this.fullFormDisabled) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form'
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Close'
            });

            if (this.model && this.model.collection && !this.navigateButtonsDisabled) {
                this.buttonList.push({
                    name: 'previous',
                    html: '<span class="fas fa-chevron-left"></span>',
                    title: this.translate('Previous Entry'),
                    pullLeft: true,
                    className: 'btn-icon',
                    style: 'text',
                    disabled: true
                });
                this.buttonList.push({
                    name: 'next',
                    html: '<span class="fas fa-chevron-right"></span>',
                    title: this.translate('Next Entry'),
                    pullLeft: true,
                    className: 'btn-icon',
                    style: 'text',
                    disabled: true
                });
                this.indexOfRecord = this.model.collection.indexOf(this.model);
            } else {
                this.navigateButtonsDisabled = true;
            }

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            this.waitForView('record');

            this.sourceModel = this.model;

            this.getModelFactory().create(this.scope).then(function (model) {
                if (!this.sourceModel) {
                    this.model = model;
                    this.model.id = this.id;

                    this.setupAfterModelCreated();

                    this.listenTo(this.model, 'sync', this.controlRecordButtonsVisibility, this);

                    this.listenToOnce(this.model, 'sync', function () {
                        this.createRecordView();
                    }, this);
                    this.model.fetch();
                } else {
                    this.model = this.sourceModel.clone();
                    this.model.collection = this.sourceModel.collection;

                    this.setupAfterModelCreated();

                    this.listenTo(this.model, 'change', function () {
                        this.sourceModel.set(this.model.getClonedAttributes());
                    }, this);

                    this.listenTo(this.model, 'sync', this.controlRecordButtonsVisibility, this);

                    this.once('after:render', function () {
                        this.model.fetch();
                    }, this);
                    this.createRecordView();
                }
            }.bind(this));

            this.listenToOnce(this.getRouter(), 'routed', function () {
                this.remove();
            }, this);
        },

        setupAfterModelCreated: function () {
        },

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
            }, true);
        },

        removeEditButton: function () {
            this.removeButton('edit');
        },

        addRemoveButton: function () {
            this.addDropdownItem({
                name: 'remove',
                label: 'Remove'
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
            var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);

            this.headerHtml += this.getLanguage().translate(scope, 'scopeNames');

            if (model.get('name')) {
                this.headerHtml += ' &raquo; ' + Handlebars.Utils.escapeExpression(model.get('name'));
            }
            if (!this.fullFormDisabled) {
                this.headerHtml = '<a href="#' + scope + '/view/' + this.id+'" class="action font-size-flexible" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.headerHtml + '</a>';
            }

            this.headerHtml = iconHtml + this.headerHtml;

            if (!this.editDisabled) {
                var editAccess = this.getAcl().check(model, 'edit', true);
                if (editAccess) {
                    this.showButton('edit');
                } else {
                    this.hideButton('edit');
                    if (editAccess === null) {
                        this.listenToOnce(model, 'sync', function() {
                            if (this.getAcl().check(model, 'edit')) {
                                this.showButton('edit');
                            }
                        }, this);
                    }
                }
            }

            if (!this.removeDisabled) {
                var removeAccess = this.getAcl().check(model, 'delete', true);
                if (removeAccess) {
                    this.showButton('remove');
                } else {
                    this.hideButton('remove');
                    if (removeAccess === null) {
                        this.listenToOnce(model, 'sync', function() {
                            if (this.getAcl().check(model, 'delete')) {
                                this.showButton('remove');
                            }
                        }, this);
                    }
                }
            }

            var viewName =
                this.detailViewName ||
                this.detailView ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'detailSmall']) ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'detailQuick']) ||
                'views/record/detail-small';
            var options = {
                model: model,
                el: this.containerSelector + ' .record-container',
                type: 'detailSmall',
                layoutName: this.layoutName || 'detailSmall',
                columnCount: this.columnCount,
                buttonsDisabled: true,
                inlineEditDisabled: true,
                sideDisabled: this.sideDisabled,
                bottomDisabled: this.bottomDisabled,
                exit: function () {}
            };
            this.createView('record', viewName, options, callback);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            setTimeout(function () {
                this.$el.children(0).scrollTop(0);
            }.bind(this), 50);

            if (!this.navigateButtonsDisabled) {
                this.controlNavigationButtons();
            }
        },

        controlNavigationButtons: function () {
            var recordView = this.getView('record');
            if (!recordView) return;

            var indexOfRecord = this.indexOfRecord;

            var previousButtonEnabled = false;
            var nextButtonEnabled = false;

            if (indexOfRecord > 0) {
                previousButtonEnabled = true;
            }

            if (indexOfRecord < this.model.collection.total - 1) {
                nextButtonEnabled = true;
            } else {
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
            if (!this.model.collection) return;

            var previousModel = this.model;

            this.sourceModel = this.model.collection.at(indexOfRecord);

            if (!this.sourceModel) {
                throw new Error("Model is not found in collection by index.");
            }

            this.indexOfRecord = indexOfRecord;

            this.id = this.sourceModel.id;
            this.scope = this.sourceModel.name;

            this.model = this.sourceModel.clone();
            this.model.collection = this.sourceModel.collection;

            this.listenTo(this.model, 'change', function () {
                this.sourceModel.set(this.model.getClonedAttributes());
            }, this);

            this.listenTo(this.model, 'sync', this.controlRecordButtonsVisibility, this);

            this.once('after:render', function () {
                this.model.fetch();
            }, this);

            this.createRecordView(function () {
                this.reRender();
            }.bind(this));

            this.controlNavigationButtons();

            this.trigger('switch-model', this.model, previousModel);
        },

        actionPrevious: function () {
            if (!this.model.collection) return;
            if (!(this.indexOfRecord > 0)) return;

            var indexOfRecord = this.indexOfRecord - 1;
            this.switchToModelByIndex(indexOfRecord);
        },

        actionNext: function () {
            if (!this.model.collection) return;
            if (!(this.indexOfRecord < this.model.collection.total - 1) && this.model.collection.total >= 0) return;
            if (this.model.collection.total === -2 && this.indexOfRecord >= this.model.collection.length - 1) {
                return;
            }

            var collection = this.model.collection;

            var indexOfRecord = this.indexOfRecord + 1;
            if (indexOfRecord <= collection.length - 1) {
                this.switchToModelByIndex(indexOfRecord);
            } else {
                var initialCount = collection.length;

                this.listenToOnce(collection, 'sync', function () {
                    var model = collection.at(indexOfRecord);
                    this.switchToModelByIndex(indexOfRecord);
                }, this);
                collection.fetch({
                    more: true,
                    remove: false,
                });
            }
        },

        actionEdit: function () {
            if (this.options.quickEditDisabled) {
                var options = {
                    id: this.id,
                    model: this.model,
                    returnUrl: this.getRouter().getCurrentUrl(),
                };
                if (this.options.rootUrl) {
                    options.rootUrl = this.options.rootUrl;
                }
                this.getRouter().navigate('#' + this.scope + '/edit/' + this.id, {trigger: false});
                this.getRouter().dispatch(this.scope, 'edit', options);
                return;
            }

            var viewName = this.getMetadata().get(['clientDefs', this.scope, 'modalViews', 'edit']) || 'views/modals/edit';
            this.createView('quickEdit', viewName, {
                scope: this.scope,
                entityType: this.model.entityType,
                id: this.id,
                fullFormDisabled: this.fullFormDisabled
            }, function (view) {
                view.once('after:render', function () {
                    Espo.Ui.notify(false);
                    this.dialog.hide();
                }, this);

                this.listenToOnce(view, 'remove', function () {
                    this.dialog.show();
                }, this);

                this.listenToOnce(view, 'leave', function () {
                    this.remove();
                }, this);

                this.listenToOnce(view, 'after:save', function (model) {
                    this.model.set(model.getClonedAttributes());

                    this.trigger('after:save', model);

                    this.controlRecordButtonsVisibility();
                }, this);

                view.render();
            }, this);
        },

        actionRemove: function () {
            var model = this.getView('record').model;

            this.confirm(this.translate('removeRecordConfirmation', 'messages'), function () {
                var $buttons = this.dialog.$el.find('.modal-footer button');
                $buttons.addClass('disabled').attr('disabled', 'disabled');
                model.destroy({
                    success: function () {
                        this.trigger('after:destroy', model);
                        this.dialog.close();
                    }.bind(this),
                    error: function () {
                        $buttons.removeClass('disabled').removeAttr('disabled');
                    }
                });
            }, this);
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
                id: this.id
            };
            if (this.options.rootUrl) {
                options.rootUrl = this.options.rootUrl;
            }

            setTimeout(function () {
                router.dispatch(scope, 'view', options);
                router.navigate(url, {trigger: false});
            }.bind(this), 10);

            this.trigger('leave');
            this.dialog.close();
        }
    });
});
