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
            this.selectData = this.options.selectData;
            this.byWhere = this.options.byWhere;

            this.headerHtml = this.translate(this.scope, 'scopeNamesPlural') + ' &raquo ' + this.translate('Mass Update');

            var fobiddenList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit') || [];

            this.wait(true);
            this.getModelFactory().create(this.entityType, function (model) {
                this.model = model;
                this.getHelper().layoutManager.get(this.entityType, this.layoutName, function (layout) {
                    layout = layout || [];
                    this.fieldList = [];
                    layout.forEach(function (field) {
                        if (~fobiddenList.indexOf(field)) return;
                        if (model.hasField(field)) {
                            this.fieldList.push(field);
                        }
                    }, this);

                    this.wait(false);
                }.bind(this));
            }.bind(this));

            this.addedFieldList = [];
        },

        addField: function (name) {
            this.enableButton('update');

            this.$el.find('[data-action="reset"]').removeClass('hidden');

            this.$el.find('ul.filter-list li[data-name="'+name+'"]').addClass('hidden');

            if (this.$el.find('ul.filter-list li:not(.hidden)').length == 0) {
                this.$el.find('button.select-field').addClass('disabled').attr('disabled', 'disabled');
            }

            this.notify('Loading...');
            var label = this.translate(name, 'fields', this.entityType);
            var html = '<div class="cell form-group col-sm-6" data-name="'+name+'"><label class="control-label">'+label+'</label><div class="field" data-name="'+name+'" /></div>';
            this.$el.find('.fields-container').append(html);

            var type = this.model.getFieldType(name);

            var viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

            this.createView(name, viewName, {
                model: this.model,
                el: this.getSelector() + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                },
                mode: 'edit'
            }, function (view) {
                this.addedFieldList.push(name);
                view.render();
                view.notify(false);
            }.bind(this));
        },

        actionUpdate: function () {
            this.disableButton('update');

            var self = this;

            var attributes = {};
            this.addedFieldList.forEach(function (field) {
                var view = self.getView(field);
                _.extend(attributes, view.fetch());
            });

            this.model.set(attributes);

            var notValid = false;
            this.addedFieldList.forEach(function (field) {
                var view = self.getView(field);
                notValid = view.validate() || notValid;
            });

            if (!notValid) {
                self.notify('Saving...');
                $.ajax({
                    url: this.entityType + '/action/massUpdate',
                    type: 'PUT',
                    data: JSON.stringify({
                        attributes: attributes,
                        ids: self.ids || null,
                        where: (!self.ids || self.ids.length == 0) ? self.options.where : null,
                        selectData: (!self.ids || self.ids.length == 0) ? self.options.selectData : null,
                        byWhere: this.byWhere
                    }),
                    success: function (result) {
                        var result = result || {};
                        var count = result.count;

                        self.trigger('after:update', count);
                    },
                    error: function () {
                        self.notify('Error occurred', 'error');
                        self.enableButton('update');
                    }
                });
            } else {
                this.notify('Not valid', 'error');
                this.enableButton('update');
            }
        },

        reset: function () {
            this.addedFieldList.forEach(function (field) {
                this.clearView(field);
                this.$el.find('.cell[data-name="'+field+'"]').remove();
            }, this);

            this.addedFieldList = [];

            this.model.clear();

            this.$el.find('[data-action="reset"]').addClass('hidden');

            this.$el.find('button.select-field').removeClass('disabled').removeAttr('disabled');
            this.$el.find('ul.filter-list').find('li').removeClass('hidden');

            this.disableButton('update');
        }
    });
});
