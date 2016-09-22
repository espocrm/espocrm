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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/export/record/record', 'views/record/base', function (Dep) {

    return Dep.extend({

        template: 'export/record/record',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.scope = this.options.scope;

            var fieldList = this.getFieldManager().getScopeFieldList(this.scope);

            var forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.scope);

            fieldList = fieldList.filter(function (item) {
                return !~forbiddenFieldList.indexOf(item);
            }, this);

            this.getLanguage().sortFieldList(this.scope, fieldList);

            fieldList.unshift('id');

            var translatedOptions = {};
            fieldList.forEach(function (item) {
                translatedOptions[item] = this.getLanguage().translate(item, 'fields', this.scope);
            }, this);

            this.createField('useCustomFieldList', 'views/fields/bool', {
            });

            this.createField('fieldList', 'views/fields/multi-enum', {
                required: true,
                translatedOptions: translatedOptions,
                options: fieldList
            });

            this.controlVisibility();
            this.listenTo(this.model, 'change:useCustomFieldList', function () {
                this.controlVisibility();
            }, this);
        },

        controlVisibility: function () {
            if (this.model.get('useCustomFieldList')) {
                this.showField('fieldList');
            } else {
                this.hideField('fieldList');
            }
        }

    });

});

