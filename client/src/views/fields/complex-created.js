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

define('views/fields/complex-created', 'views/fields/base', function (Dep) {

    return Dep.extend({

        detailTemplateContent:
            `<span data-name="{{baseName}}At" class="field">{{{atField}}}</span> `+
            `<span class="text-muted">&raquo;</span> `+
            `<span data-name="{{baseName}}By" class="field">{{{byField}}}</span>`,

        baseName: 'created',

        getAttributeList: function () {
            return [this.fieldAt, this.fieldBy];
        },

        init: function () {
            this.baseName = this.options.baseName || this.baseName;
            this.fieldAt = this.baseName + 'At';
            this.fieldBy = this.baseName + 'By';

            Dep.prototype.init.call(this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.createField('at');
            this.createField('by');
        },

        data: function () {
            return _.extend({
                baseName: this.baseName,
            }, Dep.prototype.data.call(this));
        },

        createField: function (part) {
            var field = this.baseName + Espo.Utils.upperCaseFirst(part);

            var type = this.model.getFieldType(field) || 'base';

            var viewName = this.model.getFieldParam(field, 'view') || this.getFieldManager().getViewName(type);

            this.createView(part + 'Field', viewName, {
                name: field,
                model: this.model,
                mode: 'detail',
                readOnly: true,
                readOnlyLocked: true,
                el: this.getSelector() + ' [data-name="'+field+'"]',
            });
        },

        fetch: function () {
            return {};
        },

    });
});
