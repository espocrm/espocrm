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

define('views/user/fields/generate-password', 'views/fields/base', function (Dep) {

    return Dep.extend({

        _template: '<button type="button" class="btn btn-default" data-action="generatePassword">{{translate \'Generate\' scope=\'User\'}}</button>',

        events: {
            'click [data-action="generatePassword"]': function () {
                this.actionGeneratePassword();
            },
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:password', function (model, value, o) {
                if (o.isGenerated) return;
                this.model.set({
                    passwordPreview: '',
                });
            }, this);
        },

        fetch: function () {
            return {};
        },

        actionGeneratePassword: function () {
            var length = this.getConfig().get('passwordStrengthLength');
            var letterCount = this.getConfig().get('passwordStrengthLetterCount');
            var numberCount = this.getConfig().get('passwordStrengthNumberCount');

            var generateLength = this.getConfig().get('passwordGenerateLength') || 10;
            var generateLetterCount = this.getConfig().get('passwordGenerateLetterCount') || 4;
            var generateNumberCount = this.getConfig().get('passwordGenerateNumberCount') || 2;

            length = (typeof length === 'undefined') ? generateLength : length;
            letterCount = (typeof letterCount === 'undefined') ? generateLetterCount : letterCount;
            numberCount = (typeof numberCount === 'undefined') ? generateNumberCount : numberCount;

            if (length < generateLength) length = generateLength;
            if (letterCount < generateLetterCount) letterCount = generateLetterCount;
            if (numberCount < generateNumberCount) numberCount = generateNumberCount;

            var password = this.generatePassword(length, letterCount, numberCount, true);

            this.model.set({
                password: password,
                passwordConfirm: password,
                passwordPreview: password,
            }, {isGenerated: true});
        },

        generatePassword: function (length, letters, numbers, bothCases) {
            var chars = [
                'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
                '0123456789',
                'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
                'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'abcdefghijklmnopqrstuvwxyz',
            ];

            var upperCase = 0;
            var lowerCase = 0;

            if (bothCases) {
                upperCase = 1;
                lowerCase = 1;
                if (letters >= 2) letters = letters - 2;
                    else letters = 0;
            }

            var either = length - (letters + numbers + upperCase + lowerCase);
            if (either < 0) either = 0;

            var setList = [letters, numbers, either, upperCase, lowerCase];

            var shuffle = function (array) {
                var currentIndex = array.length, temporaryValue, randomIndex;
                while (0 !== currentIndex) {
                    randomIndex = Math.floor(Math.random() * currentIndex);
                    currentIndex -= 1;
                    temporaryValue = array[currentIndex];
                    array[currentIndex] = array[randomIndex];
                    array[randomIndex] = temporaryValue;
                }
                return array;
            };

            var array = setList.map(
                function (len, i) {
                    return Array(len).fill(chars[i]).map(
                        function (x) {
                            return x[Math.floor(Math.random() * x.length)];
                        }
                    ).join('');
                }
            ).concat();

            return shuffle(array).join('');
        },

    });
});
