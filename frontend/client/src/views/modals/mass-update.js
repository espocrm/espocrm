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

Espo.define('Views.Modals.MassUpdate', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'mass-update',

        header: false,

        template: 'modals.mass-update',

        data: function () {
            return {
                scope: this.scope,
                fields: this.fields
            };
        },

        events: {
            'click button[data-action="update"]': function () {
                this.update();
            },
            'click a[data-action="add-field"]': function (e) {
                var field = $(e.currentTarget).data('name');
                var $ul = $(e.currentTarget).closest('ul');
                $(e.currentTarget).parent().remove();
                if ($ul.children().size() == 0) {
                    $ul.parent().find('button').addClass('disabled');
                }
                this.addField(field);
            },
        },

        setup: function () {
            this.buttons = [
                {
                    name: 'update',
                    label: 'Update',
                    style: 'danger',
                    onClick: function (dialog) {
                        this.update();
                    }.bind(this),
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];

            this.scope = this.options.scope;
            this.ids = this.options.ids;
            this.where = this.options.where;
            this.byWhere = this.options.byWhere;

            this.header = this.translate(this.scope, 'scopeNamesPlural') + ' &raquo ' + this.translate('Mass Update');

            this.wait(true);
            this.getModelFactory().create(this.scope, function (model) {
                this.model = model;
                this.getHelper().layoutManager.get(this.scope, 'massUpdate', function (layout) {
                    this.fields = layout;
                    this.wait(false);
                }.bind(this));
            }.bind(this));

            this.fieldsToUpdate = [];
        },

        afterRender: function () {
            $(this.containerSelector + ' button[data-name="update"]').addClass('disabled');
        },

        addField: function (name) {
            $(this.containerSelector + ' button[data-name="update"]').removeClass('disabled');

            this.notify('Loading...');
            var label = this.translate(name, 'fields', this.scope);
            var html = '<div class="cell form-group col-sm-6"><label class="control-label">'+label+'</label><div class="field field-'+name+'" /></div>';
            this.$el.find('.fields-container').append(html);

            var type = Espo.Utils.upperCaseFirst(this.model.getFieldParam(name, 'type'));
            this.createView(name, this.getFieldManager().getViewName(type), {
                model: this.model,
                el: this.$el.selector + ' .field-' + name,
                defs: {
                    name: name,
                },
                mode: 'edit'
            }, function (view) {
                this.fieldsToUpdate.push(name);
                view.render();
                view.notify(false);
            }.bind(this));
        },

        update: function () {
            var self = this;

            var attributes = {};
            this.fieldsToUpdate.forEach(function (field) {
                var view = self.getView(field);
                _.extend(attributes, view.fetch());
            });

            this.model.set(attributes);

            var notValid = false;
            this.fieldsToUpdate.forEach(function (field) {
                var view = self.getView(field);
                notValid = view.validate() || notValid;
            });

            if (!notValid) {
                self.notify('Saving...');
                $.ajax({
                    url: this.scope + '/action/massUpdate',
                    type: 'PUT',
                    data: JSON.stringify({
                        attributes: attributes,
                        ids: self.ids || null,
                        where: (self.ids.length == 0) ? self.options.where : null,
                        byWhere: this.byWhere
                    }),
                    success: function (result) {
                        var result = result || {};
                        var count = result.count;

                        self.trigger('after:update', count);
                    },
                    error: function () {
                        self.notify('Error occurred', 'error');
                    },
                });
            } else {
                this.notify('Not valid', 'error');
            }
        },
    });
});

