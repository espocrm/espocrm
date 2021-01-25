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

define('views/user/fields/password', 'views/fields/password', function (Dep) {

    return Dep.extend({

        validations: ['required', 'strength', 'confirm'],

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        init: function () {
            var tooltipItemList = [];

            this.strengthParams = this.options.strengthParams || {
                passwordStrengthLength: this.getConfig().get('passwordStrengthLength'),
                passwordStrengthLetterCount: this.getConfig().get('passwordStrengthLetterCount'),
                passwordStrengthNumberCount: this.getConfig().get('passwordStrengthNumberCount'),
                passwordStrengthBothCases: this.getConfig().get('passwordStrengthBothCases'),
            };

            var minLength = this.strengthParams.passwordStrengthLength;
            if (minLength) {
                tooltipItemList.push(
                    '* ' + this.translate('passwordStrengthLength', 'messages', 'User').replace('{length}', minLength.toString())
                );
            }

            var requiredLetterCount = this.strengthParams.passwordStrengthLetterCount;
            if (requiredLetterCount) {
                tooltipItemList.push(
                    '* ' + this.translate('passwordStrengthLetterCount', 'messages', 'User').replace('{count}', requiredLetterCount.toString())
                );
            }

            var requiredNumberCount = this.strengthParams.passwordStrengthNumberCount;
            if (requiredNumberCount) {
                tooltipItemList.push(
                    '* ' + this.translate('passwordStrengthNumberCount', 'messages', 'User').replace('{count}', requiredNumberCount.toString())
                );
            }

            var bothCases = this.strengthParams.passwordStrengthBothCases;
            if (bothCases) {
                tooltipItemList.push(
                    '* ' + this.translate('passwordStrengthBothCases', 'messages', 'User')
                );
            }

            if (tooltipItemList.length) {
                this.tooltip = true;
                this.tooltipText = this.translate('Requirements', 'labels', 'User') + ':\n' + tooltipItemList.join('\n');
            }

            Dep.prototype.init.call(this);
        },

        validateStrength: function () {
            if (!this.model.get(this.name)) return;

            var password = this.model.get(this.name);

            var minLength = this.strengthParams.passwordStrengthLength;
            if (minLength) {
                if (password.length < minLength) {
                    var msg = this.translate('passwordStrengthLength', 'messages', 'User').replace('{length}', minLength.toString());
                    this.showValidationMessage(msg);
                    return true;;
                }
            }

            var requiredLetterCount = this.strengthParams.passwordStrengthLetterCount;
            if (requiredLetterCount) {
                var letterCount = 0;
                password.split('').forEach(function (c) {
                    if (c.toLowerCase() !== c.toUpperCase()) letterCount++;
                }, this);

                if (letterCount < requiredLetterCount) {
                    var msg = this.translate('passwordStrengthLetterCount', 'messages', 'User').replace('{count}', requiredLetterCount.toString());
                    this.showValidationMessage(msg);
                    return true;;
                }
            }

            var requiredNumberCount = this.strengthParams.passwordStrengthNumberCount;
            if (requiredNumberCount) {
                var numberCount = 0;
                password.split('').forEach(function (c) {
                    if (c >= '0' && c <= '9') numberCount++;
                }, this);

                if (numberCount < requiredNumberCount) {
                    var msg = this.translate('passwordStrengthNumberCount', 'messages', 'User').replace('{count}', requiredNumberCount.toString());
                    this.showValidationMessage(msg);
                    return true;;
                }
            }

            var bothCases = this.strengthParams.passwordStrengthBothCases;
            if (bothCases) {
                var ucCount = 0;
                password.split('').forEach(function (c) {
                    if (c.toLowerCase() !== c.toUpperCase() && c === c.toUpperCase()) ucCount++;
                }, this);
                var lcCount = 0;
                password.split('').forEach(function (c) {
                    if (c.toLowerCase() !== c.toUpperCase() && c === c.toLowerCase()) lcCount++;
                }, this);

                if (!ucCount || !lcCount) {
                    var msg = this.translate('passwordStrengthBothCases', 'messages', 'User');
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

    });
});
