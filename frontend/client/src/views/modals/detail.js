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

Espo.define('Views.Modals.Detail', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'detail-modal',

        header: false,

        template: 'modals.detail',

        editButton: true,

        fullFormButton: true,

        detailViewName: null,

        columnCount: 1,

        backdrop: true,

        setup: function () {

            var self = this;

            this.buttons = [];

            if ('editButton' in this.options) {
                this.editButton = this.options.editButton;
            }

            if ('fullFormButton' in this.options) {
                this.fullFormButton = this.options.fullFormButton;
            }

            if (this.fullFormButton) {
                this.buttons.push({
                    name: 'fullForm',
                    text: this.getLanguage().translate('Full Form'),
                    onClick: function (dialog) {
                        var url;
                        var router = this.getRouter();

                        url = '#' + this.scope + '/view/' + this.id;

                        var attributes = this.getView('record').fetch();
                        var model = this.getView('record').model;
                        attributes = _.extend(attributes, model.getClonedAttributes());

                        setTimeout(function () {
                            router.dispatch(this.scope, 'view', {
                                attributes: attributes,
                                returnUrl: Backbone.history.fragment,
                                id: this.id
                            });
                            router.navigate(url, {trigger: false});
                        }.bind(this), 10);


                        this.trigger('leave');
                        dialog.close();

                    }.bind(this)
                });
            }

            this.buttons.push({
                name: 'close',
                text: this.getLanguage().translate('Close'),
                onClick: function (dialog) {
                    dialog.close();
                }
            });

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            this.header = this.getLanguage().translate(this.scope, 'scopeNames');

            this.waitForView('record');

            this.getModelFactory().create(this.scope, function (model) {
                this.model = model;

                model.id = this.id;
                model.once('sync', function () {
                    if (model.get('name')) {
                        this.header += ' &raquo; ' + model.get('name');
                    }
                    if (this.editButton && this.getAcl().check(model, 'edit')) {
                        this.addEditButton();
                    }
                    this.createRecord(model);
                }, this);
                model.fetch();

            }.bind(this));
        },

        addEditButton: function () {
            this.buttons.unshift({
                name: 'edit',
                text: this.getLanguage().translate('Edit'),
                style: 'primary',
                onClick: function (dialog) {
                    this.createView('quickEdit', 'Modals.Edit', {
                        scope: this.scope,
                        id: this.id,
                        fullFormButton: this.fullFormButton
                    }, function (view) {
                        view.once('after:render', function () {
                            Espo.Ui.notify(false);
                        });
                        view.render();
                        view.once('after:save', function () {
                            this.trigger('after:save');
                        }, this);
                    }.bind(this));

                    dialog.close();
                }.bind(this)
            });
        },

        createRecord: function (model, callback) {
            var viewName = this.detailViewName || this.getMetadata().get('clientDefs.' + model.name + '.recordViews.detailQuick') || 'Record.Detail'; 
            var options = {
                model: model,
                el: this.containerSelector + ' .record-container',
                type: 'detailSmall',
                layoutName: this.layoutName || 'detailSmall',
                columnCount: this.columnCount,
                buttonsPosition: false,
                exit: function () {},
            };
            this.createView('record', viewName, options, callback);
        },
    });
});

