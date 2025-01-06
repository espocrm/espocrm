/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import PasswordFieldView from 'views/fields/password';

class UserPasswordFieldView extends PasswordFieldView {

    validations = [
        'required',
        'strength',
        'confirm',
    ]

    init() {
        const tooltipItemList = [];

        this.strengthParams = this.options.strengthParams || {
            passwordStrengthLength: this.getConfig().get('passwordStrengthLength'),
            passwordStrengthLetterCount: this.getConfig().get('passwordStrengthLetterCount'),
            passwordStrengthNumberCount: this.getConfig().get('passwordStrengthNumberCount'),
            passwordStrengthBothCases: this.getConfig().get('passwordStrengthBothCases'),
            passwordStrengthSpecialCharacterCount: this.getConfig().get('passwordStrengthSpecialCharacterCount'),
        };

        const minLength = this.strengthParams.passwordStrengthLength;

        if (minLength) {
            tooltipItemList.push(
                '* ' + this.translate('passwordStrengthLength', 'messages', 'User')
                    .replace('{length}', minLength.toString())
            );
        }

        const requiredLetterCount = this.strengthParams.passwordStrengthLetterCount;

        if (requiredLetterCount) {
            tooltipItemList.push(
                '* ' + this.translate('passwordStrengthLetterCount', 'messages', 'User')
                    .replace('{count}', requiredLetterCount.toString())
            );
        }

        const requiredNumberCount = this.strengthParams.passwordStrengthNumberCount;

        if (requiredNumberCount) {
            tooltipItemList.push(
                '* ' + this.translate('passwordStrengthNumberCount', 'messages', 'User')
                    .replace('{count}', requiredNumberCount.toString())
            );
        }

        const bothCases = this.strengthParams.passwordStrengthBothCases;

        if (bothCases) {
            tooltipItemList.push(
                '* ' + this.translate('passwordStrengthBothCases', 'messages', 'User')
            );
        }

        if (this.strengthParams.passwordStrengthSpecialCharacterCount) {
            tooltipItemList.push(
                '* ' + this.translate('passwordStrengthSpecialCharacterCount', 'messages', 'User')
                    .replace('{count}', this.strengthParams.passwordStrengthSpecialCharacterCount.toString())
            );
        }

        if (tooltipItemList.length) {
            this.tooltip = true;
            this.tooltipText = this.translate('Requirements', 'labels', 'User') + ':\n' + tooltipItemList.join('\n');
        }

        super.init();
    }

    // noinspection JSUnusedGlobalSymbols
    validateStrength() {
        if (!this.model.get(this.name)) {
            return;
        }

        const password = this.model.get(this.name);
        const minLength = this.strengthParams.passwordStrengthLength;

        if (minLength) {
            if (password.length < minLength) {
                const msg = this.translate('passwordStrengthLength', 'messages', 'User')
                    .replace('{length}', minLength.toString());

                this.showValidationMessage(msg);

                return true;
            }
        }

        const requiredLetterCount = this.strengthParams.passwordStrengthLetterCount;

        if (requiredLetterCount) {
            let letterCount = 0;

            password.split('').forEach(c => {
                if (c.toLowerCase() !== c.toUpperCase()) {
                    letterCount++;
                }
            });

            if (letterCount < requiredLetterCount) {
                const msg = this.translate('passwordStrengthLetterCount', 'messages', 'User')
                    .replace('{count}', requiredLetterCount.toString());

                this.showValidationMessage(msg);

                return true;
            }
        }

        const requiredNumberCount = this.strengthParams.passwordStrengthNumberCount;

        if (requiredNumberCount) {
            let numberCount = 0;

            password.split('').forEach((c) => {
                if (c >= '0' && c <= '9') {
                    numberCount++;
                }
            });

            if (numberCount < requiredNumberCount) {
                const msg = this.translate('passwordStrengthNumberCount', 'messages', 'User')
                    .replace('{count}', requiredNumberCount.toString());

                this.showValidationMessage(msg);

                return true;
            }
        }

        const bothCases = this.strengthParams.passwordStrengthBothCases;

        if (bothCases) {
            let ucCount = 0;

            password.split('').forEach((c) => {
                if (c.toLowerCase() !== c.toUpperCase() && c === c.toUpperCase()) {
                    ucCount++;
                }
            });

            let lcCount = 0;

            password.split('').forEach(c => {
                if (c.toLowerCase() !== c.toUpperCase() && c === c.toLowerCase()) {
                    lcCount++;
                }
            });

            if (!ucCount || !lcCount) {
                const msg = this.translate('passwordStrengthBothCases', 'messages', 'User');

                this.showValidationMessage(msg);

                return true;
            }
        }

        const requiredSpecialCharacterCount = this.strengthParams.passwordStrengthSpecialCharacterCount;

        if (requiredSpecialCharacterCount) {
            let count = 0;

            password.split('').forEach(c => {
                if ("'-!\"#$%&()*,./:;?@[]^_`{|}~+<=>".includes(c)) {
                    count++;
                }
            });

            if (count < requiredSpecialCharacterCount) {
                const msg = this.translate('passwordStrengthSpecialCharacterCount', 'messages', 'User')
                    .replace('{count}', requiredSpecialCharacterCount.toString());

                this.showValidationMessage(msg);

                return true;
            }
        }
    }
}

export default UserPasswordFieldView;
