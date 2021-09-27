/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/modals/edit', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'edit-modal',

        template: 'modals/edit',

        saveDisabled: false,

        fullFormDisabled: false,

        editView: null,

        escapeDisabled: true,

        fitHeight: true,

        className: 'dialog dialog-record',

        sideDisabled: false,

        bottomDisabled: false,

        setup: function () {
            this.buttonList = [];

            if ('saveDisabled' in this.options) {
                this.saveDisabled = this.options.saveDisabled;
            }

            if (!this.saveDisabled) {
                this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                });
            }

            this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

            this.layoutName = this.options.layoutName || this.layoutName;

            if (!this.fullFormDisabled) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form',
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel',
            });

            this.scope = this.scope || this.options.scope;

            this.entityType = this.options.entityType || this.scope;

            this.id = this.options.id;

            if (!this.id) {
                this.headerHtml = this.getLanguage().translate('Create ' + this.scope, 'labels', this.scope);
            }
            else {
                this.headerHtml = this.getLanguage().translate('Edit');
                this.headerHtml += ': ' + this.getLanguage().translate(this.scope, 'scopeNames');
            }

            if (!this.fullFormDisabled) {
                if (!this.id) {
                    this.headerHtml =
                        '<a href="#' + this.scope +
                        '/create" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' +
                        this.headerHtml + '</a>';
                }
                else {
                    this.headerHtml =
                        '<a href="#' + this.scope + '/edit/' + this.id+'" class="action" title="' +
                        this.translate('Full Form')+'" data-action="fullForm">' + this.headerHtml + '</a>';
                }
            }

            var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);

            this.headerHtml = iconHtml + this.headerHtml;

            this.sourceModel = this.model;

            this.waitForView('edit');

            this.getModelFactory().create(this.entityType, (model) => {
                if (this.id) {
                    if (this.sourceModel) {
                        model = this.model = this.sourceModel.clone();
                    }
                    else {
                        this.model = model;

                        model.id = this.id;
                    }

                    model.once('sync', () => {
                        this.createRecordView(model);
                    });

                    model.fetch();

                    return;
                }

                this.model = model;

                if (this.options.relate) {
                    model.setRelate(this.options.relate);
                }

                if (this.options.attributes) {
                    model.set(this.options.attributes);
                }

                this.createRecordView(model);
            });
        },

        createRecordView: function (model, callback) {
            var viewName =
                this.editViewName ||
                this.editView ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editSmall']) ||
                this.getMetadata().get(['clientDefs', model.name, 'recordViews', 'editQuick']) ||
                'views/record/edit-small';

            var options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                buttonsDisabled: true,
                sideDisabled: this.sideDisabled,
                bottomDisabled: this.bottomDisabled,
                exit: function () {},
            };

            this.handleRecordViewOptions(options);

            this.createView('edit', viewName, options, callback);
        },

        handleRecordViewOptions: function (options) {},

        getRecordView: function () {
            return this.getView('edit');
        },

        actionSave: function () {
            var editView = this.getView('edit');

            var model = editView.model;

            editView.once('after:save', () => {
                this.trigger('after:save', model);
                this.dialog.close();
            });

            editView.once('before:save', () => {
                this.trigger('before:save', model);
            });

            var $buttons = this.dialog.$el.find('.modal-footer button');

            $buttons.addClass('disabled').attr('disabled', 'disabled');

            editView.once('cancel:save', () => {
                $buttons.removeClass('disabled').removeAttr('disabled');
            });

            editView.save();
        },

        actionFullForm: function (dialog) {
            var url;
            var router = this.getRouter();

            if (!this.id) {
                url = '#' + this.scope + '/create';

                var attributes = this.getView('edit').fetch();
                var model = this.getView('edit').model;

                attributes = _.extend(attributes, model.getClonedAttributes());

                var options = {
                    attributes: attributes,
                    relate: this.options.relate,
                    returnUrl: this.options.returnUrl || Backbone.history.fragment,
                    returnDispatchParams: this.options.returnDispatchParams || null,
                };

                if (this.options.rootUrl) {
                    options.rootUrl = this.options.rootUrl;
                }

                setTimeout(() => {
                    router.dispatch(this.scope, 'create', options);
                    router.navigate(url, {trigger: false});
                }, 10);
            }
            else {
                url = '#' + this.scope + '/edit/' + this.id;

                var attributes = this.getView('edit').fetch();
                var model = this.getView('edit').model;

                attributes = _.extend(attributes, model.getClonedAttributes());

                var options = {
                    attributes: attributes,
                    returnUrl: this.options.returnUrl || Backbone.history.fragment,
                    returnDispatchParams: this.options.returnDispatchParams || null,
                    model: this.sourceModel,
                    id: this.id,
                };

                if (this.options.rootUrl) {
                    options.rootUrl = this.options.rootUrl;
                }

                setTimeout(() => {
                    router.dispatch(this.scope, 'edit', options);
                    router.navigate(url, {trigger: false});
                }, 10);
            }

            this.trigger('leave');

            this.dialog.close();
        },
    });
});

