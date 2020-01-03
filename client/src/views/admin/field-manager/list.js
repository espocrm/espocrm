/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

define('views/admin/field-manager/list', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/field-manager/list',

        data: function () {
            return {
                scope: this.scope,
                fieldDefsArray: this.fieldDefsArray,
                typeList: this.typeList
            };
        },

        events: {
            'click [data-action="removeField"]': function (e) {
                var field = $(e.currentTarget).data('name');

                this.removeField(field);
            }
        },

        setup: function () {
            this.scope = this.options.scope;

            this.wait(
                this.buildFieldDefs()
            );
        },

        buildFieldDefs: function () {
            return this.getModelFactory().create(this.scope).then(
                function (model) {
                    this.fields = model.defs.fields;
                    this.fieldList = Object.keys(this.fields).sort();
                    this.fieldDefsArray = [];
                    this.fieldList.forEach(function (field) {
                        var defs = this.fields[field];
                        if (defs.customizationDisabled) return;
                        this.fieldDefsArray.push({
                            name: field,
                            isCustom: defs.isCustom || false,
                            type: defs.type
                        });
                    }, this);
                }.bind(this)
            );
        },

        removeField: function (field) {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                Espo.Ui.notify(this.translate('Removing...'));

                Espo.Ajax.request('Admin/fieldManager/' + this.scope + '/' + field, 'delete').then(function () {
                    Espo.Ui.success(this.translate('Removed'));

                    this.$el.find('tr[data-name="'+field+'"]').remove();
                    var data = this.getMetadata().data;

                    delete data['entityDefs'][this.scope]['fields'][field];

                    this.getMetadata().load(function () {
                        this.getMetadata().storeToCache();

                        this.buildFieldDefs()
                        .then(
                            function () {
                                return this.reRender()
                            }.bind(this)
                        )
                        .then(
                            function () {
                                Espo.Ui.success(this.translate('Removed'));
                            }.bind(this)
                        );
                    }.bind(this), true);
                }.bind(this));
            }.bind(this));
        },
    });
});
