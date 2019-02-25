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

Espo.define('views/fields/password', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'password',

        detailTemplate: 'fields/password/detail',

        editTemplate: 'fields/password/edit',

        validations: ['required', 'confirm'],

        events: {
            'click [data-action="change"]': function (e) {
                this.changePassword();
            }
        },

        changePassword: function () {
            this.$el.find('[data-action="change"]').addClass('hidden');
            this.$element.removeClass('hidden');
            this.changing = true;
        },

        data: function () {
            return _.extend({
                isNew: this.model.isNew(),
            }, Dep.prototype.data.call(this));
        },

        validateConfirm: function () {
            if (this.model.has(this.name + 'Confirm')) {
                if (this.model.get(this.name) != this.model.get(this.name + 'Confirm')) {
                    var msg = this.translate('fieldBadPasswordConfirm', 'messages').replace('{field}', this.getLabelText());
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.changing = false;

            if (this.params.readyToChange) {
                this.changePassword();
            }
        },

        fetch: function () {
            if (!this.model.isNew() && !this.changing) {
                return {};
            }
            return Dep.prototype.fetch.call(this);
        }
    });
});


