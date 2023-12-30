/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/user/fields/user-name', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.validations.push('userName');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            let userNameRegularExpression = this.getUserNameRegularExpression();

            if (this.isEditMode()) {
                this.$element.on('change', () => {
                    let value = this.$element.val();
                    let re = new RegExp(userNameRegularExpression, 'gi');

                    value = value
                        .replace(re, '')
                        .replace(/[\s]/g, '_')
                        .toLowerCase();

                    this.$element.val(value);
                    this.trigger('change');
                });
            }
        },

        getUserNameRegularExpression: function () {
            return this.getConfig().get('userNameRegularExpression') || '[^a-z0-9\-@_\.\s]';
        },

        validateUserName: function () {
            let value = this.model.get(this.name);

            if (!value) {
                return;
            }

            let userNameRegularExpression = this.getUserNameRegularExpression();

            let re = new RegExp(userNameRegularExpression, 'gi');

            if (!re.test(value)) {
                return;
            }

            let msg = this.translate('fieldInvalid', 'messages').replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        },
    });
});
