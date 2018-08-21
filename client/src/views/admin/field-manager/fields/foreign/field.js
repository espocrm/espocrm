/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/admin/field-manager/fields/foreign/field', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (!this.model.isNew()) {
                this.setReadOnly(true);
            }
            this.listenTo(this.model, 'change:field', function () {
                this.manageField();
            }, this);

            this.viewValue = this.model.get('view');
        },

        setupOptions: function () {
            this.listenTo(this.model, 'change:link', function () {
                this.setupOptionsByLink();
                this.reRender();
            }, this);
            this.setupOptionsByLink();
        },

        setupOptionsByLink: function () {
            this.typeList = this.getMetadata().get(['fields', 'foreign', 'fieldTypeList']);

            var link = this.model.get('link');

            if (!link) {
                this.params.options = [''];
                return;
            }

            var scope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', link, 'entity']);

            if (!scope) {
                this.params.options = [''];
                return;
            }

            var fields = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

            this.params.options = Object.keys(Espo.Utils.clone(fields)).filter(function (item) {
                var type = fields[item].type;
                if (!~this.typeList.indexOf(type)) return;
                if (fields[item].notStorable) return;

                return true;
            }, this);

            this.translatedOptions = {};
            this.params.options.forEach(function (item) {
                this.translatedOptions[item] = this.translate(item, 'fields', scope);
            }, this);

            this.params.options.unshift('');
        },

        manageField: function () {
            if (!this.model.isNew()) return;

            var link = this.model.get('link');
            var field = this.model.get('field');

            if (!link || !field) {
                return;
            }
            var scope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', link, 'entity']);
            if (!scope) {
                return;
            }
            var type = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

            if (type == 'enum') {
                this.viewValue = 'views/fields/foreign-enum';
            } else if (type == 'enumInt') {
                this.viewValue = 'views/fields/foreign-int';
            } else if (type == 'enumFloat') {
                this.viewValue = 'views/fields/foreign-float';
            } else if (type == 'varchar') {
                this.viewValue = 'views/fields/foreign-varchar';
            } else if (type == 'int') {
                this.viewValue = 'views/fields/foreign-int';
            } else if (type == 'float') {
                this.viewValue = 'views/fields/foreign-float';
            } else if (type == 'date') {
                this.viewValue = 'views/fields/foreign-date';
            } else if (type == 'datetime') {
                this.viewValue = 'views/fields/foreign-datetime';
            } else if (type == 'text') {
                this.viewValue = 'views/fields/foreign-text';
            } else if (type == 'number') {
                this.viewValue = 'views/fields/foreign-varchar';
            } else if (type == 'bool') {
                this.viewValue = 'views/fields/foreign-bool';
            } else {
                this.viewValue = null;
            }
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (this.model.isNew()) {
                if (this.viewValue) {
                    data['view'] = this.viewValue;
                }
            }
            return data;
        }
    });

});
