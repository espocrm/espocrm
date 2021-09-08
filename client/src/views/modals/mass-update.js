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

define('views/modals/mass-update', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'mass-update',

        template: 'modals/mass-update',

        layoutName: 'massUpdate',

        data: function () {
            return {
                scope: this.scope,
                fieldList: this.fieldList,
                entityType: this.entityType,
            };
        },

        events: {
            'click button[data-action="update"]': function () {
                this.update();
            },
            'click a[data-action="add-field"]': function (e) {
                var field = $(e.currentTarget).data('name');
                this.addField(field);
            },
            'click button[data-action="reset"]': function (e) {
                this.reset();
            }
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'update',
                    label: 'Update',
                    style: 'danger',
                    disabled: true
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            this.entityType = this.options.entityType || this.options.scope;
            this.scope = this.options.scope || this.entityType;

            this.ids = this.options.ids;
            this.where = this.options.where;
            this.searchParams = this.options.searchParams;
            this.byWhere = this.options.byWhere;

            this.headerHtml = this.translate(this.scope, 'scopeNamesPlural') +
                ' <span class="chevron-right"></span> ' + this.translate('Mass Update');

            var forbiddenList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit') || [];

            this.wait(true);

            this.getModelFactory().create(this.entityType, (model) => {
                this.model = model;

                this.getHelper().layoutManager.get(this.entityType, this.layoutName, (layout) => {
                    layout = layout || [];

                    this.fieldList = [];

                    layout.forEach((field) => {
                        if (~forbiddenList.indexOf(field)) {
                            return;
                        }

                        if (model.hasField(field)) {
                            this.fieldList.push(field);
                        }
                    });

                    this.wait(false);
                });
            });

            this.addedFieldList = [];
        },

        addField: function (name) {
            this.enableButton('update');

            this.$el.find('[data-action="reset"]').removeClass('hidden');

            this.$el.find('ul.filter-list li[data-name="'+name+'"]').addClass('hidden');

            if (this.$el.find('ul.filter-list li:not(.hidden)').length === 0) {
                this.$el.find('button.select-field').addClass('disabled').attr('disabled', 'disabled');
            }

            this.notify('Loading...');

            var label = this.translate(name, 'fields', this.entityType);

            var html = '<div class="cell form-group" data-name="'+name+'">' +
                '<label class="control-label">' + label + '</label>' +
                '<div class="field" data-name="'+name+'" /></div>';

            this.$el.find('.fields-container').append(html);

            var type = this.model.getFieldType(name);

            var viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

            this.createView(name, viewName, {
                model: this.model,
                el: this.getSelector() + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                },
                mode: 'edit',
            }, (view) => {
                this.addedFieldList.push(name);

                view.render();

                view.notify(false);
            });
        },

        actionUpdate: function () {
            this.disableButton('update');

            var self = this;

            var attributes = {};

            this.addedFieldList.forEach((field) => {
                var view = self.getView(field);

                _.extend(attributes, view.fetch());
            });

            this.model.set(attributes);

            var notValid = false;

            this.addedFieldList.forEach((field) => {
                var view = self.getView(field);

                notValid = view.validate() || notValid;
            });

            if (!notValid) {
                Espo.Ui.notify(
                    this.translate('Saving...')
                );

                Espo.Ajax.postRequest('MassAction', {
                    action: 'update',
                    entityType: this.entityType,
                    params: {
                        ids: self.ids || null,
                        where: (!self.ids || self.ids.length === 0) ? self.options.where : null,
                        searchParams: (!self.ids || self.ids.length === 0) ? self.options.searchParams : null,
                    },
                    data: attributes,
                })
                    .then(
                        (result) => {
                            var result = result || {};
                            var count = result.count;

                            self.trigger('after:update', count);
                        }
                    )
                    .catch(
                        () =>{
                            self.notify('Error occurred', 'error');

                            self.enableButton('update');
                        }
                    );
            } else {
                this.notify('Not valid', 'error');

                this.enableButton('update');
            }
        },

        reset: function () {
            this.addedFieldList.forEach((field) => {
                this.clearView(field);

                this.$el.find('.cell[data-name="'+field+'"]').remove();
            });

            this.addedFieldList = [];

            this.model.clear();

            this.$el.find('[data-action="reset"]').addClass('hidden');

            this.$el.find('button.select-field').removeClass('disabled').removeAttr('disabled');
            this.$el.find('ul.filter-list').find('li').removeClass('hidden');

            this.disableButton('update');
        },

    });
});
