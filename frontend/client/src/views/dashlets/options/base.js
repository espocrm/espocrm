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

Espo.define('Views.Dashlets.Options.Base', ['Views.Modal', 'Views.Record.Detail', 'Model'], function (Dep, Detail, Model) {

    var self;

    return Dep.extend({

        name: null,

        template: 'dashlets.options.base',

        cssName: 'options-modal',

        fieldsMode: 'edit',

        data: function () {
            return {
                options: this.optionsData,
            };
        },

        buttons: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                onClick: function (dialog) {
                    self.save(dialog);
                },
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: function (dialog) {
                    dialog.close();
                }
            }
        ],

        getDetailLayout: function () {
            var layout = [{rows: []}];
            var i = 0;
            var a = [];
            for (var field in this.fields) {

                if (!(i % 2)) {
                    a = [];
                    layout[0].rows.push(a);
                }
                a.push({name: field});
                i++;
            }
            return layout;
        },

        init: function () {
            Dep.prototype.init.call(this);
            this.fields = this.options.fields;
            this.fieldList = Object.keys(this.fields);
            this.optionsData = this.options.optionsData;
        },

        setup: function (dialog) {
            this.id = 'dashlet-options';

            self = this;
            var model = this.model = new Model();
            model.name = 'DashletOptions';
            model.defs = {
                fields: this.fields
            };
            model.set(this.optionsData);

            this.createView('record', 'Base', {
                model: model,
                _layout: {
                    type: 'record',
                    layout: Detail.prototype.convertDetailLayout.call(this, this.getDetailLayout())
                },
                el: this.id + ' .record',
                layoutData: {
                    model: model,
                    columnCount: 2,
                },
            });

            this.header = this.getLanguage().translate('Dashlet Options') + ': ' + this.getLanguage().translate(this.name, 'dashlets');
        },

        fetchAttributes: function () {
            var attributes = {};
            this.fieldList.forEach(function (field) {
                var fieldView = this.getView('record').getView(field);
                _.extend(attributes, fieldView.fetch());
            }, this);

            this.model.set(attributes, {silent: true});

            var valid = true;
            this.fieldList.forEach(function (field) {
                var fieldView = this.getView('record').getView(field);
                valid = !fieldView.validate() && valid;
            }, this);

            if (!valid) {
                this.notify('Not Valid', 'error');
                return null;
            }
            return attributes;
        },

        save: function (dialog) {
            var attributes = this.fetchAttributes();
            if (attributes == null) {
                return;
            }

            var id = this.getParentView().id;

            this.notify('Saving...');

            this.getPreferences().once('sync', function () {
                this.getPreferences().trigger('update');

                this.notify(false);
                var dashlet = this.getParentView();

                this.close();

                dashlet.setup();
                dashlet.render();

            }, this);

            var o = this.getPreferences().get('dashletOptions') || {};
            o[id] = attributes;

            this.getPreferences().save({
                dashletOptions: o
            }, {patch: true});

        },
    });
});


