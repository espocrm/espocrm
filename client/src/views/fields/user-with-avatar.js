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

import UserFieldView from 'views/fields/user';

class UserWithAvatarFieldView extends UserFieldView {

    listTemplate = 'fields/user-with-avatar/list'
    detailTemplate = 'fields/user-with-avatar/detail'

    data() {
        const data = super.data();

        if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
            data.avatar = this.getAvatarHtml();
            data.isOwn = this.model.get(this.idName) === this.getUser().id;
        }

        return data;
    }

    getAvatarHtml() {
        const size = this.mode === this.MODE_DETAIL ? 18: 16;

        return this.getHelper().getAvatarHtml(this.model.get(this.idName), 'small', size, 'avatar-link');
    }

    afterRender() {
        super.afterRender();

        if (this.isEditMode()) {
            this.controlEditModeAvatar();
        }
    }

    setup() {
        super.setup();

        this.addHandler('keydown', `input[data-name="${this.nameName}"]`, (e, target) => {
            target.classList.add('being-typed');
        });

        this.addHandler('change', `input[data-name="${this.nameName}"]`, (e, target) => {
            setTimeout(() => target.classList.remove('being-typed'), 200);
        });

        this.on('change', () => {
            if (!this.isEditMode()) {
                return;
            }

            const img = this.element.querySelector('img.avatar');

            if (img) {
                img.parentNode.removeChild(img);
            }

            this.controlEditModeAvatar();
        });
    }

    /**
     * @private
     */
    controlEditModeAvatar() {
        const nameElement = this.element.querySelector(`input[data-name="${this.nameName}"]`);
        nameElement.classList.remove('being-typed');

        const userId = this.model.attributes[this.idName];

        if (!userId) {
            return;
        }

        const avatarHtml = this.getHelper().getAvatarHtml(userId, 'small', 18, 'avatar-link');

        if (!avatarHtml) {
            return;
        }

        const img = new DOMParser().parseFromString(avatarHtml, 'text/html').body.childNodes[0];

        if (!(img instanceof HTMLImageElement)) {
            return;
        }

        img.classList.add('avatar-in-input')
        img.draggable = false;

        const input = this.element.querySelector('.input-group > input');

        if (!input) {
            return;
        }

        input.after(img);
    }
}

export default UserWithAvatarFieldView;
