/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('Views.Modals.Edit', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'edit-modal',

        header: false,

        template: 'modals.edit',

        saveButton: true,

        fullFormButton: true,

        editViewName: null,

        columnCount: 1,

        escapeDisabled: true,

        setup: function () {

            var self = this;

            this.buttonList = [];

            if ('saveButton' in this.options) {
                this.saveButton = this.options.saveButton;
            }

            if (this.saveButton) {
                this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                });
            }

            if ('fullFormButton' in this.options) {
                this.fullFormButton = this.options.fullFormButton;
            }

            if (this.fullFormButton) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form'
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create');
            } else {
                this.header = this.getLanguage().translate('Edit');
            }
            this.header += ' ' + this.getLanguage().translate(this.scope, 'scopeNames');

            this.waitForView('edit');

            this.getModelFactory().create(this.scope, function (model) {
                this.model = model;
                if (this.id) {
                    model.id = this.id;
                    model.once('sync', function () {
                        this.createEdit(model);
                    }, this);
                    model.fetch();
                } else {
                    model.populateDefaults();
                    if (this.options.relate) {
                        model.setRelate(this.options.relate);
                    }
                    if (this.options.attributes) {
                        model.set(this.options.attributes);
                    }
                    this.createEdit(model);
                }
            }.bind(this));
        },

        createEdit: function (model, callback) {
            var viewName = this.editViewName || this.getMetadata().get('clientDefs.' + model.name + '.recordViews.editQuick') || 'Record.EditSmall'; 
            var options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                columnCount: this.columnCount,
                buttonsPosition: false,
                exit: function () {},
            };
            this.createView('edit', viewName, options, callback);
        },

        actionSave: function (dialog) {
            var editView = this.getView('edit');

            var model = editView.model;
            editView.once('after:save', function () {
                this.trigger('after:save', model);
                dialog.close();
            }, this);

            var $buttons = dialog.$el.find('.modal-footer button');
            $buttons.addClass('disabled');

            editView.once('cancel:save', function () {
                $buttons.removeClass('disabled');
            }, this);

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

                setTimeout(function () {
                    router.dispatch(this.scope, 'create', {
                        attributes: attributes,
                        relate: this.options.relate,
                        returnUrl: Backbone.history.fragment,
                    });
                    router.navigate(url, {trigger: false});
                }.bind(this), 10);
            } else {
                url = '#' + this.scope + '/edit/' + this.id;

                var attributes = this.getView('edit').fetch();
                var model = this.getView('edit').model;
                attributes = _.extend(attributes, model.getClonedAttributes());

                setTimeout(function () {
                    router.dispatch(this.scope, 'edit', {
                        attributes: attributes,
                        returnUrl: Backbone.history.fragment,
                        id: this.id
                    });
                    router.navigate(url, {trigger: false});
                }.bind(this), 10);
            }

            this.trigger('leave');
            dialog.close();
        }
    });
});

