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

Espo.define('views/stream/message', 'view', function (Dep) {

    return Dep.extend({

        setup: function () {
            var template = this.options.messageTemplate;
            var data = this.options.messageData;

            for (var key in data) {
                var value = data[key] || '';

                if (value.indexOf('field:') === 0) {
                    var field = value.substr(6);
                    this.createField(key, field);

                    template = template.replace('{' + key +'}', '{{{' + key +'}}}');
                } else {
                    template = template.replace('{' + key +'}', value);
                }
            }

            this._template = template;
        },

        createField: function (key, name, type, params) {
            type = type || this.model.getFieldType(name) || 'base';
            this.createView(key, this.getFieldManager().getViewName(type), {
                model: this.model,
                defs: {
                    name: name,
                    params: params || {}
                },
                mode: 'detail',
                readOnly: true
            });
        }

    });
});

