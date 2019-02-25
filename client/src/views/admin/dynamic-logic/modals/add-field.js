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

Espo.define('views/admin/dynamic-logic/modals/add-field', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        _template: '<div class="field" data-name="field">{{{field}}}</div>',

        events: {
            'click a[data-action="addField"]': function (e) {
                this.trigger('add-field', $(e.currentTarget).data().name);
            }
        },

        setup: function () {
            this.header = this.translate('Add Field');
            this.scope = this.options.scope;

            var model = new Model();

            this.createView('field', 'views/admin/dynamic-logic/fields/field', {
                el: this.getSelector() + ' [data-name="field"]',
                model: model,
                mode: 'edit',
                scope: this.scope,
                defs: {
                    name: 'field',
                    params: {}
                }
            }, function (view) {
                this.listenTo(view, 'change', function () {
                    var list = model.get('field') || [];
                    if (!list.length) return;
                    this.trigger('add-field', list[0]);
                }, this);
            });
        }

    });
});

